# Changelog

**重要**：本文件是项目变更的**唯一正式记录**。凡是项目的更新，都要统一在本文件里记录。这是项目管理的强制性要求。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.1.0/)。

## [Unreleased]

### Added（新增）

- 新增了 `app/tests/Feature/Api/Vibe/DevtoolsApiTest.php` 与 `app/tests/Feature/Admin/AdminArticleManagementTest.php` 的精华主频道约束回归用例：用于锁定“精华”只能作为聚合频道、不能被当作文章主频道的行为

- 新增了 `app/tests/Feature/Channels/TopNavChannelVisibilityTest.php` 的移动端频道切换回归断言：用于锁定左上角频道按钮、移动端抽屉入口与当前频道高亮状态
- 新增了文章标题自动编号与 TOC 导航：文章页现在会基于 Markdown 正文标题自动生成层级编号，并同时提供桌面端侧栏目录与移动端折叠目录
- 新增了 `app/tests/Unit/Support/ArticleBodyFormatterTest.php` 与 `app/tests/Feature/Articles/ArticleTocTest.php`：用于锁定文章目录生成、标题编号、中文标题锚点回退与文章页 / 静态页渲染行为

- 新增了真实微信 / QQ 扫码登录能力：项目现在可在默认演示二维码模式与官方 OAuth 模式之间切换，且两条链路共用统一的社交账号绑定逻辑
- 新增了 `app/tests/Feature/Auth/SocialOAuthLoginTest.php`：用于覆盖微信授权跳转、微信回调登录、QQ 授权跳转、QQ 回调登录与 state 校验失败等关键回归点
- 新增了 `docs/如何让本项目支持微信和QQ扫码登陆.md`：用于手把手说明微信开放平台 / QQ 互联的站外申请、审核、回调配置、项目参数填写与 Docker 重部署步骤
- 新增了 `auth-service/tests/email-otp.test.js`、`auth-service/tests/mail-config.test.js` 与 `auth-service/tests/otp-email.test.js`：用于锁定 Better Auth 邮件链路会读取后台 SMTP 配置、能解密 Laravel 加密密码，并在真实投递失败时向上返回错误

- 新增了稳定用户 ID 体系：系统管理员固定为 `0`，新注册或首次登录创建的用户从 `101` 开始递增，且账号修改昵称、邮箱、手机号后 ID 仍保持不变
- 新增了稳定用户 ID 回归测试覆盖：用于锁定系统管理员、验证码登录、账户设置、后台用户管理与 DevTools 用户更新链路的稳定标识行为

- 新增了 `app/database/migrations/2026_03_10_000000_add_test_recipient_to_mail_settings_table.php`：用于持久化管理员 SMTP 配置页中的“测试收件邮箱”字段，避免页面刷新后回退到默认管理员邮箱

- 新增了管理员 SMTP“测试 SMTP”按钮与独立探测链路：管理员现在可直接验证当前表单里的服务器、端口、账号和发件人配置是否可正常投递，且测试不会误保存草稿配置

- 新增了“精华频道 + 文章置顶/精华运营”实现计划 `docs/plans/2026-03-09-featured-channel-and-article-curation.md`：用于沉淀系统频道、文章状态、聚合频道页与 Docker 验收步骤
- 新增了 `app/tests/Feature/Channels/FeaturedChannelTest.php`：用于锁定“精华”频道聚合展示跨频道精华文章的行为
- 新增了频道顶栏显示实现计划 `docs/plans/2026-03-08-top-nav-channel-visibility.md`：用于沉淀“频道可隐藏但不删除”的实现路径、测试点与 Docker 验收步骤
- 新增了用户自助“账户设置”页面与对应控制器 / 视图 / 回归测试：登录用户现在可直接修改昵称、邮箱、手机号、头像链接、个人简介与登录密码
- 新增了账户设置实现计划 `docs/plans/2026-03-08-account-settings.md`：用于沉淀本次“用户可自助修改账户信息”功能的实现路径与验证步骤

- 新增了 GitHub Actions 工作流 `.github/workflows/publish-release-images.yml`：每 12 小时自动检查一次最新 GitHub Release，并在 Docker Hub 尚未存在对应版本镜像时构建并推送 `web` / `auth` 双镜像
- 新增了 `skills/bensz-channel-devtools/CHANGELOG.md` 与对应 `plans/`、`tests/` 自动测试会话产物：用于沉淀本次 skill 级优化的可追溯记录
- 新增了 `app/tests/Feature/Api/Vibe/DevtoolsApiTest.php` 与 `skills/bensz-channel-devtools/tests/v202603082316/_scripts/live_smoke.sh`：分别用于锁定 DevTools API 回归点与执行真实 Docker 环境 CLI smoke test
- 新增了 `app/database/seeders/SystemBootstrapSeeder.php`、`app/tests/Feature/Database/SystemBootstrapSeederTest.php` 与 `auth-service/tests/ensure-schema.test.js`：用于保证生产启动仅初始化必要系统数据，并锁定 auth schema 初始化行为
- 新增了 `app/tests/Feature/Channels/TopNavChannelVisibilityTest.php`：用于锁定“仅显示被允许出现在顶栏的频道”以及“隐藏频道仍可直达访问”的回归行为

