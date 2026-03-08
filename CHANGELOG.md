# Changelog

**重要**：本文件是项目变更的**唯一正式记录**。凡是项目的更新，都要统一在本文件里记录。这是项目管理的强制性要求。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.1.0/)。

## [Unreleased]

## [1.2.0] - 2026-03-08

### Added（新增）

- 新增了 `auth-service/` Better Auth 自托管认证服务：用于承载邮箱/手机号 OTP 的发送与验证，并与 Laravel 主应用解耦
- 新增了 `app/tests/Feature/Auth/BetterAuthLoginTest.php`：用于覆盖 Better Auth 手机登录、服务异常与验证失败等关键回归场景
- 新增了 Better Auth 相关环境变量与 Docker 服务配置：用于在 `docker compose up --build -d` 后自动完成认证服务迁移与启动

### Changed（变更）

- 重构了 Laravel 登录主链路：邮箱/手机号 OTP 由 Better Auth 内部 API 驱动，Laravel 继续负责本地用户同步与 Session 建立
- 更新了 `README.md`：补充 Better Auth 架构、Docker 审查登录方式与本地双服务启动说明
- 更新了 `docker-compose.yml` 与相关 Dockerfile：新增 `auth` 服务、健康检查与安全长度的 Better Auth secrets
- 优化了登录页文案与视觉标识：明确 Better Auth 驱动的自托管登录，并保留扫码演示入口
- 优化了 Node 依赖托管脚本：避免 `npm install` 后迁移 `node_modules` 时因目录非空导致失败

### Fixed（修复）

- 修复了 Docker 构建 `auth-service` 时把本地 `node_modules` 带入 context 导致镜像构建失败的问题
- 修复了 Better Auth 接入后测试仍依赖旧本地验证码表的问题：现在测试通过 HTTP fake 锁定新认证边界
- 修复了 Docker 重部署后的真实邮箱验证码登录链路：已验证从 Laravel 登录页发码、Better Auth 验证到本地 Session 建立全部成功

### Changed（变更）

- **彻底重构登录页面为极简主义设计**：
  - 移除冗余的 Hero 区域、统计卡片和功能特性展示
  - 采用单列居中布局，最大宽度 480px，大量留白
  - 简化表单结构，保持验证码登录和扫码登录功能完整
  - 优化视觉层次：品牌标识 → 主标题 → 表单操作 → 扫码备选
  - 更新 CSS 样式：更精致的按钮和输入框设计，微妙交互动效
  - 保持所有现有功能（验证码发送/验证、微信/QQ 扫码登录）
- 优化了布局数据注入方式：仅对 `layouts.app` 注入最小必需站点数据，避免对所有视图重复执行社区聚合查询
- 更新了 `docker-compose.yml` 的认证预览配置：关闭 Docker 审查环境中的验证码预览展示，避免生产式部署默认泄露一次性验证码

### Fixed（修复）

- 修复了登录/注册页与扫码授权页点击即报错的问题：此前认证页继承主布局但未稳定获得 `pageTitle` 等布局必需变量，导致 Docker 部署后访问 `/login` 返回 500
- 修复了本地功能测试无法启动的问题：为测试进程显式设置 `APP_BASE_PATH`，避免第三方包托管目录导致 Laravel 测试基路径推断错误
- 修复了 Docker 环境下登录后仍显示游客态的问题：统一会话 Cookie 名为 `bensz_channel_session`，并让 Nginx 基于该 Cookie 正确回退到动态应用
- 修复了 Docker 环境下已登录请求返回 `418` 的问题：为 Nginx 增加 `error_page 418 = @dynamic` 回退规则，确保首页与频道页在登录后由 Laravel 正常渲染
- 修复了登录页直接暴露测试账号与密码的问题：移除公开凭据展示，并保留 README 作为审查阶段的受控说明入口
- 修复了验证码登录失败后的表单回填体验：统一兼容发送验证码与验证验证码两个阶段的旧输入回显，减少重复输入

### Security（安全）

- 收紧了一次性验证码预览条件：仅在 `local`/`testing` 环境且显式启用时才展示预览码，避免 Docker 审查环境意外暴露动态登录凭据

## [1.1.2] - 2026-03-08

### Changed（变更）

- 优化了 `app/vendor` 的托管方式：将 Composer 依赖目录迁移到 `/Volumes/2T01/Test/bensz-channel/app/vendor`，并通过符号链接回连项目目录
- 更新了 `app/composer.json`：增加 `pre-autoload-dump` 钩子与 Composer 缓存目录配置，确保后续 `composer install` 持续符合统一托管规则

### Fixed（修复）

- 修复了 PHP 第三方包仍落在项目目录内的问题：此前 `app/vendor` 为实体目录，不符合 `AGENTS.md` 的统一托管规则

## [1.1.1] - 2026-03-08

### Changed（变更）

- 优化了 `app/node_modules` 的托管方式：将依赖目录迁移到 `/Volumes/2T01/Test/bensz-channel/app/node_modules`，并通过符号链接回连项目目录
- 新增了 `app/.npmrc`：将 npm 缓存统一托管到 `/Volumes/2T01/Test/bensz-channel/npm-cache`，避免后续安装再次写回项目内
- 更新了 `app/package.json`：增加 `postinstall` 与 `deps:sync` 钩子，确保 `npm install` 后自动恢复统一托管的依赖目录

