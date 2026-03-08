# bensz-channel

一个基于 **Laravel（PHP）+ Better Auth + PostgreSQL + Redis + Docker** 的 Web 社区原型，交互形态参考 **QQ 频道**：左侧频道导航，中间内容流，右侧用户与社区信息。

## 已实现能力

- **频道内容架构**：频道首页、文章详情、右侧社区信息栏
- **管理员后台**：默认预置 1 位管理员，可新增、编辑、删除文章，管理频道、用户资料/角色，并可在后台维护站点设置、用户登录/注册方式与全站 SMTP 配置
- **成员登录体系**：支持邮箱验证码、邮箱密码，以及微信/QQ 演示扫码登录；手机号验证码链路继续保留为后端兼容能力
- **订阅能力**：支持注册用户通过 SMTP 订阅全部/指定版块新文章、接收评论 @ 提醒，并提供公开 RSS 链接
- **游客静态访问**：游客优先命中构建好的静态 HTML，并提供 Gzip 压缩版本
- **Markdown 内容流**：文章与评论都支持 Markdown 渲染
- **Docker 一键部署**：应用、PostgreSQL、Redis、Mailpit 一起启动
- **CDN 友好资源策略**：Vite 指纹资源 + `ASSET_URL` 预留 + Nginx 长缓存头

## 技术栈

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

## 快速部署

```bash
cp config/.env.example config/.env
./scripts/compose.sh up --build -d
```

配置职责约定：

- `config/config.toml`：托管关键**非密钥**参数，例如端口、站点名、数据库主机、邮件主机、OTP TTL
- `config/.env`：托管密码、密钥等**敏感**参数，例如 `APP_KEY`、数据库密码、Better Auth secrets

如果 `config/.env` 里的 `APP_KEY` 还是空值，可先生成一个：

```bash
php -r 'echo "APP_KEY=base64:".base64_encode(random_bytes(32)), PHP_EOL;'
```

启动后访问：

- 站点首页：`http://localhost:6542`
- 登录页：`http://localhost:6542/login`
- Mailpit：`http://localhost:8025`

默认管理员：

- 邮箱：`admin@example.com`
- 密码：`admin123456`

默认成员（非管理员）：

- 邮箱：`member@example.com`
- 密码：`member123456`
- 手机号：`13800138000`（验证码登录）

登录审查建议：

- 邮箱验证码：在登录页选择“邮箱 + 验证码”，填写任意邮箱，例如 `member@example.com`
- 邮箱密码：可直接使用管理员邮箱 `admin@example.com` 与配置密码登录
- 微信 / QQ：在登录页选择对应扫码方式后生成二维码继续审查
- Docker 审查环境默认不在页面展示验证码，请到 Mailpit 查看邮件验证码
- 手机验证码链路仍保留为后端兼容能力，但当前不在登录页主入口展示
- 管理员登录后可进入“订阅设置”，在原有订阅偏好之外维护全站 SMTP 服务器、端口、账号、密钥与发件人信息
- 管理员还可进入“站点设置”页面，覆盖 `config/config.toml` 中 `APP_NAME`、`SITE_NAME`、`SITE_TAGLINE` 的默认值
- 管理员也可在“站点设置”页面控制是否开放邮箱验证码、邮箱密码、微信扫码、QQ 扫码四种登录/注册入口
- 管理后台的大部分高频操作已改为紧凑图标按钮，鼠标悬停会显示用途提示，便于节省空间并提升审查体验

## 订阅说明

### SMTP 邮件订阅

- 仅注册用户可用
- 默认开启“全部版块新文章提醒”和“评论 @ 提醒”
- 登录后可在 `订阅设置` 页面关闭全部提醒，或仅保留指定版块

### RSS 订阅

- 全部版块：`/feeds/articles.xml`
- 单个版块：`/feeds/channels/{channel-slug}.xml`
- 任何拿到链接的用户都可直接在 RSS 阅读器中订阅

## 目录结构

```text
bensz-channel/
├── config/                 # 根配置：config.toml 放非密钥，.env 放密钥
├── scripts/                # 配置导出与 Docker Compose 包装脚本
├── app/                    # Laravel 应用源码
├── auth-service/           # Better Auth 自托管认证服务
├── docker/                 # Dockerfile、Nginx、Supervisor 配置
├── docker-compose.yml      # 容器编排模板（由 scripts/compose.sh 注入环境）
├── config.yaml             # 项目版本单一事实来源
├── AGENTS.md               # Codex 项目指令
├── CLAUDE.md               # Claude Code 项目指令
├── CHANGELOG.md            # 变更记录
└── README.md               # 项目说明
```

## 认证说明

### 管理员

- 默认预置 1 位管理员账号
- 能力：管理频道、发布文章、管理用户资料与角色、发布评论

### 登录成员

- 邮箱 + 验证码登录
- 邮箱 + 密码登录
- 微信扫码演示登录
- QQ 扫码演示登录
- Better Auth 手机验证码链路（后端兼容保留）
- 能力：发表评论

## Better Auth 架构

- Laravel 继续负责页面渲染、业务权限与本地 Session
- `auth-service/` 负责 OTP 发送与验证，底层由 Better Auth 自托管管理
- 邮箱验证码通过 Mailpit/SMTP 投递；手机号验证码保留演示模式回调
- Better Auth 独立使用 PostgreSQL `auth` schema，避免与 Laravel 的 `public` schema 冲突
- Laravel 通过内部共享密钥调用 `auth-service` 的 `/internal/otp/send` 与 `/internal/otp/verify`
- `app/` 与 `auth-service/` 都会在启动早期自动加载根目录 `config/config.toml` 与 `config/.env`

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
cp config/.env.example config/.env
# 手动补齐 APP_KEY / 各类密码密钥

cd app
sh ./scripts/ensure-managed-vendor.sh
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

另开一个终端启动 Better Auth：

```bash
cd auth-service
npm install
npm run migrate
npm run start
```

如果只想查看当前会注入哪些运行时环境变量，可执行：

```bash
./scripts/load-config-env.sh env-file
```

- `app/node_modules` 通过符号链接指向 `/Volumes/2T01/Test/bensz-channel/app/node_modules`
- `auth-service/node_modules` 通过符号链接指向 `/Volumes/2T01/Test/bensz-channel/auth-service/node_modules`
- `app/vendor` 通过符号链接指向 `/Volumes/2T01/Test/bensz-channel/app/vendor`
- `app/composer.json` 将 Composer 的 `vendor-dir` 固定为 `/Volumes/2T01/Test/bensz-channel/app/vendor`
- `composer install` 会通过 `pre-autoload-dump` 自动执行 `app/scripts/ensure-managed-vendor.sh`，避免依赖回写到项目目录
- Composer 缓存统一写入 `/Volumes/2T01/Test/bensz-channel/composer-cache`
- `npm install` 会读取 `app/.npmrc`，将缓存统一写入 `/Volumes/2T01/Test/bensz-channel/npm-cache`
- `npm install` 完成后会自动执行 `app/scripts/ensure-managed-node-modules.sh`，把可能被 npm 重建到项目内的依赖目录重新迁回托管路径
- `auth-service/.npmrc` 也复用同一 npm 缓存目录，`auth-service/scripts/ensure-managed-node-modules.sh` 会将依赖迁回统一托管路径

## 后续可扩展点

- 接入真实微信 / QQ OAuth 扫码平台
- 增加帖子列表分页、全文搜索、消息通知
- 为频道补充更完整的权限模型与内容审核流程
