# bensz-channel-devtools

通过 Vibe Coding 工具（Claude Code、Codex CLI 等）远程管理 bensz-channel 社区平台内容。

## 快速上手

### 1. 获取 API 密钥

登录 bensz-channel → 管理员菜单 → **DevTools 远程管理** → 生成 API 密钥（仅显示一次，请立即保存）。

### 2. 配置环境变量

```bash
# 方式一：导出环境变量
export BENSZ_CHANNEL_URL=http://your-server:6542
export BENSZ_CHANNEL_KEY=bdc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# 方式二：写入 .env 文件（项目根目录或 ~/.bensz-channel.env）
BENSZ_CHANNEL_URL=http://your-server:6542
BENSZ_CHANNEL_KEY=bdc_xxxxxxxx...
```

### 3. 验证连接

```bash
cd skills/bensz-channel-devtools
python3 scripts/env_check.py       # 检查环境变量
python3 scripts/client.py ping     # 检查服务器连通性
python3 scripts/client.py doctor   # 完整诊断
```

## 常用命令

```bash
# 频道管理
python3 scripts/client.py channels list
python3 scripts/client.py channels create --name 公告 --icon 📢 --accent-color '#3b82f6'
python3 scripts/client.py channels update --id 1 --name 新名称
python3 scripts/client.py channels delete --id 1

# 文章管理
python3 scripts/client.py articles list
python3 scripts/client.py articles list --channel-id 1 --published true
python3 scripts/client.py articles show --id 42
python3 scripts/client.py articles create \
  --channel-id 1 \
  --title "Hello World" \
  --body "这是正文内容" \
  --published
python3 scripts/client.py articles update --id 42 --title "新标题"
python3 scripts/client.py articles delete --id 42

# 评论管理
python3 scripts/client.py comments list
python3 scripts/client.py comments list --article-id 42 --visible false
python3 scripts/client.py comments update --id 10 --visible false   # 隐藏评论
python3 scripts/client.py comments delete --id 10

# 用户管理
python3 scripts/client.py users list
python3 scripts/client.py users list --q alice --role member
python3 scripts/client.py users update --id 5 --role admin
python3 scripts/client.py users update --id 5 --name "新昵称" --bio "简介"
```

## 安全边界

- 只允许调用 `/api/vibe/*` 端点
- 不修改软件源代码，只操作数据库内容
- API 密钥仅在管理界面生成时显示一次，之后不可恢复（只能重新生成）
- 撤销密钥后立即失效

## 故障排查

| 错误 | 原因 | 解决 |
|------|------|------|
| `Missing BENSZ_CHANNEL_KEY` | 未配置密钥 | 设置环境变量 |
| `HTTP 401 invalid_or_revoked_api_key` | 密钥无效或已撤销 | 重新生成密钥 |
| `connect failed` | 服务器不可达 | 检查 URL 和服务器状态 |
| `HTTP 422` | 参数验证失败 | 检查 JSON 响应中的错误信息 |
