# Personal Channel Comment Switch Implementation Plan

**Goal:** 增加“个人频道模式”系统设置，默认关闭普通用户评论与评论媒体上传，让公开站点默认成为只读内容频道；管理员可显式开启评论后再进入互动社区模式。

**Architecture:** 以 `site_settings.comments_enabled` 作为运行态事实来源，默认值为 `false`。后端在评论创建、评论回复、评论订阅和 `context=comment` 的媒体上传入口做硬拦截；前端按同一配置渲染只读评论区或互动表单；静态页构建与站点设置保存后重建保持一致。历史评论默认保留只读展示，避免关闭评论时破坏既有内容和后台治理能力。

**Tech Stack:** Laravel 12, Blade, PostgreSQL migration, PHPUnit Feature Tests, Vite frontend regression

---

## 设计原则

- 默认安全：新部署站点默认 `comments_enabled=false`，对外表现为个人频道 / 内容站。
- 后端兜底：关闭评论时，提交评论、回复评论、评论订阅、评论图片 / 视频上传都必须被服务端拒绝。
- 低破坏：保留历史评论展示与后台评论管理，不删除既有数据，不影响管理员文章发布。
- 单一口径：页面文案、控制器拦截、上传限制、静态构建都读取同一配置。
- 可回退：管理员在站点设置中开启评论后，现有评论功能按原行为恢复。

## Task 1: 固定评论开关的配置模型

**Files:**
- Create: `app/database/migrations/YYYY_MM_DD_HHMMSS_add_comments_enabled_to_site_settings_table.php`
- Modify: `app/app/Models/SiteSetting.php`
- Modify: `app/app/Support/SiteSettingsManager.php`
- Modify: `app/config/community.php`
- Modify: `app/config.toml`
- Modify: `config/.env.example`
- Modify: `app/.env.example`
- Modify: `app/tests/Unit/Support/SiteSettingsManagerTest.php`

**Step 1: Write the failing tests**

- 断言没有数据库覆盖时，`SiteSettingsManager` 返回 `comments_enabled=false`。
- 断言保存站点设置时可以写入 `comments_enabled=true/false`。
- 断言 `applyConfiguredSettings()` 会把值同步到 `config('community.comments.enabled')`。

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Unit/Support/SiteSettingsManagerTest.php`

Expected: FAIL，因为当前没有评论开关字段和配置读取能力。

**Step 3: Write minimal implementation**

- 新增 `site_settings.comments_enabled` 布尔字段，默认 `false`。
- `SiteSetting` 增加 cast 与 fillable。
- `SiteSettingsManager::siteFormData()`、`save()`、`siteUsingOverrides()`、`applyConfiguredSettings()` 纳入 `comments_enabled`。
- `community.php` 增加 `comments.enabled`，默认读取 `COMMENTS_ENABLED=false`。
- 示例配置补充 `COMMENTS_ENABLED=false`。

**Step 4: Run test to verify it passes**

Run: `cd app && php artisan test tests/Unit/Support/SiteSettingsManagerTest.php`

Expected: PASS

## Task 2: 在站点设置页暴露“个人频道模式”

**Files:**
- Modify: `app/app/Http/Controllers/Admin/SiteSettingsController.php`
- Modify: `app/resources/views/admin/site-settings/edit.blade.php`
- Modify: `app/tests/Feature/Admin/AdminSiteSettingsTest.php`

**Step 1: Write the failing tests**

- 断言站点设置页展示“允许用户评论”或“个人频道模式”开关。
- 断言默认表单值为关闭评论。
- 断言管理员保存开启评论后，数据库和运行时配置同步变化。
- 断言保存关闭评论后会触发 `StaticPageBuilder::rebuildAll()`。

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Admin/AdminSiteSettingsTest.php`

Expected: FAIL，因为设置页还没有该字段。

**Step 3: Write minimal implementation**

- 后台校验新增 `comments_enabled` 布尔字段，未勾选时保存为 `false`。
- 设置页增加一个清晰的二元开关：
  - 关闭：个人频道模式，外部用户只能阅读，不能评论或上传评论媒体。
  - 开启：互动社区模式，恢复评论、回复、评论订阅和评论媒体上传。
