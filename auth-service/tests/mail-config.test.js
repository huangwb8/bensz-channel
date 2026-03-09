import test from 'node:test';
import assert from 'node:assert/strict';

import {
    buildBaseMailConfig,
    decryptLaravelEncryptedString,
    deriveTransportOptions,
    resolveRuntimeMailConfig,
} from '../src/mail-config.js';

test('buildBaseMailConfig honors MAIL_SCHEME for ssl and tls', () => {
    const sslConfig = buildBaseMailConfig({
        MAIL_HOST: 'smtp.qq.com',
        MAIL_PORT: '465',
        MAIL_SCHEME: 'ssl',
    });

    assert.equal(sslConfig.secure, true);
    assert.equal(sslConfig.requireTls, false);

    const tlsConfig = buildBaseMailConfig({
        MAIL_HOST: 'smtp.example.com',
        MAIL_PORT: '587',
        MAIL_SCHEME: 'tls',
    });

    assert.equal(tlsConfig.secure, false);
    assert.equal(tlsConfig.requireTls, true);
});

test('decryptLaravelEncryptedString decrypts Laravel encrypted payloads', () => {
    const encrypted = 'eyJpdiI6IllXRmhZV0ZoWVdGaFlXRmhZV0ZoWVE9PSIsInZhbHVlIjoiY0FCM0ZocEdUdVh1NW5tUGNPK3hLdz09IiwibWFjIjoiOTA0ZjM5MzRjYjhlOTMwMWNiOTRmZWNmYWY5YmNkNWYyZmJiZmE0Y2M1NTE2NDgwMzkzMWU4YTY1MmM5YzJlOCJ9';

    assert.equal(
        decryptLaravelEncryptedString(encrypted, 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='),
        'super-secret',
    );
});

test('resolveRuntimeMailConfig prefers enabled database SMTP settings', async () => {
    const pool = {
        async query() {
            return {
                rows: [{
                    enabled: true,
                    smtp_scheme: 'ssl',
                    smtp_host: 'smtp.qq.com',
                    smtp_port: 465,
                    smtp_username: 'admin@qq.com',
                    smtp_password: 'eyJpdiI6IllXRmhZV0ZoWVdGaFlXRmhZV0ZoWVE9PSIsInZhbHVlIjoiY0FCM0ZocEdUdVh1NW5tUGNPK3hLdz09IiwibWFjIjoiOTA0ZjM5MzRjYjhlOTMwMWNiOTRmZWNmYWY5YmNkNWYyZmJiZmE0Y2M1NTE2NDgwMzkzMWU4YTY1MmM5YzJlOCJ9',
                    from_address: 'admin@qq.com',
                    from_name: 'Bensz Channel',
                }],
            };
        },
    };

    const runtimeConfig = await resolveRuntimeMailConfig({
        pool,
        appKey: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        baseConfig: {
            host: 'mailpit',
            port: 1025,
            secure: false,
            requireTls: false,
            user: undefined,
            pass: undefined,
            fromAddress: 'noreply@bensz-channel.local',
            fromName: 'Bensz Channel',
        },
        logger: {
            warn() {},
        },
    });

    assert.equal(runtimeConfig.host, 'smtp.qq.com');
    assert.equal(runtimeConfig.port, 465);
    assert.equal(runtimeConfig.secure, true);
    assert.equal(runtimeConfig.user, 'admin@qq.com');
    assert.equal(runtimeConfig.pass, 'super-secret');
    assert.equal(runtimeConfig.fromAddress, 'admin@qq.com');
});

test('resolveRuntimeMailConfig falls back when mail_settings table is missing', async () => {
    const baseConfig = {
        host: 'mailpit',
        port: 1025,
        secure: false,
        requireTls: false,
        user: undefined,
        pass: undefined,
        fromAddress: 'noreply@bensz-channel.local',
        fromName: 'Bensz Channel',
    };

    const runtimeConfig = await resolveRuntimeMailConfig({
        pool: {
            async query() {
                const error = new Error('relation does not exist');
                error.code = '42P01';
                throw error;
            },
        },
        appKey: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        baseConfig,
        logger: {
            warn() {},
        },
    });

    assert.deepEqual(runtimeConfig, baseConfig);
});

test('deriveTransportOptions maps auth and tls requirements', () => {
    assert.deepEqual(
        deriveTransportOptions({
            host: 'smtp.example.com',
            port: 587,
            secure: false,
            requireTls: true,
            user: 'demo',
            pass: 'secret',
        }),
        {
            host: 'smtp.example.com',
            port: 587,
            secure: false,
            requireTLS: true,
            auth: {
                user: 'demo',
                pass: 'secret',
            },
        },
    );
});
