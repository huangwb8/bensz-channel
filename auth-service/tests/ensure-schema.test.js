import test from 'node:test';
import assert from 'node:assert/strict';

import { buildCreateSchemaSql } from '../src/ensure-schema.js';

test('buildCreateSchemaSql honors configured schema name', () => {
    assert.equal(
        buildCreateSchemaSql('custom_auth'),
        'CREATE SCHEMA IF NOT EXISTS "custom_auth"',
    );
});

test('buildCreateSchemaSql safely escapes identifier quotes', () => {
    assert.equal(
        buildCreateSchemaSql('auth"schema'),
        'CREATE SCHEMA IF NOT EXISTS "auth""schema"',
    );
});
