import crypto from 'node:crypto';

const SMTP_SETTINGS_QUERY = `
    select enabled, smtp_scheme, smtp_host, smtp_port, smtp_username, smtp_password, from_address, from_name
    from public.mail_settings
    order by id asc
    limit 1
`;

const parseBoolean = (value, fallback = false) => {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    return ['1', 'true', 'yes', 'on'].includes(String(value).toLowerCase());
};

const parseNumber = (value, fallback) => {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
};

const normalizeString = (value) => {
    if (typeof value !== 'string') {
        return undefined;
    }

    const trimmed = value.trim();

    return trimmed === '' ? undefined : trimmed;
};

const normalizeScheme = (value) => {
    const normalized = String(value ?? '').trim().toLowerCase();

    if (['ssl', 'smtps'].includes(normalized)) {
        return 'ssl';
    }

    if (normalized === 'tls') {
        return 'tls';
    }

    return null;
};

const decodeAppKey = (appKey) => {
    const normalized = normalizeString(appKey);

    if (!normalized) {
        throw new Error('APP_KEY 未配置，无法解密后台保存的 SMTP 密码。');
    }

    const rawKey = normalized.startsWith('base64:')
        ? Buffer.from(normalized.slice(7), 'base64')
        : Buffer.from(normalized, 'utf8');

    if (rawKey.length !== 32) {
        throw new Error('APP_KEY 长度无效，无法解密后台保存的 SMTP 密码。');
    }

    return rawKey;
};

const decodeEncryptedPayload = (payload) => {
    const decoded = JSON.parse(Buffer.from(String(payload), 'base64').toString('utf8'));

    if (!decoded || typeof decoded !== 'object') {
        throw new Error('加密载荷格式无效。');
    }

    return decoded;
};

export const decryptLaravelEncryptedString = (payload, appKey) => {
    const key = decodeAppKey(appKey);
    const decoded = decodeEncryptedPayload(payload);
    const iv = Buffer.from(String(decoded.iv ?? ''), 'base64');
    const value = String(decoded.value ?? '');
    const mac = String(decoded.mac ?? '');
    const expectedMac = crypto
        .createHmac('sha256', key)
        .update(String(decoded.iv ?? '') + value)
        .digest('hex');

    if (!mac || !crypto.timingSafeEqual(Buffer.from(mac), Buffer.from(expectedMac))) {
        throw new Error('SMTP 密码校验失败，可能是 APP_KEY 与应用配置不一致。');
    }

    const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
    const decrypted = Buffer.concat([
        decipher.update(Buffer.from(value, 'base64')),
        decipher.final(),
    ]);

    return decrypted.toString('utf8');
};

export const buildBaseMailConfig = (env = process.env) => {
    const scheme = normalizeScheme(env.SMTP_SCHEME ?? env.MAIL_SCHEME);
    const secure = scheme === 'ssl'
        ? true
        : scheme === 'tls'
            ? false
            : parseBoolean(env.SMTP_SECURE, false);

    return {
        host: normalizeString(env.SMTP_HOST ?? env.MAIL_HOST) ?? 'mailpit',
        port: parseNumber(env.SMTP_PORT ?? env.MAIL_PORT, 1025),
        secure,
        requireTls: scheme === 'tls',
        user: normalizeString(env.SMTP_USER ?? env.MAIL_USERNAME),
        pass: normalizeString(env.SMTP_PASS ?? env.MAIL_PASSWORD),
        fromAddress: normalizeString(env.MAIL_FROM_ADDRESS) ?? 'noreply@bensz-channel.local',
        fromName: normalizeString(env.MAIL_FROM_NAME) ?? 'Bensz Channel',
    };
};

const buildOverrideMailConfig = (row, appKey) => {
    const scheme = normalizeScheme(row.smtp_scheme);
    const encryptedPassword = normalizeString(row.smtp_password);

    return {
        host: normalizeString(row.smtp_host),
        port: parseNumber(row.smtp_port, undefined),
        secure: scheme === 'ssl',
        requireTls: scheme === 'tls',
        user: normalizeString(row.smtp_username),
        pass: encryptedPassword ? decryptLaravelEncryptedString(encryptedPassword, appKey) : undefined,
        fromAddress: normalizeString(row.from_address),
        fromName: normalizeString(row.from_name),
    };
};

const hasValidOverride = (row) => Boolean(row)
    && Boolean(row.enabled)
    && Boolean(normalizeString(row.smtp_host))
    && Number.isFinite(Number(row.smtp_port))
    && Boolean(normalizeString(row.from_address))
    && Boolean(normalizeString(row.from_name));

export const resolveRuntimeMailConfig = async ({ pool, appKey, baseConfig, logger }) => {
    try {
        const result = await pool.query(SMTP_SETTINGS_QUERY);
        const row = result.rows?.[0];

        if (!hasValidOverride(row)) {
            return baseConfig;
        }

        return buildOverrideMailConfig(row, appKey);
    } catch (error) {
        if (error?.code === '42P01') {
            return baseConfig;
        }

        if (String(error?.message ?? '').includes('APP_KEY')) {
            throw error;
        }

        logger?.warn?.('Failed to load runtime SMTP settings, fallback to base config', {
            error: error instanceof Error ? error.message : String(error),
        });

        return baseConfig;
    }
};

export const deriveTransportOptions = (mailConfig) => ({
    host: mailConfig.host,
    port: mailConfig.port,
    secure: mailConfig.secure,
    requireTLS: mailConfig.requireTls,
    auth: mailConfig.user && mailConfig.pass
        ? {
            user: mailConfig.user,
            pass: mailConfig.pass,
        }
        : undefined,
});
