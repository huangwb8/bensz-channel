import pg from 'pg';
import { betterAuth } from 'better-auth';
import { emailOTP, phoneNumber } from 'better-auth/plugins';

import { config, isPreviewEnabled, normalizePhone, previewKey } from './config.js';
import { logger } from './logger.js';
import { resolveRuntimeMailConfig } from './mail-config.js';
import { sendOtpEmail } from './otp-email.js';
import { rememberPreviewCode } from './preview-store.js';

const { Pool } = pg;

export const pool = new Pool({
    connectionString: config.databaseUrl,
});

export const sendLoginEmail = async ({ email, otp, type }) => {
    rememberPreviewCode(previewKey('email', email), otp, config.otpTtlSeconds);

    await sendOtpEmail({
        email,
        otp,
        type,
        ttlSeconds: config.otpTtlSeconds,
        getMailConfig: async () => resolveRuntimeMailConfig({
            pool,
            appKey: process.env.APP_KEY,
            baseConfig: config.mail,
            logger,
        }),
        logger,
    });
};

const sendPhoneCode = async ({ phoneNumber, code }) => {
    rememberPreviewCode(previewKey('phone', phoneNumber), code, config.otpTtlSeconds);

    logger.info('Phone OTP issued', {
        phoneNumber: `${phoneNumber.slice(0, 3)}****${phoneNumber.slice(-4)}`,
        previewEnabled: isPreviewEnabled(),
        code: isPreviewEnabled() ? code : undefined,
    });
};

export const auth = betterAuth({
    appName: 'Bensz Channel Auth',
    baseURL: config.baseUrl,
    secret: config.betterAuthSecret,
    database: pool,
    trustedOrigins: config.trustedOrigins,
    emailAndPassword: {
        enabled: false,
    },
    plugins: [
        emailOTP({
            otpLength: config.otpLength,
            expiresIn: config.otpTtlSeconds,
            sendVerificationOTP: sendLoginEmail,
        }),
        phoneNumber({
            otpLength: config.otpLength,
            expiresIn: config.otpTtlSeconds,
            sendOTP: sendPhoneCode,
            phoneNumberValidator: async (phoneNumber) => /^\d{6,24}$/.test(normalizePhone(phoneNumber)),
            signUpOnVerification: {
                getTempEmail: (phoneNumber) => `${normalizePhone(phoneNumber)}@phone.bensz-channel.local`,
                getTempName: (phoneNumber) => `用户${normalizePhone(phoneNumber).slice(-4)}`,
            },
        }),
    ],
});
