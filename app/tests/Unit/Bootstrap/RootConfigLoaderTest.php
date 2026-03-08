<?php

namespace Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../bootstrap/load-root-config.php';

class RootConfigLoaderTest extends TestCase
{
    public function test_it_loads_root_config_without_overriding_existing_values(): void
    {
        $configDir = $this->makeConfigDirectory(
            <<<'TOML'
[env]
APP_NAME = "Bensz Channel"
AUTH_OTP_TTL = 10
APP_DEBUG = false
TOML,
            <<<'ENV'
DB_PASSWORD=super-secret
BETTER_AUTH_INTERNAL_SECRET=internal-secret
ENV,
        );

        $environment = [
            'APP_NAME' => 'Existing App',
        ];

        $loaded = load_root_config(
            $configDir,
            static fn (string $key): bool => array_key_exists($key, $environment),
            static function (string $key, string $value) use (&$environment): void {
                $environment[$key] = $value;
            },
        );

        self::assertSame('Existing App', $environment['APP_NAME']);
        self::assertSame('10', $environment['AUTH_OTP_TTL']);
        self::assertSame('false', $environment['APP_DEBUG']);
        self::assertSame('super-secret', $environment['DB_PASSWORD']);
        self::assertSame('internal-secret', $environment['BETTER_AUTH_INTERNAL_SECRET']);
        self::assertArrayNotHasKey('APP_NAME', $loaded);
        self::assertSame('10', $loaded['AUTH_OTP_TTL']);
    }

    private function makeConfigDirectory(string $toml, string $env): string
    {
        $directory = sys_get_temp_dir().'/root-config-loader-'.bin2hex(random_bytes(6));

        mkdir($directory, 0777, true);
        file_put_contents($directory.'/config.toml', $toml);
        file_put_contents($directory.'/.env', $env);

        return $directory;
    }
}
