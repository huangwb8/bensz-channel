# bensz-channel

一个基于 **Laravel（PHP）+ Better Auth + PostgreSQL + Redis + Docker** 的 Web 社区原型，交互形态参考 **QQ 频道**：左侧频道导航，中间内容流，右侧用户与社区信息。

## 已实现能力

- **频道内容架构**：频道首页、文章详情、右侧社区信息栏，内置不可删除的“精华”与“未分类”系统频道；其中“精华”仅作为跨频道聚合入口，文章仍保留自己的实际归属频道并可同时被设为精华
- **管理员后台**：默认预置 1 位管理员，可新增、编辑、删除文章，管理频道，并可完整托管普通用户资料/角色（含头像链接）与安全删除普通用户；后台用户管理页现已支持最近 7 天登录 / 评论 / 发文仪表盘、用户卡片折叠展开、当前页多选批量删除与图标化快捷操作；后台还支持维护站点设置、用户登录/注册方式、频道顶栏显示、文章置顶/精华状态与全站 SMTP 配置；后台 SMTP 现已同时覆盖订阅通知与邮箱验证码登录链路；用户管理现已支持稳定用户 ID 检索
- **成员登录体系**：支持邮箱验证码、邮箱密码，以及微信/QQ 扫码登录（默认内置演示模式，配置开放平台后可切换为真实 OAuth）；手机号验证码链路继续保留为后端兼容能力
- **账户自助维护**：登录用户现在可在“账户设置”页面自助修改昵称、邮箱、手机号、头像链接、个人简介与登录密码，并查看不会随资料变更而变化的稳定用户 ID
- **订阅能力**：支持注册用户通过 SMTP 订阅全部/指定版块新文章、接收评论 @ 提醒，并提供公开 RSS 链接
- **游客静态访问**：未登录用户优先命中预构建静态 HTML，并自动使用 Gzip 压缩版本；登录后回退到 PHP 动态界面
- **Markdown 内容流**：文章与评论都支持 Markdown 渲染
- **文章结构增强**：文章页现已为 Markdown 标题自动生成层级编号与目录 TOC，且 TOC 同时适配桌面端侧栏与移动端折叠面板
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

> 之后会出教程。

启动后访问：

- 站点首页：`http://localhost:6542`
- 登录页：`http://localhost:6542/login`
- Mailpit：`http://localhost:8025`

## 版本管理与自动发布

### 版本号管理

项目版本号统一在 [config.yaml](config.yaml) 中管理（Single Source of Truth）：

```yaml
project_info:
  version: 1.24.0  # 当前开发版本
```

### 创建新版本

1. **更新版本号**：修改 [config.yaml](config.yaml) 中的 `version` 字段
2. **更新变更日志**：在 [CHANGELOG.md](CHANGELOG.md) 中添加新版本的变更内容
3. **创建 Release**：在 GitHub Actions 页面手动触发 `Create Release from config.yaml` 工作流
   - 工作流会自动从 config.yaml 读取版本号
   - 自动创建 Git tag（格式：`v{version}`）
   - 自动从 CHANGELOG.md 提取 Release Notes
   - 创建 GitHub Release

### 自动发布 Docker 镜像

项目包含 GitHub Actions 工作流 `publish-release-images`，会按 `0 */12 * * *`（UTC 每天 `00:00` 与 `12:00`）自动检查最新的 **已发布 GitHub Release**。

当 Docker Hub 中还不存在该 Release 对应的镜像标签时，工作流会自动构建并推送：

- `DOCKERHUB_NAMESPACE/<仓库名>-web:<release-tag>` 与 `:latest`
- `DOCKERHUB_NAMESPACE/<仓库名>-auth:<release-tag>` 与 `:latest`

**配置要求**：

Repository Variables：
- `DOCKERHUB_NAMESPACE`：Docker Hub 命名空间，必填（若未单独指定镜像仓库）
- `DOCKERHUB_WEB_REPOSITORY`：可选，覆盖默认的 Web 镜像仓库名
- `DOCKERHUB_AUTH_REPOSITORY`：可选，覆盖默认的 Auth 镜像仓库名

Repository Secrets：
- `DOCKERHUB_USERNAME`：Docker Hub 用户名
- `DOCKERHUB_TOKEN`：Docker Hub Access Token

**构建使用的项目资源**：

Web 镜像（[docker/web/Dockerfile](docker/web/Dockerfile)）：
- [app/](app/) - Laravel 应用主目录（PHP 后端 + 前端资源）
- [config/](config/) - 项目配置文件
- [docker/web/](docker/web/) - Web 容器配置（entrypoint、supervisord）
- [docker/nginx/](docker/nginx/) - Nginx 服务器配置

