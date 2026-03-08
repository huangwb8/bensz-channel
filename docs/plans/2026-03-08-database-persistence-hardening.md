# Database Persistence Hardening Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ensure bensz-channel persists critical business data in PostgreSQL across Docker redeploys without relying on demo seed data.

**Architecture:** Keep Laravel business data and Better Auth identity data in PostgreSQL, keep cache/session/queue behavior unchanged, and replace unconditional demo seeding with idempotent system bootstrap. Validate the result with targeted tests plus a real Docker redeploy smoke test that checks schema creation, baseline records, and cross-restart persistence.

**Tech Stack:** Laravel 12, PHP 8.4, Better Auth, Express, PostgreSQL 17, Redis 7, Docker Compose

---

### Task 1: Audit persistence boundaries

**Files:**
- Review: `docker/web/entrypoint.sh`
- Review: `app/database/seeders/DatabaseSeeder.php`
- Review: `auth-service/src/ensure-schema.js`
- Review: `docker-compose.yml`

**Step 1: Inspect current bootstrap path**
Run: `sed -n '1,260p' docker/web/entrypoint.sh`
Expected: Find `php artisan db:seed` in container bootstrap.

**Step 2: Inspect demo seeding scope**
Run: `sed -n '1,260p' app/database/seeders/DatabaseSeeder.php`
Expected: Demo member/channels/articles/comments are seeded unconditionally.

**Step 3: Inspect auth schema bootstrap**
Run: `sed -n '1,200p' auth-service/src/ensure-schema.js`
Expected: Schema creation is hard-coded to `auth`.

### Task 2: Add idempotent production bootstrap

**Files:**
- Create: `app/database/seeders/SystemBootstrapSeeder.php`
- Modify: `app/database/seeders/DatabaseSeeder.php`
- Modify: `docker/web/entrypoint.sh`

**Step 1: Write the failing test**
Add a feature test that asserts the production bootstrap creates only baseline system data and does not insert demo content.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=SystemBootstrap`
Expected: FAIL because no dedicated bootstrap seeder exists.

**Step 3: Write minimal implementation**
Create `SystemBootstrapSeeder` to upsert the admin account only, and switch Docker bootstrap from `db:seed` to `db:seed --class=SystemBootstrapSeeder`.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=SystemBootstrap`
Expected: PASS.

### Task 3: Make auth schema bootstrap configurable

**Files:**
- Modify: `auth-service/src/ensure-schema.js`
- Add: `auth-service/tests/ensure-schema.test.js`

**Step 1: Write the failing test**
Assert `AUTH_DB_SCHEMA` is honored instead of always using `auth`.

**Step 2: Run test to verify it fails**
Run: `node --test auth-service/tests/*.test.js`
Expected: FAIL on schema-name assertion.

**Step 3: Write minimal implementation**
Use configured schema name and quote safely when issuing `CREATE SCHEMA IF NOT EXISTS`.

**Step 4: Run test to verify it passes**
Run: `node --test auth-service/tests/*.test.js`
Expected: PASS.

### Task 4: Update docs and changelog

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `config.yaml`

**Step 1: Document new bootstrap behavior**
Describe that Docker bootstraps only required system data, not demo content.

**Step 2: Record versioned change**
Bump patch version and add changelog entries for persistence hardening.

### Task 5: Validate real Docker persistence

**Files:**
- Verify: `docker-compose.yml`
- Verify: `data/`

**Step 1: Build and start containers**
Run: `./scripts/compose.sh up --build -d`
Expected: `web`, `auth`, `postgres`, `redis`, `mailpit` healthy.

**Step 2: Verify baseline tables and records**
Run SQL against PostgreSQL to confirm public/auth schemas exist and only baseline bootstrap data is present.
Expected: Admin exists, demo records do not.

**Step 3: Verify cross-restart persistence**
Insert or create one real business record, restart stack, and confirm the record remains.
Expected: PASS after restart.
