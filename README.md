# bensz-channel

一个类似 **QQ 频道** 的 Web 版在线社区/论坛平台。用户通过浏览器访问，支持实时交流、频道管理、内容分享等核心功能。

## 核心特性

- **频道系统** - 支持创建、管理多个主题频道
- **实时通信** - 频道内实时消息收发与通知
- **用户系统** - 注册、登录、权限管理
- **内容管理** - 帖子发布、评论互动、资源分享
- **社交功能** - 关注、私信、@提醒等
- **模块化设计** - 清晰的架构分层，易于扩展
- **现代化技术栈** - 采用主流前后端技术

## 快速开始

### 环境要求

- Node.js >= 18.x
- pnpm / npm / yarn
- 数据库（根据技术选型确定）

### 安装

```bash
# 克隆项目
git clone https://github.com/bensz/bensz-channel.git
cd bensz-channel

# 安装依赖（根据实际包管理器选择）
pnpm install
```

### 开发

```bash
# 启动开发服务器
pnpm dev
```

## 目录结构

```
bensz-channel/
├── frontend/          # 前端代码
├── backend/           # 后端代码
├── docs/              # 项目文档
├── AGENTS.md          # 跨平台 AI 项目指令
├── CLAUDE.md          # Claude Code 项目指令
└── README.md          # 项目说明
```

## AI 辅助开发

本项目配置了 AI 辅助开发支持，可以使用以下工具进行智能开发：

### Claude Code

使用 `CLAUDE.md` 作为项目指令。

```bash
# 在项目目录启动 Claude Code
claude

# Claude Code 会自动读取 CLAUDE.md 理解项目上下文
```

### OpenAI Codex CLI

使用 `AGENTS.md` 作为项目指令。

```bash
# 在项目目录启动 Codex CLI
codex

# Codex 会自动读取 AGENTS.md 理解项目上下文
```

### AI 开发最佳实践

1. **新功能开发**：描述需求，AI 会按照项目工作流进行开发
2. **代码审查**：请求 AI 审查代码，它会按照工程原则给出建议
3. **文档更新**：AI 会自动同步更新相关文档
4. **问题排查**：描述问题现象，AI 会分析并给出解决方案
5. **变更记录**：**重要** - 凡是项目的更新，都要统一在 `CHANGELOG.md` 文件里记录

## 技术选型（待定）

| 层级 | 候选技术 |
|-----|---------|
| 前端框架 | React / Vue / Next.js / Nuxt |
| 后端框架 | Node.js / Go / Rust |
| 数据库 | PostgreSQL / MySQL / MongoDB |
| 实时通信 | WebSocket / Server-Sent Events |
| 缓存 | Redis |

## 贡献

欢迎提交 Issue 和 Pull Request。

## 许可证

MIT License