- 文案避免制造“合规免除”的误解，只说明“降低互动社区相关风险”。

**Step 4: Run test to verify it passes**

Run: `cd app && php artisan test tests/Feature/Admin/AdminSiteSettingsTest.php`

Expected: PASS

## Task 3: 后端硬关闭评论写入口

**Files:**
- Modify: `app/app/Http/Controllers/CommentController.php`
- Modify: `app/app/Http/Controllers/CommentSubscriptionController.php`
- Modify: `app/app/Http/Controllers/ImageUploadController.php`
- Modify: `app/app/Http/Controllers/VideoUploadController.php`
- Create or Modify: `app/tests/Feature/Comments/CommentPostingTest.php`
- Create or Modify: `app/tests/Feature/Uploads/MarkdownMediaUploadTest.php`

**Step 1: Write the failing tests**

- 当 `comments_enabled=false` 时，登录用户 `POST /articles/{article}/comments` 应返回 403 或带错误提示重定向，且不创建评论。
- 当 `comments_enabled=false` 时，回复评论同样被拒绝。
- 当 `comments_enabled=false` 时，评论订阅 / 取消订阅入口被拒绝，避免对已关闭的互动功能继续产生通知关系。
- 当 `comments_enabled=false` 且上传 `context=comment` 时，图片和视频上传被拒绝。
- 当 `comments_enabled=true` 时，现有评论、回复、订阅和评论媒体上传测试继续通过。

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Comments/CommentPostingTest.php tests/Feature/Uploads/MarkdownMediaUploadTest.php`

Expected: FAIL，因为当前登录用户仍可评论和上传评论媒体。

**Step 3: Write minimal implementation**

- 在 `CommentController@store` 开头检查 `config('community.comments.enabled')`。
- 在 `CommentSubscriptionController` 的订阅和取消订阅入口检查同一配置。
- 在 `ImageUploadController` / `VideoUploadController` 中仅拦截 `context=comment`；`context=article` 继续按管理员权限走原逻辑。
- 错误信息统一为“当前站点处于个人频道模式，暂不开放评论互动。”。

**Step 4: Run test to verify it passes**

Run: `cd app && php artisan test tests/Feature/Comments/CommentPostingTest.php tests/Feature/Uploads/MarkdownMediaUploadTest.php`

Expected: PASS

## Task 4: 前台只读评论区体验

**Files:**
- Modify: `app/app/Support/CommunityViewData.php`
- Modify: `app/resources/views/articles/show.blade.php`
- Modify: `app/resources/views/articles/partials/comment-item.blade.php`
- Modify: `app/resources/views/partials/article-card.blade.php`
- Modify: `app/resources/views/channels/show.blade.php`
- Modify: `app/resources/views/home.blade.php`
- Modify: `app/tests/Feature/Articles/ArticlePageTest.php`
- Modify: `app/tests/Feature/Static/StaticBuildTest.php`

**Step 1: Write the failing tests**

- 关闭评论时，文章页不展示主评论表单、回复按钮、回复表单、评论订阅按钮、评论上传提示。
- 关闭评论时，历史评论可只读展示，并显示“评论已关闭”状态。
- 关闭评论时，游客提示不再说“登录后即可参与评论”。
- 开启评论时，文章页恢复原有评论表单和回复控件。
- 静态构建后的文章页与动态页保持同样表现。

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Articles/ArticlePageTest.php tests/Feature/Static/StaticBuildTest.php`

Expected: FAIL，因为 Blade 目前无条件展示评论互动控件。

**Step 3: Write minimal implementation**

- `CommunityViewData` 注入 `commentsEnabled` 到文章页数据。
- `articles/show.blade.php` 关闭评论时只展示历史评论和状态说明。
- `comment-item.blade.php` 仅在 `commentsEnabled=true` 时展示回复、订阅和删除以外的互动按钮；删除按钮可保留给有权限用户，便于历史治理。
- 首页、频道页、文章卡片的“评论数”文案在关闭评论时调整为“只读评论”或弱化展示，避免鼓励互动。

**Step 4: Run test to verify it passes**

