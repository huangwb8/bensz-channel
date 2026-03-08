import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

import { loadRootConfig } from '../src/load-root-config.js';

test('loadRootConfig reads root config files and keeps existing env first', () => {
    const configDir = fs.mkdtempSync(path.join(os.tmpdir(), 'root-config-loader-'));

    fs.writeFileSync(path.join(configDir, 'config.toml'), [
        '[env]',
        'APP_NAME = "Bensz Channel"',
        'AUTH_OTP_TTL = 10',
        'AUTH_PREVIEW_CODES = false',
        '',
    ].join('\n'));

    fs.writeFileSync(path.join(configDir, '.env'), [
        'DB_PASSWORD=super-secret',
        'BETTER_AUTH_SECRET=auth-secret',
        '',
    ].join('\n'));

    const env = {
        APP_NAME: 'Existing App',
    };

    const loaded = loadRootConfig({ configDir, env });

    assert.equal(env.APP_NAME, 'Existing App');
    assert.equal(env.AUTH_OTP_TTL, '10');
    assert.equal(env.AUTH_PREVIEW_CODES, 'false');
    assert.equal(env.DB_PASSWORD, 'super-secret');
    assert.equal(env.BETTER_AUTH_SECRET, 'auth-secret');
    assert.equal(loaded.APP_NAME, undefined);
    assert.equal(loaded.AUTH_OTP_TTL, '10');
});