Auth 镜像（[auth-service/Dockerfile](auth-service/Dockerfile)）：
- [auth-service/](auth-service/) - 认证服务（Node.js）
- [config/](config/) - 项目配置文件（与 Web 共享）

### 版本同步检查

项目包含自动版本同步检查工作流，会在以下情况触发：
- 修改 config.yaml 或 CHANGELOG.md 时
- 创建 Pull Request 时

检查内容：
- config.yaml 版本号与最新 GitHub Release 是否同步
- CHANGELOG.md 是否包含当前版本的变更记录
- 自动在 PR 中添加版本状态评论

默认管理员：

- 邮箱：`admin@example.com`
- 密码：`admin123456`
- 用户ID：`0`

登录审查建议：

- 邮箱验证码：在登录页选择“邮箱 + 验证码”，填写任意可接收邮件的邮箱；Docker 审查环境请到 Mailpit 查看验证码
- 邮箱密码：可直接使用管理员邮箱 `admin@example.com` 与配置密码登录
- 微信 / QQ：在登录页选择对应扫码方式后生成二维码继续审查
- Docker 审查环境默认不在页面展示验证码，请到 Mailpit 查看邮件验证码
- 手机验证码链路仍保留为后端兼容能力，但当前不在登录页主入口展示
- 管理员登录后可进入“订阅设置”，在原有订阅偏好之外维护全站 SMTP 服务器、端口、账号、密钥与发件人信息，并可直接点击“测试 SMTP”按钮验证当前表单配置是否可正常投递；保存后的配置会同时用于文章订阅通知、评论提醒与邮箱验证码发送
- 任意已登录用户都可进入“账户设置”页面，自助修改昵称、邮箱、手机号、头像链接、个人简介与登录密码
- 新注册或首次登录创建的用户会自动获得稳定用户 ID，普通用户从 `101` 开始递增；后续修改昵称、邮箱、手机号都不会改变该 ID
- 管理员还可进入“站点设置”页面，覆盖 `config/config.toml` 中 `APP_NAME`、`SITE_NAME`、`SITE_TAGLINE` 的默认值
- 管理员可在“站点设置”页面填写静态资源 CDN 地址，加速前台 CSS、JS、图片等公开资源加载
- 管理员也可在“站点设置”页面控制是否开放邮箱验证码、邮箱密码、微信扫码、QQ 扫码四种登录/注册入口
- 管理员可在“用户管理”页维护普通用户的昵称、邮箱、手机号、头像链接、简介与角色；用户卡片支持折叠 / 展开，右上角统一提供保存、删除、展开 / 收起图标按钮
- 管理员可在“用户管理”页查看最近 7 天登录 / 活跃、评论与发文仪表盘，并支持当前页多选批量删除普通用户；删除时会一并清理被删账号的登录会话、密码重置令牌以及关联内容
- 管理员可在“频道管理”页决定哪些频道出现在顶栏导航中；频道被隐藏后仍保留访问地址与内容，`未分类` 默认隐藏但可按需显示，`精华` 为内置系统频道且默认展示
- 管理员可在“文章管理”页一键将文章设为“置顶”或“精华”，也可随时取消；首页会优先展示置顶文章，“精华”频道会聚合所有精华文章
- 管理后台的大部分高频操作已改为紧凑图标按钮，鼠标悬停会显示用途提示，便于节省空间并提升审查体验

## 订阅说明

### SMTP 邮件订阅