Run: `cd app && php artisan test tests/Feature/Articles/ArticlePageTest.php tests/Feature/Static/StaticBuildTest.php`

Expected: PASS

## Task 5: 后台、DevTools 与历史治理边界

**Files:**
- Modify: `app/resources/views/admin/comments/index.blade.php`
- Modify: `app/app/Http/Controllers/Api/Vibe/CommentController.php`
- Modify: `app/tests/Feature/Admin/AdminCommentManagementTest.php`
- Modify: `app/tests/Feature/Api/Vibe/DevtoolsApiTest.php`

**Step 1: Write the failing tests**

- 关闭评论时，后台评论管理页仍可查看、隐藏、恢复和删除历史评论。
- 关闭评论时，后台评论管理页不展示“直接回复评论”入口。
- Vibe Comment API 仍可管理历史评论可见性和删除，但不提供创建评论能力。

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Admin/AdminCommentManagementTest.php tests/Feature/Api/Vibe/DevtoolsApiTest.php`

Expected: 视当前测试覆盖而定；若后台已有回复入口，应先失败于“关闭评论仍展示回复入口”。

**Step 3: Write minimal implementation**

- 后台评论页按 `commentsEnabled` 隐藏直接回复入口，保留治理按钮。
- DevTools 评论接口无需新增创建能力；现有更新 / 删除接口保留，用于治理历史评论。
- 如果当前 DevTools 测试依赖评论统计或列表，补充关闭评论场景断言。

**Step 4: Run test to verify it passes**

Run: `cd app && php artisan test tests/Feature/Admin/AdminCommentManagementTest.php tests/Feature/Api/Vibe/DevtoolsApiTest.php`

Expected: PASS

## Task 6: 法律文档、开发者文档与版本记录

**Files:**
- Modify: `docs/law/中国大陆合规初步评估.md`
- Modify: `docs/开发者文档.md`
- Modify: `README.md`
- Modify: `README_EN.md`
- Modify: `CHANGELOG.md`
- Modify: `app/config.toml`

**Step 1: Update docs**

- 在法律评估中补充“个人频道模式”的合规收益和剩余义务。
- 在开发者文档说明 `comments_enabled` 的默认值、行为边界和测试覆盖。
- README / README_EN 将核心特性中的评论能力改为“可选开启”，避免默认宣传为互动社区。
- 根据新增功能推进版本号。

**Step 2: Run docs consistency checks**

Run: `rg -n "评论|comment|互动社区|个人频道|COMMENTS_ENABLED" README.md README_EN.md docs/开发者文档.md docs/law/中国大陆合规初步评估.md app/config.toml config/.env.example app/.env.example`

Expected: 文档口径一致，默认关闭评论的描述明确。

## Task 7: 全量验证

**Files:**
- Verify only

**Step 1: Run focused regression**

Run:

```bash
cd app && php artisan test \
  tests/Unit/Support/SiteSettingsManagerTest.php \
  tests/Feature/Admin/AdminSiteSettingsTest.php \
  tests/Feature/Comments/CommentPostingTest.php \
  tests/Feature/Uploads/MarkdownMediaUploadTest.php \
  tests/Feature/Articles/ArticlePageTest.php \
  tests/Feature/Admin/AdminCommentManagementTest.php
```

Expected: PASS

**Step 2: Run app regression**

Run: `./scripts/test/app-regression.sh`

Expected: PASS

**Step 3: Run complete project gate before delivery**

Run: `./scripts/test/all.sh`

Expected: NORMAL、STABLE、EFFICIENT、SAFE_CHANGE 全部通过。

## 风险与取舍

- 历史评论只读展示比直接隐藏更透明，也保留已有内容沉淀；如果运营上希望完全个人频道化，可后续增加 `show_existing_comments_when_disabled` 独立设置。
- 关闭评论不等于免除备案、个人信息保护和内容安全义务；它只是显著降低跟帖评论 / 论坛社区相关的互动风险。
- 评论开关应只影响普通互动能力，不影响管理员文章发布、历史评论治理和数据备份恢复。
- `context=comment` 上传必须后端拒绝，否则用户仍可绕过 UI 上传评论媒体。
