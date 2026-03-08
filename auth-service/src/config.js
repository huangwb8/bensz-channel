import { loadRootConfig } from './load-root-config.js';

loadRootConfig();

const parseBoolean = (value, fallback = false) => {
    if (value === undefined) {
        return fallback;
    }

    return ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
};

const parseNumber = (value, fallback) => {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
};

export const normalizeEmail = (value) => String(value ?? '').trim().toLowerCase();

export const normalizePhone = (value) => String(value ?? '').replace(/\D+/g, '');

export const previewKey = (channel, target) => `${channel}:${target}`;

export const config = {
    nodeEnv: process.env.NODE_ENV ?? 'development',
    port: parseNumber(process.env.PORT, 3001),
    logLevel: process.env.LOG_LEVEL ?? 'info',
    baseUrl: process.env.BETTER_AUTH_BASE_URL
        ?? process.env.BETTER_AUTH_URL
        ?? `http://127.0.0.1:${parseNumber(process.env.PORT, 3001)}`,
    databaseUrl: process.env.BETTER_AUTH_DATABASE_URL
        ?? process.env.DATABASE_URL
        ?? `postgres://${encodeURIComponent(process.env.DB_USERNAME ?? 'bensz')}:${encodeURIComponent(process.env.DB_PASSWORD ?? process.env.POSTGRES_PASSWORD ?? 'bensz_secret')}@${process.env.DB_HOST ?? 'postgres'}:${parseNumber(process.env.DB_PORT, 5432)}/${process.env.DB_DATABASE ?? 'bensz_channel'}?options=-c%20search_path%3D${encodeURIComponent(process.env.AUTH_DB_SCHEMA ?? 'auth')},public`,
    betterAuthSecret: process.env.BETTER_AUTH_SECRET ?? 'dev-better-auth-secret-change-me',
    internalSecret: process.env.BETTER_AUTH_INTERNAL_SECRET ?? 'dev-internal-secret-change-me',
    otpLength: parseNumber(process.env.AUTH_OTP_LENGTH, 6),
    otpTtlSeconds: parseNumber(process.env.AUTH_OTP_TTL, 10) * 60,
    previewCodes: parseBoolean(process.env.AUTH_PREVIEW_CODES, false),
    trustedOrigins: String(process.env.BETTER_AUTH_TRUSTED_ORIGINS ?? process.env.APP_URL ?? '')
        .split(',')
        .map((origin) => origin.trim())
        .filter(Boolean),
    mail: {
        host: process.env.SMTP_HOST ?? process.env.MAIL_HOST ?? 'mailpit',
        port: parseNumber(process.env.SMTP_PORT ?? process.env.MAIL_PORT, 1025),
        secure: parseBoolean(process.env.SMTP_SECURE, false),
        user: process.env.SMTP_USER ?? process.env.MAIL_USERNAME ?? undefined,
        pass: process.env.SMTP_PASS ?? process.env.MAIL_PASSWORD ?? undefined,
        fromAddress: process.env.MAIL_FROM_ADDRESS ?? 'noreply@bensz-channel.local',
        fromName: process.env.MAIL_FROM_NAME ?? 'Bensz Channel',
    },
};

export const isPreviewEnabled = () => config.previewCodes && config.nodeEnv !== 'production';
