# Clipboard Image Upload Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 让管理员写文章和登录用户发评论时，都能直接通过 `Ctrl+V` 粘贴图片并自动上传、插入 Markdown，同时保证图片文件被 Docker 持久化目录托管，方便整站备份。

**Architecture:** 复用现有 Laravel `public` 磁盘与 `storage:link` 机制，把粘贴图片统一存到 `storage/app/public/media/{article|comment}/YYYY/MM`，并通过前端粘贴监听把返回的 Markdown 图片链接插回对应 `textarea`。图片链接使用 `/storage/...` 相对路径，避免绑定部署域名；运行时文件继续由 `docker-compose.yml` 现有 `./data/web/storage` 挂载托管。

**Tech Stack:** Laravel 12、Blade、Vite、原生浏览器 Clipboard API、PHPUnit、Docker Compose

---

### Task 1: 锁定上传与界面回归

**Files:**
- Create: `app/tests/Feature/Uploads/ImageUploadTest.php`
- Modify: `app/tests/Feature/Comments/CommentPostingTest.php`
- Modify: `app/tests/Feature/Admin/AdminArticleManagementTest.php`

**Step 1: 锁定上传接口行为**
- 验证登录用户可以上传图片并获得 `/storage/media/...` Markdown 链接。
- 验证游客不可上传，非图片文件会被拒绝。

**Step 2: 锁定文章/评论界面提示**
- 验证评论区与后台文章表单都渲染“Ctrl+V 粘贴图片自动上传”的稳定提示和上传端点数据属性。

### Task 2: 实现后端上传链路

**Files:**
- Create: `app/app/Http/Controllers/ImageUploadController.php`
- Modify: `app/routes/web.php`

**Step 1: 增加鉴权上传端点**
- 新增仅登录用户可访问的图片上传接口。
- 限制格式、大小和上下文，避免非图片文件混入公开存储。

**Step 2: 统一持久化路径**
- 图片统一写入 `public` 磁盘的 `media/{context}/YYYY/MM` 子目录。
- 返回稳定 JSON：相对 URL、绝对 URL、Markdown 片段、存储路径。

### Task 3: 接入文章编辑器与评论区

**Files:**
- Modify: `app/resources/views/admin/articles/form.blade.php`
- Modify: `app/resources/views/articles/show.blade.php`
- Modify: `app/resources/js/bootstrap.js`
- Modify: `app/resources/js/app.js`
- Modify: `app/resources/views/layouts/app.blade.php`
- Modify: `app/resources/views/layouts/auth.blade.php`
- Modify: `app/resources/css/app.css`

**Step 1: 绑定粘贴上传交互**
- 在两个 `textarea` 上增加数据属性与提示文案。
- 前端监听剪贴板图片，自动上传并把 Markdown 插回光标位置。

**Step 2: 提升可用性与显示稳定性**
- 增加上传状态提示（上传中 / 成功 / 失败）。
- 为 Markdown 图片补齐响应式样式，避免大图撑坏文章或评论布局。

### Task 4: 文档、版本与部署验收

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `app/config.toml`

**Step 1: 更新说明与版本**
- 记录 Ctrl+V 粘贴图片能力、图片持久化目录与备份路径。
- 将项目版本从 `1.28.3` 提升到 `1.29.0`。

**Step 2: 执行验证与 Docker 重部署**
- `cd app && php artisan test --filter=ImageUploadTest`
- `cd app && php artisan test --filter=CommentPostingTest`
- `cd app && php artisan test --filter=AdminArticleManagementTest`
- `cd app && php artisan test`
- `cd app && npm run build`
- `./scripts/build.sh`
- `./scripts/compose.sh up -d`
- `docker compose ps`
