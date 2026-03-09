# Admin User Governance Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 让管理员可以完整托管普通用户资料，并可安全删除普通用户账号而不破坏现有内容、权限与登录稳定性。

**Architecture:** 复用现有后台用户管理页与 DevTools 用户 API，新增“删除普通用户”动作，并把删除逻辑下沉到共享支持类中统一处理。删除时不仅依赖数据库级联移除用户关联数据，还要额外清理无外键保护的会话与密码重置令牌，同时回补受影响文章的 `comment_count`，避免内容统计失真。

**Tech Stack:** Laravel 12、Blade、Eloquent、PHPUnit、Docker Compose

---

### Task 1: 先写失败测试

**Files:**
- Modify: `app/tests/Feature/Admin/AdminUserManagementTest.php`
- Modify: `app/tests/Feature/Api/Vibe/DevtoolsApiTest.php`

**Step 1: 锁定后台资料托管范围**
- 验证管理员更新普通用户时可保存 `avatar_url`，确保后台真正覆盖完整资料字段。

**Step 2: 锁定后台删除链路**
- 验证管理员可删除普通用户。
- 验证删除后同步清理 `sessions`、`password_reset_tokens`，并回补被删评论对应文章的 `comment_count`。
- 验证管理员不能直接删除管理员账号。

**Step 3: 锁定 DevTools API 行为**
- 验证 `/api/vibe/users/{user}` 支持删除普通用户并返回成功响应。
- 验证 DevTools API 同样拒绝直接删除管理员账号。

### Task 2: 实现共享删除能力

**Files:**
- Create: `app/app/Support/ManagedUserService.php`
- Modify: `app/app/Support/UserAccountManager.php`

**Step 1: 统一资料字段处理**
- 为管理员资料托管补齐头像字段输入与归一化复用。

**Step 2: 实现安全删除流程**
- 在共享服务中封装“仅允许删除普通用户”的约束。
- 删除前记录受影响文章 ID；删除时清理 `sessions`、`password_reset_tokens`；删除后回补评论计数。
- 使用事务保证删除与统计修复一致完成。

### Task 3: 接入后台与 DevTools API

**Files:**
- Modify: `app/app/Http/Controllers/Admin/UserController.php`
- Modify: `app/app/Http/Controllers/Api/Vibe/UserController.php`
- Modify: `app/resources/views/admin/users/index.blade.php`
- Modify: `app/routes/web.php`
- Modify: `app/routes/api.php`

**Step 1: 后台增加删除动作**
- 为管理员用户管理增加 `DELETE /admin/users/{user}` 路由与控制器动作。
- 在普通用户卡片上增加删除按钮与确认文案；管理员卡片不展示删除入口。
- 补齐头像链接输入框，让后台可维护头像资料。

**Step 2: DevTools API 同步补齐**
- 增加 `DELETE /api/vibe/users/{user}`，复用共享删除服务。
- 统一错误语义，保持 Web 与 API 的删除约束一致。

### Task 4: 文档、版本与验收

**Files:**
- Modify: `CHANGELOG.md`
- Modify: `README.md`
- Modify: `config.yaml`

**Step 1: 更新版本与记录**
- 将版本从 `1.22.0` 提升到 `1.23.0`。
- 记录管理员普通用户治理增强、删除安全约束与相关回归测试。

**Step 2: 执行验证与部署**
- `cd app && php artisan test --filter=AdminUserManagementTest`
- `cd app && php artisan test --filter=DevtoolsApiTest`
- `cd app && php artisan test`
- `cd auth-service && npm test`
- `./scripts/compose.sh down && ./scripts/compose.sh up --build -d`
- `docker compose ps`

