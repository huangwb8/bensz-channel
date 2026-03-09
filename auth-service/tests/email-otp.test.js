import test from 'node:test';
import assert from 'node:assert/strict';

import { issueEmailOtp } from '../src/email-otp.js';

test('issueEmailOtp sends a newly created OTP', async () => {
    let delivered = null;

    await issueEmailOtp({
        authApi: {
            async createVerificationOTP() {
                return '654321';
            },
        },
        email: 'user@example.com',
        headers: { foo: 'bar' },
        async sendEmail(payload) {
            delivered = payload;
        },
    });

    assert.deepEqual(delivered, {
        email: 'user@example.com',
        otp: '654321',
        type: 'sign-in',
    });
});

test('issueEmailOtp reuses existing OTP when createVerificationOTP conflicts', async () => {
    let delivered = null;

    await issueEmailOtp({
        authApi: {
            async createVerificationOTP() {
                throw new Error('duplicate key');
            },
            async getVerificationOTP() {
                return {
                    otp: '112233',
                };
            },
        },
        email: 'user@example.com',
        headers: { foo: 'bar' },
        async sendEmail(payload) {
            delivered = payload;
        },
    });

    assert.equal(delivered?.otp, '112233');
});

test('issueEmailOtp surfaces sendEmail failures', async () => {
    await assert.rejects(
        issueEmailOtp({
            authApi: {
                async createVerificationOTP() {
                    return '654321';
                },
            },
            email: 'user@example.com',
            headers: { foo: 'bar' },
            async sendEmail() {
                throw new Error('smtp unavailable');
            },
        }),
        /smtp unavailable/,
    );
});
