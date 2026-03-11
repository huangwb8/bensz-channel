<?php

namespace Tests\Feature\Auth;

use App\Data\SocialAuthIdentity;
use App\Models\QrLoginRequest;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SocialAccountResolver;
use App\Support\PendingTwoFactorLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_enable_two_factor_from_account_settings(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('settings.account.edit'))
            ->assertOk()
            ->assertSee('两步验证');

        $secret = (string) session('auth.two_factor.setup.secret');

        $this->assertNotSame('', $secret);

        $this->actingAs($user)
            ->post(route('settings.account.two-factor.enable'), [
                'code' => $this->currentTotp($secret),
            ])
            ->assertRedirect(route('settings.account.edit'))
            ->assertSessionHas('two_factor_recovery_codes');

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_enabled_at);
        $this->assertIsArray($user->two_factor_recovery_codes);
        $this->assertNotEmpty($user->two_factor_recovery_codes);
    }

    public function test_authenticated_user_can_disable_two_factor_with_valid_code(): void
    {
        $user = $this->createTwoFactorUser();
        $secret = $this->twoFactorSecret();

        $this->actingAs($user)
            ->delete(route('settings.account.two-factor.disable'), [
                'code' => $this->currentTotp($secret),
            ])
            ->assertRedirect(route('settings.account.edit'));

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_enabled_at);
        $this->assertNull($user->two_factor_recovery_codes);
    }

    public function test_password_login_requires_two_factor_challenge_when_enabled(): void
    {
        $user = $this->createTwoFactorUser([
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ]);

        $this->post(route('auth.password.login'), [
            'login_method' => 'email-password',
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ])->assertRedirect(route('auth.two-factor.challenge'));

        $this->assertGuest();

        $this->get(route('auth.two-factor.challenge'))
            ->assertOk()
            ->assertSee('两步验证');

        $this->post(route('auth.two-factor.verify'), [
            'code' => $this->currentTotp($this->twoFactorSecret()),
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_recovery_code_can_complete_two_factor_challenge_once(): void
    {
        $recoveryCode = 'A1B2-C3D4';
        $user = $this->createTwoFactorUser([
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ], recoveryCodes: [$recoveryCode]);

        $this->post(route('auth.password.login'), [
            'login_method' => 'email-password',
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ])->assertRedirect(route('auth.two-factor.challenge'));

        $this->post(route('auth.two-factor.verify'), [
            'recovery_code' => $recoveryCode,
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame([], $user->fresh()->two_factor_recovery_codes ?? []);
    }

    public function test_email_otp_login_requires_two_factor_challenge_when_enabled(): void
    {
        Config::set('community.auth.driver', 'better_auth');
        Config::set('services.better_auth.base_url', 'http://auth:3001');
        Config::set('services.better_auth.internal_secret', 'test-secret');

        $user = $this->createTwoFactorUser([
            'email' => 'member@example.com',
            'name' => '测试成员',
        ]);

        Http::fake([
            'http://auth:3001/internal/otp/verify' => Http::response([
                'user' => [
                    'id' => 'auth-user-1',
                    'email' => 'member@example.com',
                    'phone' => null,
                    'name' => '测试成员',
                    'image' => null,
                    'emailVerified' => true,
                    'phoneVerified' => false,
                ],
            ]),
        ]);

        $this->post(route('auth.code.verify'), [
            'channel' => 'email',
            'target' => 'member@example.com',
            'code' => '123456',
            'name' => '测试成员',
        ])->assertRedirect(route('auth.two-factor.challenge'));

        $this->assertGuest();

        $this->post(route('auth.two-factor.verify'), [
            'code' => $this->currentTotp($this->twoFactorSecret()),
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_banned_user_cannot_complete_two_factor_challenge(): void
    {
        $user = $this->createTwoFactorUser([
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ]);

        $this->post(route('auth.password.login'), [
            'login_method' => 'email-password',
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ])->assertRedirect(route('auth.two-factor.challenge'));

        $user->forceFill([
            'banned_at' => now()->subMinute(),
            'banned_until' => now()->addDay(),
        ])->save();

        $this->post(route('auth.two-factor.verify'), [
            'code' => $this->currentTotp($this->twoFactorSecret()),
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login_method']);

        $this->assertGuest();
    }

    public function test_social_identity_resolution_can_start_two_factor_challenge_for_enabled_user(): void
    {
        $user = $this->createTwoFactorUser([
            'email' => 'member@example.com',
            'name' => '测试成员',
        ]);

        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'wechat',
            'provider_user_id' => 'wx-union-id',
            'profile_snapshot' => [
                'seeded' => true,
            ],
        ]);

        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'wechat',
            'provider_user_id' => 'wx-union-id',
        ]);

        $resolvedUser = app(SocialAccountResolver::class)->resolve(new SocialAuthIdentity(
            provider: 'wechat',
            providerUserId: 'wx-union-id',
            name: '微信测试用户',
            avatarUrl: 'https://example.com/wx-avatar.png',
            profileSnapshot: [
                'openid' => 'wx-open-id',
                'unionid' => 'wx-union-id',
            ],
        ));

        $this->assertSame($user->id, $resolvedUser->id);

        $request = Request::create('/auth/social/wechat/callback', 'GET');
        $request->setLaravelSession(new Store('test', new ArraySessionHandler(120)));

        $requiresChallenge = app(PendingTwoFactorLogin::class)->start($request, $resolvedUser, true);

        $this->assertTrue($requiresChallenge);
        $this->assertSame($user->id, $request->session()->get('auth.two_factor.pending.user_id'));
        $this->assertGuest();
    }

    public function test_qr_login_status_redirects_to_two_factor_challenge_when_required(): void
    {
        $user = $this->createTwoFactorUser();
        $request = QrLoginRequest::query()->create([
            'provider' => 'wechat',
            'token' => 'qr-token-123',
            'status' => QrLoginRequest::STATUS_APPROVED,
            'approved_user_id' => $user->id,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->getJson(route('auth.qr.status', $request))
            ->assertOk()
            ->assertJson([
                'status' => QrLoginRequest::STATUS_CONSUMED,
                'redirect' => route('auth.two-factor.challenge'),
            ]);

        $this->assertGuest();
    }

    public function test_qr_login_status_redirects_banned_user_back_to_login(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subMinute(),
            'banned_until' => now()->addDays(7),
        ]);

        $request = QrLoginRequest::query()->create([
            'provider' => 'wechat',
            'token' => 'qr-token-banned',
            'status' => QrLoginRequest::STATUS_APPROVED,
            'approved_user_id' => $user->id,
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->getJson(route('auth.qr.status', $request))
            ->assertOk()
            ->assertJson([
                'status' => QrLoginRequest::STATUS_CONSUMED,
                'redirect' => route('login'),
            ]);

        $this->assertGuest();
    }

    private function createTwoFactorUser(array $attributes = [], ?string $secret = null, array $recoveryCodes = ['ZXCV-ASDF']): User
    {
        $secret ??= $this->twoFactorSecret();

        $user = User::factory()->create($attributes);

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => array_map(
                fn (string $code) => hash('sha256', strtoupper(str_replace('-', '', $code))),
                $recoveryCodes,
            ),
            'two_factor_enabled_at' => now(),
        ])->save();

        return $user;
    }

    private function twoFactorSecret(): string
    {
        return 'JBSWY3DPEHPK3PXP';
    }

    private function currentTotp(string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $counter = intdiv($timestamp, 30);
        $binaryCounter = pack('N2', $counter >> 32, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $binaryCounter, $this->decodeBase32($secret), true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $chunk = substr($hash, $offset, 4);
        $value = unpack('N', $chunk)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function decodeBase32(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $cleaned = strtoupper(preg_replace('/[^A-Z2-7]/', '', $value) ?? '');
        $bits = '';

        foreach (str_split($cleaned) as $character) {
            $position = strpos($alphabet, $character);

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
}
