# Better Auth Login Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 使用 Better Auth 作为自托管认证内核重构登录功能，同时保留 Laravel 社区应用现有会话、权限、扫码演示与其它业务功能不受破坏，并确保 Docker 重部署后可直接审查。

**Architecture:** 新增一个独立的 `auth-service` Node 服务承载 Better Auth。Laravel 继续作为主应用与会话边界：登录页和表单仍由 Laravel 渲染，验证码请求与验证改为 Laravel 服务端调用 Better Auth 内部 API，验证成功后将 Better Auth 用户身份同步到 Laravel `users` 表并建立 Laravel Session。二维码演示登录链路保留，避免对现有展示与测试造成无关破坏。

**Tech Stack:** Laravel 12、PHP 8.4、PostgreSQL 17、Redis 7、Node 22、Better Auth、Express、Docker Compose、PHPUnit。

---

### Task 1: 盘点当前认证边界并固定回归测试

**Files:**
- Modify: `app/tests/Feature/Auth/AuthPagesTest.php`
- Modify: `app/tests/Feature/Auth/OtpLoginTest.php`
- Create: `app/tests/Feature/Auth/BetterAuthLoginTest.php`

**Step 1: Write the failing test**
- 为登录页补充 Better Auth 文案与兼容二维码展示断言。
- 为邮箱验证码登录新增基于 HTTP fake 的 Better Auth 回归测试。
- 为手机号验证码登录新增基于 HTTP fake 的 Better Auth 回归测试。
- 为 Better Auth 服务异常新增失败回归测试。

**Step 2: Run test to verify it fails**
Run: `cd app && php artisan test tests/Feature/Auth`
Expected: FAIL，因为 Laravel 仍在使用旧的本地 OTP broker，且缺少 Better Auth 集成。

**Step 3: Write minimal implementation**
- 引入一个新的 Laravel 认证桥接服务接口。
- 先只让测试能命中新的调用路径。

**Step 4: Run test to verify it passes**
Run: `cd app && php artisan test tests/Feature/Auth`
Expected: PASS。

### Task 2: 引入 Laravel Better Auth 桥接层

**Files:**
- Create: `app/app/Contracts/Auth/OtpAuthGateway.php`
- Create: `app/app/Services/Auth/BetterAuthGateway.php`
- Create: `app/app/Services/Auth/LegacyOtpGateway.php`
- Create: `app/app/Services/Auth/LoginUserResolver.php`
- Modify: `app/app/Http/Controllers/Auth/LoginController.php`
- Modify: `app/config/services.php`
- Modify: `app/config/community.php`
- Modify: `app/.env.example`

**Step 1: Write the failing test**
- 用 HTTP fake 断言 Laravel 调用 Better Auth 内部发送/验证接口。
- 断言验证成功后用户被正确 upsert 并登录。

**Step 2: Run test to verify it fails**
Run: `cd app && php artisan test tests/Feature/Auth/BetterAuthLoginTest.php`
Expected: FAIL。

**Step 3: Write minimal implementation**
- 使用 `services.better_auth` 配置集中管理 URL、secret、timeout。
- 通过 `OtpAuthGateway` 统一邮件/手机号验证码逻辑。
- Better Auth 成功返回后，通过 `LoginUserResolver` 同步 `users` 表的邮箱/手机号/验证时间/最近活跃时间。
- 保留 Legacy 网关作为显式降级后备，但默认走 Better Auth。

**Step 4: Run test to verify it passes**
Run: `cd app && php artisan test tests/Feature/Auth/BetterAuthLoginTest.php`
Expected: PASS。

### Task 3: 新增 Better Auth Node 服务

**Files:**
- Create: `auth-service/package.json`
- Create: `auth-service/package-lock.json`
- Create: `auth-service/src/config.ts`
- Create: `auth-service/src/auth.ts`
- Create: `auth-service/src/server.ts`
- Create: `auth-service/src/logger.ts`
- Create: `auth-service/tsconfig.json`
- Create: `auth-service/.env.example`

**Step 1: Write the failing test**
- 先通过 Laravel 集成测试暴露内部接口缺失。

**Step 2: Run test to verify it fails**
Run: `curl http://127.0.0.1:3001/health`
Expected: connection refused / 404。

**Step 3: Write minimal implementation**
- 使用 Better Auth + PostgreSQL 建立独立认证服务。
- 启用 `emailOTP` 与 `phoneNumber` 插件。
- 暴露内部接口：`POST /internal/otp/send`、`POST /internal/otp/verify`、`GET /health`。
- 内部接口必须校验共享密钥，日志禁止输出密钥，仅允许在开发/测试环境预览 OTP。

**Step 4: Run test to verify it passes**
Run: `cd auth-service && npm run test:smoke`
Expected: PASS or zero-exit health smoke。

### Task 4: 对接 Docker 与部署配置

**Files:**
- Modify: `docker-compose.yml`
- Modify: `docker/web/Dockerfile`
- Modify: `README.md`
- Modify: `.gitignore`
- Create: `auth-service/Dockerfile`
- Create: `auth-service/scripts/entrypoint.sh`

**Step 1: Write the failing test**
- Docker compose 启动后 `auth-service` 不存在，Laravel 无法访问 Better Auth。

**Step 2: Run test to verify it fails**
Run: `docker compose up -d --build && docker compose ps`
Expected: FAIL / 缺少认证服务。

**Step 3: Write minimal implementation**
- 新增 `auth` 服务并接入同一 Postgres。
- 通过环境变量配置 `BETTER_AUTH_URL`、内部共享密钥、OTP 预览开关。
- 为 `auth` 服务加入健康检查，确保 `web` 在其就绪后再启动。

**Step 4: Run test to verify it passes**
Run: `docker compose up -d --build && docker compose ps`
Expected: `web`、`auth`、`postgres`、`redis`、`mailpit` 全部 healthy/running。

### Task 5: 文档、版本与变更记录同步

**Files:**
- Modify: `config.yaml`
- Modify: `CHANGELOG.md`
- Modify: `README.md`

**Step 1: Write the failing test**
- 无自动化测试，使用人工核对版本与说明是否同步。

**Step 2: Run test to verify it fails**
- 检查现有文档中无 Better Auth 架构说明。

**Step 3: Write minimal implementation**
- 版本号按新增功能做次版本升级。
- 在 `CHANGELOG.md` 的 `Unreleased` 记录新增认证服务、登录重构、Docker 变更。
- 在 `README.md` 说明登录架构、关键环境变量与部署方式。

**Step 4: Run test to verify it passes**
- 核对 `config.yaml`、`CHANGELOG.md`、`README.md` 三处口径一致。

### Task 6: 执行完整验证与代码审查

**Files:**
- Verify only

**Step 1: Run focused tests**
Run: `cd app && php artisan test tests/Feature/Auth`
Expected: PASS。

**Step 2: Run broader app tests**
Run: `cd app && php artisan test`
Expected: PASS。

**Step 3: Run Docker validation**
Run: `docker compose up -d --build && docker compose exec web php artisan test`
Expected: PASS。

**Step 4: Code review**
- 按 `code-reviewer` 输出 Critical / Important / Minor 审查结果。
- 若存在 Critical/Important，先修复再交付。