### Fixed（修复）

- 修复了前端第三方包落在项目目录内的问题：此前 `app/node_modules` 为实体目录，不符合 `AGENTS.md` 的统一托管规则

## [1.1.0] - 2026-03-08

### Added（新增）

- 新增了基于 `Laravel + PostgreSQL + Redis` 的社区应用骨架：用于承载频道、文章、评论与登录流程
- 新增了 `docker-compose.yml` 与 `docker/` 目录：用于一键部署 Web、数据库、缓存与 Mailpit
- 新增了邮箱验证码、手机号验证码、微信/QQ 演示扫码三种登录方式：用于覆盖成员注册/登录入口
- 新增了游客静态页面构建机制：生成压缩后的静态 HTML 与 `.gz` 文件供 Nginx 优先分发
- 新增了 `config.yaml`：作为项目版本号的单一事实来源

### Changed（变更）

- 更新了 `README.md`：补充真实技术栈、部署命令、默认账号、静态访问说明与目录结构
- 更新了根目录 `.gitignore`：补充 Laravel 依赖、构建产物与缓存文件的忽略规则

### Fixed（修复）

- 修复了仓库只有文档、没有可运行应用的问题：现在支持 Docker 重新部署后直接审查

### Changed（变更）

- 优化 `AGENTS.md`：移除冗余的序号前缀（如 `### 1.` → `###`），遵循文档自身的设计哲学

### Added（新增）

- 新增 `AGENTS.md` 依赖管理章节：第三方包统一托管在 `/Volumes/2T01/Test/bensz-channel`

## [1.0.0] - 2026-03-08

### Added（新增）

- 初始化 AI 项目指令文件
- 生成 `CLAUDE.md`（Claude Code 项目指令）
- 生成 `AGENTS.md`（OpenAI Codex CLI 项目指令）
- 配置项目工程原则和工作流
- 生成 `.gitignore`（包含安全和项目特定规则）
- 生成 `README.md`（项目说明文档）

### Changed（变更）

- 更新 `AGENTS.md`：明确项目目标为类似 QQ 频道的 Web 版社区平台
- 更新 `README.md`：完善项目特性、技术选型和目录结构说明

### Changed（变更）

### Fixed（修复）

---

## 记录规范（强制性要求）

### 必须记录的变更类型

每次修改以下内容时，**必须**在本文件追加记录：

1. **项目指令文件变更**
   - CLAUDE.md 的任何修改
   - AGENTS.md 的任何修改

2. **项目结构变更**
   - 新增/删除/重命名目录
   - 新增/删除/重命名关键文件（如核心源码文件、配置文件）

3. **工作流变更**
   - 核心工作流程的调整
   - 开发流程的修改

4. **工程原则变更**
   - 新增工程原则
   - 修改或删除现有工程原则

5. **重要配置变更**
   - 影响项目行为的配置文件修改
   - 依赖关系的重大变更

### 记录格式

```markdown
## [版本号] - YYYY-MM-DD

### Added（新增）
- 新增了 XXX 功能/章节：用途是 YYY

### Changed（变更）
- 修改了 XXX 章节：原因是 YYY，具体变更内容是 ZZZ
- 修改了项目目录结构：将 ABC 目录移至 DEF 位置

### Fixed（修复）
- 修复了 XXX 问题：表现是 YYY，修复方式是 ZZZ

### Deprecated（即将弃用）
- XXX 功能将在下一版本移除：原因是 YYY

### Removed（已移除）
- 移除了 XXX 功能：原因是 YYY

### Security（安全）
- 修复了 XXX 安全漏洞：影响是 YYY
```

### 记录时机

- **修改前**：先在 `[Unreleased]` 部分草拟变更内容
- **修改后**：完善变更描述，添加具体细节和影响范围
- **发布时**：将 `[Unreleased]` 内容移至具体版本号下

### 版本号规则

遵循语义化版本（Semantic Versioning）：

- **主版本号（Major）**：重大架构变更、不兼容的 API 修改
- **次版本号（Minor）**：新增功能或章节，向后兼容
- **修订号（Patch）**：修复问题或微调，向后兼容

### 变更类型说明

| 类型 | 说明 | 示例 |
|------|------|------|
| Added | 新增的功能或章节 | "新增了 `## 变更记录规范` 章节" |
| Changed | 对现有功能或内容的变更 | "修改了 `## 工程原则` 章节，增加了早期返回原则" |
| Deprecated | 即将移除的功能（警告） | "旧的目录结构将在下个版本重构" |
| Removed | 已移除的功能 | "移除了已废弃的 `## 代码审查` 章节" |
| Fixed | 修复的问题 | "修复了模板中目录树生成的 bug" |
| Security | 安全相关的修复 | "修复了依赖包的安全漏洞" |

### 质量标准

每条记录应该：
- **清晰具体**：说明改了什么、为什么改
- **可追溯**：包含足够的上下文信息
- **格式统一**：遵循上述模板
- **及时更新**：修改后立即记录，不要拖延
