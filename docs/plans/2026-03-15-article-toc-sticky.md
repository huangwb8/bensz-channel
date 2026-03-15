# Article TOC Sticky Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 修复文章详情页桌面端右侧目录在长文滚动时无法持续停留在视口内的问题，并保证目录过长时仍可稳定点击跳转。

**Architecture:** 保留现有 Blade 结构与目录生成逻辑，只对桌面端容器约束做最小调整：解除会阻断 `position: sticky` 的桌面端裁剪、让目录侧栏在 CSS Grid 中按内容高度起始对齐，并通过既有目录面板的内部滚动能力承接超长目录。以 Blade 回归测试锁定关键类名，避免后续样式调整再次破坏粘性行为。

**Tech Stack:** Laravel 12、Blade、Tailwind CSS 4、PHPUnit

---

### Task 1: 锁定回归契约

**Files:**
- Modify: `app/tests/Feature/Articles/ArticleTocTest.php`

**Step 1: Write the failing test**

为文章详情页新增断言，要求桌面端文章容器包含 `lg:overflow-visible`，桌面端目录 `aside` 包含 `lg:self-start`，目录面板保留 `sticky top-24`。

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Articles/ArticleTocTest.php`
Expected: FAIL because current Blade markup does not expose the new desktop sticky support classes.

### Task 2: 实施最小修复

**Files:**
- Modify: `app/resources/views/articles/show.blade.php`

**Step 1: Update article shell**

让文章根容器在小屏继续 `overflow-hidden`，在 `lg` 及以上切回 `overflow-visible`，避免桌面端阻断 sticky。

**Step 2: Update desktop TOC column**

让桌面端目录列使用 `lg:self-start`，避免在 grid 默认拉伸下影响 sticky 计算。

**Step 3: Keep sticky panel unchanged**

保留目录面板 `sticky top-24` 与内部滚动能力，确保长目录仍在视口内可操作。

### Task 3: 验证与交付

**Files:**
- Modify: `CHANGELOG.md`
- Modify: `app/config.toml`

**Step 1: Run targeted tests**

Run: `php artisan test tests/Feature/Articles/ArticleTocTest.php tests/Feature/Static/StaticBuildTest.php`
Expected: PASS

**Step 2: Run project regression scripts**

Run: `scripts/test/all.sh`
Expected: PASS

**Step 3: Rebuild and redeploy Docker stack**

Run: `scripts/test/docker-redeploy.sh`
Expected: PASS with containers rebuilt and restarted for manual review.

**Step 4: Record release metadata**

将本次桌面端目录修复记录到 `CHANGELOG.md`，并按补丁版本递增 `app/config.toml` 中的项目版本号。
