# SEO Optimization Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为公开内容页补齐可抓取、可索引、可分享的 SEO 能力，并让动态站与静态构建保持一致。

**Architecture:** 新增统一的 SEO 数据构建层，由控制器/视图数据输出页面级 metadata，再由布局模板统一渲染 canonical、Open Graph、Twitter 与 JSON-LD。新增 `robots.txt` 与 `sitemap.xml` 动态入口，并让静态构建同步产出对应文件。

**Tech Stack:** Laravel 12、Blade、PHPUnit、静态构建命令 `site:build-static`

---

### Task 1: 锁定 SEO 页面输出

**Files:**
- Create: `app/tests/Feature/Seo/PageSeoTest.php`
- Modify: `app/tests/Feature/Routing/PublicUrlRoutingTest.php`

**Step 1: Write the failing test**

- 验证首页输出 canonical、Open Graph、Twitter 与 WebSite JSON-LD
- 验证频道页输出 CollectionPage/Breadcrumb JSON-LD 与频道 RSS alternate
- 验证文章页输出 Article JSON-LD、comments anchor canonical、article 级 description
- 验证登录页默认 `noindex, nofollow`

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PageSeoTest`

Expected: FAIL，因为当前布局尚未输出这些 SEO 数据。

**Step 3: Write minimal implementation**

- 新增 SEO 数据构建支持
- 将页面数据注入布局
- 统一渲染 head metadata

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=PageSeoTest`

Expected: PASS

### Task 2: 锁定 robots 与 sitemap 行为

**Files:**
- Create: `app/tests/Feature/Seo/SiteDiscoveryTest.php`
- Modify: `app/routes/web.php`

**Step 1: Write the failing test**

- 验证 `GET /robots.txt` 返回 sitemap 地址与允许抓取规则
- 验证 `GET /sitemap.xml` 仅包含首页、公开频道、已发布文章与公开 feed

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SiteDiscoveryTest`

Expected: FAIL，因为路由与生成器尚未存在。

**Step 3: Write minimal implementation**

- 新增 sitemap/robots 控制器或生成器
- 在路由中注册公开入口

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=SiteDiscoveryTest`

Expected: PASS

### Task 3: 锁定静态构建 SEO 产物

**Files:**
- Modify: `app/tests/Feature/Static/StaticBuildTest.php`
- Modify: `app/app/Support/StaticPageBuilder.php`

**Step 1: Write the failing test**

- 验证静态构建输出 `static/robots.txt`
- 验证静态构建输出 `static/sitemap.xml`
- 验证静态文章页包含 canonical / JSON-LD

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=StaticBuildTest`

Expected: FAIL，因为静态构建尚未生成这些文件。

**Step 3: Write minimal implementation**

- 在静态构建流程中增加 robots/sitemap 输出
- 确保首页、频道页、文章页静态 HTML 带完整 SEO head

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=StaticBuildTest`

Expected: PASS

### Task 4: 收尾验证与交付

**Files:**
- Modify: `CHANGELOG.md`
- Modify: `README.md`（仅当需要补充公开入口说明）
- Modify: `README_EN.md`（若 README.md 发生变化则同步）

**Step 1: Run focused tests**

Run: `php artisan test --filter=PageSeoTest`
Run: `php artisan test --filter=SiteDiscoveryTest`
Run: `php artisan test --filter=StaticBuildTest`
Run: `php artisan test --filter=PublicUrlRoutingTest`

**Step 2: Run project validation scripts**

Run: `./scripts/test/app-regression.sh`
Run: `./scripts/test/docker-redeploy.sh`

**Step 3: Review the diff**

- 对照需求检查是否存在索引泄漏、canonical 错误、静态/动态行为不一致

**Step 4: Update changelog**

- 在 `CHANGELOG.md` 的 `Unreleased` 中记录 SEO 增强、站点地图与 robots 支持
