# DevTools Icon Shortcuts Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 将 `admin/devtools` 顶部的文章管理、频道管理、用户管理入口改为与其它管理页一致的图标化快捷入口，并用回归测试锁定行为。

**Architecture:** 复用现有 Blade 组件 `x-icon-button` 与容器样式 `icon-action-group`，避免引入新的样式体系或重复实现。先补一个针对 DevTools 页面的特性测试，确认当前文本按钮实现会失败，再做最小视图替换并验证通过。

**Tech Stack:** Laravel、Blade、PHPUnit

---

### Task 1: 为 DevTools 管理页补回归测试

**Files:**
- Create: `app/tests/Feature/Admin/AdminDevtoolsTest.php`
- Test: `app/tests/Feature/Admin/AdminDevtoolsTest.php`

**Step 1: Write the failing test**

新增一个管理员访问 `admin.devtools.index` 的测试，断言页面中出现：
- `data-tooltip="文章管理"`
- `aria-label="文章管理"`
- `data-tooltip="频道管理"`
- `aria-label="频道管理"`
- `data-tooltip="用户管理"`
- `aria-label="用户管理"`

同时断言旧的纯文本链接不再存在：

```php
->assertDontSee('<a href="'.route('admin.articles.index').'" class="btn-secondary">文章管理</a>', false)
```

**Step 2: Run test to verify it fails**

Run: `cd app && php artisan test tests/Feature/Admin/AdminDevtoolsTest.php`
Expected: FAIL，因为当前页面仍使用 `btn-secondary` 文本按钮。

### Task 2: 替换 DevTools 顶部快捷入口

**Files:**
- Modify: `app/resources/views/admin/devtools/index.blade.php`
- Reference: `app/resources/views/admin/site-settings/edit.blade.php`
- Reference: `app/resources/views/components/icon-button.blade.php`

**Step 1: Write minimal implementation**

将：

```blade
<div class="flex flex-wrap gap-2">
```

替换为：

```blade
<div class="icon-action-group">
```

并把三个 `<a class="btn-secondary">` 入口分别替换为：

```blade
<x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
<x-icon-button :href="route('admin.channels.index')" icon="folder" label="频道管理" title="频道管理" />
<x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
```

**Step 2: Run test to verify it passes**

Run: `cd app && php artisan test tests/Feature/Admin/AdminDevtoolsTest.php`
Expected: PASS

### Task 3: 做相关回归验证

**Files:**
- Test: `app/tests/Feature/Admin/AdminSiteSettingsTest.php`

**Step 1: Run related tests**

Run: `cd app && php artisan test tests/Feature/Admin/AdminDevtoolsTest.php tests/Feature/Admin/AdminSiteSettingsTest.php`
Expected: PASS

**Step 2: Review for regressions**

检查点：
- 没有新增自定义样式或重复组件
- 页面仍可正常访问
- 图标按钮具有 tooltip 与无障碍标签
