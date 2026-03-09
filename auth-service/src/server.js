import crypto from 'node:crypto';

import express from 'express';
import { fromNodeHeaders, toNodeHandler } from 'better-auth/node';

import { auth, pool, sendLoginEmail } from './auth.js';
import { config, isPreviewEnabled, normalizeEmail, normalizePhone, previewKey } from './config.js';
import { issueEmailOtp } from './email-otp.js';
import { logger } from './logger.js';
import { takePreviewCode } from './preview-store.js';

const app = express();

app.disable('x-powered-by');
app.use(express.json());

const betterAuthHandler = toNodeHandler(auth);

const compareSecret = (provided, expected) => {
    const left = Buffer.from(String(provided ?? ''));
    const right = Buffer.from(String(expected ?? ''));

    if (left.length !== right.length || left.length === 0) {
        return false;
    }

    return crypto.timingSafeEqual(left, right);
};

const requireInternalSecret = (req, res, next) => {
    if (!compareSecret(req.get('x-internal-auth-secret'), config.internalSecret)) {
        return res.status(401).json({
            message: 'Unauthorized',
        });
    }

    return next();
};

const getHeaders = (req) => fromNodeHeaders(req.headers);

const normalizeChannelTarget = (channel, target) => channel === 'phone'
    ? normalizePhone(target)
    : normalizeEmail(target);

const mapUser = (user) => ({
    id: user.id,
    email: user.email ?? null,
    phone: user.phoneNumber ?? null,
    name: user.name ?? null,
    image: user.image ?? null,
    emailVerified: Boolean(user.emailVerified),
    phoneVerified: Boolean(user.phoneNumberVerified),
});

const fail = (res, field, message, status = 422) => res.status(status).json({
    message,
    errors: {
        [field]: [message],
    },
});

const handleAuthError = (res, error) => {
    const status = Number(error?.status ?? error?.statusCode ?? 500);
    const message = error?.body?.message
        ?? error?.message
        ?? '认证服务处理失败，请稍后重试。';

    logger.error('Better Auth request failed', {
        status,
        message,
    });

    if (status >= 400 && status < 500) {
        return fail(res, 'code', message, status);
    }

    return fail(res, 'target', '登录服务暂时不可用，请稍后重试。', 503);
};

app.get('/health', async (_req, res) => {
    await pool.query('select 1');

    res.json({ status: 'ok' });
});

app.use('/api/auth', (req, res) => betterAuthHandler(req, res));

app.post('/internal/otp/send', requireInternalSecret, async (req, res) => {
    const channel = String(req.body?.channel ?? '').trim();
    const normalizedTarget = normalizeChannelTarget(channel, req.body?.target ?? '');

    if (!['email', 'phone'].includes(channel)) {
        return fail(res, 'channel', '不支持的登录渠道。');
    }

    if (normalizedTarget === '') {
        return fail(res, 'target', '请输入有效的邮箱或手机号。');
    }

    try {
        if (channel === 'email') {
            await issueEmailOtp({
                authApi: auth.api,
                email: normalizedTarget,
                headers: getHeaders(req),
                sendEmail: ({ email, otp, type }) => sendLoginEmail({ email, otp, type }),
            });
        } else {
            await auth.api.sendPhoneNumberOTP({
                body: {
                    phoneNumber: normalizedTarget,
                },
                headers: getHeaders(req),
            });
        }

        return res.json({
            status: 'sent',
            previewCode: isPreviewEnabled() ? takePreviewCode(previewKey(channel, normalizedTarget)) : null,
        });
    } catch (error) {
        return handleAuthError(res, error);
    }
});

app.post('/internal/otp/verify', requireInternalSecret, async (req, res) => {
    const channel = String(req.body?.channel ?? '').trim();
    const normalizedTarget = normalizeChannelTarget(channel, req.body?.target ?? '');
    const code = String(req.body?.code ?? '').trim();
    const name = String(req.body?.name ?? '').trim() || undefined;

    if (!['email', 'phone'].includes(channel)) {
        return fail(res, 'channel', '不支持的登录渠道。');
    }

    if (normalizedTarget === '' || code === '') {
        return fail(res, 'code', '验证码不正确或已过期。');
    }

    try {
        const result = channel === 'email'
            ? await auth.api.signInEmailOTP({
                body: {
                    email: normalizedTarget,
                    otp: code,
                    name,
                },
                headers: getHeaders(req),
            })
            : await auth.api.verifyPhoneNumber({
                body: {
                    phoneNumber: normalizedTarget,
                    code,
                    disableSession: true,
                    ...(name ? { name } : {}),
                },
                headers: getHeaders(req),
            });

        return res.json({
            user: mapUser(result.user),
        });
    } catch (error) {
        return handleAuthError(res, error);
    }
});

app.use((error, _req, res, _next) => {
    logger.error('Unhandled auth-service error', {
        message: error instanceof Error ? error.message : String(error),
    });

    res.status(500).json({
        message: '认证服务处理失败，请稍后重试。',
    });
});

app.listen(config.port, () => {
    logger.info('Auth service started', {
        port: config.port,
        baseUrl: config.baseUrl,
    });
});
