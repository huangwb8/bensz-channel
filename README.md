<div align="center">

# 🌐 Bensz Channel

**类似 QQ 频道的现代化 Web 社区平台**

[![Version](https://img.shields.io/badge/version-1.32.0-blue.svg)](https://github.com/huangwb8/bensz-channel/releases)
[![Platform](https://img.shields.io/badge/platform-Docker-lightgrey.svg)](https://www.docker.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[中文](README.md) | [English](README_EN.md)

</div>

---

## ✨ 项目简介

Bensz Channel 是一个基于 **Laravel + Better Auth + PostgreSQL + Redis + Docker** 构建的 Web 社区平台，交互形态参考 **QQ 频道**：左侧频道导航，中间内容流，右侧用户与社区信息。

### 核心特性

- 🏠 **频道系统**：支持创建、管理多个主题频道，内置"精华"与"未分类"系统频道
- 👥 **用户管理**：完整的用户注册、登录、权限管理与封禁系统
- 🔐 **多种登录方式**：邮箱验证码、邮箱密码、微信/QQ 扫码登录
- 📝 **Markdown 支持**：文章与评论支持 Markdown 渲染，支持粘贴图片自动上传
- 📧 **订阅通知**：SMTP 邮件订阅与 RSS 订阅
- ⚡ **静态页面优化**：游客访问自动使用预构建静态 HTML + Gzip 压缩
- 🎨 **管理后台**：完整的频道、文章、用户、站点设置管理
- 🚀 **一键部署**：Docker Compose 一键启动所有服务

## 🚀 快速开始

### 前置要求

- Docker 20.10+
- Docker Compose 2.0+

### 使用 Docker Hub 镜像（推荐）

最快的部署方式，直接使用已构建的镜像：

```bash
# 1. 克隆仓库
git clone https://github.com/huangwb8/bensz-channel.git
cd bensz-channel

# 2. 复制生产环境配置示例
cp self/remote.env config/.env
cp self/docker-compose.yml docker-compose.yml

# 3. 编辑配置文件（可选）
# 修改 config/.env 中的域名、端口等配置

# 4. 启动服务
./scripts/compose.sh up -d

# 5. 查看日志
./scripts/compose.sh logs -f
```

启动后访问：
- 🌐 站点首页：`http://localhost:6542`
- 🔑 登录页：`http://localhost:6542/login`
- 📬 Mailpit：`http://localhost:8025`

### 本地构建镜像

如果需要自定义构建：

```bash
# 1. 克隆仓库
git clone https://github.com/huangwb8/bensz-channel.git
cd bensz-channel

# 2. 构建镜像（使用本地缓存，快速且支持离线）
./scripts/build.sh

# 3. 启动服务
./scripts/compose.sh up -d
```

### 默认管理员账号

- 📧 邮箱：`admin@example.com`
- 🔑 密码：`admin123456`
- 🆔 用户ID：`0`

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

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📄 许可证

[MIT License](LICENSE)

---

<div align="center">

**[⬆ 回到顶部](#-bensz-channel)**

Made with ❤️ by [Bensz](https://github.com/huangwb8)

</div>
