---
name: bensz-channel-devtools
description: bensz-channel 社区平台 DevTools：通过 API 密钥让 Vibe Coding 工具（Claude Code、Codex 等）远程管理频道、文章、评论和用户，操作数据库层面配置，不修改软件源代码。
metadata:
  author: Bensz Conan
  short-description: bensz-channel 远程管理桥梁（DevTools API）
  keywords:
    - bensz-channel
    - devtools
    - 远程配置
    - channels
    - articles
    - comments
    - users
  category: 运维支持
  platform: Claude Code | OpenAI Codex | Cursor
---

# bensz-channel-devtools

## 目标

把"人类的管理意图"稳定翻译为对 `bensz-channel` **DevTools API** 的一组受限操作：

- 频道：列表 / 新增 / 修改 / 删除
- 文章：列表 / 查看 / 发布 / 修改 / 删除
- 评论：列表 / 修改可见性 / 删除
- 用户：列表 / 修改资料和角色

## 安全边界（强制）

- 只调用 `{BENSZ_CHANNEL_URL}/api/vibe/*`
- 不修改软件源代码，只操作数据（数据库层面）
- 不输出完整 Key；日志中必须脱敏（仅显示前缀）
- 所有变更类请求（POST/PUT/DELETE/PATCH）默认使用 `connect → 执行 → disconnect` 闭环
- 若服务端 heartbeat 返回 `terminate: true`：立刻停止操作并 disconnect

## 环境变量

优先从环境变量读取：

- `BENSZ_CHANNEL_URL`：默认 `http://localhost:6542`
- `BENSZ_CHANNEL_KEY`：长度需 ≥ 20

兼容别名：

- URL：`bensz_channel_url`、`bdc_url`
- KEY：`bensz_channel_key`、`bdc_key`

在 `~/.bensz-channel.env` 或项目根目录 `.env` 中设置。

## 首次使用流程

1. 登录 bensz-channel 管理界面，进入 **管理员 → DevTools 远程管理**
2. 生成一个 API 密钥并复制（仅显示一次）
3. 设置环境变量：
   ```bash
   export BENSZ_CHANNEL_URL=http://your-server:6542
   export BENSZ_CHANNEL_KEY=bdc_xxxxxxxx...
   ```
4. 验证连接：
   ```bash
   python3 scripts/env_check.py
   python3 scripts/client.py ping
   python3 scripts/client.py doctor
   ```

## 标准工作流

1. **环境检查**（不泄露 Key）
   ```bash
   python3 scripts/env_check.py
   ```

2. **健康检查**
   ```bash
   python3 scripts/client.py ping
   python3 scripts/client.py doctor
   ```

3. **执行操作**（按需选择子命令）

## 常见任务映射

| 意图 | 命令 |
|------|------|
| 查看所有频道 | `channels list` |
| 新增频道 | `channels create --name 公告 --icon 📢 --accent-color #3b82f6` |
| 修改频道 | `channels update --id 1 --name 新名称` |
| 查看文章列表 | `articles list` |
| 发布文章 | `articles create --channel-id 1 --title 标题 --body 正文` |
| 隐藏评论 | `comments update --id 42 --visible false` |
| 删除评论 | `comments delete --id 42` |
| 查看用户 | `users list` |
| 修改用户角色 | `users update --id 1 --role admin` |

## API 端点速查

所有端点 Base URL：`{BENSZ_CHANNEL_URL}/api/vibe`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/ping` | 健康检查（无需鉴权） |
| POST | `/connect` | 建立连接 |
| POST | `/heartbeat` | 发送心跳 |
| POST | `/disconnect` | 断开连接 |
| GET | `/channels` | 频道列表 |
| POST | `/channels` | 创建频道 |
| PUT | `/channels/{id}` | 更新频道 |
| DELETE | `/channels/{id}` | 删除频道 |
| GET | `/articles` | 文章列表（支持 channel_id/published 过滤） |
| GET | `/articles/{id}` | 文章详情 |
| POST | `/articles` | 创建文章 |
| PUT | `/articles/{id}` | 更新文章 |
| DELETE | `/articles/{id}` | 删除文章 |
| GET | `/comments` | 评论列表（支持 article_id 过滤） |
| PATCH | `/comments/{id}` | 更新评论（如修改可见性） |
| DELETE | `/comments/{id}` | 删除评论 |
| GET | `/users` | 用户列表（支持 q/role 过滤） |
| PUT | `/users/{id}` | 更新用户 |

认证方式：请求头 `X-Devtools-Key: <你的密钥>`
