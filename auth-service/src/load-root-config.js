import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const normalizeValue = (value) => {
    const trimmed = String(value ?? '').trim();
    const quote = trimmed[0];

    if ((quote === '"' || quote === "'") && trimmed.at(-1) === quote) {
        return trimmed
            .slice(1, -1)
            .replace(/\\"/g, '"')
            .replace(/\\'/g, "'")
            .replace(/\\\\/g, '\\');
    }

    return trimmed;
};

export const parseTomlEnv = (content) => {
    const values = {};
    let section = null;

    for (const rawLine of String(content ?? '').split(/\r?\n/)) {
        const line = rawLine.trim();

        if (line === '' || line.startsWith('#')) {
            continue;
        }

        const sectionMatch = line.match(/^\[([^\]]+)\]$/);

        if (sectionMatch) {
            section = sectionMatch[1].trim();
            continue;
        }

        if (section !== 'env' || !line.includes('=')) {
            continue;
        }

        const [keyPart, ...valueParts] = line.split('=');
        const key = keyPart.trim();

        if (key === '') {
            continue;
        }

        values[key] = normalizeValue(valueParts.join('='));
    }

    return values;
};

export const parseDotenv = (content) => {
    const values = {};

    for (const rawLine of String(content ?? '').split(/\r?\n/)) {
        let line = rawLine.trim();

        if (line === '' || line.startsWith('#')) {
            continue;
        }

        if (line.startsWith('export ')) {
            line = line.slice(7).trim();
        }

        if (!line.includes('=')) {
            continue;
        }

        const [keyPart, ...valueParts] = line.split('=');
        const key = keyPart.trim();

        if (key === '') {
            continue;
        }

        values[key] = normalizeValue(valueParts.join('='));
    }

    return values;
};

const applyDerivedValues = (values) => {
    const derived = { ...values };

    if (!derived.APP_URL && derived.WEB_HOST && derived.WEB_PORT) {
        derived.APP_URL = `http://${derived.WEB_HOST}:${derived.WEB_PORT}`;
    }

    if (!derived.BETTER_AUTH_URL && derived.AUTH_HOST && derived.AUTH_PORT) {
        derived.BETTER_AUTH_URL = `http://${derived.AUTH_HOST}:${derived.AUTH_PORT}`;
    }

    if (!derived.BETTER_AUTH_BASE_URL && derived.BETTER_AUTH_URL) {
        derived.BETTER_AUTH_BASE_URL = derived.BETTER_AUTH_URL;
    }

    if (!derived.BETTER_AUTH_TRUSTED_ORIGINS && derived.APP_URL) {
        derived.BETTER_AUTH_TRUSTED_ORIGINS = derived.APP_URL;
    }

    if (!derived.MAIL_FROM_NAME && derived.APP_NAME) {
        derived.MAIL_FROM_NAME = derived.APP_NAME;
    }

    if (!derived.PORT && derived.AUTH_PORT) {
        derived.PORT = derived.AUTH_PORT;
    }

    if (!derived.POSTGRES_DB && derived.DB_DATABASE) {
        derived.POSTGRES_DB = derived.DB_DATABASE;
    }

    if (!derived.POSTGRES_USER && derived.DB_USERNAME) {
        derived.POSTGRES_USER = derived.DB_USERNAME;
    }

    if (!derived.POSTGRES_PASSWORD && derived.DB_PASSWORD) {
        derived.POSTGRES_PASSWORD = derived.DB_PASSWORD;
    }

    return derived;
};

export const loadRootConfig = ({ configDir = path.resolve(__dirname, '../../config'), env = process.env } = {}) => {
    if (env.APP_ENV === 'testing' || env.NODE_ENV === 'test' || env.DB_CONNECTION === 'sqlite') {
        return {};
    }

    const values = {};
    const tomlPath = path.resolve(__dirname, '../../app/config.toml');
    const dotenvPath = path.join(configDir, '.env');

    if (fs.existsSync(tomlPath)) {
        Object.assign(values, parseTomlEnv(fs.readFileSync(tomlPath, 'utf8')));
    }

    if (fs.existsSync(dotenvPath)) {
        Object.assign(values, parseDotenv(fs.readFileSync(dotenvPath, 'utf8')));
    }

    const derivedValues = applyDerivedValues(values);
    const loaded = {};

    for (const [key, value] of Object.entries(derivedValues)) {
        if (Object.prototype.hasOwnProperty.call(env, key)) {
            continue;
        }

        env[key] = value;
        loaded[key] = value;
    }

    return loaded;
};
