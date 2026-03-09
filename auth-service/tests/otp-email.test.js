import test from 'node:test';
import assert from 'node:assert/strict';

import { sendOtpEmail } from '../src/otp-email.js';

test('sendOtpEmail awaits successful delivery', async () => {
    let sentMessage = null;

    await sendOtpEmail({
        email: 'user@example.com',
        otp: '123456',
        type: 'sign-in',
        ttlSeconds: 600,
        getMailConfig: async () => ({
            host: 'smtp.example.com',
            port: 587,
            secure: false,
            requireTls: true,
            user: 'demo',
            pass: 'secret',
            fromAddress: 'noreply@example.com',
            fromName: 'Bensz Channel',
        }),
        transportFactory: () => ({
            async sendMail(message) {
                sentMessage = message;
            },
        }),
        logger: {
            error() {},
        },
    });

    assert.equal(sentMessage.to, 'user@example.com');
    assert.equal(sentMessage.subject, 'Bensz Channel 登录验证码');
});

test('sendOtpEmail rethrows delivery failures instead of swallowing them', async () => {
    await assert.rejects(
        sendOtpEmail({
            email: 'user@example.com',
            otp: '123456',
            type: 'sign-in',
            ttlSeconds: 600,
            getMailConfig: async () => ({
                host: 'smtp.example.com',
                port: 587,
                secure: false,
                requireTls: true,
                user: 'demo',
                pass: 'secret',
                fromAddress: 'noreply@example.com',
                fromName: 'Bensz Channel',
            }),
            transportFactory: () => ({
                async sendMail() {
                    throw new Error('smtp unavailable');
                },
            }),
            logger: {
                error() {},
            },
        }),
        /smtp unavailable/,
    );
});
