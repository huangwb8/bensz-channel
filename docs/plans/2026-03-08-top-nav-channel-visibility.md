# Top Nav Channel Visibility Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为社区平台增加“频道是否出现在顶栏”的管理员可配机制，并让 `未分类` 频道默认隐藏但仍可保留访问与承接迁移文章能力。

**Architecture:** 通过为 `channels` 表新增独立布尔字段保存顶栏可见性，把“频道存在性”和“频道是否展示在顶栏”彻底解耦。前台布局只读取允许出现在顶栏的频道；后台频道管理页允许管理员在创建与编辑时切换该开关，并对系统保留的 `未分类` 频道保留最小可配置入口而不开放删除。

**Tech Stack:** Laravel 12、Blade、Eloquent、PHPUnit、Docker Compose

---

### Task 1: 锁定顶栏显示回归

**Files:**
- Modify: `app/tests/Feature/Admin/AdminChannelManagementTest.php`
- Create: `app/tests/Feature/Channels/TopNavChannelVisibilityTest.php`

**Step 1: 写失败测试**

- 覆盖后台新增/更新频道时可提交“顶栏显示”开关。
- 覆盖首页顶栏只渲染 `show_in_top_nav=true` 的频道。
- 覆盖 `uncategorized` 默认隐藏，但仍可通过后台配置切换为显示。

**Step 2: 运行局部测试确认失败**

Run: `cd app && php artisan test --filter=TopNavChannelVisibilityTest --filter=AdminChannelManagementTest`

**Step 3: 最小实现通过**

- 数据层新增字段与模型 cast / fillable。
- 前台布局改为读取新的顶栏可见频道集合。
- 后台频道管理表单加入开关，并保留系统频道说明。

**Step 4: 重新运行局部测试确认通过**

Run: `cd app && php artisan test --filter=TopNavChannelVisibilityTest --filter=AdminChannelManagementTest`

### Task 2: 迁移与系统默认值收敛

**Files:**
- Create: `app/database/migrations/2026_03_09_*.php`
- Modify: `app/app/Http/Controllers/Admin/ChannelController.php`
- Modify: `app/app/Models/Channel.php`

**Step 1: 编写迁移**

- 为 `channels` 表新增 `show_in_top_nav`，默认值为 `true`。
- 迁移现有 `uncategorized` 记录为 `false`，保证默认隐藏生效。

**Step 2: 收敛系统保留频道默认值**

- `ensureUncategorizedChannel()` 创建时写入 `show_in_top_nav=false`。
- 后台校验统一归一化复选框输入，避免空值误判。

**Step 3: 验证迁移与创建逻辑**

Run: `cd app && php artisan migrate --force`

### Task 3: 更新后台与文档

**Files:**
- Modify: `app/resources/views/admin/channels/index.blade.php`
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `config.yaml`

**Step 1: 更新后台交互**

- 新建频道表单增加“顶栏显示”勾选项，默认勾选。
- 频道列表增加对应开关与系统说明，允许管理员显式控制 `未分类` 顶栏展示。

**Step 2: 更新文档与版本**

- 在 `README.md` 记录频道可隐藏但不删除的管理能力。
- 在 `CHANGELOG.md` 记录本次计划、功能、测试与版本变更。
- 根据新增功能将 `config.yaml` 版本从 `1.14.0` 提升到 `1.15.0`。

### Task 4: 全量验证与 Docker 验收

**Files:**
- Verify only

**Step 1: 跑相关测试**

Run: `cd app && php artisan test --filter=AdminChannelManagementTest --filter=TopNavChannelVisibilityTest --filter=StaticBuildTest`

**Step 2: Docker 重部署**

Run: `./scripts/compose.sh up --build -d`

**Step 3: 健康检查**

- `docker compose ps`
- `docker compose exec web php artisan migrate --force`
- `docker compose exec web php artisan test --filter=AdminChannelManagementTest --filter=TopNavChannelVisibilityTest`

