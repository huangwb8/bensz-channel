import pg from 'pg';

import { config } from './config.js';
import { logger } from './logger.js';

const { Client } = pg;

const sleep = (ms) => new Promise((resolve) => {
    setTimeout(resolve, ms);
});

export const waitForDatabase = async ({
    connectionString = config.databaseUrl,
    timeoutMs = 60_000,
    intervalMs = 2_000,
} = {}) => {
    const deadline = Date.now() + timeoutMs;
    let lastError = null;

    while (Date.now() < deadline) {
        const client = new Client({ connectionString });

        try {
            await client.connect();
            await client.query('select 1');
            await client.end();
            return;
        } catch (error) {
            lastError = error;

            try {
                await client.end();
            } catch {
            }

            await sleep(intervalMs);
        }
    }

    logger.error('Database did not become ready in time', {
        message: lastError instanceof Error ? lastError.message : String(lastError),
        timeoutMs,
    });

    throw lastError ?? new Error('Database did not become ready in time.');
};

await waitForDatabase();
