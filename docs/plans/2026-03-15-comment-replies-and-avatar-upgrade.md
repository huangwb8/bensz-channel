# Comment Replies And Avatar Upgrade Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为社区平台补齐评论回复树、评论回复邮件通知与订阅开关，同时升级默认头像体系并支持用户上传自定义头像。

**Architecture:** 评论系统在现有 `comments` 表上扩展父子关系，并新增“评论订阅”表承载每条评论的参与者订阅状态；通知沿用 Laravel Notification，在发布回复后向符合条件的订阅者发邮件。头像系统在 `users` 表上补充头像来源与默认风格字段，通过统一头像解析器生成“上传图片 / 外链 / 默认风格”三种输出，前端与静态页统一复用。

**Tech Stack:** Laravel 12, Blade, PHPUnit, Notifications, Storage(public), PostgreSQL, Docker Compose

---

### Task 1: 数据模型与回归测试

**Files:**
- Create: `app/database/migrations/2026_03_15_*.php`
- Modify: `app/tests/Feature/Comments/CommentPostingTest.php`
- Modify: `app/tests/Feature/Subscriptions/*.php`
- Modify: `app/tests/Feature/Settings/AccountSettingsTest.php`
- Create: `app/tests/Feature/Uploads/AvatarUploadTest.php`

**Step 1: Write the failing tests**

- 为评论回复、评论订阅邮件、取消订阅、头像上传和头像风格切换分别补测试。

**Step 2: Run tests to verify they fail**

- Run: `php artisan test --filter=CommentPostingTest`
- Run: `php artisan test --filter=CommentReplyNotificationTest`
- Run: `php artisan test --filter=AccountSettingsTest`

**Step 3: Write minimal schema/model support**

- 为评论增加 `parent_id`、`root_id`。
- 为用户通知偏好增加评论回复邮件总开关。
- 新增评论订阅表。
- 为用户增加头像类型/默认风格字段。

**Step 4: Run targeted tests**

- Run the same commands until PASS.

### Task 2: 评论回复与订阅通知链路

**Files:**
- Modify: `app/app/Models/Comment.php`
- Modify: `app/app/Models/User.php`
- Modify: `app/app/Models/UserNotificationPreference.php`
- Create: `app/app/Models/CommentSubscription.php`
- Modify: `app/app/Http/Controllers/CommentController.php`
- Create: `app/app/Http/Controllers/CommentSubscriptionController.php`
- Create: `app/app/Support/CommentReplyNotifier.php`
- Create: `app/app/Notifications/CommentReplyNotification.php`
- Modify: `app/app/Support/CommunityViewData.php`
- Modify: `app/routes/web.php`

**Step 1: Write the failing tests**

- 断言回复评论可写入层级。
- 断言原评论作者默认收到邮件。
- 断言关闭全局邮件或单条评论订阅后不再收到。
- 断言作者可重新开启单条评论订阅。

**Step 2: Run tests to verify they fail**

- `php artisan test app/tests/Feature/Comments app/tests/Feature/Subscriptions`

**Step 3: Write minimal implementation**

- 存储评论父子关系。
- 发布评论/回复时自动维护订阅记录。
- 回复时筛选通知对象并发送邮件。
- 提供订阅开关路由。

**Step 4: Run targeted tests**

- `php artisan test app/tests/Feature/Comments app/tests/Feature/Subscriptions`

### Task 3: 头像系统升级

**Files:**
- Create: `app/app/Support/AvatarPresenter.php`
- Create: `app/app/Http/Controllers/AvatarUploadController.php`
- Modify: `app/app/Support/UserAccountManager.php`
- Modify: `app/app/Http/Controllers/AccountSettingsController.php`
- Modify: `app/resources/views/settings/account.blade.php`
- Modify: `app/resources/views/articles/show.blade.php`
- Modify: `app/resources/views/layouts/app.blade.php`
- Modify: `app/resources/views/admin/users/index.blade.php`
- Create: `app/resources/views/components/user-avatar.blade.php`

**Step 1: Write the failing tests**

- 断言账户页支持上传 JPG/PNG 且限制 1MB。
- 断言可切换多个默认头像风格。
- 断言已有外链头像和上传头像都能正确渲染。

**Step 2: Run tests to verify they fail**

- `php artisan test --filter=AccountSettingsTest`
- `php artisan test --filter=AvatarUploadTest`

**Step 3: Write minimal implementation**

- 新增头像上传接口。
- 将资料页改为“上传 / 默认风格 / 外链”统一入口。
- 通过统一组件替换零散首字母头像。

**Step 4: Run targeted tests**

- `php artisan test --filter=AccountSettingsTest`
- `php artisan test --filter=AvatarUploadTest`

### Task 4: 文档、版本与部署验证

**Files:**
- Modify: `CHANGELOG.md`
- Modify: `app/config.toml`

**Step 1: Update project metadata**

- 评估为向下兼容功能新增，版本升级为次版本。

**Step 2: Run validation**

- `./scripts/test/all.sh`
- `./scripts/test/docker-redeploy.sh`
- `./scripts/test/docker-smoke.sh`

**Step 3: Review**

- 基于需求做一轮 code review，重点检查通知去重、权限校验、头像上传安全与静态页回归。