### Changed（变更）

- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.22.0`，并明确“精华”是跨频道聚合入口，文章实际归属仍由普通频道承载
- 更新了后台文章表单与 DevTools 文章接口：现在会统一拒绝把 `featured` 作为文章主频道，同时继续允许文章通过 `is_featured` 同时出现在“精华”与原始频道中

- 更新了社区顶栏导航的移动端交互：当频道数量增多时，小屏幕界面现在会在左上角显示当前频道按钮，并通过抽屉式频道列表完成切换，桌面端横向频道标签保持原有行为不变
- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.20.0`，并补充微信 / QQ 扫码登录支持“默认演示模式 + 可切换真实 OAuth”的运行方式
- 更新了登录页与认证配置：微信 / QQ 登录卡片现在会明确展示当前处于演示模式、真实 OAuth 模式或平台参数未配置完整状态
- 更新了扫码登录实现：微信 / QQ 不再只有本地演示授权壳子，切换到真实模式后会走官方 OAuth 回调并落库到 `social_accounts`

- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.19.0`，并补充稳定用户 ID 的分配规则、后台检索方式与审查说明
- 更新了用户创建与管理链路：新增 `users.user_id` 稳定标识字段、分配序列表与创建钩子，同时在账户设置、后台用户管理和 DevTools 用户接口中展示/返回稳定用户 ID

- 更新了 `config.yaml`：将项目版本推进到 `1.18.2`，用于记录本次管理员 SMTP 测试收件邮箱持久化修复
- 更新了管理员 SMTP 配置保存链路与界面文案：现在“保存 SMTP 配置”会同时保存测试收件邮箱，页面回显优先使用已保存值，不再总是退回 `admin@example.com`
- 更新了 `README.md`：补充“测试收件邮箱会随 SMTP 配置一起保存，但测试动作本身仍不会保存当前 SMTP 草稿”的行为说明

- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.18.0`，并补充管理员 SMTP 测试按钮、测试邮件投递目标与页面使用说明
- 更新了后台 SMTP 配置兼容层：现在会把后台表单中的 `TLS / SSL` 选项安全映射到 Symfony SMTP 传输层，避免配置保存成功但真实发信失败

- 更新了 `config.yaml`：将项目版本推进到 `1.17.0`，用于记录本次 DogeCloud CDN 兼容性加固与接入文档完善
- 更新了 Laravel URL 生成策略：现在统一以 `APP_URL` 作为公开访问根地址，避免 CDN 回源 Host 与公网域名不一致时泄漏源站地址
- 更新了 `README.md`：补充 DogeCloud 融合 CDN 的接入步骤、环境变量说明、控制台推荐配置与 Docker 重部署审查清单
- 更新了后台“站点设置”页提示：明确 `APP_URL` 负责页面主域名，`cdn_asset_url` 用于静态资源 CDN

- 更新了 `config.yaml`：将项目版本推进到 `1.16.2`，用于记录频道管理拖拽排序与顶栏显示交互精简
- 更新了频道管理界面：支持拖拽排序并提供“保存排序”动作，顶栏显示改为精简开关样式

- 更新了文章列表卡片交互：现在点击文章卡片任意区域都会进入详情页，避免必须点标题的操作成本

- 更新了 `config.yaml`：将项目版本推进到 `1.16.1`，用于记录本次全局布局底栏贴底修复
- 更新了主站布局：页面根容器改为纵向弹性布局，确保内容较少时页脚仍稳定贴底，不会上浮到页面中部

- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.16.0`，并补充内置“精华”频道、文章置顶/精华管理及前台聚合展示说明
- 更新了系统初始化与频道体系：现在默认创建不可删除的 `精华` / `未分类` 两个系统频道，其中 `精华` 作为公开聚合频道展示站内精选内容
- 更新了文章管理链路：管理员现在可在文章列表一键切换“置顶 / 精华”状态，并可在编辑页显式保存这两个运营标签
- 更新了首页、频道页与频道 RSS：首页优先展示最新置顶文章，`精华` 频道与其 RSS 会聚合所有被标记为精华的文章

- 更新了 `config.yaml`：将项目版本推进到 `1.15.1`，用于记录本次文章页订阅入口收敛调整
- 更新了文章详情页：移除了标题下方重复的“RSS 订阅本版块 / 管理 SMTP 订阅”按钮，避免与其它页面功能重叠并减少业务歧义

- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.15.0`，并补充管理员可控制频道顶栏显示、`未分类` 默认隐藏但可按需显示的说明
- 更新了频道管理链路：管理员现在可在后台决定频道是否出现在顶栏导航中；系统保留的 `未分类` 频道也提供最小可配置入口，仅允许调整顶栏显示状态
- 更新了 Vibe 频道 API：频道列表 / 创建 / 更新结果与请求体现已对齐 `show_in_top_nav` 字段，避免后台与 DevTools 管理口径漂移
- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.14.0`，并补充“账户设置”能力与审查说明
- 重构了用户资料更新逻辑：新增共享的 `UserAccountManager`，统一自助设置与后台用户编辑的输入归一化、唯一性校验、登录标识约束与联系方式变更后的验证状态处理
- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.13.1`，并补充 Docker Hub 自动发布工作流所需的 GitHub Secrets / Variables 配置说明
- 更新了 `docker-compose.yml`：为 `web` 与 `auth` 服务补充了已注释的远程镜像示例，便于需要时切换到 Docker Hub 发布镜像，同时保留“直接克隆仓库后本地 build”为默认推荐方式
- 优化了 `skills/bensz-channel-devtools`：修复匿名 `ping` 被错误要求 KEY、列表查询未 URL 编码、`doctor` 未响应 `terminate: true` 等问题，并同步统一 URL 规范化与环境搜索配置集中化
- 优化了 DevTools Vibe API：频道与文章端点现在同时支持数值 `id` 与 `slug` 标识；频道 / 文章 / 用户更新支持 partial update；文章与评论列表的 `published=false` / `visible=false` 过滤已按真实布尔语义处理
- 更新了 `skills/bensz-channel-devtools/tests/v202603082316/`：补充本轮真实 Docker 环境 smoke test 产物、环境脚本检查结果与修复闭环记录
- 更新了 `docker-compose.yml`：将 PostgreSQL、Redis、Mailpit 与 Laravel 运行时写盘目录统一显式挂载到根目录 `./data/`，确保仓库更新与镜像重建不会覆盖现有用户数据
- 更新了 `scripts/compose.sh`：新增首次升级时的旧命名卷 / 容器内运行时数据自动迁移逻辑，并在启动前自动创建 `./data/` 目录树
- 更新了 `.gitignore` 与 `.dockerignore`：统一忽略 `./data/`，避免运行时数据进入 Git 历史或 Docker 构建上下文
- 更新了 `README.md`：补充 `./data/` 持久化目录、升级迁移行为与推荐的重部署方式
- 更新了 `docker/web/entrypoint.sh`：容器启动时改为执行 `SystemBootstrapSeeder`，仅初始化默认管理员等必需基线数据，不再自动写入示例频道、文章、评论与示例成员
- 更新了 `README.md`：明确 Docker 审查环境默认不再注入 demo 数据，并补充手动加载演示数据的方法
- 更新了 `auth-service/src/ensure-schema.js` 与 `auth-service/src/config.js`：auth schema 初始化改为读取 `AUTH_DB_SCHEMA` 配置，并增加安全的 SQL 标识符转义
- 更新了 `auth-service/scripts/entrypoint.sh` 与 `auth-service/src/wait-for-database.js`：auth 服务启动前会主动等待 PostgreSQL 就绪，避免整栈重启时因数据库尚未接受连接而启动失败

- 优化了管理员频道管理页的行内操作：将“保存”和“删除”改为带 tooltip 与无障碍标签的图标按钮，减少表格行宽占用并统一后台操作风格
- 更新了图标按钮组件与图标库：支持 `aria-label` 属性透传，并新增保存图标供后台使用
- 优化了“站点设置”页的管理入口：将“文章管理 / 频道管理 / 用户管理”改为统一的图标按钮，减少页头视觉噪声并保持后台管理入口风格一致

### Fixed（修复）

