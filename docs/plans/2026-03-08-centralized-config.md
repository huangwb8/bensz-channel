# Centralized Config Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 将关键非密钥配置集中托管到 `config/config.toml`，将密码与密钥集中托管到 `config/.env`，并让 Laravel、Better Auth 与 Docker Compose 共用同一套配置来源。

**Architecture:** 保留现有 `env()` / `process.env` 使用方式，但在应用启动早期从根目录 `config/` 注入共享配置，避免大规模重写业务逻辑。Docker Compose 通过一个轻量 shell 包装脚本预生成运行时环境文件，既支持官方镜像的环境变量要求，也让 `config/config.toml` 成为非密钥参数的单一事实来源。

**Tech Stack:** Laravel 12、PHP 8.4、Node 22、Express、Docker Compose、POSIX shell、PHPUnit、Node Test Runner。

---

### Task 1: 固定共享配置加载行为

**Files:**
- Create: `app/tests/Unit/Bootstrap/RootConfigLoaderTest.php`
- Create: `auth-service/tests/root-config-loader.test.js`

**Step 1: Write the failing test**
- 为 PHP 根配置加载器补充测试：断言会读取 `config/config.toml` 与 `config/.env`，且不覆盖显式传入的已有环境变量。
- 为 Node 根配置加载器补充测试：断言能读取同样的两个文件，并保持已有 `process.env` 优先。

**Step 2: Run test to verify it fails**
Run: `cd app && php artisan test tests/Unit/Bootstrap/RootConfigLoaderTest.php`
Expected: FAIL，因为共享加载器尚不存在。

Run: `cd auth-service && node --test tests/root-config-loader.test.js`
Expected: FAIL，因为共享加载器尚不存在。

**Step 3: Write minimal implementation**
- 实现一个仅支持当前所需 TOML 子集的轻量解析器。
- 实现 `.env` 解析与“已有环境变量优先”的注入策略。

**Step 4: Run test to verify it passes**
Run: `cd app && php artisan test tests/Unit/Bootstrap/RootConfigLoaderTest.php`
Expected: PASS。

Run: `cd auth-service && node --test tests/root-config-loader.test.js`
Expected: PASS。

### Task 2: 接入 Laravel 与 Better Auth 启动链路

**Files:**
- Create: `app/bootstrap/load-root-config.php`
- Create: `auth-service/src/load-root-config.js`
- Modify: `app/bootstrap/app.php`
- Modify: `auth-service/src/config.js`

**Step 1: Write the failing test**
- 依赖 Task 1 的测试，新增/扩展断言以覆盖真实启动文件会调用共享加载器。

**Step 2: Run test to verify it fails**
Run: `cd app && php artisan test tests/Unit/Bootstrap/RootConfigLoaderTest.php`
Expected: FAIL。

Run: `cd auth-service && node --test tests/root-config-loader.test.js`
Expected: FAIL。

**Step 3: Write minimal implementation**
- 在 Laravel `bootstrap/app.php` 最早阶段注入根配置。
- 在 Better Auth `src/config.js` 顶部加载根配置，再读取 `process.env`。

**Step 4: Run test to verify it passes**
Run: `cd app && php artisan test tests/Unit/Bootstrap/RootConfigLoaderTest.php`
Expected: PASS。

Run: `cd auth-service && node --test tests/root-config-loader.test.js`
Expected: PASS。

### Task 3: 建立根配置文件与 Compose 包装流程

**Files:**
- Modify: `config/config.toml`
- Modify: `config/.env`
- Create: `config/.env.example`
- Create: `scripts/load-config-env.sh`
- Create: `scripts/compose.sh`
- Modify: `docker-compose.yml`
- Modify: `.gitignore`

**Step 1: Write the failing test**
- 用 shell 级验证确认 `scripts/load-config-env.sh env-file` 会输出 Compose 所需变量。

**Step 2: Run test to verify it fails**
Run: `./scripts/load-config-env.sh env-file`
Expected: FAIL，因为脚本尚不存在。

**Step 3: Write minimal implementation**
- 将关键非密钥项迁移到 `config/config.toml` 的 `[env]` 分组。
- 将密码/密钥迁移到 `config/.env`，并补充 `config/.env.example` 模板。
- 让 `scripts/compose.sh` 先生成 `config/.compose.env` 再调用 `docker compose`。

**Step 4: Run test to verify it passes**
Run: `./scripts/load-config-env.sh env-file | head`
Expected: 输出非密钥与密钥混合后的运行时环境变量。

### Task 4: 更新文档与变更记录

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `config.yaml`

**Step 1: Write the failing test**
- 人工检查 README 当前仍以分散环境变量方式描述配置。

**Step 2: Run test to verify it fails**
- 检查 `README.md` 与 `CHANGELOG.md` 中尚无新的集中配置说明。

**Step 3: Write minimal implementation**
- 记录 `config/config.toml` / `config/.env` 的职责边界。
- 更新部署命令为 `./scripts/compose.sh`。
- 同步版本号与 `[Unreleased]` 记录。

**Step 4: Run test to verify it passes**
- 核对 README、CHANGELOG、`config.yaml` 三处口径一致。

### Task 5: 执行验证与代码审查

**Files:**
- Verify only

**Step 1: Run focused tests**
Run: `cd app && php artisan test tests/Unit/Bootstrap/RootConfigLoaderTest.php`
Expected: PASS。

Run: `cd auth-service && node --test tests/root-config-loader.test.js`
Expected: PASS。

**Step 2: Run broader regression**
Run: `cd app && php artisan test tests/Feature/Auth`
Expected: PASS。

**Step 3: Run config smoke**
Run: `./scripts/load-config-env.sh env-file | rg '^(APP_URL|DB_HOST|DB_PASSWORD|BETTER_AUTH_INTERNAL_SECRET)='`
Expected: PASS。

**Step 4: Code review**
- 按 `code-reviewer` 输出 Critical / Important / Minor 审查结果。
- 若存在 Critical/Important，先修复再交付。
