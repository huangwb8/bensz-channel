<div align="center">

# 🌐 Bensz Channel

**A Modern Web Community Platform Inspired by QQ Channel**

[![Version](https://img.shields.io/badge/version-1.32.0-blue.svg)](https://github.com/huangwb8/bensz-channel/releases)
[![Platform](https://img.shields.io/badge/platform-Docker-lightgrey.svg)](https://www.docker.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[中文](README.md) | [English](README_EN.md)

</div>

---

## ✨ Introduction

Bensz Channel is a web community platform built with **Laravel + Better Auth + PostgreSQL + Redis + Docker**, featuring a UI layout inspired by **QQ Channel**: left sidebar for channel navigation, center content stream, and right sidebar for user and community information.

### Key Features

- 🏠 **Channel System**: Create and manage multiple topic channels with built-in "Featured" and "Uncategorized" system channels
- 👥 **User Management**: Complete user registration, login, permission management, and ban system
- 🔐 **Multiple Login Methods**: Email verification code, email password, WeChat/QQ QR code login
- 📝 **Markdown Support**: Articles and comments support Markdown rendering with paste-to-upload images
- 📧 **Subscription Notifications**: SMTP email subscription and RSS feeds
- ⚡ **Static Page Optimization**: Guest access automatically uses pre-built static HTML + Gzip compression
- 🎨 **Admin Dashboard**: Complete management for channels, articles, users, and site settings
- 🚀 **One-Click Deployment**: Docker Compose launches all services with one command

## 🚀 Quick Start

### Prerequisites

- Docker 20.10+
- Docker Compose 2.0+

### Using Docker Hub Images (Recommended)

The fastest deployment method using pre-built images:

```bash
# 1. Clone the repository
git clone https://github.com/huangwb8/bensz-channel.git
cd bensz-channel

# 2. Copy production environment configuration example
cp self/remote.env config/.env
cp self/docker-compose.yml docker-compose.yml

# 3. Edit configuration file (optional)
# Modify domain, port, and other settings in config/.env

# 4. Start services
./scripts/compose.sh up -d

# 5. View logs
./scripts/compose.sh logs -f
```

After startup, visit:
- 🌐 Homepage: `http://localhost:6542`
- 🔑 Login: `http://localhost:6542/login`
- 📬 Mailpit: `http://localhost:8025`

### Build Images Locally

If you need custom builds:

```bash
# 1. Clone the repository
git clone https://github.com/huangwb8/bensz-channel.git
cd bensz-channel

# 2. Build images (using local cache, fast and offline-capable)
./scripts/build.sh

# 3. Start services
./scripts/compose.sh up -d
```

### Default Admin Account

- 📧 Email: `admin@example.com`
- 🔑 Password: `admin123456`
- 🆔 User ID: `0`

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

## 🤝 Contributing

Issues and Pull Requests are welcome!

## 📄 License

[MIT License](LICENSE)

---

<div align="center">

**[⬆ Back to Top](#-bensz-channel)**

Made with ❤️ by [Bensz](https://github.com/huangwb8)

</div>