- 修复了管理员 SMTP 测试收件目标容易误导的问题：现在测试邮件优先发往页面显式填写的“测试收件邮箱”，不再默认显示 `admin@example.com` 造成“看起来没用当前表单配置”的误解
- 修复了 DogeCloud 等 CDN 使用独立回源 Host 时，页面链接、表单地址、RSS 等绝对 URL 可能回退到源站域名的问题
- 修复了 Docker 重部署后静态页虽然会自动重建，但 README 缺少可直接照做的 DogeCloud 配置说明的问题
- 修复了文章卡片点击区域过小导致的可用性问题：现在整块卡片可点击进入文章
- 修复了主站底栏在短页面内容场景下上浮到中间的问题：现在底栏会稳定停留在视口底部
- 修复了站内缺少内置精选聚合入口的问题：现在管理员不需要挪动原频道即可运营精华文章，且前台存在稳定的系统频道承载展示
- 修复了文章运营状态只能依赖正文编辑间接处理的问题：现在后台可直接一键置顶、设为精华或随时移除
- 修复了文章详情页重复暴露订阅入口的问题：现在文章阅读页只保留内容与评论主路径，避免出现用途不清晰的重复操作
- 修复了管理员只能通过删除频道来影响顶栏展示的问题：现在频道可被安全隐藏，且隐藏后仍保留原有 URL、文章归属与其它业务能力
- 修复了 `未分类` 频道默认出现在顶栏且无法单独控制的问题：现在默认隐藏，并可在后台或 DevTools 中按需切换显示状态
- 修复了普通用户无法修改自己密码、邮箱及其它账户资料的问题：现在已登录用户可通过“账户设置”完成自助维护，且不会影响现有登录、订阅与后台管理链路
- 修复了后台编辑用户资料与前台账户设置规则可能漂移的问题：两条链路现已复用同一套资料归一化与约束校验逻辑
- 修复了旧版 Docker 命名卷仅持久化 PostgreSQL 的问题：现在升级到新版本后可自动迁移到 `./data/`，避免用户在 `git pull` 后重建容器时丢失既有业务数据
- 修复了 DevTools 频道创建在省略 `slug` 时触发 500 的问题：改为安全生成 slug，并在中文标题无法转写时提供稳定 fallback，避免空 slug 或未定义数组键
- 修复了 DevTools 频道 / 文章 `show`、`update`、`delete` 命令与服务端路由绑定不一致的问题：skill 传递数值 `id` 时现已可被 API 正确解析
- 修复了 DevTools 用户更新只能整表提交的问题：现在可按 skill 文档仅提交变更字段，同时保持“至少一个联系方式”和“最后一位管理员不可降级”的服务端约束
- 修复了 Docker 重部署会重复灌入 demo 业务数据的问题：现在重启后只保留真实数据库内容与必要系统基线，避免审查环境被示例数据污染
- 修复了 auth-service schema 初始化写死为 `auth` 的问题：自定义 `AUTH_DB_SCHEMA` 时现在也能正确建库

## [1.12.0] - 2026-03-08

### Changed（变更）

- 优化了管理员信息架构：管理员菜单顺序统一调整为“站点设置 → 管理文章 → 管理频道 → 管理用户 → 订阅设置 → DevTools → 退出登录”，并将 `/admin` 默认入口同步改到站点设置页
- 重构了用户管理页卡片：将用户编辑表单压缩为与频道管理一致的紧凑卡片布局，保留角色切换、联系方式维护、简介编辑与单用户错误提示
- 调整了频道管理页：不再显性展示系统保留的“未分类”频道卡片，同时增加说明文案，明确删除频道后的文章仍会自动归入该系统频道

### Fixed（修复）

- 新增了管理员导航与频道管理回归测试：锁定后台菜单顺序、`/admin` 默认跳转以及“未分类”频道隐藏展示的行为，避免后续优化回归
- 修复了邮箱验证码注册/登录邮件未跟随后台 SMTP 配置的问题：Better Auth 认证服务现在会优先读取 `mail_settings` 中已启用的 SMTP 覆盖配置，并兼容 Laravel 加密保存的 SMTP 密码
- 修复了认证服务邮件发送失败仍向前端返回“已发送”的问题：邮箱验证码投递改为等待 SMTP 实际结果，连接失败或认证失败时会直接返回错误，避免用户空等邮件
- 修复了文章 Markdown 标题只能裸展示、缺少结构导航的问题：现已自动生成标题编号与可点击 TOC，并对移动端阅读场景做了单独适配

## [1.10.0] - 2026-03-08

### Added（新增）

- 新增了管理员文章删除能力与对应回归测试：用于补齐后台文章 CRUD 的最后一环，确保管理员可完整维护内容生命周期
- 新增了可复用的图标按钮 Blade 组件：用于统一后台与频道页的高频操作样式、tooltip 与无障碍标签

### Changed（变更）

- 优化了后台与频道页的高频操作按钮：将查看、编辑、删除、管理跳转、订阅等高频功能改为紧凑图标按钮，并在悬停时通过 tooltip 展示用途
- 更新了管理员文章编辑页：现在支持直接删除当前文章，并保持删除后自动重建静态页面
- 更新了 `README.md`：补充管理员可删除文章及图标按钮交互说明

### Fixed（修复）

- 修复了 DevTools UUID 迁移在 SQLite 测试环境下无法执行的问题：移除数据库特定的默认值生成，改由应用层负责 UUID 生成
- 修复了登录页入口配置读取陈旧的问题：现在会实时根据站点设置决定展示和允许使用的登录方式

