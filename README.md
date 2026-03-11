<div align="center">

# 🌐 Bensz Channel

**现代化 Web 社区平台 - 频道管理、实时互动、内容沉淀一体化解决方案**

[![Version](https://img.shields.io/badge/version-1.32.0-blue.svg)](https://github.com/huangwb8/bensz-channel/releases)
[![Platform](https://img.shields.io/badge/platform-Docker-lightgrey.svg)](https://www.docker.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[中文](README.md) | [English](README_EN.md)

</div>

---

## ✨ 项目简介

Bensz Channel 是一个基于 **Laravel + Better Auth + PostgreSQL + Redis + Docker** 构建的 Web 社区平台，采用三栏式布局设计（左侧频道导航、中间内容流、右侧社区信息），适合团队协作、知识分享与内容沉淀。

**🌟 核心亮点**：通过定制的 Agent Skill，支持使用 **Claude Code/Codex CLI 远程管理频道、文章、评论、用户**等内容，实现 AI 驱动的社区运营。

### 核心特性

- 🤖 **AI 工具集成**：通过定制的 Agent Skill 支持 Claude Code/Codex CLI 远程管理内容，实现智能化社区运营
- 🏠 **频道系统**：支持创建、管理多个主题频道，内置"精华"与"未分类"系统频道
- 👥 **用户管理**：完整的用户注册、登录、权限管理与封禁系统
- 🔐 **多种登录方式**：邮箱验证码、邮箱密码、微信/QQ 扫码登录
- 📝 **Markdown 支持**：文章与评论支持 Markdown 渲染，支持粘贴图片自动上传
- 📧 **订阅通知**：SMTP 邮件订阅与 RSS 订阅
- ⚡ **静态页面优化**：游客访问自动使用预构建静态 HTML + Gzip 压缩
- 🎨 **管理后台**：完整的频道、文章、用户、站点设置管理
- 🚀 **一键部署**：Docker Compose 一键启动所有服务

---

<div align="center">

### ⭐ 如果这个项目对你有帮助，请点个 Star 支持一下！

开发和维护这个项目需要大量时间和精力。你的 Star 是对我最大的鼓励，也能帮助更多人发现这个项目。

[![Star History Chart](https://api.star-history.com/svg?repos=huangwb8/bensz-channel&type=Date)](https://star-history.com/#huangwb8/bensz-channel&Date)

</div>

---

## 🚀 快速开始

### 前置要求

- Docker 20.10+
- Docker Compose 2.0+

### 使用 Docker Hub 镜像（推荐）

最快的部署方式，直接使用已构建的镜像，无需克隆仓库：

**1. 创建 `docker-compose.yml`**

```yaml
services:
  channel-web:
    image: huangwb8/bensz-channel-web:latest
    container_name: channel-web
    ports:
      - "${WEB_PORT:-6542}:80"
    env_file:
      - config/.env
    depends_on:
      channel-auth:
        condition: service_healthy
      channel-postgres:
        condition: service_healthy
      channel-redis:
        condition: service_started
      channel-mailpit:
        condition: service_started
    volumes:
      - ./data/web/storage:/var/www/html/storage
      - ./data/web/bootstrap-cache:/var/www/html/bootstrap/cache
      - ./data/web/static:/var/www/html/public/${STATIC_SITE_OUTPUT_DIR:-static}
    healthcheck:
      test: ["CMD-SHELL", "curl -fsS http://127.0.0.1/up || exit 1"]
      interval: 10s
      timeout: 5s
      retries: 12

  channel-worker:
    image: huangwb8/bensz-channel-web:latest
    container_name: channel-worker
    entrypoint:
      - php
      - /var/www/html/artisan
      - queue:work
      - --queue=static-builds,default
      - --sleep=1
      - --tries=3
      - --timeout=900
    env_file:
      - config/.env
    depends_on:
      channel-web:
        condition: service_healthy
      channel-postgres:
        condition: service_healthy
      channel-redis:
        condition: service_started
    volumes:
      - ./data/web/storage:/var/www/html/storage
      - ./data/web/bootstrap-cache:/var/www/html/bootstrap/cache
      - ./data/web/static:/var/www/html/public/${STATIC_SITE_OUTPUT_DIR:-static}
    restart: unless-stopped

  channel-auth:
    image: huangwb8/bensz-channel-auth:latest
    container_name: channel-auth
    env_file:
      - config/.env
    depends_on:
      channel-postgres:
        condition: service_healthy
      channel-mailpit:
        condition: service_started
    healthcheck:
      test: ["CMD-SHELL", "node -e \"fetch('http://127.0.0.1:3001/health').then(r => process.exit(r.ok ? 0 : 1)).catch(() => process.exit(1))\""]
      interval: 10s
      timeout: 5s
      retries: 12
      start_period: 120s

  channel-postgres:
    image: postgres:17-alpine
    container_name: channel-postgres
    env_file:
      - config/.env
    volumes:
      - ./data/postgres:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER:-bensz} -d ${POSTGRES_DB:-bensz_channel}"]
      interval: 5s
      timeout: 5s
      retries: 12

  channel-redis:
    image: redis:7-alpine
    container_name: channel-redis
    command: redis-server --appendonly yes --dir /data
    volumes:
      - ./data/redis:/data

  channel-mailpit:
    image: axllent/mailpit:latest
    container_name: channel-mailpit
    command: ["--database", "/data/mailpit.db"]
    ports:
      - "${MAILPIT_PORT:-8025}:8025"
    volumes:
      - ./data/mailpit:/data
```

**2. 创建 `config/.env`**

```bash
# ============================================
# Docker Compose 直接引用的公开配置
# ============================================

# 端口映射
WEB_PORT=6542
MAILPIT_PORT=8025

# 静态站点配置
STATIC_SITE_OUTPUT_DIR=static

# PostgreSQL 配置
DB_HOST=postgres
POSTGRES_DB=bensz_channel
POSTGRES_USER=bensz

# Laravel 数据库连接配置
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=bensz_channel
DB_USERNAME=bensz

# ============================================
# 密钥类配置（敏感信息 - 必须修改）
# ============================================

# Laravel 应用密钥（运行下面命令生成）
# docker run --rm huangwb8/bensz-channel-web:latest php artisan key:generate --show
APP_KEY=base64:your_generated_app_key_here

# 数据库密码（修改为强密码）
DB_PASSWORD=your_secure_db_password_here
POSTGRES_PASSWORD=your_secure_db_password_here

# Better Auth 密钥（修改为强随机字符串）
BETTER_AUTH_SECRET=your_secure_auth_secret_here
BETTER_AUTH_INTERNAL_SECRET=your_secure_internal_secret_here

# 管理员密码
ADMIN_PASSWORD=your_admin_password_here

# 邮件服务凭证（可选）
MAIL_USERNAME=
MAIL_PASSWORD=

# AWS 凭证（可选）
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=

# 第三方登录凭证（可选）
WECHAT_CLIENT_ID=
QQ_CLIENT_ID=
```

**3. 启动服务**

```bash
docker compose up -d
```

**4. 查看日志**

```bash
docker compose logs -f
```

启动后访问：
- 🌐 站点首页：`http://localhost:6542`
- 🔑 登录页：`http://localhost:6542/login`
- 📬 Mailpit：`http://localhost:8025`

### 本地构建镜像

如果需要自定义构建，请参考 [开发者文档 - Docker 镜像构建](docs/开发者文档.md#docker-镜像构建)。

### 默认管理员账号

- 📧 邮箱：`admin@example.com`
- 🔑 密码：`admin123456`
- 🆔 用户ID：`0`

## 🤖 AI 工具集成（Agent Skill）

本项目的核心特色是通过定制的 Agent Skill，支持使用 **Claude Code/Codex CLI 远程管理频道、文章、评论、用户**等内容，实现 AI 驱动的社区运营。

### 安装步骤

1. 首先安装 [Bensz Skills](https://github.com/huangwb8/skills) 项目，掌握 `install-bensz-skills` 这个 skill 的使用方法

2. 在 Claude Code 或 Codex CLI 中输入以下命令安装本项目的 Agent Skill：

```
install-bensz-skills --source https://github.com/huangwb8/bensz-channel/tree/main/skills
```

3. 安装完成后，参考 [Agent Skill 使用指南](skills/bensz-channel-devtools/README.md) 配置 API 密钥并开始使用

### 功能特性

- 📝 **频道管理**：创建、更新、删除频道，控制显示状态
- 📰 **文章管理**：发布、编辑、删除文章，设置置顶和精华
- 💬 **评论管理**：查看、隐藏、删除评论
- 👤 **用户管理**：查看、更新用户信息，管理角色权限
- 🔐 **安全可控**：基于 API 密钥认证，不修改软件源代码

## 📚 核心功能

### 频道管理
- 创建、编辑、删除频道
- 频道顶栏显示控制
- 内置"精华"与"未分类"系统频道

### 用户系统
- 多种登录方式：邮箱验证码、邮箱密码、微信/QQ 扫码
- 用户资料自助维护：昵称、邮箱、手机号、头像、简介
- 管理员可管理用户资料、角色、封禁状态
- 稳定用户 ID（不随资料变更而变化）

### 内容管理
- Markdown 文章与评论
- 粘贴图片自动上传
- 文章置顶与精华标记
- 自动生成目录 TOC

### 订阅功能
- SMTP 邮件订阅（全部/指定版块）
- 评论 @ 提醒
- RSS 订阅（全部/单个版块）

### 性能优化
- 游客访问自动使用静态 HTML
- Gzip 压缩
- CDN 友好资源策略
- Redis 缓存

## 🛠️ 技术栈

| 层级 | 方案 |
|------|------|
| Web 应用 | Laravel 12 + Blade + Tailwind CSS |
| 认证服务 | Better Auth + Express |
| 数据库 | PostgreSQL 17 |
| 缓存 | Redis 7 |
| Web 服务 | Nginx + PHP-FPM |
| 前端构建 | Vite 7 |
| 本地邮件 | Mailpit |
| 部署 | Docker Compose |

## 📖 文档

- 📘 [开发者文档](docs/开发者文档.md) - 详细的技术文档与开发指南
- 🔧 [构建说明](scripts/BUILD.md) - Docker 镜像构建详细说明
- 🔐 [微信/QQ 登录配置](docs/如何让本项目支持微信和QQ扫码登陆.md) - 第三方登录配置教程
- 🤖 [Agent Skill 使用指南](skills/bensz-channel-devtools/README.md) - 通过 Claude Code/Codex CLI 远程管理内容
- 📝 [项目介绍博客](https://blognas.hwb0307.com/linux/docker/7053) - 详细的项目介绍与使用体验

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 💝 赞助

开发和维护这个社区平台需要大量时间和精力 😓，**您的赞助将帮助我持续优化功能、快速响应问题和 Bug 修复、开发新的 Agent Skills 和特性，以及保持项目的长期维护和更新**。如果本项目对您有帮助，欢迎赞助支持我的开发工作！🙏

<div align="center">

<img src="https://raw.githubusercontent.com/huangwb8/ChineseResearchLaTeX/main/logo/pay-1024x541.jpg" alt="赞助码" width="400"/>

</div>

## 📄 许可证

[MIT License](LICENSE)

---

<div align="center">

**[⬆ 回到顶部](#-bensz-channel)**

Made with ❤️ by [Bensz](https://github.com/huangwb8)

</div>