- 仅注册用户可用
- 默认开启“全部版块新文章提醒”和“评论 @ 提醒”
- 登录后可在 `订阅设置` 页面关闭全部提醒，或仅保留指定版块
- 登录后也可在 `账户设置` 页面维护邮箱、手机号与密码登录信息
- 管理员可在同一页面测试当前 SMTP 表单配置；测试不会保存当前 SMTP 草稿配置，邮件会发到“测试收件邮箱”字段指定的地址；该测试收件邮箱会在点击“保存 SMTP 配置”后保留，便于下次继续验证

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
├── data/                   # Docker 持久化数据目录（自动创建，默认 Git 忽略）
├── config.yaml             # 项目版本单一事实来源
├── AGENTS.md               # Codex 项目指令
├── CLAUDE.md               # Claude Code 项目指令
├── CHANGELOG.md            # 变更记录
└── README.md               # 项目说明
```

## 数据持久化

- PostgreSQL 数据显式挂载到 `./data/postgres`
- Redis AOF 显式挂载到 `./data/redis`
- Mailpit 数据库显式挂载到 `./data/mailpit`
- Laravel 运行时存储与静态页面输出显式挂载到 `./data/web/`
- Web 容器启动时只执行 `migrate + SystemBootstrapSeeder`，避免重部署时反复写入 demo 内容
- 仓库更新时只需执行 `git pull` 后重新运行 `./scripts/compose.sh up --build -d`

## 认证说明

### 管理员

- 默认预置 1 位管理员账号
- 能力：管理频道、发布文章、管理普通用户资料与角色、删除普通用户、发布评论

### 登录成员

- 邮箱 + 验证码登录
- 邮箱 + 密码登录
- 微信扫码登录（默认演示模式，可切换真实微信开放平台 OAuth）
- QQ 扫码登录（默认演示模式，可切换真实 QQ 互联 OAuth）
- Better Auth 手机验证码链路（后端兼容保留）
- 能力：发表评论

## 微信 / QQ 扫码登录

- 项目现在已经同时支持两种运行形态：
  - **演示模式**：默认开启，无需任何外部平台账号，适合 Docker 重部署后直接审查
  - **真实 OAuth 模式**：在微信开放平台 / QQ 互联完成网站应用创建、审核和回调配置后即可启用
- 默认配置下，`config/config.toml` 里的 `WECHAT_QR_MODE` 与 `QQ_QR_MODE` 都是 `demo`，因此 `./scripts/compose.sh up --build -d` 后登录页可直接使用演示二维码流程
- 切换到真实模式时：
  - 在 `config/config.toml` 设置 `WECHAT_QR_MODE="oauth"`、`QQ_QR_MODE="oauth"`
  - 填写 `WECHAT_CLIENT_ID`、`QQ_CLIENT_ID`
  - 在 `config/.env` 填写 `WECHAT_CLIENT_SECRET`、`QQ_CLIENT_SECRET`
  - 如需自定义回调地址，再补 `WECHAT_REDIRECT_URI`、`QQ_REDIRECT_URI`
- 若不显式设置回调地址，项目默认使用：
  - 微信：`{APP_URL}/auth/social/wechat/callback`
  - QQ：`{APP_URL}/auth/social/qq/callback`
- 更详细的新手教程见 `docs/如何让本项目支持微信和QQ扫码登陆.md`

## Better Auth 架构

- Laravel 继续负责页面渲染、业务权限与本地 Session
- `auth-service/` 负责 OTP 发送与验证，底层由 Better Auth 自托管管理
- 邮箱验证码通过 Mailpit/SMTP 投递；手机号验证码保留演示模式回调
- 微信 / QQ 扫码登录由 Laravel 直接对接各自官方 OAuth，避免把第三方网页登录协议强塞进 Better Auth OTP 服务
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

## 静态资源 CDN 配置

本项目支持两种方式配置静态资源 CDN，用于加速 CSS、JS、图片等公开资源的加载：

### 配置方式

**方式一：后台站点设置（推荐）**

1. 以管理员身份登录
2. 进入"站点设置"页面
3. 找到"静态资源 CDN"字段
4. 填写 CDN 域名，例如：`https://cdn.example.com`
5. 点击"保存站点设置"

**方式二：环境变量**

在 `config/.env` 中配置：

```env
ASSET_URL=https://cdn.example.com
```

### 配置优先级

后台"站点设置"中的配置会覆盖环境变量 `ASSET_URL`。如果后台未配置，则使用环境变量的值。

### 配置效果

配置后，页面中的静态资源会自动使用 CDN 域名：

- **页面链接**：继续使用 `APP_URL`（如 `https://community.example.com`）
- **静态资源**：使用 CDN 域名（如 `https://cdn.example.com/build/assets/app.js`）

### 使用场景

- **DogeCloud 等 CDN 服务**：将静态资源分发到 CDN 节点，加速全球访问
- **独立静态资源域名**：将静态资源与主站分离，优化浏览器并发加载
- **开发环境**：留空即可，使用本地资源

### 注意事项

- CDN 域名应配置回源到你的应用服务器
- 建议为 `/build/*` 路径配置长缓存（资源文件带内容哈希）
- 修改配置后会立即生效，无需重启服务

## DogeCloud CDN 接入

本项目支持两种 DogeCloud 接入方式，按需选择：

| 方式 | 适用场景 | CDN 域名数量 |
|------|----------|-------------|
| **全站加速**（主域名挂 CDN） | 希望游客静态页也走 CDN 节点 | 1 个，与 `APP_URL` 相同 |
| **仅静态资源加速**（独立 CDN 域名） | 主站直连，只把 CSS/JS/图片挂 CDN | 2 个，主站 + CDN 子域名 |