## [1.11.0] - 2026-03-08

### Added（新增）

- 新增了后台 CDN 配置能力：管理员现在可以在“站点设置”页面填写静态资源 CDN 地址，用于加速 CSS、JS、图片等公开资源
- 新增了 `site_settings.cdn_asset_url` 配置项：用于持久化保存 CDN 基础地址并在运行时覆盖资源 URL

### Changed（变更）

- 强化了游客静态页面分发：Nginx 继续对未登录用户优先返回预构建静态 HTML，并为首页、频道页、文章页增加显式缓存头和 `gzip` 变体支持
- 更新了 `README.md`：补充游客静态页面分发与后台 CDN 加速配置说明

## [1.9.0] - 2026-03-08

### Added（新增）

- 新增了管理员控制登录/注册方式的后台能力：管理员现在可在“站点设置”页面决定是否开放邮箱验证码、邮箱密码、微信扫码、QQ 扫码四种用户入口
- 新增了 `site_settings.auth_enabled_methods` 配置项：用于持久化保存当前允许用户使用的认证方式列表

### Changed（变更）

- 更新了登录页渲染逻辑：现在只展示管理员启用的登录/注册方式，并保持既定顺序不变
- 更新了认证接口守卫：已关闭的邮箱验证码、邮箱密码与扫码方式不会再被用户侧直接使用
- 更新了 `README.md`：补充管理员可在后台控制用户登录/注册方式的说明

## [1.8.0] - 2026-03-08

### Added（新增）

- 新增了管理员“站点设置”后台页面：管理员现在可以在后台界面维护 `APP_NAME`、`SITE_NAME` 与 `SITE_TAGLINE` 三项站点展示配置
- 新增了 `site_settings` 配置表与 `SiteSettingsManager`：用于保存站点名称/标语覆盖值，并在运行时优先应用后台设置
- 新增了 `app/tests/Feature/Admin/AdminSiteSettingsTest.php`：用于覆盖管理员访问、保存站点设置与普通成员越权访问三类关键回归场景

### Changed（变更）

- 更新了管理员菜单：新增“⚙️ 站点设置”入口，便于后台直接维护站点名称和标语，不必手动修改 `config/config.toml`
- 更新了站点设置保存流程：保存后会自动重建静态游客页面，确保首页和频道页文案同步刷新
- 更新了 `README.md`：补充站点名称与标语可在后台覆盖 `config/config.toml` 默认值的说明

## [1.7.0] - 2026-03-08

### Added（新增）

- 新增了管理员 SMTP 配置能力：管理员现在可在订阅设置页维护全站邮件投递的服务器地址、端口、账号、密钥/密码与发件人信息
- 新增了 `mail_settings` 持久化配置表与 `MailSettingsManager`：用于安全保存后台 SMTP 覆盖配置，并在运行时优先应用数据库中的有效配置
- 新增了 `app/tests/Feature/Subscriptions/AdminMailSettingsTest.php`：用于覆盖管理员可见性、配置保存与普通成员越权保护三类关键回归场景

### Changed（变更）

- 更新了订阅设置页：普通用户继续只管理自己的订阅偏好，管理员则在同页额外看到 SMTP 管理区块，不影响原有订阅体验
- 更新了 `README.md`：补充管理员可在订阅设置页维护全站 SMTP 覆盖配置的说明

## [1.6.0] - 2026-03-08

### Added（新增）

- 新增了邮箱 + 密码登录链路：用于让已有密码的成员和管理员直接通过邮箱账号快速登录
- 新增了 `app/tests/Feature/Auth/PasswordLoginTest.php`：用于覆盖邮箱密码登录成功与错误凭据提示两类关键回归场景

### Changed（变更）

- 重构了登录页交互结构：登录方式改为四种并列可切换的面板，按“邮箱 + 验证码 → 邮箱 + 密码 → 微信扫码 → QQ 扫码”的顺序展示，避免所有入口堆叠在同一屏
- 更新了 `README.md`：补充登录页当前提供的四种审查入口，并说明手机号验证码链路当前保留为后端兼容能力

## [1.5.0] - 2026-03-08

### Added（新增）

