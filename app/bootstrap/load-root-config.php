<?php

if (! function_exists('root_config_parse_toml_env')) {
    function root_config_parse_toml_env(string $content): array
    {
        $values = [];
        $section = null;

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^\[([^\]]+)\]$/', $trimmed, $matches) === 1) {
                $section = trim($matches[1]);
                continue;
            }

            if ($section !== 'env') {
                continue;
            }

            if (! str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $rawValue] = array_map('trim', explode('=', $trimmed, 2));

            if ($key === '') {
                continue;
            }

            $values[$key] = root_config_normalize_value($rawValue);
        }

        return $values;
    }
}

if (! function_exists('root_config_parse_dotenv')) {
    function root_config_parse_dotenv(string $content): array
    {
        $values = [];

        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }

            if (! str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $rawValue] = array_map('trim', explode('=', $trimmed, 2));

            if ($key === '') {
                continue;
            }

            $values[$key] = root_config_normalize_value($rawValue);
        }

        return $values;
    }
}

if (! function_exists('root_config_normalize_value')) {
    function root_config_normalize_value(string $value): string
    {
        $trimmed = trim($value);
        $quotedWith = $trimmed[0] ?? null;
        $lastChar = $trimmed !== '' ? substr($trimmed, -1) : null;

        if (($quotedWith === '"' || $quotedWith === "'") && $quotedWith === $lastChar) {
            $trimmed = substr($trimmed, 1, -1);
        }

        return str_replace(['\\"', "\\'", '\\\\'], ['"', "'", '\\'], $trimmed);
    }
}

if (! function_exists('root_config_apply_derived_values')) {
    function root_config_apply_derived_values(array $values): array
    {
        if (! isset($values['APP_URL']) && isset($values['WEB_HOST'], $values['WEB_PORT'])) {
            $values['APP_URL'] = sprintf('http://%s:%s', $values['WEB_HOST'], $values['WEB_PORT']);
        }

        if (! isset($values['BETTER_AUTH_URL']) && isset($values['AUTH_HOST'], $values['AUTH_PORT'])) {
            $values['BETTER_AUTH_URL'] = sprintf('http://%s:%s', $values['AUTH_HOST'], $values['AUTH_PORT']);
        }

        if (! isset($values['BETTER_AUTH_BASE_URL']) && isset($values['BETTER_AUTH_URL'])) {
            $values['BETTER_AUTH_BASE_URL'] = $values['BETTER_AUTH_URL'];
        }

        if (! isset($values['BETTER_AUTH_TRUSTED_ORIGINS']) && isset($values['APP_URL'])) {
            $values['BETTER_AUTH_TRUSTED_ORIGINS'] = $values['APP_URL'];
        }

        if (! isset($values['MAIL_FROM_NAME']) && isset($values['APP_NAME'])) {
            $values['MAIL_FROM_NAME'] = $values['APP_NAME'];
        }

        if (! isset($values['PORT']) && isset($values['AUTH_PORT'])) {
            $values['PORT'] = $values['AUTH_PORT'];
        }

        if (! isset($values['POSTGRES_DB']) && isset($values['DB_DATABASE'])) {
            $values['POSTGRES_DB'] = $values['DB_DATABASE'];
        }

        if (! isset($values['POSTGRES_USER']) && isset($values['DB_USERNAME'])) {
            $values['POSTGRES_USER'] = $values['DB_USERNAME'];
        }

        if (! isset($values['POSTGRES_PASSWORD']) && isset($values['DB_PASSWORD'])) {
            $values['POSTGRES_PASSWORD'] = $values['DB_PASSWORD'];
        }

        return $values;
    }
}

if (! function_exists('load_root_config')) {
    function load_root_config(?string $configDirectory = null, ?callable $hasValue = null, ?callable $setValue = null): array
    {
        $configDirectory ??= dirname(__DIR__, 2).'/config';
        $hasValue ??= static function (string $key): bool {
            return getenv($key) !== false || array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER);
        };
        $setValue ??= static function (string $key, string $value): void {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        };

        $currentAppEnv = getenv('APP_ENV') !== false ? getenv('APP_ENV') : ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null);
        $currentDbConnection = getenv('DB_CONNECTION') !== false ? getenv('DB_CONNECTION') : ($_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? null);

        if ($currentAppEnv === 'testing' || $currentDbConnection === 'sqlite') {
            return [];
        }

        if (! is_dir($configDirectory)) {
            return [];
        }

        $values = [];
        $tomlPath = $configDirectory.'/config.toml';
        $envPath = $configDirectory.'/.env';

        if (is_file($tomlPath)) {
            $values = array_merge($values, root_config_parse_toml_env((string) file_get_contents($tomlPath)));
        }

        if (is_file($envPath)) {
            $values = array_merge($values, root_config_parse_dotenv((string) file_get_contents($envPath)));
        }

        $values = root_config_apply_derived_values($values);
        $loaded = [];

        foreach ($values as $key => $value) {
            if ($hasValue($key)) {
                continue;
            }

            $setValue($key, $value);
            $loaded[$key] = $value;
        }

        return $loaded;
    }
}
