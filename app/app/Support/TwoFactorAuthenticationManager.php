<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class TwoFactorAuthenticationManager
{
    public const SETUP_SESSION_KEY = 'auth.two_factor.setup.secret';

    private const TOTP_DIGITS = 6;

    private const TOTP_PERIOD = 30;

    private const TOTP_WINDOW = 1;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const RECOVERY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function hasEnabledTwoFactor(User $user): bool
    {
        return filled($user->two_factor_secret) && $user->two_factor_enabled_at !== null;
    }

    /**
     * @return array{secret: string, provisioningUri: string}|null
     */
    public function setupPayload(Request $request, User $user): ?array
    {
        if ($this->hasEnabledTwoFactor($user)) {
            return null;
        }

        $secret = $this->pendingSetupSecret($request);

        return [
            'secret' => $secret,
            'provisioningUri' => $this->provisioningUri($user, $secret),
        ];
    }

    public function pendingSetupSecret(Request $request): string
    {
        $secret = $request->session()->get(self::SETUP_SESSION_KEY);

        if (is_string($secret) && $this->isValidSecret($secret)) {
            return $secret;
        }

        $secret = $this->generateSecret();
        $request->session()->put(self::SETUP_SESSION_KEY, $secret);

        return $secret;
    }

    public function clearPendingSetup(Request $request): void
    {
        $request->session()->forget(self::SETUP_SESSION_KEY);
    }

    /**
     * @return list<string>
     */
    public function enable(User $user, string $secret): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($recoveryCodes),
            'two_factor_enabled_at' => now(),
        ])->save();

        return $recoveryCodes;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
        ])->save();
    }

    /**
     * @return list<string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($recoveryCodes),
        ])->save();

        return $recoveryCodes;
    }

    public function verifyPendingSetupCode(Request $request, string $code): bool
    {
        return $this->verifyCode($this->pendingSetupSecret($request), $code);
    }

    public function verifyUserCode(User $user, string $code): bool
    {
        $secret = $this->decryptUserSecret($user);

        if ($secret === null) {
            return false;
        }

        return $this->verifyCode($secret, $code);
    }

    public function verifyChallenge(User $user, ?string $code, ?string $recoveryCode): bool
    {
        if (filled($code) && $this->verifyUserCode($user, (string) $code)) {
            return true;
        }

        if (filled($recoveryCode) && $this->consumeRecoveryCode($user, (string) $recoveryCode)) {
            return true;
        }

        return false;
    }

    public function consumeRecoveryCode(User $user, string $recoveryCode): bool
    {
        $normalized = $this->normalizeRecoveryCode($recoveryCode);

        if ($normalized === null) {
            return false;
        }

        $hash = hash('sha256', $normalized);
        $storedCodes = array_values(array_filter((array) $user->two_factor_recovery_codes, 'is_string'));
        $remainingCodes = [];
        $consumed = false;

        foreach ($storedCodes as $storedCode) {
            if (! $consumed && hash_equals($storedCode, $hash)) {
                $consumed = true;

                continue;
            }

            $remainingCodes[] = $storedCode;
        }

        if (! $consumed) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $remainingCodes,
        ])->save();

        return true;
    }

    public function currentTotp(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $counter = intdiv($timestamp, self::TOTP_PERIOD);
        $binaryCounter = pack('N2', $counter >> 32, $counter & 0xffffffff);
        $hash = hash_hmac('sha1', $binaryCounter, $this->decodeBase32($secret), true);
        $offset = ord(substr($hash, -1)) & 0x0f;
        $chunk = substr($hash, $offset, 4);
        $value = unpack('N', $chunk)[1] & 0x7fffffff;

        return str_pad((string) ($value % (10 ** self::TOTP_DIGITS)), self::TOTP_DIGITS, '0', STR_PAD_LEFT);
    }

    private function verifyCode(string $secret, string $code): bool
    {
        $normalizedCode = $this->normalizeTotpCode($code);

        if ($normalizedCode === null) {
            return false;
        }

        $timestamp = time();

        for ($window = -self::TOTP_WINDOW; $window <= self::TOTP_WINDOW; $window++) {
            if (hash_equals($this->currentTotp($secret, $timestamp + ($window * self::TOTP_PERIOD)), $normalizedCode)) {
                return true;
            }
        }

        return false;
    }

    private function decryptUserSecret(User $user): ?string
    {
        if (! is_string($user->two_factor_secret) || $user->two_factor_secret === '') {
            return null;
        }

        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
        } catch (Throwable) {
            return null;
        }

        return $this->isValidSecret($secret) ? $secret : null;
    }

    private function provisioningUri(User $user, string $secret): string
    {
        $issuer = trim((string) config('community.site.name', 'Bensz Channel')) ?: 'Bensz Channel';
        $identifier = $user->email
            ?: ($user->phone ?: 'user-'.($user->user_id ?? $user->id));
        $label = rawurlencode($issuer.':'.$identifier);

        return "otpauth://totp/{$label}?secret={$secret}&issuer=".rawurlencode($issuer).'&digits='.self::TOTP_DIGITS.'&period='.self::TOTP_PERIOD;
    }

    private function generateSecret(): string
    {
        return $this->encodeBase32(random_bytes(20));
    }

    /**
     * @return list<string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        $count = max(4, (int) config('community.auth.two_factor_recovery_code_count', 8));

        for ($index = 0; $index < $count; $index++) {
            $codes[] = $this->randomRecoverySegment(4).'-'.$this->randomRecoverySegment(4);
        }

        return $codes;
    }

    /**
     * @param  list<string>  $recoveryCodes
     * @return list<string>
     */
    private function hashRecoveryCodes(array $recoveryCodes): array
    {
        return array_map(
            fn (string $code) => hash('sha256', (string) $this->normalizeRecoveryCode($code)),
            $recoveryCodes,
        );
    }

    private function normalizeTotpCode(string $code): ?string
    {
        $normalized = preg_replace('/\D+/', '', $code) ?: '';

        return strlen($normalized) === self::TOTP_DIGITS ? $normalized : null;
    }

    private function normalizeRecoveryCode(string $recoveryCode): ?string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $recoveryCode) ?: '');

        return $normalized !== '' ? $normalized : null;
    }

    private function isValidSecret(string $secret): bool
    {
        return preg_match('/^[A-Z2-7]{16,64}$/', $secret) === 1;
    }

    private function encodeBase32(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $character) {
            $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            if ($chunk === '') {
                continue;
            }

            $encoded .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private function decodeBase32(string $value): string
    {
        $cleaned = strtoupper(preg_replace('/[^A-Z2-7]/', '', $value) ?? '');
        $bits = '';

        foreach (str_split($cleaned) as $character) {
            $position = strpos(self::BASE32_ALPHABET, $character);

            if ($position === false) {
                continue;
            }

            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $output = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                continue;
            }

            $output .= chr(bindec($chunk));
        }

        return $output;
    }

    private function randomRecoverySegment(int $length): string
    {
        $segment = '';
        $alphabetLength = strlen(self::RECOVERY_ALPHABET) - 1;

        for ($index = 0; $index < $length; $index++) {
            $segment .= self::RECOVERY_ALPHABET[random_int(0, $alphabetLength)];
        }

        return $segment;
    }
}
