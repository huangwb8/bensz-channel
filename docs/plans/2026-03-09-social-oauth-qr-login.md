# Social OAuth QR Login Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为项目补齐真实可用的微信扫码登录与 QQ 扫码登录，同时保留现有演示扫码模式作为无凭证时的稳定降级路径。

**Architecture:** 继续由 Laravel 负责社交登录入口、状态校验、OAuth 回调与本地会话建立；Better Auth 仍专注邮箱/手机号 OTP，不强行把微信/QQ OAuth 塞进现有 Node 认证服务。抽取统一的社交身份解析与本地用户绑定服务，供真实 OAuth 与现有演示二维码共用，避免重复逻辑并降低回归风险。

**Tech Stack:** Laravel 12、PHP 8.4、Blade、HTTP Client、PostgreSQL、Docker Compose、PHPUnit。

---

### Task 1: 审计并固化当前行为

**Files:**
- Modify: `docs/plans/2026-03-09-social-oauth-qr-login.md`
- Test: `app/tests/Feature/Auth/AuthPagesTest.php`

**Step 1: 记录当前扫码登录的真实状态**
- 明确 `LoginController` + `QrLoginBroker` 实现的是本地演示授权流，而非官方微信/QQ OAuth。

**Step 2: 保留演示回归测试**
- 确保现有 `auth.qr.start` / `auth.qr.show` / `auth.qr.approve.show` 的测试仍存在，防止降级方案被破坏。

### Task 2: 抽取统一社交身份绑定服务

**Files:**
- Create: `app/app/Data/SocialAuthIdentity.php`
- Create: `app/app/Services/Auth/SocialAccountResolver.php`
- Modify: `app/app/Support/QrLoginBroker.php`
- Test: `app/tests/Feature/Auth/SocialOAuthLoginTest.php`

**Step 1: 写失败测试**
- 为“根据 provider + provider_user_id 绑定/复用本地用户”写回归测试。

**Step 2: 实现最小服务**
- 新增 `SocialAuthIdentity` DTO。
- 新增 `SocialAccountResolver`，统一处理：查找旧绑定、按邮箱/手机号复用、创建用户、更新头像/快照、写入 `social_accounts`。
- 让 `QrLoginBroker` 复用该服务。

**Step 3: 跑测试验证**
- 执行目标测试，确认演示模式与真实 OAuth 后续都能共用同一绑定逻辑。

### Task 3: 实现微信 / QQ OAuth Provider

**Files:**
- Create: `app/app/Contracts/Auth/SocialOAuthProvider.php`
- Create: `app/app/Services/Auth/Social/WeChatOAuthProvider.php`
- Create: `app/app/Services/Auth/Social/QqOAuthProvider.php`
- Create: `app/app/Services/Auth/SocialOAuthManager.php`
- Modify: `app/config/services.php`
- Test: `app/tests/Feature/Auth/SocialOAuthLoginTest.php`

**Step 1: 写失败测试**
- 覆盖微信授权跳转 URL、微信回调成功登录。
- 覆盖 QQ 授权跳转 URL、QQ 回调成功登录。
- 覆盖 state 校验失败与上游异常处理。

**Step 2: 实现 Provider**
- 微信：拼接 `open.weixin.qq.com/connect/qrconnect`，校验 state，使用 code 换 token，再取用户信息。
- QQ：拼接 `graph.qq.com/oauth2.0/authorize`，用 code 换 token，获取 openid，再取用户信息。
- 所有外部请求都使用 Laravel HTTP Client，设置超时、错误处理与可测试性。

### Task 4: 新增社交登录控制器与路由

**Files:**
- Create: `app/app/Http/Controllers/Auth/SocialLoginController.php`
- Modify: `app/routes/web.php`
- Modify: `app/app/Http/Controllers/Auth/LoginController.php`
- Test: `app/tests/Feature/Auth/SocialOAuthLoginTest.php`

**Step 1: 新增入口 / 回调路由**
- `GET /auth/social/{provider}`
- `GET /auth/social/{provider}/callback`

**Step 2: 控制器逻辑**
- 仅允许后台启用的 provider 进入真实 OAuth。
- `oauth` 模式走官方回调。
- `demo` 模式继续走现有二维码演示链路。
- 成功后建立 Laravel Session 并跳转首页。

### Task 5: 补齐配置与前台展示

**Files:**
- Modify: `app/config/community.php`
- Modify: `config/config.toml`
- Modify: `config/.env.example`
- Modify: `app/resources/views/auth/login.blade.php`
- Modify: `README.md`
- Test: `app/tests/Feature/Auth/AuthPagesTest.php`

**Step 1: 配置模型**
- 为微信 / QQ 增加 `mode`、`client_id`、`redirect_uri` 等非密钥配置。
- 为 secret/appkey 增加 `.env.example` 模板。

**Step 2: 登录页展示**
- 已配置真实 OAuth 时展示“前往微信/QQ扫码登录”。
- 未配置时明确标注“演示模式”。
- 不移除现有二维码演示能力。

### Task 6: 文档与变更记录

**Files:**
- Create: `docs/如何让本项目支持微信和QQ扫码登陆.md`
- Modify: `CHANGELOG.md`
- Modify: `README.md`

**Step 1: 写新手教程**
- 说明微信开放平台 / QQ互联需要在站外完成的注册、审核、域名配置、回调配置。
- 给出本项目需要填写的配置项、Docker 重部署步骤、联调检查清单。
- 标注官方文档链接与常见报错。

**Step 2: 更新变更记录**
- 在 `CHANGELOG.md` 的 `[Unreleased]` 中记录新增真实 OAuth 扫码登录能力、演示模式降级与操作文档。

### Task 7: 验证与 Docker 审查

**Files:**
- Test: `app/tests/Feature/Auth/AuthPagesTest.php`
- Test: `app/tests/Feature/Auth/SocialOAuthLoginTest.php`
- Test: `app/tests/Feature/Auth/BetterAuthLoginTest.php`

**Step 1: 本地测试**
- 先跑针对性 Feature Tests。
- 再跑 `php artisan test`。

**Step 2: Docker 重部署**
- 执行 `./scripts/compose.sh down`。
- 执行 `./scripts/compose.sh up --build -d`。
- 用 `curl` 检查登录页、健康检查与容器状态。

**Step 3: 代码审查**
- 按 `code-reviewer` 清单自审安全性、回归风险与配置边界。
