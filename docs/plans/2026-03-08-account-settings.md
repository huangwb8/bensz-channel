# Account Settings Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为已登录用户补齐可自助维护的账户设置能力，支持修改昵称、邮箱、手机号、头像、简介与密码，并保持现有登录、后台管理、静态页与 Docker 部署稳定。

**Architecture:** 在 Laravel Web 侧新增独立“账户设置”页面与控制器；将用户资料归一化、唯一性校验、至少保留一个登录标识、联系方式变更后重置验证状态等逻辑抽到共享支持类，供“自助设置”和“管理员编辑用户”复用。密码修改保持独立提交流程，避免与资料更新耦合，并通过回归测试锁定页面入口、资料保存与密码变更行为。

**Tech Stack:** Laravel 12、Blade、PHPUnit、Docker Compose

---

### Task 1: 锁定回归场景

**Files:**
- Create: `app/tests/Feature/Settings/AccountSettingsTest.php`
- Modify: `app/tests/Feature/Admin/AdminNavigationTest.php`

**Step 1: Write the failing test**

- 验证登录用户可访问 `settings.account.edit`
- 验证用户可更新 `name/email/phone/avatar_url/bio`
- 验证修改邮箱后会清空 `email_verified_at`
- 验证密码修改需要正确旧密码（若用户已有密码）
- 验证无邮箱用户不能直接设置密码登录
- 验证顶部菜单存在“账户设置”入口

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Settings/AccountSettingsTest.php tests/Feature/Admin/AdminNavigationTest.php`

Expected: 失败，提示缺少账户设置路由/页面/控制器。

### Task 2: 抽取共享账户更新逻辑

**Files:**
- Create: `app/app/Support/UserAccountManager.php`
- Modify: `app/app/Http/Controllers/Admin/UserController.php`

**Step 1: Write minimal shared API**

- 提供输入归一化
- 提供通用资料校验规则
- 提供“至少保留一个登录标识”约束
- 提供联系方式变化后的验证状态同步

**Step 2: Wire admin path to shared logic**

- 后台用户编辑继续支持角色管理
- 资料字段改为复用共享逻辑，避免双份规则漂移

### Task 3: 新增账户设置页面与控制器

**Files:**
- Create: `app/app/Http/Controllers/AccountSettingsController.php`
- Create: `app/resources/views/settings/account.blade.php`
- Modify: `app/routes/web.php`
- Modify: `app/resources/views/layouts/app.blade.php`

**Step 1: Add routes and controller**

- `GET /settings/account`
- `PUT /settings/account/profile`
- `PUT /settings/account/password`

**Step 2: Implement minimal profile update**

- 仅允许当前登录用户改自己的资料
- 资料变更后重建静态页
- 保存成功后回跳设置页并提示状态

**Step 3: Implement password update flow**

- 已有密码时要求旧密码
- 无邮箱时阻止设置邮箱密码登录
- 新密码要求确认一致

**Step 4: Build the Blade UI**

- 单独分区展示基础资料、账户状态、密码设置
- 保持与现有订阅设置页一致的视觉风格

### Task 4: 验证、审查与部署

**Files:**
- Modify: `CHANGELOG.md`
- Modify: `config.yaml`
- Modify: `README.md`

**Step 1: Run targeted tests**

Run: `cd app && php artisan test tests/Feature/Settings/AccountSettingsTest.php tests/Feature/Admin/AdminNavigationTest.php tests/Feature/Admin/AdminUserManagementTest.php`

**Step 2: Run broader safety net**

Run: `cd app && php artisan test`

**Step 3: Review implementation**

- 按 `code-reviewer` 检查鉴权、唯一索引、验证状态、菜单入口、测试覆盖

**Step 4: Rebuild and redeploy Docker**

Run: `./scripts/compose.sh up --build -d`

**Step 5: Smoke verify**

- `./scripts/compose.sh ps`
- 打开健康检查或容器日志确认 `web` / `auth` 正常

