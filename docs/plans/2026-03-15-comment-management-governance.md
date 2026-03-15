# Comment Management Governance Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 为后台评论管理页补齐站内直接回复能力，并让普通用户只能管理自己及自己评论下游回复，减少管理员介入成本。

**Architecture:** 复用现有 `CommentController@store` 作为唯一回复入口，通过显式的 `redirect_back` 标记让后台回复后返回评论管理页；新增独立的评论管理删除入口，由服务层统一承接删除和评论计数刷新，并把“管理员全量管理、普通用户仅管理自己相关线程”封装到评论模型授权方法中。前台评论树与后台评论管理页分别只展示当前用户有权执行的操作，避免 UI 和后端权限漂移。

**Tech Stack:** Laravel 12, Blade, PHPUnit Feature Tests

---

### Task 1: 锁定后台评论页直接回复行为

**Files:**
- Modify: `app/tests/Feature/Admin/AdminCommentManagementTest.php`
- Modify: `app/resources/views/admin/comments/index.blade.php`
- Modify: `app/app/Http/Controllers/CommentController.php`

**Step 1: Write the failing test**

- 为后台评论管理页增加断言：页面应出现“回复评论”入口。
- 为管理员回复增加断言：从后台页提交回复后，落库成功且跳回后台评论页。

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Admin/AdminCommentManagementTest.php`

Expected: FAIL，因为后台页当前没有回复入口，评论回复后也只会跳转文章页。

**Step 3: Write minimal implementation**

- 在后台评论卡片中增加内联回复表单。
- `CommentController@store` 支持 `redirect_back`，仅用于回到来源页面，不改变评论创建链路。

**Step 4: Run test to verify it passes**

Run: `cd app && php artisan test tests/Feature/Admin/AdminCommentManagementTest.php`

Expected: PASS

**Step 5: Commit**

```bash
git add docs/plans/2026-03-15-comment-management-governance.md app/tests/Feature/Admin/AdminCommentManagementTest.php app/resources/views/admin/comments/index.blade.php app/app/Http/Controllers/CommentController.php
git commit -m "feat: support inline replies in admin comment management"
```

### Task 2: 锁定普通用户评论管理边界

**Files:**
- Modify: `app/tests/Feature/Comments/CommentPostingTest.php`
- Create or Modify: `app/app/Http/Controllers/CommentManagementController.php`
- Modify: `app/app/Models/Comment.php`
- Modify: `app/routes/web.php`
- Modify: `app/resources/views/articles/partials/comment-item.blade.php`

**Step 1: Write the failing test**

- 断言普通用户可以删除自己的评论。
- 断言普通用户可以删除别人对自己评论继续发出的下游回复。
- 断言普通用户不能删除与自己无关的评论。
- 断言文章页只在有权限时显示删除入口。

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Comments/CommentPostingTest.php`

Expected: FAIL，因为当前没有普通用户评论删除入口，也没有相关权限控制。

**Step 3: Write minimal implementation**

- 为评论模型增加统一的“是否可被某用户管理”判断。
- 新增受 `auth` 保护的评论删除入口，管理员放行全部，普通用户仅放行自己及自己评论下游回复。
- 前台评论树按同一授权方法控制删除按钮显隐。

**Step 4: Run test to verify it passes**

Run: `cd app && php artisan test tests/Feature/Comments/CommentPostingTest.php`

Expected: PASS

**Step 5: Commit**

```bash
git add app/tests/Feature/Comments/CommentPostingTest.php app/app/Http/Controllers/CommentManagementController.php app/app/Models/Comment.php app/routes/web.php app/resources/views/articles/partials/comment-item.blade.php
git commit -m "feat: allow users to manage their own comment threads"
```

### Task 3: 文档、版本与回归

**Files:**
- Modify: `docs/开发者文档.md`
- Modify: `CHANGELOG.md`
- Modify: `app/config.toml`

**Step 1: Update docs**

- 在开发者文档的评论管理章节补充后台直接回复与普通用户评论治理边界。
- 在 `CHANGELOG.md` 记录新增能力。
- 根据变更类型推进版本号。

**Step 2: Run regression**

Run: `cd app && php artisan test tests/Feature/Admin/AdminCommentManagementTest.php tests/Feature/Comments/CommentPostingTest.php`

Run: `./scripts/test/app-regression.sh`

Expected: PASS

**Step 3: Commit**

```bash
git add docs/开发者文档.md CHANGELOG.md app/config.toml
git commit -m "docs: record comment management governance updates"
```
