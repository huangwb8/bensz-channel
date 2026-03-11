<div align="center">

# 🌐 Bensz Channel

**Modern Web Community Platform - Integrated Solution for Channel Management, Real-time Interaction, and Content Curation**

[![Version](https://img.shields.io/badge/version-1.32.0-blue.svg)](https://github.com/huangwb8/bensz-channel/releases)
[![Platform](https://img.shields.io/badge/platform-Docker-lightgrey.svg)](https://www.docker.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[中文](README.md) | [English](README_EN.md)

</div>

---

## ✨ Introduction

Bensz Channel is a web community platform built with **Laravel + Better Auth + PostgreSQL + Redis + Docker**, featuring a three-column layout design (left channel navigation, center content stream, right community info), ideal for team collaboration, knowledge sharing, and content curation.

**🌟 Core Highlight**: Through custom Agent Skills, supports **remote management of channels, articles, comments, and users via Claude Code/Codex CLI**, enabling AI-driven community operations.

### Key Features

- 🤖 **AI Tool Integration**: Remote content management via custom Agent Skill for Claude Code/Codex CLI, enabling intelligent community operations
- 🏠 **Channel System**: Create and manage multiple topic channels with built-in "Featured" and "Uncategorized" system channels
- 👥 **User Management**: Complete user registration, login, permission management, and ban system
- 🔐 **Multiple Login Methods**: Email verification code, email password, WeChat/QQ QR code login
- 📝 **Markdown Support**: Articles and comments support Markdown rendering with paste-to-upload images
- 📧 **Subscription Notifications**: SMTP email subscription and RSS feeds
- ⚡ **Static Page Optimization**: Guest access automatically uses pre-built static HTML + Gzip compression
- 🎨 **Admin Dashboard**: Complete management for channels, articles, users, and site settings
- 🚀 **One-Click Deployment**: Docker Compose launches all services with one command

---

<div align="center">

### ⭐ If this project helps you, please give it a Star!

Developing and maintaining this project takes significant time and effort. Your Star is the greatest encouragement and helps more people discover this project.

[![Star History Chart](https://api.star-history.com/svg?repos=huangwb8/bensz-channel&type=Date)](https://star-history.com/#huangwb8/bensz-channel&Date)

</div>

---

## 🚀 Quick Start

### Prerequisites

- Docker 20.10+
- Docker Compose 2.0+

### Using Docker Hub Images (Recommended)

The fastest deployment method using pre-built images, no need to clone the repository:

**1. Create `docker-compose.yml`**

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

**2. Create `config/.env`**

```bash
# ============================================
# Public configurations referenced by Docker Compose
# ============================================

# Port mapping
WEB_PORT=6542
MAILPIT_PORT=8025

# Static site configuration
STATIC_SITE_OUTPUT_DIR=static

# PostgreSQL configuration
DB_HOST=postgres
POSTGRES_DB=bensz_channel
POSTGRES_USER=bensz

# Laravel database connection configuration
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=bensz_channel
DB_USERNAME=bensz

# ============================================
# Secret configurations (sensitive - must modify)
# ============================================

# Laravel application key (generate with command below)
# docker run --rm huangwb8/bensz-channel-web:latest php artisan key:generate --show
APP_KEY=base64:your_generated_app_key_here

# Database password (change to strong password)
DB_PASSWORD=your_secure_db_password_here
POSTGRES_PASSWORD=your_secure_db_password_here

# Better Auth secrets (change to strong random strings)
BETTER_AUTH_SECRET=your_secure_auth_secret_here
BETTER_AUTH_INTERNAL_SECRET=your_secure_internal_secret_here

# Admin password
ADMIN_PASSWORD=your_admin_password_here

# Mail service credentials (optional)
MAIL_USERNAME=
MAIL_PASSWORD=

# AWS credentials (optional)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=

# Third-party login credentials (optional)
WECHAT_CLIENT_ID=
QQ_CLIENT_ID=
```

**3. Start services**

```bash
docker compose up -d
```

**4. View logs**

```bash
docker compose logs -f
```

After startup, visit:
- 🌐 Homepage: `http://localhost:6542`
- 🔑 Login: `http://localhost:6542/login`
- 📬 Mailpit: `http://localhost:8025`

### Build Images Locally

For custom builds, please refer to [Developer Documentation - Docker Image Building](docs/开发者文档.md#docker-镜像构建).

### Default Admin Account

- 📧 Email: `admin@example.com`
- 🔑 Password: `admin123456`
- 🆔 User ID: `0`

## 🤖 AI Tool Integration (Agent Skill)

The core feature of this project is the custom Agent Skill that enables **remote management of channels, articles, comments, and users via Claude Code/Codex CLI**, enabling AI-driven community operations.

### Installation Steps

1. First, install the [Bensz Skills](https://github.com/huangwb8/skills) project and learn how to use the `install-bensz-skills` skill

2. In Claude Code or Codex CLI, enter the following command to install this project's Agent Skills:

```
install-bensz-skills --source https://github.com/huangwb8/bensz-channel/tree/main/skills
```

3. After installation, refer to the [Agent Skill Guide](skills/bensz-channel-devtools/README.md) to configure API keys and start using

### Features

- 📝 **Channel Management**: Create, update, delete channels, control visibility
- 📰 **Article Management**: Publish, edit, delete articles, set pinned and featured status
- 💬 **Comment Management**: View, hide, delete comments
- 👤 **User Management**: View, update user information, manage roles and permissions
- 🔐 **Secure & Controlled**: API key-based authentication, no source code modification

## 📚 Core Features

### Channel Management
- Create, edit, and delete channels
- Control channel visibility in top navigation
- Built-in "Featured" and "Uncategorized" system channels

### User System
- Multiple login methods: email verification code, email password, WeChat/QQ QR code
- Self-service profile maintenance: nickname, email, phone, avatar, bio
- Admin can manage user profiles, roles, and ban status
- Stable user ID (unchanged by profile updates)

### Content Management
- Markdown articles and comments
- Paste-to-upload images
- Article pinning and featured marking
- Auto-generated table of contents (TOC)

### Subscription Features
- SMTP email subscription (all/specific channels)
- Comment @ mentions
- RSS feeds (all/individual channels)

### Performance Optimization
- Guest access automatically uses static HTML
- Gzip compression
- CDN-friendly asset strategy
- Redis caching

## 🛠️ Tech Stack

| Layer | Solution |
|-------|----------|
| Web Application | Laravel 12 + Blade + Tailwind CSS |
| Authentication | Better Auth + Express |
| Database | PostgreSQL 17 |
| Cache | Redis 7 |
| Web Server | Nginx + PHP-FPM |
| Frontend Build | Vite 7 |
| Local Mail | Mailpit |
| Deployment | Docker Compose |

## 📖 Documentation

- 📘 [Developer Documentation](docs/开发者文档.md) - Detailed technical documentation and development guide
- 🔧 [Build Instructions](scripts/BUILD.md) - Detailed Docker image build instructions
- 🔐 [WeChat/QQ Login Configuration](docs/如何让本项目支持微信和QQ扫码登陆.md) - Third-party login configuration tutorial
- 🤖 [Agent Skill Guide](skills/bensz-channel-devtools/README.md) - Remote content management via Claude Code/Codex CLI
- 📝 [Project Introduction Blog](https://blognas.hwb0307.com/linux/docker/7053) - Detailed project introduction and user experience

## 🤝 Contributing

Issues and Pull Requests are welcome!

## 💝 Sponsorship

Developing and maintaining this community platform takes significant time and effort 😓. **Your sponsorship will help me continuously optimize features, quickly respond to issues and bug fixes, develop new Agent Skills and features, and maintain long-term project updates**. If this project helps you, please consider sponsoring my development work! 🙏

<div align="center">

<img src="https://raw.githubusercontent.com/huangwb8/ChineseResearchLaTeX/main/logo/pay-1024x541.jpg" alt="Sponsorship QR Code" width="400"/>

</div>

## 📄 License

[MIT License](LICENSE)

---

<div align="center">

**[⬆ Back to Top](#-bensz-channel)**

Made with ❤️ by [Bensz](https://github.com/huangwb8)

</div>