### 方式一：全站加速

适合把整个站点（包括游客静态页）都挂到 DogeCloud 加速的场景。

**第一步：配置应用**

编辑 `config/.compose.env`：

```env
APP_URL=https://community.example.com   # 公网访问域名（即 CDN 加速域名）
SESSION_SECURE_COOKIE=true
```

`ASSET_URL` 留空，静态资源跟随主域名。

**第二步：DogeCloud 控制台**

1. 新建加速域名，填写 `community.example.com`，业务类型选”网页小文件”
2. 源站填写你的服务器 IP 或内网域名，回源协议按实际选 HTTP/HTTPS
3. 回源 Host 填 `community.example.com`（与加速域名一致）
4. 配置缓存规则（见下方缓存规则说明）
5. 将域名 DNS 解析 CNAME 到 DogeCloud 提供的加速域名

**第三步：重启应用**

```bash
./scripts/compose.sh up --build -d
```

### 方式二：仅静态资源加速

适合主站直连、只把 CSS/JS/图片挂到独立 CDN 域名的场景，登录、评论等动态请求不经过 CDN。

**第一步：配置应用**

编辑 `config/.compose.env`：

```env
APP_URL=https://community.example.com   # 主站域名，直连服务器
ASSET_URL=https://cdn.example.com       # 静态资源 CDN 域名
SESSION_SECURE_COOKIE=true
```

也可以不改 `config/.compose.env`，部署后在后台”站点设置 → 静态资源 CDN”填写 CDN 域名，效果相同。

**第二步：DogeCloud 控制台**

1. 新建加速域名，填写 `cdn.example.com`，业务类型选”网页小文件”
2. 源站填写你的服务器 IP 或内网域名，回源协议按实际选 HTTP/HTTPS
3. 回源 Host 填 `community.example.com`（主站域名，让 Nginx 能正确响应）
4. 配置缓存规则（见下方缓存规则说明）
5. 将 `cdn.example.com` DNS 解析 CNAME 到 DogeCloud 提供的加速域名

**第三步：重启应用**

```bash
./scripts/compose.sh up --build -d
```

### 缓存规则说明

Nginx 已为不同路径设置了对应的缓存头，DogeCloud 建议跟随源站：

| 路径 | Nginx 缓存头 | DogeCloud 建议 | 说明 |
|------|-------------|----------------|------|
| `/build/*` | `Cache-Control: public, immutable`，30 天 | 跟随源站或设长缓存 | Vite 指纹资源，内容变则文件名变，可永久缓存 |
| `/static/*` | `Cache-Control: public, max-age=600`，10 分钟 | 跟随源站 | 游客静态页，文章更新后会重建 |
| 其他路径 | 无缓存头 | 不缓存 | 动态页面、登录、API 等 |

其他建议：
- **智能压缩**：保持开启，项目已生成 `.gz` 静态文件，DogeCloud 可继续做 Brotli 分发
- **回源请求头**：DogeCloud 默认带 `X-Forwarded-For` 和 `X-Forwarded-Proto`，无需额外配置

### 验证

部署完成后检查：

```bash
# 检查首页资源链接是否指向正确域名
curl -s https://community.example.com | grep -o 'src=”[^”]*build[^”]*”' | head -3
```

- 全站加速：资源链接应包含 `community.example.com/build/`
- 仅静态资源加速：资源链接应包含 `cdn.example.com/build/`

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

## 统一测试入口

为了让 AI 或人工在改动后快速判断“系统是否仍然正常、稳定、高效工作”，仓库现在提供统一测试入口 `scripts/test/`：

```bash
# 完整回归 + Docker 重部署 + 冒烟 + 稳定性 + 性能
./scripts/test/all.sh

# 按需执行单项验证
./scripts/test/auth-regression.sh
./scripts/test/app-regression.sh
./scripts/test/docker-redeploy.sh
./scripts/test/docker-smoke.sh
./scripts/test/stability.sh
./scripts/test/performance.sh
```

判定口径统一如下：

- `NORMAL`：现有单元/功能回归、前端构建与 Docker 冒烟均通过
- `STABLE`：关键健康检查与管理员登录链路连续多轮通过
- `EFFICIENT`：首页、登录页、RSS 在默认性能预算内
- `SAFE_CHANGE`：以上三项全部通过，可作为“本次改动暂无回归迹象”的自动化证据

## 后续可扩展点

- 接入真实微信 / QQ OAuth 扫码平台
- 增加帖子列表分页、全文搜索、消息通知
- 为频道补充更完整的权限模型与内容审核流程
