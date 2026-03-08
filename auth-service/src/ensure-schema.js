import pg from 'pg';

import { config } from './config.js';

const { Client } = pg;

const client = new Client({
    connectionString: config.databaseUrl,
});

try {
    await client.connect();
    await client.query('CREATE SCHEMA IF NOT EXISTS auth');
} finally {
    await client.end();
}