- 新增 DevTools 远程管理 API 系统：允许 Claude Code、Codex CLI 等 Vibe Coding 工具通过 API 密钥远程管理社区内容（频道/文章/评论/用户），操作限于数据层不涉及源代码修改
  - `devtools_api_keys` 表：存储 API 密钥（SHA-256 哈希 + 前缀，不明文保存）
  - `devtools_connections` 表：记录工具连接会话（含心跳监控和终止请求机制）
  - `EnsureVibeApiKey` 中间件：通过 `X-Devtools-Key` 请求头鉴权
  - `routes/api.php`：完整的 RESTful API 路由（`/api/vibe/*`）
  - `Api/Vibe/AgentController`：连接生命周期管理（connect/heartbeat/disconnect）
  - `Api/Vibe/ChannelController`：频道 CRUD
  - `Api/Vibe/ArticleController`：文章 CRUD（支持 Markdown 渲染 + 订阅通知）
  - `Api/Vibe/CommentController`：评论列表/可见性/删除
  - `Api/Vibe/UserController`：用户列表/资料更新
- 新增管理员 DevTools 管理界面（`/admin/devtools`）：支持生成/撤销 API 密钥，以及查看和终止活跃连接
- 新增 `skills/bensz-channel-devtools/`：零依赖 Python Skill，提供 `client.py`（频道/文章/评论/用户全操作）和 `env_check.py`（环境诊断）

### Changed（变更）

- 更新管理员下拉菜单：新增 "🔧 DevTools" 入口，管理员可一键跳转至 DevTools 管理页面
- `bootstrap/app.php` 注册 `api.php` 路由文件和 `vibe-api` 中间件别名

## [1.4.0] - 2026-03-08

### Added（新增）

- 新增了管理员用户管理页面与后台路由：用于按昵称/邮箱/手机号筛选用户，并维护昵称、邮箱、手机号、简介和角色
- 新增了 `app/tests/Feature/Admin/AdminUserManagementTest.php`：用于覆盖普通成员越权访问、管理员更新用户资料与“最后一位管理员不可降级”回归场景

### Changed（变更）

- 更新了后台导航与管理入口：文章、频道、用户三类管理能力现在可互相跳转，管理员菜单同步展示“管理用户”入口
- 更新了 `README.md`：补充管理员可管理用户资料与角色的能力说明

### Fixed（修复）

- 修复了管理员修改用户公开资料后静态页面可能继续显示旧身份信息的问题：现在保存用户后会自动重新构建静态站点

## [1.3.0] - 2026-03-08

### Added（新增）

- 新增了 `user_notification_preferences` 与 `channel_email_subscriptions` 数据模型：用于支持默认开启的邮件提醒偏好与指定版块订阅
- 新增了文章发布邮件通知、评论 @ 提醒通知与公开 RSS Feed：用于覆盖 SMTP 订阅和匿名 RSS 订阅两类场景
- 新增了 `订阅设置` 页面与导航入口：用于让登录用户随时开启/关闭全部版块、指定版块与 @ 评论提醒

### Changed（变更）

- 更新了首页、频道页与文章页：补充 RSS 订阅入口与 SMTP 设置入口，降低订阅功能的发现成本
- 更新了 `README.md`：补充订阅能力、RSS 路径和 SMTP 使用说明

## [1.2.1] - 2026-03-08

### Added（新增）

- 新增了根目录 `config/config.toml` 与 `config/.env.example`：用于集中托管关键非密钥参数与敏感参数模板
- 新增了 `scripts/load-config-env.sh` 与 `scripts/compose.sh`：用于从根配置生成运行时环境变量，并统一驱动 Docker Compose 启动
- 新增了 `app/tests/Unit/Bootstrap/RootConfigLoaderTest.php` 与 `auth-service/tests/root-config-loader.test.js`：用于锁定根配置加载与“已有环境变量优先”的行为

### Changed（变更）

- 重构了 Laravel 与 Better Auth 的启动配置加载：现在都会在启动早期读取根目录 `config/config.toml` 与 `config/.env`
- 更新了 `docker-compose.yml` 与相关 Dockerfile：容器运行参数改由 `config/.compose.env` 注入，减少配置分散与重复维护
- 更新了 `README.md`：补充根配置分层规则、`./scripts/compose.sh` 启动方式与本地开发说明

### Fixed（修复）

- 修复了关键参数分散在 `docker-compose.yml`、`app/.env` 与 `auth-service` 环境变量中的问题：现在非密钥与密钥分别集中到 `config/config.toml` 与 `config/.env`

## [1.2.0] - 2026-03-08

### Added（新增）

- 新增了 `auth-service/` Better Auth 自托管认证服务：用于承载邮箱/手机号 OTP 的发送与验证，并与 Laravel 主应用解耦
- 新增了 `app/tests/Feature/Auth/BetterAuthLoginTest.php`：用于覆盖 Better Auth 手机登录、服务异常与验证失败等关键回归场景
- 新增了 Better Auth 相关环境变量与 Docker 服务配置：用于在 `docker compose up --build -d` 后自动完成认证服务迁移与启动

### Changed（变更）

