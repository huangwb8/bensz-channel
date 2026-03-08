# Admin SMTP Settings Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为管理员在订阅设置页增加 SMTP 配置能力，同时保持普通成员订阅体验与现有邮件通知链路不受影响。

**Architecture:** 通过新增一张轻量的 `mail_settings` 配置表保存管理员维护的 SMTP 覆盖配置，密码字段使用加密存储；应用启动时按“数据库配置优先、环境变量兜底”的策略覆盖运行时邮件配置；订阅页保持普通用户原有表单不变，仅管理员额外看到 SMTP 配置区块与保存入口。

**Tech Stack:** Laravel 12, Blade, Eloquent, Notifications, Docker Compose, PHPUnit

---

### Task 1: 锁定订阅页行为

**Files:**
- Modify: `app/tests/Feature/Subscriptions/SubscriptionSettingsTest.php`
- Create: `app/tests/Feature/Subscriptions/AdminMailSettingsTest.php`

**Step 1: Write the failing test**
- 验证普通成员看不到 SMTP 管理区块
- 验证管理员能看到 SMTP 管理区块
- 验证管理员可保存 SMTP host / port / username / password / from 信息
- 验证非管理员不可提交 SMTP 配置保存接口

**Step 2: Run test to verify it fails**
Run: `php artisan test tests/Feature/Subscriptions/AdminMailSettingsTest.php tests/Feature/Subscriptions/SubscriptionSettingsTest.php`
Expected: FAIL because route / model / view section do not exist yet

### Task 2: 建立 SMTP 配置持久化

**Files:**
- Create: `app/database/migrations/2026_03_08_220000_create_mail_settings_table.php`
- Create: `app/app/Models/MailSetting.php`
- Create: `app/app/Support/MailSettingsManager.php`

**Step 1: Write minimal implementation**
- 用单表保存启用状态、scheme、host、port、username、encrypted password、from address、from name
- 提供读取当前有效配置、保存配置、应用到 `config('mail')` 的方法
- 密码留空时保留旧值，保证更新安全

**Step 2: Run targeted test**
Run: `php artisan test tests/Feature/Subscriptions/AdminMailSettingsTest.php`
Expected: still fail only on controller / view wiring

### Task 3: 接入控制器与页面

**Files:**
- Modify: `app/app/Http/Controllers/SubscriptionSettingsController.php`
- Modify: `app/routes/web.php`
- Modify: `app/resources/views/settings/subscriptions.blade.php`
- Modify: `app/app/Providers/AppServiceProvider.php`

**Step 1: Add admin-only section**
- 订阅设置页管理员额外显示 SMTP 配置卡片
- 普通用户继续只看到订阅表单

**Step 2: Add admin-only update endpoint**
- 新增管理员 SMTP 保存路由
- 校验 host / port / username / password / from address / from name / scheme
- 保存后清理缓存并返回成功提示

**Step 3: Apply runtime mail config**
- 应用启动时读取 DB SMTP 配置并覆盖运行时 `mail.default` / `mail.mailers.smtp` / `mail.from`
- 若无 DB 配置或关闭启用，则继续使用环境变量

### Task 4: 文档与验证

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `config.yaml`

**Step 1: Update docs**
- 记录管理员 SMTP 配置能力
- 同步版本与变更记录

**Step 2: Validate**
Run: `php artisan test ...`（若被仓库现有 SQLite 迁移问题阻塞，则用 Docker 实机验证）
Run: `npm run build`
Run: `./scripts/compose.sh up --build -d`
Expected: 登录管理员后在订阅页可见 SMTP 配置区块，并能成功保存
