import pg from 'pg';
import { pathToFileURL } from 'node:url';

import { config } from './config.js';

const { Client } = pg;

export const quoteIdentifier = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`;

export const buildCreateSchemaSql = (schemaName) => `CREATE SCHEMA IF NOT EXISTS ${quoteIdentifier(schemaName)}`;

export const ensureSchema = async ({
    connectionString = config.databaseUrl,
    schemaName = config.authDbSchema,
} = {}) => {
    const client = new Client({
        connectionString,
    });

    try {
        await client.connect();
        await client.query(buildCreateSchemaSql(schemaName));
    } finally {
        await client.end();
    }
};

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
    await ensureSchema();
}