- 重构了 Laravel 登录主链路：邮箱/手机号 OTP 由 Better Auth 内部 API 驱动，Laravel 继续负责本地用户同步与 Session 建立
- 更新了 `README.md`：补充 Better Auth 架构、Docker 审查登录方式与本地双服务启动说明
- 更新了 `docker-compose.yml` 与相关 Dockerfile：新增 `auth` 服务、健康检查与安全长度的 Better Auth secrets
- 优化了登录页文案与视觉标识：明确 Better Auth 驱动的自托管登录，并保留扫码演示入口
- 优化了 Node 依赖托管脚本：避免 `npm install` 后迁移 `node_modules` 时因目录非空导致失败

### Fixed（修复）

- 修复了 Docker 构建 `auth-service` 时把本地 `node_modules` 带入 context 导致镜像构建失败的问题
- 修复了 Better Auth 接入后测试仍依赖旧本地验证码表的问题：现在测试通过 HTTP fake 锁定新认证边界
- 修复了 Docker 重部署后的真实邮箱验证码登录链路：已验证从 Laravel 登录页发码、Better Auth 验证到本地 Session 建立全部成功

### Changed（变更）

- **彻底重构登录页面为极简主义设计**：
  - 移除冗余的 Hero 区域、统计卡片和功能特性展示
  - 采用单列居中布局，最大宽度 480px，大量留白
  - 简化表单结构，保持验证码登录和扫码登录功能完整
  - 优化视觉层次：品牌标识 → 主标题 → 表单操作 → 扫码备选
  - 更新 CSS 样式：更精致的按钮和输入框设计，微妙交互动效
  - 保持所有现有功能（验证码发送/验证、微信/QQ 扫码登录）
- 优化了布局数据注入方式：仅对 `layouts.app` 注入最小必需站点数据，避免对所有视图重复执行社区聚合查询
- 更新了 `docker-compose.yml` 的认证预览配置：关闭 Docker 审查环境中的验证码预览展示，避免生产式部署默认泄露一次性验证码

### Fixed（修复）

- 修复了登录/注册页与扫码授权页点击即报错的问题：此前认证页继承主布局但未稳定获得 `pageTitle` 等布局必需变量，导致 Docker 部署后访问 `/login` 返回 500
- 修复了本地功能测试无法启动的问题：为测试进程显式设置 `APP_BASE_PATH`，避免第三方包托管目录导致 Laravel 测试基路径推断错误
- 修复了 Docker 环境下登录后仍显示游客态的问题：统一会话 Cookie 名为 `bensz_channel_session`，并让 Nginx 基于该 Cookie 正确回退到动态应用
- 修复了 Docker 环境下已登录请求返回 `418` 的问题：为 Nginx 增加 `error_page 418 = @dynamic` 回退规则，确保首页与频道页在登录后由 Laravel 正常渲染
- 修复了登录页直接暴露测试账号与密码的问题：移除公开凭据展示，并保留 README 作为审查阶段的受控说明入口
- 修复了验证码登录失败后的表单回填体验：统一兼容发送验证码与验证验证码两个阶段的旧输入回显，减少重复输入

### Security（安全）

- 收紧了一次性验证码预览条件：仅在 `local`/`testing` 环境且显式启用时才展示预览码，避免 Docker 审查环境意外暴露动态登录凭据

## [1.1.2] - 2026-03-08

### Changed（变更）

- 优化了 `app/vendor` 的托管方式：将 Composer 依赖目录迁移到 `/Volumes/2T01/Test/bensz-channel/app/vendor`，并通过符号链接回连项目目录
- 更新了 `app/composer.json`：增加 `pre-autoload-dump` 钩子与 Composer 缓存目录配置，确保后续 `composer install` 持续符合统一托管规则

### Fixed（修复）

- 修复了 PHP 第三方包仍落在项目目录内的问题：此前 `app/vendor` 为实体目录，不符合 `AGENTS.md` 的统一托管规则

## [1.1.1] - 2026-03-08

### Changed（变更）

- 优化了 `app/node_modules` 的托管方式：将依赖目录迁移到 `/Volumes/2T01/Test/bensz-channel/app/node_modules`，并通过符号链接回连项目目录
- 新增了 `app/.npmrc`：将 npm 缓存统一托管到 `/Volumes/2T01/Test/bensz-channel/npm-cache`，避免后续安装再次写回项目内
- 更新了 `app/package.json`：增加 `postinstall` 与 `deps:sync` 钩子，确保 `npm install` 后自动恢复统一托管的依赖目录

### Fixed（修复）

- 修复了前端第三方包落在项目目录内的问题：此前 `app/node_modules` 为实体目录，不符合 `AGENTS.md` 的统一托管规则

## [1.1.0] - 2026-03-08

### Added（新增）

