# Admin Comment Management And Admin Mail Notifications Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 新增后台评论管理界面，并在新用户首次注册与用户发布评论时向管理员发送邮件通知，确保功能、测试和 Docker 部署链路一起稳定落地。

**Architecture:** 后台评论管理复用现有 Laravel Admin 路由、Blade 与 `x-icon-button` 组件，新增独立的 `admin/comments` 控制器与页面，提供筛选、显隐切换、删除和跳转查看能力。管理员邮件通知通过统一的 `AdminActivityNotifier` 服务发送，分别挂到“用户首次创建”与“评论创建”两个事件点，避免把通知逻辑散落在控制器和鉴权流程里。

**Tech Stack:** Laravel、Blade、PHPUnit、Laravel Notifications、Docker

---

### Task 1: 锁定后台导航与评论管理页入口

**Files:**
- Modify: `app/tests/Feature/Admin/AdminNavigationTest.php`
- Create: `app/tests/Feature/Admin/AdminCommentManagementTest.php`

**Step 1: Write the failing tests**

补两个回归测试：
- 管理员下拉菜单顺序必须变为 `站点设置 → CDN 设置 → 管理文章 → 管理评论 → 管理频道 → 管理用户 → 订阅设置 → DevTools → 退出登录`
- 管理员访问 `admin.comments.index` 时，页面要显示评论管理标题、筛选项、评论元信息与操作入口

**Step 2: Run tests to verify they fail**

Run: `cd app && php artisan test tests/Feature/Admin/AdminNavigationTest.php tests/Feature/Admin/AdminCommentManagementTest.php`

Expected: FAIL，因为当前不存在 `admin/comments` 页面，也没有“管理评论”入口。

### Task 2: 实现后台评论管理页

**Files:**
- Create: `app/app/Http/Controllers/Admin/CommentController.php`
- Modify: `app/routes/web.php`
- Create: `app/resources/views/admin/comments/index.blade.php`
- Modify: `app/resources/views/layouts/app.blade.php`
- Modify: `app/resources/views/admin/site-settings/edit.blade.php`
- Modify: `app/resources/views/admin/users/index.blade.php`
- Modify: `app/resources/views/admin/devtools/index.blade.php`
- Reference: `app/resources/views/admin/articles/index.blade.php`

**Step 1: Write minimal implementation**

实现：
- `GET /admin/comments`
- `PATCH /admin/comments/{comment}/visibility`
- `DELETE /admin/comments/{comment}`

页面提供：
- 关键词搜索（评论内容 / 用户昵称 / 文章标题）
- 可见性筛选（全部 / 仅显示 / 仅隐藏）
- 评论卡片信息（作者、文章、频道、发布时间、显隐状态）
- 操作按钮（查看文章、隐藏/显示、删除评论）

并在管理员导航与相关后台页快捷入口中加入“评论管理”，位置放在“文章管理”和“频道管理”之间。

**Step 2: Run tests to verify they pass**

Run: `cd app && php artisan test tests/Feature/Admin/AdminNavigationTest.php tests/Feature/Admin/AdminCommentManagementTest.php`

Expected: PASS

### Task 3: 锁定评论管理动作的副作用

**Files:**
- Modify: `app/tests/Feature/Admin/AdminCommentManagementTest.php`
- Reference: `app/app/Support/StaticPageBuilder.php`

**Step 1: Write the failing tests**

