import nodemailer from 'nodemailer';
import pg from 'pg';
import { betterAuth } from 'better-auth';
import { emailOTP, phoneNumber } from 'better-auth/plugins';

import { config, isPreviewEnabled, normalizePhone, previewKey } from './config.js';
import { logger } from './logger.js';
import { rememberPreviewCode } from './preview-store.js';

const { Pool } = pg;

export const pool = new Pool({
    connectionString: config.databaseUrl,
});

const transporter = nodemailer.createTransport({
    host: config.mail.host,
    port: config.mail.port,
    secure: config.mail.secure,
    auth: config.mail.user && config.mail.pass
        ? {
            user: config.mail.user,
            pass: config.mail.pass,
        }
        : undefined,
});

const sendLoginEmail = async ({ email, otp, type }) => {
    rememberPreviewCode(previewKey('email', email), otp, config.otpTtlSeconds);

    const subject = type === 'sign-in' ? 'Bensz Channel 登录验证码' : 'Bensz Channel 验证码';
    const html = `
        <div style="font-family:system-ui,sans-serif;line-height:1.6;color:#111827">
            <h2 style="margin:0 0 12px">Bensz Channel 登录验证码</h2>
            <p>你的验证码为：</p>
            <p style="font-size:28px;font-weight:700;letter-spacing:6px">${otp}</p>
            <p>验证码将在 ${Math.round(config.otpTtlSeconds / 60)} 分钟后失效。</p>
        </div>
    `;

    void transporter.sendMail({
        from: `${config.mail.fromName} <${config.mail.fromAddress}>`,
        to: email,
        subject,
        html,
        text: `你的验证码是 ${otp}，${Math.round(config.otpTtlSeconds / 60)} 分钟内有效。`,
    }).catch((error) => {
        logger.error('Failed to send OTP email', {
            email,
            error: error instanceof Error ? error.message : String(error),
        });
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
