# bensz-channel

一个基于 **Laravel（PHP）+ PostgreSQL + Redis + Docker** 的 Web 社区原型，交互形态参考 **QQ 频道**：左侧频道导航，中间内容流，右侧用户与社区信息。

## 已实现能力

- **频道内容架构**：频道首页、文章详情、右侧社区信息栏
- **管理员唯一角色**：默认仅 1 位管理员，可管理频道、发布文章、发表评论
- **成员登录体系**：邮箱验证码、手机号验证码、微信/QQ 演示扫码登录
- **游客静态访问**：游客优先命中构建好的静态 HTML，并提供 Gzip 压缩版本
- **Markdown 内容流**：文章与评论都支持 Markdown 渲染
- **Docker 一键部署**：应用、PostgreSQL、Redis、Mailpit 一起启动
- **CDN 友好资源策略**：Vite 指纹资源 + `ASSET_URL` 预留 + Nginx 长缓存头

## 技术栈

| 层级 | 方案 |
|------|------|
| Web 应用 | Laravel 12 + Blade + Tailwind CSS |
| 数据库 | PostgreSQL 17 |
| 缓存 | Redis 7 |
| Web 服务 | Nginx + PHP-FPM |
| 前端构建 | Vite 7 |
| 本地邮件 | Mailpit |
| 部署 | Docker Compose |

## 快速部署

```bash
docker compose up --build -d
```

如果本机 `6542` 已被占用，可改用：

```bash
WEB_PORT=16542 docker compose up --build -d
```

启动后访问：

- 站点首页：`http://localhost:6542`
- Mailpit：`http://localhost:8025`

默认管理员：

- 邮箱：`admin@example.com`
- 密码：`admin123456`

示例成员：

- 邮箱：`member@example.com`
- 密码：`member123456`

手机号登录：

- 示例手机号：`13800138000`
- 验证码会在开发/演示环境直接显示在页面提示中，同时写入日志

## 目录结构

```text
bensz-channel/
├── app/                    # Laravel 应用源码
├── docker/                 # Dockerfile、Nginx、Supervisor 配置
├── docker-compose.yml      # 本地/审查部署入口
├── config.yaml             # 项目版本单一事实来源
├── AGENTS.md               # Codex 项目指令
├── CLAUDE.md               # Claude Code 项目指令
├── CHANGELOG.md            # 变更记录
└── README.md               # 项目说明
```

## 认证说明

### 管理员

- 仅 1 位，默认由 Seeder 创建
- 能力：管理频道、发布文章、发布评论

### 登录成员

- 邮箱验证码登录
- 手机验证码登录
- 微信 / QQ 扫码演示登录
- 能力：发表评论

### 游客

- 仅可浏览压缩静态页面
- 不可发文、不可评论

## 静态 HTML 与 CDN

- 游客 GET 请求优先命中 `public/static/` 内的构建结果
- 每次管理员改文章/频道、成员发评论后，都会重新构建静态站点
- 静态 HTML 会额外生成 `.gz` 文件，Nginx 开启 `gzip_static`
- 资源文件采用 Vite 指纹命名，可通过 `ASSET_URL` 接入 CDN

## 本地开发

如果需要在宿主机直接运行：

```bash
cd app
cp .env.example .env
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

- `app/node_modules` 通过符号链接指向 `/Volumes/2T01/Test/bensz-channel/app/node_modules`
- `npm install` 会读取 `app/.npmrc`，将缓存统一写入 `/Volumes/2T01/Test/bensz-channel/npm-cache`
- `npm install` 完成后会自动执行 `app/scripts/ensure-managed-node-modules.sh`，把可能被 npm 重建到项目内的依赖目录重新迁回托管路径

## 后续可扩展点

- 接入真实微信 / QQ OAuth 扫码平台
- 增加帖子列表分页、全文搜索、消息通知
- 为频道补充更完整的权限模型与内容审核流程