为评论管理补动作回归：
- 隐藏评论后 `is_visible=false`
- 重新显示评论后 `is_visible=true`
- 删除评论后数据库记录消失
- 文章 `comment_count` 按可见评论数刷新
- 每次动作后触发对应文章的静态页重建

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Admin/AdminCommentManagementTest.php`

Expected: FAIL，因为当前后台没有评论管理动作，也没有刷新评论计数。

### Task 4: 落实评论管理动作与评论计数刷新

**Files:**
- Modify: `app/app/Models/Article.php`
- Modify: `app/app/Http/Controllers/CommentController.php`
- Modify: `app/app/Support/ManagedUserService.php`
- Modify: `app/app/Http/Controllers/Admin/CommentController.php`

**Step 1: Write minimal implementation**

收口评论计数逻辑，统一按“当前可见评论数”刷新，确保：
- 前台用户发评论
- 后台管理员隐藏/恢复/删除评论
- 删除用户导致评论清理

这些场景都会保持 `articles.comment_count` 与实际对外展示一致。

**Step 2: Run related tests**

Run: `cd app && php artisan test tests/Feature/Admin/AdminCommentManagementTest.php tests/Feature/Comments/CommentPostingTest.php tests/Feature/Admin/AdminUserManagementTest.php tests/Feature/Api/Vibe/DevtoolsApiTest.php`

Expected: PASS

### Task 5: 锁定管理员活动邮件通知

**Files:**
- Create: `app/tests/Feature/Notifications/AdminActivityNotificationTest.php`
- Modify: `app/tests/Feature/Auth/OtpLoginTest.php`
- Modify: `app/tests/Feature/Auth/SocialOAuthLoginTest.php`

**Step 1: Write the failing tests**

补通知回归：
- 新用户首次通过邮箱验证码完成登录并创建本地账号时，向管理员邮箱发送一次注册通知
- 已存在用户再次登录时，不重复发送注册通知
- 新用户首次通过微信/QQ OAuth 创建本地账号时，也发送注册通知
- 已注册用户发布评论时，向管理员邮箱发送一次评论通知

测试使用 `Notification::fake()` 与 `assertSentOnDemand()`，并验证目标收件人为 `config('community.admin.email')`。

**Step 2: Run tests to verify they fail**

Run: `cd app && php artisan test tests/Feature/Notifications/AdminActivityNotificationTest.php tests/Feature/Auth/OtpLoginTest.php tests/Feature/Auth/SocialOAuthLoginTest.php`

Expected: FAIL，因为当前没有管理员活动通知服务。

### Task 6: 实现管理员邮件通知服务

**Files:**
- Create: `app/app/Support/AdminActivityNotifier.php`
- Create: `app/app/Notifications/AdminNewUserRegisteredNotification.php`
- Create: `app/app/Notifications/AdminCommentPostedNotification.php`
- Modify: `app/app/Services/Auth/LoginUserResolver.php`
- Modify: `app/app/Services/Auth/SocialAccountResolver.php`
- Modify: `app/app/Http/Controllers/CommentController.php`
- Modify: `docs/开发者文档.md`

**Step 1: Write minimal implementation**

新增统一服务：
- `sendUserRegistered(User $user, string $source)`
- `sendCommentPosted(Comment $comment)`

约束：
- 仅在首次创建本地用户时发送注册通知
- 评论通知在评论落库后发送
- 使用当前 SMTP 配置与管理员邮箱，不影响现有文章订阅、@ 提醒和验证码邮件链路

**Step 2: Run tests to verify they pass**

Run: `cd app && php artisan test tests/Feature/Notifications/AdminActivityNotificationTest.php tests/Feature/Auth/OtpLoginTest.php tests/Feature/Auth/SocialOAuthLoginTest.php tests/Feature/Comments/CommentPostingTest.php`

Expected: PASS

### Task 7: 更新版本与变更记录

**Files:**
- Modify: `app/config.toml`
- Modify: `CHANGELOG.md`
- Modify: `docs/开发者文档.md`

**Step 1: Apply docs/version updates**

更新：
- 项目版本号
- `CHANGELOG.md` 的 `[Unreleased]`
- 开发者文档中后台评论管理与管理员活动通知说明

**Step 2: Sanity check docs**

确认文档描述与实现一致，不引用不存在的入口或行为。

### Task 8: 完整验证、代码审查与 Docker 重部署

**Files:**
- Test: `scripts/test/all.sh`
- Test: `scripts/test/app-regression.sh`
- Test: `scripts/test/auth-regression.sh`
- Test: `scripts/test/docker-smoke.sh`
- Run: `./scripts/build.sh`
- Run: `./scripts/compose.sh up -d`

**Step 1: Run full verification**

至少执行：
- `cd app && php artisan test`
- `cd auth-service && npm test`
- `./scripts/test/all.sh`

如果脚本已覆盖上述能力，则以 `scripts/test/` 结果为准。

**Step 2: Review implementation**

按 `code-reviewer` 清单检查：
- 后台权限是否只在服务端放行给管理员
- 评论管理动作是否保持数据一致性
- 管理员通知是否只在目标事件触发一次
- 是否保留了必要的回归测试

**Step 3: Rebuild and redeploy**

Run:
- `./scripts/build.sh`
- `./scripts/compose.sh up -d`
- `./scripts/test/docker-smoke.sh`

Expected: 容器成功重建并启动，基础冒烟通过，可供人工审查。