- 新增了基于 `Laravel + PostgreSQL + Redis` 的社区应用骨架：用于承载频道、文章、评论与登录流程
- 新增了 `docker-compose.yml` 与 `docker/` 目录：用于一键部署 Web、数据库、缓存与 Mailpit
- 新增了邮箱验证码、手机号验证码、微信/QQ 演示扫码三种登录方式：用于覆盖成员注册/登录入口
- 新增了游客静态页面构建机制：生成压缩后的静态 HTML 与 `.gz` 文件供 Nginx 优先分发
- 新增了 `config.yaml`：作为项目版本号的单一事实来源

### Changed（变更）

- 更新了 `README.md`：补充真实技术栈、部署命令、默认账号、静态访问说明与目录结构
- 更新了根目录 `.gitignore`：补充 Laravel 依赖、构建产物与缓存文件的忽略规则

### Fixed（修复）

- 修复了仓库只有文档、没有可运行应用的问题：现在支持 Docker 重新部署后直接审查

### Changed（变更）

- 优化 `AGENTS.md`：移除冗余的序号前缀（如 `### 1.` → `###`），遵循文档自身的设计哲学

### Added（新增）

- 新增 `AGENTS.md` 依赖管理章节：第三方包统一托管在 `/Volumes/2T01/Test/bensz-channel`

## [1.0.0] - 2026-03-08

### Added（新增）

- 初始化 AI 项目指令文件
- 生成 `CLAUDE.md`（Claude Code 项目指令）
- 生成 `AGENTS.md`（OpenAI Codex CLI 项目指令）
- 配置项目工程原则和工作流
- 生成 `.gitignore`（包含安全和项目特定规则）
- 生成 `README.md`（项目说明文档）

### Changed（变更）

- 更新 `AGENTS.md`：明确项目目标为类似 QQ 频道的 Web 版社区平台
- 更新 `README.md`：完善项目特性、技术选型和目录结构说明

### Changed（变更）

### Fixed（修复）

---

## 记录规范（强制性要求）

### 必须记录的变更类型

每次修改以下内容时，**必须**在本文件追加记录：

1. **项目指令文件变更**
   - CLAUDE.md 的任何修改
   - AGENTS.md 的任何修改

2. **项目结构变更**
   - 新增/删除/重命名目录
   - 新增/删除/重命名关键文件（如核心源码文件、配置文件）

3. **工作流变更**
   - 核心工作流程的调整
   - 开发流程的修改

4. **工程原则变更**
   - 新增工程原则
   - 修改或删除现有工程原则

5. **重要配置变更**
   - 影响项目行为的配置文件修改
   - 依赖关系的重大变更

### 记录格式

```markdown
## [版本号] - YYYY-MM-DD

### Added（新增）
- 新增了 XXX 功能/章节：用途是 YYY

### Changed（变更）
- 修改了 XXX 章节：原因是 YYY，具体变更内容是 ZZZ
- 修改了项目目录结构：将 ABC 目录移至 DEF 位置

### Fixed（修复）
- 修复了 XXX 问题：表现是 YYY，修复方式是 ZZZ

### Deprecated（即将弃用）
- XXX 功能将在下一版本移除：原因是 YYY

### Removed（已移除）
- 移除了 XXX 功能：原因是 YYY

### Security（安全）
- 修复了 XXX 安全漏洞：影响是 YYY
```

### 记录时机

- **修改前**：先在 `[Unreleased]` 部分草拟变更内容
- **修改后**：完善变更描述，添加具体细节和影响范围
- **发布时**：将 `[Unreleased]` 内容移至具体版本号下

### 版本号规则

遵循语义化版本（Semantic Versioning）：

- **主版本号（Major）**：重大架构变更、不兼容的 API 修改
- **次版本号（Minor）**：新增功能或章节，向后兼容
- **修订号（Patch）**：修复问题或微调，向后兼容

### 变更类型说明

| 类型 | 说明 | 示例 |
|------|------|------|
| Added | 新增的功能或章节 | "新增了 `## 变更记录规范` 章节" |
| Changed | 对现有功能或内容的变更 | "修改了 `## 工程原则` 章节，增加了早期返回原则" |
| Deprecated | 即将移除的功能（警告） | "旧的目录结构将在下个版本重构" |
| Removed | 已移除的功能 | "移除了已废弃的 `## 代码审查` 章节" |
| Fixed | 修复的问题 | "修复了模板中目录树生成的 bug" |
| Security | 安全相关的修复 | "修复了依赖包的安全漏洞" |

### 质量标准

每条记录应该：
- **清晰具体**：说明改了什么、为什么改
- **可追溯**：包含足够的上下文信息
- **格式统一**：遵循上述模板
- **及时更新**：修改后立即记录，不要拖延
