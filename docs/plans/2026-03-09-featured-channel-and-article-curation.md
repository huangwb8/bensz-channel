# Featured Channel And Article Curation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为社区新增不可删除的内置“精华”频道，并让管理员可以在文章管理中随时将文章切换为“置顶”或“精华”，也可随时移除。

**Architecture:** 通过为 `articles` 表新增 `is_pinned` 与 `is_featured` 两个布尔字段承载内容运营状态；“精华频道”作为系统保留频道存在于 `channels` 表中，但其频道页与 RSS 采用聚合视图，展示所有被标记为精华的文章而不改变原始文章归属频道。后台文章管理页提供一键切换操作，编辑表单同时支持显式设置，前台首页与频道页展示对应标记。

**Tech Stack:** Laravel 12、Blade、Eloquent、PHPUnit、Docker Compose

---

### Task 1: 先写失败测试

**Files:**
- Modify: `app/tests/Feature/Database/SystemBootstrapSeederTest.php`
- Modify: `app/tests/Feature/Admin/AdminArticleManagementTest.php`
- Create: `app/tests/Feature/Channels/FeaturedChannelTest.php`
- Modify: `app/tests/Feature/Subscriptions/RssFeedTest.php`

**Step 1: 锁定系统频道基线**
- 验证 `SystemBootstrapSeeder` 会创建 `精华` 与 `未分类` 两个系统频道，且重复执行仍保持幂等。

**Step 2: 锁定后台运营动作**
- 验证管理员创建 / 更新文章时可保存 `is_pinned` / `is_featured`。
- 验证管理员在文章管理列表中可一键切换置顶与精华状态。

**Step 3: 锁定前台聚合视图**
- 验证“精华”频道展示跨频道的精华文章，不展示普通文章。
- 验证“精华”频道 RSS 也按相同聚合口径输出。

### Task 2: 实现最小可用数据层

**Files:**
- Create: `app/database/migrations/2026_03_09_235000_add_curation_flags_to_articles_table.php`
- Modify: `app/app/Models/Article.php`
- Modify: `app/app/Models/Channel.php`
- Modify: `app/database/seeders/SystemBootstrapSeeder.php`
- Modify: `app/database/seeders/DatabaseSeeder.php`

**Step 1: 新增文章运营字段**
- 为文章增加 `is_pinned` / `is_featured`。
- 为模型补充 cast、fillable 与查询 scope。

**Step 2: 新增系统精华频道**
- 在系统启动 Seeder 中内置 `featured` 与 `uncategorized`。
- 将 `精华` 纳入系统保留频道集合，不允许删除。

### Task 3: 实现后台与前台行为

**Files:**
- Modify: `app/app/Http/Controllers/Admin/ArticleController.php`
- Modify: `app/routes/web.php`
- Modify: `app/resources/views/admin/articles/index.blade.php`
- Modify: `app/resources/views/admin/articles/form.blade.php`
- Modify: `app/app/Support/CommunityViewData.php`
- Modify: `app/resources/views/home.blade.php`
- Modify: `app/resources/views/channels/show.blade.php`
- Modify: `app/app/Http/Controllers/RssFeedController.php`
- Modify: `app/resources/views/components/icon.blade.php`

**Step 1: 后台可一键切换**
- 为置顶 / 精华增加单独切换路由与控制器动作。
- 在文章管理列表中补充状态徽标与快速操作按钮。

**Step 2: 前台可正确呈现**
- 首页顶部展示最新置顶文章。
- “精华”频道页与 RSS 聚合所有精华文章。
- 卡片上显示置顶 / 精华标记，并在精华频道中保留文章来源频道标识。

### Task 4: 文档、版本与验收

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `config.yaml`

**Step 1: 更新版本与变更记录**
- 将版本从 `1.15.1` 提升到 `1.16.0`。
- 记录系统精华频道、文章运营状态与相关回归测试。

**Step 2: 全量验证与 Docker 重部署**
- `cd app && php artisan test`
- `./scripts/compose.sh up --build -d`
- `docker compose ps`
- `docker compose exec -T web php artisan migrate --force`
