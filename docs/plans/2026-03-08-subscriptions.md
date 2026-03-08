# 订阅功能（SMTP + RSS）Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为社区增加可用的 SMTP 邮件订阅与公开 RSS 订阅能力，覆盖“全部版块 / 指定版块”的新文章提醒，以及评论中的 `@用户` 邮件提醒。

**Architecture:** 采用 Laravel 原生模型 + Notification + XML Response 实现。SMTP 订阅仅对注册用户开放：使用一张用户邮件偏好表和一张按频道订阅表表达“默认订阅全部、可关闭、可仅订阅部分版块”；RSS 通过公开只读 feed 路由提供，无需登录即可凭链接订阅。文章发布与评论发布时在现有控制器链路内触发通知，RSS 由独立控制器输出。

**Tech Stack:** Laravel 12、Blade、Eloquent、Notifications、PHPUnit

---

### Task 1: 锁定订阅数据模型

**Files:**
- Create: `app/database/migrations/2026_03_08_000001_create_user_notification_preferences_table.php`
- Create: `app/database/migrations/2026_03_08_000002_create_channel_email_subscriptions_table.php`
- Create: `app/app/Models/UserNotificationPreference.php`
- Create: `app/app/Models/ChannelEmailSubscription.php`
- Modify: `app/app/Models/User.php`
- Modify: `app/app/Providers/AppServiceProvider.php`
- Test: `app/tests/Feature/Subscriptions/SubscriptionSettingsTest.php`

**Step 1: Write the failing test**
- 验证新注册用户默认拥有“全部版块邮件订阅 + @评论邮件提醒”偏好。
- 验证用户可保存“关闭全部版块、仅订阅指定频道”的设置。

**Step 2: Run test to verify it fails**
- Run: `php artisan test tests/Feature/Subscriptions/SubscriptionSettingsTest.php`
- Expected: FAIL，提示缺少表/模型/路由。

**Step 3: Write minimal implementation**
- 增加偏好与订阅表。
- 在用户模型上增加关联与默认初始化。
- 增加订阅设置页与保存接口。

**Step 4: Run test to verify it passes**
- Run: `php artisan test tests/Feature/Subscriptions/SubscriptionSettingsTest.php`
- Expected: PASS。

### Task 2: 锁定文章邮件提醒

**Files:**
- Create: `app/app/Notifications/ArticlePublishedNotification.php`
- Create: `app/app/Support/ArticleSubscriptionNotifier.php`
- Modify: `app/app/Http/Controllers/Admin/ArticleController.php`
- Test: `app/tests/Feature/Subscriptions/ArticleEmailSubscriptionTest.php`

**Step 1: Write the failing test**
- 验证发布新文章时，会给默认订阅全部版块的成员发邮件。
- 验证关闭“全部版块”后，仅给显式订阅该频道的成员发邮件。
- 验证更新已发布文章不会重复发通知。

**Step 2: Run test to verify it fails**
- Run: `php artisan test tests/Feature/Subscriptions/ArticleEmailSubscriptionTest.php`
- Expected: FAIL，提示通知未发送或重复发送。

**Step 3: Write minimal implementation**
- 提取文章发布通知服务。
- 仅在“从未发布 → 已发布”的状态变化时通知。

**Step 4: Run test to verify it passes**
- Run: `php artisan test tests/Feature/Subscriptions/ArticleEmailSubscriptionTest.php`
- Expected: PASS。

### Task 3: 锁定 @评论邮件提醒

**Files:**
- Create: `app/app/Notifications/CommentMentionedNotification.php`
- Create: `app/app/Support/CommentMentionNotifier.php`
- Modify: `app/app/Http/Controllers/CommentController.php`
- Test: `app/tests/Feature/Subscriptions/CommentMentionNotificationTest.php`

**Step 1: Write the failing test**
- 验证评论中提及 `@用户名` 时，目标用户收到邮件。
- 验证关闭 @评论提醒后不发送。
- 验证无邮箱、重复提及本人、重复用户不会错误发送。

**Step 2: Run test to verify it fails**
- Run: `php artisan test tests/Feature/Subscriptions/CommentMentionNotificationTest.php`
- Expected: FAIL，提示通知未发送或误发送。

**Step 3: Write minimal implementation**
- 在评论提交后提取被提及用户并去重。
- 仅对开启提醒且存在邮箱的用户发送通知。

**Step 4: Run test to verify it passes**
- Run: `php artisan test tests/Feature/Subscriptions/CommentMentionNotificationTest.php`
- Expected: PASS。

### Task 4: 锁定 RSS 输出

**Files:**
- Create: `app/app/Http/Controllers/RssFeedController.php`
- Create: `app/app/Support/RssFeedBuilder.php`
- Modify: `app/routes/web.php`
- Modify: `app/resources/views/home.blade.php`
- Modify: `app/resources/views/channels/show.blade.php`
- Modify: `app/resources/views/articles/show.blade.php`
- Test: `app/tests/Feature/Subscriptions/RssFeedTest.php`

**Step 1: Write the failing test**
- 验证全部版块 RSS 可匿名访问。
- 验证指定频道 RSS 仅输出该频道已发布文章。

**Step 2: Run test to verify it fails**
- Run: `php artisan test tests/Feature/Subscriptions/RssFeedTest.php`
- Expected: FAIL，提示路由缺失或 XML 内容不符。

**Step 3: Write minimal implementation**
- 输出符合 RSS 2.0 的 XML。
- 在首页/频道页/文章页提供可复制链接。

**Step 4: Run test to verify it passes**
- Run: `php artisan test tests/Feature/Subscriptions/RssFeedTest.php`
- Expected: PASS。

### Task 5: 文档与收尾

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `config.yaml`

**Step 1: Update docs**
- 记录 SMTP 与 RSS 功能、使用方式与访问入口。

**Step 2: Validate**
- Run: `php artisan test`
- Expected: 全部相关测试通过。
