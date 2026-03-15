# Changelog

**重要**：本文件是项目变更的**唯一正式记录**。凡是项目的更新，都要统一在本文件里记录。这是项目管理的强制性要求。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.1.0/)。

## [Unreleased]

### Added（新增）

- 在 README 中补充了 Agent Skill 特色功能说明：在"核心特性"中新增"AI 工具集成"条目，在"文档"部分新增 [Agent Skill 使用指南](skills/bensz-channel-devtools/README.md) 链接，介绍通过 Claude Code/Codex CLI 远程管理频道、文章、评论、用户等内容的能力；同步更新了 README_EN.md
- 在 README 中新增"Agent Skill 安装"章节：引导用户通过 [Bensz Skills](https://github.com/huangwb8/skills) 项目安装本项目的 Agent Skill，提供完整的安装步骤，明确说明 `install-bensz-skills --source https://github.com/huangwb8/bensz-channel/tree/main/skills` 命令是在 Claude Code/Codex CLI 中输入的（而非 shell 命令）；同步更新了 README_EN.md
- 在 README 中新增 Star 呼吁章节：在"核心特性"之后、"快速开始"之前添加了友好的 Star 呼吁，说明开发维护不易，鼓励用户点 Star 支持项目；同时嵌入了 Star History Chart 可视化展示项目增长趋势；同步更新了 README_EN.md
- 在 README 中新增"赞助"章节：参考 ChineseResearchLaTeX 项目，在"贡献"章节之后添加赞助说明，说明开发维护需要大量时间和精力，鼓励用户通过赞助支持项目持续发展；嵌入了赞助二维码图片；同步更新了 README_EN.md
- 在 README 的"文档"章节新增项目介绍博客链接：添加了 https://blognas.hwb0307.com/linux/docker/7053 博客文章链接，提供详细的项目介绍与使用体验；同步更新了 README_EN.md

### Changed（变更）

- 调整了后台设置的信息架构：`app/resources/views/admin/site-settings/edit.blade.php`、`app/app/Http/Controllers/Admin/SiteSettingsController.php` 与 `app/app/Support/SiteSettingsManager.php` 现将站点设置与 CDN 设置彻底拆分，站点设置页不再渲染或接收 `cdn_asset_url` 等 CDN 字段，CDN 配置统一收口到独立的 `admin/cdn-settings` 页面；同步补充 `app/tests/Feature/Admin/AdminSiteSettingsTest.php`、`app/tests/Feature/Admin/CdnSettingsTest.php` 与 `docs/开发者文档.md` 回归说明
- 重新定位了项目描述方式：移除所有"类似 QQ 频道"的表述，改为强调核心价值和使用场景；更新了 `README.md`、`README_EN.md`、`AGENTS.md`、`app/config.toml` 中的项目描述，突出"现代化 Web 社区平台"、"三栏式布局设计"、"适合团队协作、知识分享与内容沉淀"等独立特性
- 优化了 README 文档结构：创建了中英双语版本（`README.md` 和 `README_EN.md`），参考 https://github.com/huangwb8/skills 的现代化风格，使用 badges、emoji 和居中标题区增强视觉效果；将技术细节移至 `docs/开发者文档.md`，保持 README 精简并突出核心功能与快速开始
- 优化了测试规范章节：在 `AGENTS.md` 的"测试规范"中新增"交付前强制要求"小节，明确规定每次交付前必须跑通 `scripts/test/` 里的测试，且代码优化后必须同步更新测试代码，确保测试流程与最新代码相协调
- 重构了 README 结构以突出 AI 工具集成特色：将"AI 工具集成"移至"核心特性"列表首位，在"项目简介"中新增"核心亮点"段落强调 AI 驱动的社区运营能力；将"Agent Skill 安装"章节从文档部分提前到"快速开始"之后，扩展为"AI 工具集成（Agent Skill）"独立章节，详细介绍安装步骤和功能特性；同步更新了 README_EN.md
- 新增了文档同步规范章节：在 `AGENTS.md` 中新增"文档同步规范"章节，明确规定 `README_EN.md` 必须与 `README.md` 保持内容对齐（后者变前者变），`docs/开发者文档.md` 必须与最新源代码保持协调，确保文档信息准确性；同时提供了同步检查清单，规范不同变更类型对应的文档更新要求
- 重构了"本地构建镜像"文档位置：将 `README.md` 和 `README_EN.md` 中的"本地构建镜像"详细步骤转移到 `docs/开发者文档.md` 的"Docker 镜像构建"章节，README 中仅保留简短引用；开发者文档中补充了"克隆仓库"步骤，形成完整的构建流程；符合"README 面向普通用户，开发者文档面向开发者"的文档分层原则
- 优化了暗色主题灰阶渐变支持：`app/resources/css/app.css` 现补充 `from-gray-50` 与 `to-white` 的夜间模式覆盖规则，确保后台创建表单等灰阶渐变容器在暗色主题下保持一致的深色视觉；`app/tests/Feature/Static/ThemeStylesheetTest.php` 同步增加回归断言
- 补全了透明蓝色背景的暗色主题覆盖：`app/resources/css/app.css` 现新增 `bg-blue-50/50` 的夜间模式映射，确保管理员 SMTP 配置中的提示小栏在暗色主题下保持正确的强调底色；`app/tests/Feature/Static/ThemeStylesheetTest.php` 同步增加断言
- 优化了账户设置页两步验证初始化区的暗色主题表现：`app/resources/views/settings/account.blade.php` 与 `app/resources/css/app.css` 现将二维码容器、手动密钥卡片与验证确认卡片改为主题感知样式，避免夜间模式下出现大面积浅底；相关回归已补到 `app/tests/Feature/Static/ThemeStylesheetTest.php` 与 `app/tests/Feature/Settings/AccountSettingsTest.php`

### Fixed（修复）

- 修复了 README 快速开始部分引用不存在文件的问题：此前文档中引用了 `self/remote.env` 和 `self/docker-compose.yml`，但这些是私有配置文件不在 Git 仓库中；现在改为使用 `config/.env.example` 作为配置模板，并提供完整的配置说明，确保用户克隆仓库后能够正常部署
- 修复了频道管理页夜间模式下新增频道表单发白的问题：此前 `from-gray-50 → to-white` 渐变未被暗色主题覆盖，导致表单背景保持浅色、白色标签对比度异常；现在暗色模式会自动映射到深色渐变，频道管理与同类后台表单显示恢复正常
- 修复了首次仅保存 CDN 设置时站点配置写入失败的问题：`app/app/Support/SiteSettingsManager.php` 现会为未显式提交的 `article_image_max_mb` 回退到运行时默认值，避免 `site_settings` 首次插入时触发非空约束；`app/tests/Feature/Admin/CdnSettingsTest.php` 同步增加断言覆盖该回归

## [1.38.0] - 2026-03-15

### Added（新增）

- 新增了评论回复树与评论线程订阅：`app/database/migrations/2026_03_15_100000_add_threading_fields_to_comments_table.php`、`app/database/migrations/2026_03_15_100200_create_comment_subscriptions_table.php`、`app/app/Support/CommentSubscriptionManager.php` 与 `app/resources/views/articles/partials/comment-item.blade.php` 现支持按评论回复评论、展示嵌套讨论，并允许用户对单条评论线程暂停或恢复后续提醒
- 新增了评论回复邮件通知：`app/database/migrations/2026_03_15_100100_add_comment_reply_preferences_to_user_notification_preferences_table.php`、`app/app/Support/CommentReplyNotifier.php` 与 `app/app/Notifications/CommentReplyNotification.php` 现会在有人回复你发布或订阅的评论线程时发送邮件提醒，且全局默认开启
- 新增了多风格默认头像与头像上传：`app/database/migrations/2026_03_15_100300_add_avatar_fields_to_users_table.php`、`app/app/Support/AvatarPresenter.php`、`app/resources/views/components/user-avatar.blade.php` 与 `app/resources/views/settings/account.blade.php` 现支持多套站内默认头像风格、`JPG/PNG` 自定义上传头像（`<=1MB`）以及外链头像三种来源

### Changed（变更）

- 优化了文章详情页评论区：`app/app/Support/CommunityViewData.php` 与 `app/resources/views/articles/show.blade.php` 现按评论树渲染讨论，并在已登录用户界面提供“回复这条评论”“暂停/开启此评论后续提醒”等交互
- 优化了用户资料与头像渲染链路：`app/app/Support/UserAccountManager.php`、`app/app/Http/Controllers/AccountSettingsController.php`、`app/resources/views/layouts/app.blade.php`、`app/resources/views/admin/users/index.blade.php` 与认证解析器现统一处理 `generated/external/uploaded` 三类头像来源，站内头像展示全部改为复用 `x-user-avatar`
- 优化了数据备份与版本元信息：`app/app/Support/DataBackupManager.php`、`app/tests/Feature/Admin/AdminDataBackupRestoreTest.php`、`docs/开发者文档.md` 与 `app/config.toml` 已同步纳入评论线程订阅、评论回复提醒与头像来源字段，并将项目版本推进到 `1.38.0`

### Fixed（修复）

- 修复了评论只能单层平铺、无法围绕某条评论继续讨论的问题：现在任意用户都可以对任意可见评论继续回复，形成稳定的评论线程
- 修复了用户无法关闭单条高热评论后续提醒的问题：现在用户既能在全局订阅设置中关闭评论回复邮件，也能在评论区按线程暂停或恢复提醒
- 修复了默认首字母头像样式单一且观感较弱的问题：现在默认头像改为多风格可选，并支持用户上传自己的头像图片

## [1.37.0] - 2026-03-15

### Added（新增）

- 新增了后台评论管理页：`app/app/Http/Controllers/Admin/CommentController.php`、`app/resources/views/admin/comments/index.blade.php` 与 `app/routes/web.php` 现提供 `admin/comments` 入口，支持按评论内容 / 用户 / 文章 / 显隐状态筛选，并可直接查看、隐藏、恢复和删除评论
- 新增了管理员活动邮件通知：`app/app/Support/AdminActivityNotifier.php`、`app/app/Notifications/AdminNewUserRegisteredNotification.php` 与 `app/app/Notifications/AdminCommentPostedNotification.php` 现会在新用户首次注册和用户发布评论时向 `ADMIN_EMAIL` 发送通知邮件
- 新增了评论管理与管理员通知回归测试：`app/tests/Feature/Admin/AdminCommentManagementTest.php`、`app/tests/Feature/Notifications/AdminActivityNotificationTest.php` 与相关导航 / DevTools / API 测试已覆盖后台入口、评论操作副作用和管理员邮件发送链路

### Changed（变更）

- 优化了后台管理导航：`app/resources/views/layouts/app.blade.php`、`app/resources/views/admin/articles/index.blade.php`、`app/resources/views/admin/channels/index.blade.php`、`app/resources/views/admin/users/index.blade.php`、`app/resources/views/admin/site-settings/edit.blade.php` 与 `app/resources/views/admin/devtools/index.blade.php` 现统一加入“评论管理”快捷入口，并将其放到“管理文章”和“管理频道”之间
- 优化了评论治理链路：`app/app/Support/CommentModerationService.php`、`app/app/Http/Controllers/CommentController.php`、`app/app/Http/Controllers/Api/Vibe/CommentController.php`、`app/app/Models/Article.php` 与 `app/app/Support/ManagedUserService.php` 现统一按“可见评论数”刷新 `articles.comment_count`，并在评论显隐 / 删除后触发静态页增量重建
- 更新了项目文档与版本号：`README.md`、`README_EN.md`、`docs/开发者文档.md` 与 `app/config.toml` 已同步补充评论管理、管理员 SMTP 活动通知说明，并将项目版本推进到 `1.37.0`

### Fixed（修复）

- 修复了后台缺少评论治理闭环的问题：此前管理员只能间接处理评论，无法集中筛选、隐藏或删除用户评论；现在后台已提供独立评论管理入口和完整操作闭环
- 修复了管理员无法及时获知新用户注册和评论动态的问题：此前 SMTP 仅服务于订阅通知和验证码，管理员不会收到关键运营事件提醒；现在相关事件会自动投递到管理员邮箱

## [1.36.0] - 2026-03-12

### Added（新增）

- 新增了 CDN 运行态控制：`app/database/migrations/2026_03_12_150000_add_cdn_runtime_state_to_site_settings_table.php`、`app/app/Support/SiteSettingsManager.php` 与 `app/app/Http/Controllers/Admin/CdnSettingsController.php` 现支持将 CDN 草稿配置与已应用运行态分离，管理员可手动点击“应用 CDN”与“停止 CDN”控制是否启用
- 新增了 CDN 详细工作日志：`app/app/Support/Cdn/CdnWorkLogManager.php`、`app/database/migrations/2026_03_12_151000_add_details_to_cdn_sync_logs_table.php` 与 `app/resources/views/admin/cdn-settings/index.blade.php` 现记录保存、测试、应用、停止、构建、同步、清理等工作过程，并保留详细原因与配置摘要
- 新增了 CDN 运行态与失败细节回归测试：`app/tests/Feature/Admin/CdnSettingsTest.php` 与 `app/tests/Unit/Support/CdnSyncServiceTest.php` 现覆盖“保存不立即生效”“手动应用/停止”“测试失败原因入日志”“同步细节入日志”等关键场景

### Changed（变更）

- 优化了 CDN 后台工作流：`app/resources/views/admin/cdn-settings/index.blade.php` 现将“同步日志”升级为“工作日志”，展示当前运行状态，并让连接测试直接读取页面草稿表单内容
- 优化了静态构建与同步可观测性：`app/app/Support/StaticPageBuilder.php`、`app/app/Support/Cdn/CdnSyncService.php` 与 `app/app/Support/Cdn/SyncResult.php` 现会在构建、同步、远程清理时产出更完整的过程细节和错误原因
- 更新了 S3 依赖与文档：`app/composer.json`、`app/composer.lock`、`README.md`、`README_EN.md`、`docs/CDN配置指南.md`、`docs/开发者文档.md` 与 `app/config.toml` 已同步补齐对象存储驱动依赖、使用说明与版本号 `1.36.0`

### Fixed（修复）

- 修复了对象存储 CDN 连接时缺少 `league/flysystem-aws-s3-v3` 依赖导致 `Class "League\Flysystem\AwsS3V3\PortableVisibilityConverter" not found` 的问题；现在构建出的应用镜像会包含完整 S3/Flysystem 运行时依赖
- 修复了 CDN 配置“保存即生效”导致管理员无法安全演练配置的问题；现在保存只更新草稿，必须显式点击“应用 CDN”才会切换线上运行态
- 修复了 CDN 出错时日志过于粗糙的问题；现在测试失败、应用失败、构建失败、同步失败等场景都会在后台工作日志中展示具体原因

## [1.35.0] - 2026-03-12

### Added（新增）

- 新增 Markdown 视频粘贴上传能力：`app/app/Http/Controllers/VideoUploadController.php` 与 `app/routes/web.php` 现提供受鉴权保护的 `/uploads/videos` 端点，支持文章与评论编辑器自动上传不大于 500MB 的 MP4/WebM/Ogg 视频
- 新增视频播放器渲染与回归测试：`app/app/Support/MarkdownRenderer.php` 现会把独立视频链接安全转换为 `nextcloud-video` 播放器，`app/tests/Unit/Support/MarkdownRendererTest.php` 与 `app/tests/Feature/Uploads/VideoUploadTest.php` 同步锁定渲染和上传行为

### Changed（变更）

- 优化了 Markdown 粘贴上传前端：`app/resources/js/markdown-image-upload.js`、`app/resources/views/admin/articles/form.blade.php` 与 `app/resources/views/articles/show.blade.php` 现统一支持图片/视频粘贴上传、状态提示与双击全屏播放
- 优化了媒体显示与容器上传限制：`app/resources/css/app.css` 新增 `nextcloud-video` 播放器样式，`docker/nginx/default.conf` 与 `docker/web/php-upload.ini` 现将请求体与 PHP 上传上限提升到可承接 500MB 视频
- 更新了配置与文档：`app/config.toml`、`app/config/community.php`、`app/.env.example`、`config/.env.example`、`README.md`、`README_EN.md` 与 `docs/开发者文档.md` 现新增 `VIDEO_UPLOAD_MAX_MB` 说明，并将项目版本推进到 `1.35.0`

### Fixed（修复）

- 修复了 Markdown 编辑器只能粘贴图片、无法直接插入站内视频播放器的问题；现在粘贴视频后会自动上传并以稳定的 16:9 播放器形式渲染
- 修复了容器层上传上限低于业务需求的问题；此前超过约 100MB 的视频会在 Nginx/PHP 入口被提前拒绝，现在与 500MB 视频上传能力保持一致

## [1.34.5] - 2026-03-11

### Added（新增）

- 新增文章图片上传上限配置：`app/database/migrations/2026_03_11_150000_add_article_image_max_mb_to_site_settings_table.php` 新增可覆盖的站点参数，管理员可在站点设置中调整写文章时的图片大小限制（默认 50MB）
- 新增文章图片上传上限回归测试：`app/tests/Feature/Uploads/ImageUploadTest.php` 现覆盖文章上传大小限制的可配置性

### Changed（变更）

- 优化站点设置表单与配置加载：`app/resources/views/admin/site-settings/edit.blade.php` 增加上传上限字段，`app/app/Support/SiteSettingsManager.php` 支持保存并注入运行时配置，`app/config/community.php` 与 `app/config.toml` 增加默认值
- 调整 Web 容器上传限制：`docker/nginx/default.conf` 与 `docker/web/php-upload.ini` 现支持 100MB 上限，确保管理员配置不会被容器层拦截
- 更新文档与版本号：同步更新 `README.md`、`README_EN.md`、`docs/开发者文档.md`，并将 `app/config.toml` 版本推进到 `1.34.5`

### Fixed（修复）

- 修复了文章图片上传上限无法在后台调整的问题：现在站点设置可直接控制文章编辑器图片上传大小，并在验证层严格执行

## [1.34.4] - 2026-03-11

### Added（新增）

- 新增了全站源码仓库页脚入口：`app/resources/views/partials/site-footer.blade.php` 现统一渲染站点说明与 GitHub 源码链接，主站与登录页都会显示同一套优雅脚注
- 新增了源码页脚回归测试：`app/tests/Feature/Auth/AuthPagesTest.php` 与 `app/tests/Feature/Routing/PublicUrlRoutingTest.php` 现锁定登录页和首页必须展示“源代码”链接，`app/tests/Feature/Static/ThemeStylesheetTest.php` 现校验页脚主题样式存在

### Changed（变更）

- 优化了全站布局的页脚实现：`app/resources/views/layouts/app.blade.php` 与 `app/resources/views/layouts/auth.blade.php` 现复用统一页脚 partial，避免不同界面脚注风格分裂
- 优化了页脚视觉样式：`app/resources/css/app.css` 现新增 `site-footer` 系列样式，使用主题变量实现亮暗模式下一致、克制且可点击的 GitHub 仓库入口
- 优化了站点配置：`app/config/community.php` 与 `app/config.toml` 新增仓库地址配置项，页脚链接可统一维护；同时将项目版本推进到 `1.34.4`

### Fixed（修复）

- 修复了 Web 界面脚注缺少源码仓库入口的问题；现在用户可通过统一、优雅的“源代码”链接直接跳转到项目 GitHub 仓库

## [1.34.3] - 2026-03-11

### Added（新增）

- 新增了频道管理界面的主题回归校验：`app/tests/Feature/Admin/AdminChannelManagementTest.php` 现锁定页面会渲染专用主题类，`app/tests/Feature/Static/ThemeStylesheetTest.php` 现校验频道管理的面板、开关与拖拽高亮样式已接入主题变量

### Changed（变更）

- 优化了频道管理页的主题实现：`app/resources/views/admin/channels/index.blade.php` 现为新增频道表单、排序提示条、拖拽手柄、顶栏开关、空状态与系统提示统一挂载语义化样式类，避免继续依赖未完整覆盖的浅色 utility
- 优化了 `app/resources/css/app.css`：新增 `channel-admin-*` 组件样式，统一使用站点主题变量驱动亮色/暗色外观，并为拖拽排序高亮与开关交互提供更稳定的视觉反馈
- 更新了 `app/config.toml`：将项目版本推进到 `1.34.3`

### Fixed（修复）

- 修复了频道管理界面在夜间模式下部分区域仍显示浅色渐变、浅灰开关底板和不协调拖拽高亮的问题；现在频道管理页在暗色主题下保持一致的面板层级与交互反馈

## [1.34.2] - 2026-03-11

### Added（新增）

- 新增了 Docker 图片上传冒烟回归：`scripts/test/docker-smoke.sh` 现会在管理员登录后向 `/uploads/images` 提交一张约 2MB 的 JPG，锁定文章编辑器图片上传不再被 Web 容器提前拒绝

### Changed（变更）

- 优化了 Web 容器上传限制配置：`docker/nginx/default.conf` 现显式设置 `client_max_body_size 12m`，`docker/web/php-upload.ini` 现将 PHP 的 `upload_max_filesize` 调整到 `10M`、`post_max_size` 调整到 `12M`，与应用层 10MB 校验保持一致
- 更新了 `docker/web/Dockerfile` 与 `app/config.toml`：镜像构建时会复制上传配置，并将项目版本推进到 `1.34.2`

### Fixed（修复）

- 修复了文章编辑器中粘贴较大的截图/JPG 时始终提示上传失败的问题：根因是 Nginx 默认请求体上限过小，运行中的 `/uploads/images` 实际返回了 `413 Payload Too Large`；现在大图粘贴上传可正常通过到 Laravel 校验与存储流程

## [1.34.1] - 2026-03-11

### Added（新增）

- 新增了文章详情页快捷编辑回归测试与前端粘贴上传单测：`app/tests/Feature/Admin/AdminArticleManagementTest.php` 现锁定管理员在文章查看页必须可直接进入编辑，`app/resources/js/markdown-image-upload.test.js` 现校验剪贴板图片回退提取、JPG 文件名补全与上传请求格式

### Changed（变更）

- 优化了 Markdown 图片粘贴上传实现：`app/resources/js/markdown-image-upload.js` 现独立封装粘贴逻辑，优先读取 `clipboardData.items`，必要时回退到 `clipboardData.files`，并为剪贴板产生的无扩展名图片自动补齐稳定文件名；同时移除了手工指定 multipart 请求头，改由浏览器自动附带 boundary，提升 Ctrl+V 粘贴图片兼容性
- 优化了文章详情页管理员操作路径：`app/resources/views/articles/show.blade.php` 现在为管理员直接显示“编辑文章”入口，无需再回到文章管理页跳转
- 更新了 `app/config.toml`：将项目版本推进到 `1.34.1`

### Fixed（修复）

- 修复了文章编辑器中 Ctrl+V 粘贴图片偶发上传失败的问题：根因是部分浏览器/剪贴板场景只暴露 `clipboardData.files`，且对 `FormData` 手工指定 `multipart/form-data` 头会破坏 boundary；现在 JPG 等剪贴板图片可稳定上传并自动插入 Markdown 链接

## [1.34.0] - 2026-03-11

### Added（新增）

- 新增了双模式 CDN 架构：`app/app/Enums/CdnMode.php`、`app/config/cdn.php`、`app/app/Support/Cdn/` 现支持回源型 CDN 与对象存储型 CDN 两种运行模式，并提供统一的配置校验、URL 生成与对象存储适配层
- 新增了后台 `CDN 设置` 页面：`app/app/Http/Controllers/Admin/CdnSettingsController.php` 与 `app/resources/views/admin/cdn-settings/index.blade.php` 提供模式切换、对象存储凭证配置、连接测试、差异预览、立即同步、远程清空与同步日志查看能力
- 新增了 CDN 同步链路：`app/app/Console/Commands/SyncCdnFiles.php`、`app/app/Jobs/SyncCdnFilesJob.php`、`app/app/Models/CdnSyncLog.php` 与相关迁移文件现支持手动同步、队列同步、构建后自动同步和持久化日志
- 新增了 CDN 回归测试与使用文档：新增 `app/tests/Feature/Admin/CdnSettingsTest.php`、`app/tests/Feature/Static/CdnSyncTriggerTest.php`、`app/tests/Unit/Support/CdnSyncServiceTest.php` 与 `docs/CDN配置指南.md`

### Changed（变更）

- 优化了站点设置持久化逻辑：`app/app/Support/SiteSettingsManager.php` 现支持部分字段安全更新，避免独立 CDN 页面保存时误清空站点名称、认证方式等无关配置；同时新增对象存储相关字段与运行时配置覆盖
- 优化了静态构建与资源分发链路：`app/app/Support/StaticPageBuilder.php` 在对象存储模式且启用自动同步时，会在构建完成后派发 CDN 同步任务，保证静态资源与公开域名输出保持一致
- 更新了 `README.md`、`README_EN.md`、`docs/开发者文档.md`、`app/.env.example`、`config/.env.example` 与 `app/config.toml`：补充双模式 CDN、对象存储环境变量、后台入口与同步命令说明，并将项目版本推进到 `1.34.0`

### Fixed（修复）

- 修复了此前 CDN 仅支持单一 `cdn_asset_url` 覆盖的问题：现在既能保持现有回源型 CDN 行为不变，也能稳定支持对象存储同步、构建后自动推送、凭证加密存储与同步状态可观测性

## [1.32.0] - 2026-03-11

### Added（新增）

- 新增了后台用户创建入口：管理员现可在 `admin/users` 直接手动创建新用户，填写昵称、邮箱、手机号、头像、简介、角色与初始密码；当站点关闭邮箱验证码登录时，系统会强制要求设置初始密码，避免产生无法登录的账号
- 新增了后台用户封禁能力：管理员现可在 `admin/users` 对普通用户执行 1 天、3 天、7 天、30 天、永久或自定义截止时间的封禁，并可随时解除封禁；同时为用户表增加封禁时间字段与相关回归测试

### Changed（变更）

- 优化了登录与鉴权拦截链路：`app/routes/web.php`、`app/app/Http/Middleware/EnsureNotBanned.php`、`app/app/Http/Controllers/Auth/LoginController.php` 与 `app/app/Http/Controllers/Auth/SocialLoginController.php` 现统一阻止已封禁账号通过邮箱验证码、邮箱密码、扫码、2FA 挑战继续进入系统，并拦截后台与其他需要鉴权的站内功能
- 优化了后台用户管理页交互：`app/resources/views/admin/users/index.blade.php` 现统一展示新增用户表单、封禁状态徽标、封禁时长选择、自定义截止时间与解除封禁入口，并在表单校验失败时自动展开对应用户卡片
- 更新了 `app/config.toml` 与 `README.md`：将项目版本推进到 `1.32.0`，并同步补充后台手动创建用户与按时长封禁用户的使用说明

### Fixed（修复）

- 修复了后台缺少手动建号与封禁闭环的问题：此前管理员无法在 `admin/users` 直接创建账号，也无法在后台按时间封禁普通用户；现在相关入口、数据字段、验证规则与回归测试已全部补齐
- 修复了已封禁账号仍可能继续推进登录流程的风险：此前在两步验证挑战、扫码状态轮询等边角场景下，封禁状态没有被完整复核；现在这些链路会在进入最终登录态前再次校验并阻断

## [1.31.0] - 2026-03-11

### Added（新增）

- 新增了后台核心数据备份与恢复入口：管理员现在可在 `admin/site-settings` 下载包含站点设置、邮件配置、用户资料、频道、文章、评论与 DevTools 密钥等核心数据的 `tar.gz` 备份，并可上传备份包执行恢复
- 新增了核心数据恢复回归测试：`app/tests/Feature/Admin/AdminDataBackupRestoreTest.php` 覆盖“先备份、再篡改、再恢复”的完整回环，锁定备份归档与恢复导入在关键模型上的正确性

### Changed（变更）

- 优化了后台站点设置页：`app/resources/views/admin/site-settings/edit.blade.php` 现新增“数据备份与恢复”区域，展示核心表备份范围、敏感数据提醒，并在恢复前通过弹窗进行二次确认
- 优化了后台站点设置控制器与路由：`app/app/Http/Controllers/Admin/SiteSettingsController.php`、`app/routes/web.php` 现接入备份下载与恢复上传流程；恢复完成后会主动清理登录会话并要求管理员重新登录核对配置
- 更新了 `app/config.toml` 与 `README.md`：将项目版本推进到 `1.31.0`

### Fixed（修复）

- 修复了后台缺少可迁移核心数据归档能力的问题：此前管理员无法从界面导出或恢复站点核心数据；现在系统会以结构化 `tar.gz` 归档备份数据库中的关键业务表，并在恢复时同步清理易失状态数据，降低配置错乱与脏会话残留风险

## [1.30.0] - 2026-03-11

### Added（新增）

- 新增了可选两步验证能力：登录用户现在可在 `app/resources/views/settings/account.blade.php` 中自主开启或关闭基于 TOTP 的 2FA，并在首次开启或重置时获得一次性恢复码
- 新增了两步验证登录链路与回归测试：`app/resources/views/auth/two-factor.blade.php`、`app/tests/Feature/Auth/TwoFactorAuthenticationTest.php` 覆盖密码登录、邮箱验证码登录、社交身份解析与扫码登录状态轮询等关键场景
- 新增了用户两步验证持久化字段：`app/database/migrations/2026_03_11_015500_add_two_factor_columns_to_users_table.php` 为用户表补充密钥、恢复码和启用时间，用于支撑稳定的 2FA 开关能力

### Changed（变更）

- 优化了登录完成链路：`app/app/Http/Controllers/Auth/LoginController.php`、`app/app/Http/Controllers/Auth/SocialLoginController.php` 现统一接入待完成 2FA 的挑战会话逻辑，避免不同登录方式出现绕过或行为不一致
- 优化了账户安全配置体验：`app/app/Http/Controllers/AccountSettingsController.php` 与 `app/app/Support/TwoFactorAuthenticationManager.php` 现负责生成绑定二维码、验证动态码、轮换恢复码和关闭 2FA 的完整流程
- 更新了 `app/config.toml` 与 `README.md`：将项目版本推进到 `1.30.0`

### Fixed（修复）

- 修复了站点缺少用户级二次验证保护的问题：此前任意登录方式在主认证成功后会直接建立完整登录态；现在当用户已开启 2FA 时，系统会先进入独立挑战页，只有动态验证码或恢复码验证通过后才真正完成登录

## [1.29.2] - 2026-03-11

### Added（新增）

- 新增了静态 HTML 缓存策略回归测试：`app/tests/Feature/Static/StaticHtmlCachingConfigTest.php` 锁定首页、频道页与文章页的 Nginx 入口必须按 Cookie 区分缓存语义，并禁止浏览器复用过期前的游客版 HTML

### Changed（变更）

- 优化了静态访客页的 Nginx 缓存策略：`docker/nginx/default.conf` 现对首页、频道页和文章页返回 `Cache-Control: no-cache, no-store, must-revalidate` 与 `Vary: Cookie`，保留磁盘静态页加速的同时，避免登录前后复用错误的 HTML 视图
- 更新了 `app/config.toml` 与 `README.md`：将项目版本推进到 `1.29.2`

### Fixed（修复）

- 修复了进入文章正文后右上角偶发显示“登录 / 注册”、评论区误判为未登录的问题：根因是浏览器缓存了静态生成的游客版文章页，登录后同一路径偶发直接复用旧 HTML；现在请求会重新到服务端判定 Cookie，再稳定返回正确的登录态页面

## [1.29.1] - 2026-03-10

### Changed（变更）

- 优化了顶部导航右上角的用户菜单交互：`app/resources/views/layouts/app.blade.php` 改为显式的可点击下拉菜单结构，补齐 `aria-haspopup`、`aria-expanded` 与 `role="menu"` / `role="menuitem"` 等无障碍语义，移动端与桌面端统一使用同一套交互模型

### Fixed（修复）

- 修复了移动模式下登录后右上角用户/管理按钮点击无反应的问题：`app/resources/js/app.js` 现负责菜单展开、收起、点击外部关闭与 `Escape` 关闭，管理员入口和普通用户入口在触屏设备上均可稳定使用
- 修复了该问题缺少结构化回归保护的问题：新增 `app/tests/Feature/Admin/AdminNavigationTest.php` 断言，锁定用户菜单必须以可点击下拉结构渲染，避免后续再次退化为仅依赖 hover 的实现

## [1.29.0] - 2026-03-10

### Added（新增）

- 新增了粘贴图片上传能力：后台文章编辑器与前台评论区现在都支持直接通过 `Ctrl+V` 粘贴图片，浏览器会自动上传图片并把 Markdown 图片链接插回输入框
- 新增了图片上传接口与回归测试：`app/app/Http/Controllers/ImageUploadController.php`、`app/tests/Feature/Uploads/ImageUploadTest.php` 用于锁定登录鉴权、图片校验和 `/storage/media/...` 路径生成行为
- 新增了实现计划 `docs/plans/2026-03-10-clipboard-image-upload.md`：沉淀文章/评论粘贴上传、卷托管与 Docker 验收步骤

### Changed（变更）

- 优化了文章编辑器与评论输入框交互：`app/resources/views/admin/articles/form.blade.php`、`app/resources/views/articles/show.blade.php` 现展示粘贴上传提示与上传状态，`app/resources/js/app.js` 新增统一的剪贴板图片上传与 Markdown 插入逻辑
- 优化了内容展示稳定性：`app/resources/css/app.css` 为 Markdown 图片补齐响应式样式，并为上传状态增加成功 / 失败 / 上传中的视觉反馈
- 更新了 `README.md` 与 `app/config.toml`：补充 Ctrl+V 粘贴图片能力、图片持久化备份路径说明，并将项目版本推进到 `1.29.0`

### Fixed（修复）

- 修复了文章正文和评论区只能手动贴外链图片、无法直接粘贴图片并持久化托管的问题；现在上传文件统一落到 `./data/web/storage/app/public/media/`，配合现有 Docker volume 可在重部署后继续正常访问

## [1.28.3] - 2026-03-10

### Added（新增）

- 新增了移动端频道切换抽屉结构回归测试 `app/tests/Feature/Channels/TopNavChannelVisibilityTest.php`：锁定抽屉必须渲染在粘性头部之外，避免移动端浏览器再次出现固定层渲染异常

### Changed（变更）

- 优化了移动端频道切换抽屉的渲染结构与交互状态：`app/resources/views/layouts/app.blade.php` 现将抽屉从带模糊效果的 `top-nav` 外移到页面根层，`app/resources/js/app.js` 改为统一状态同步并移除易导致双触发的 `touchend` 监听，关闭后不再残留异常遮罩或卡死状态
- 优化了 `app/resources/css/app.css` 中移动端抽屉容器的隔离与滚动行为：补充 `isolation` 与 `overscroll-behavior`，提升移动端层叠稳定性

### Fixed（修复）

- 修复了移动模式下点击左上角频道选择按钮后抽屉界面异常、关闭后仍残留异常显示并影响继续操作的问题

### Fixed（修复）

- 修复了 README.md 中版本号管理章节引用过时 `config.yaml` 的问题：现已更新为正确的 `app/config.toml` 路径，并同步更新版本号示例为当前版本 `1.28.2`
- 修复了 GitHub Actions 工作流 `create-release.yml` 中仍然引用 `config.yaml` 的问题：工作流名称和提示信息已更新为 `config.toml`
- 修复了 README.md 中 Docker 镜像自动发布工作流的调度时间描述错误：从错误的 `0 */12 * * *`（每 12 小时）更正为实际的 `0 18 * * *`（UTC 每天 18:00，即北京时间 02:00）
- 修复了 GitHub Actions 工作流 `check-version-sync.yml` 中两处仍然引用 `config.yaml` 的问题：工作流输出和 PR 评论中的提示信息已更新为 `config.toml`
- 修复了 `docs/version-management.md` 文档中多处过时引用：将 `config.yaml` 更新为 `app/config.toml`，将"每 12 小时"更正为"每天北京时间 02:00"

### Changed（变更）

- 优化了 RSS 按钮复制提示的样式：从直接替换按钮内容改为独立的 Toast 提示框，使用现代渐变背景、流畅动画和更清晰的视觉反馈，提升用户体验
  - 修改文件：`app/resources/js/app.js`（重构复制逻辑，新增 `showCopyToast` 函数）
  - 修改文件：`app/resources/css/app.css`（新增 `.copy-toast` 系列样式和 `.rss-copy-success`/`.rss-copy-error` 按钮状态样式）

## [1.28.2] - 2026-03-10

### Added（新增）

- 新增了夜间模式样式回归测试 `app/tests/Feature/Static/ThemeStylesheetTest.php`：锁定主站与登录页样式表中的关键 dark override，避免夜间模式覆盖回退

### Changed（变更）

- 优化了 `app/resources/css/app.css`：统一补齐深色主题变量、组件状态色与常用 Tailwind 实用类的深色覆盖，首页、频道页、文章页、设置页与后台管理页在夜间模式下保持一致的深色基调
- 优化了 `app/resources/css/auth.css`：登录页、登录方式切换器、输入框与社交登录入口在夜间模式下改为高对比深色视觉，不再保留浅色玻璃卡片与浅色表单底板

### Fixed（修复）

- 修复了夜间模式开启后页面背景、卡片、提示框、表单和局部按钮仍大量显示浅色样式的问题

## [1.28.1] - 2026-03-10

### Added（新增）

- 新增了 `skills/bensz-channel-devtools` 的专用回归测试入口：`scripts/test/devtools-skill.sh` 与 `scripts/test/test_bensz_channel_devtools.py` 现会自动校验 DevTools CLI 的最新参数映射、用户删除命令与诊断失败返回码

### Changed（变更）

- 优化了 `skills/bensz-channel-devtools` 与当前代码对齐度：CLI 现支持频道顶栏显隐、文章置顶/精华/发布时间/主频道切换、用户头像更新与普通用户删除，并补齐仓库内 `--env ./self/remote.env` 的推荐用法说明
- 更新了 `scripts/test/all.sh`、`scripts/test/doctor.sh` 与 `scripts/test/README.md`：将 DevTools skill 回归纳入统一自动化验证闭环，并显式检查 `python3` 运行环境

### Fixed（修复）

- 修复了 `skills/bensz-channel-devtools/scripts/client.py` 中 `doctor` 在 heartbeat 返回非 200 时仍然报成功的问题，现已正确返回失败，避免 Docker 重部署后的远程审查出现误判
- 修复了 `app/tests/Feature/Auth/AuthPagesTest.php` 对旧登录页文案 `Better Auth` 的过时断言，现改为校验当前界面的稳定提示文案，恢复全量回归通过

## [1.28.0] - 2026-03-10


### Added（新增）

- 新增了后台文章批量删除能力：`admin/articles` 现支持当前页多选、全选、清空选择与批量删除，并补充了对应回归测试，确保批量操作可稳定执行

### Changed（变更）

- 更新了 AGENTS.md 版本号管理规则：明确项目版本号应反映项目本身的实际变动（功能、修复、优化等），而非依赖包的版本变化

- 优化了 `admin/articles` 的交互设计与静态页重建链路：文章卡片新增作者/评论信息与选中态反馈，批量删除改为聚合删除后一次性触发静态页增量更新，减少重复重建并提升稳定性

- 优化了 admin/devtools 页面的 API 接入信息显示：
  - 将 "Base URL" 改为 "环境变量配置"，直接显示完整的环境变量设置格式 `BENSZ_CHANNEL_URL=http://localhost:6542`
  - 添加了警告提示，明确告诉用户只需配置基础 URL，不要包含 `/api/vibe` 路径
  - 新增了 "API 端点前缀" 字段，显示 `/api/vibe`，让用户清楚地理解实际的 API 路径结构
  - 避免了用户误配置 `BENSZ_CHANNEL_URL=http://localhost:6542/api/vibe` 的问题
  - 修改文件：`app/resources/views/admin/devtools/index.blade.php`

- 优化了 admin/channels 频道管理页面的"新增频道"表单 UI 设计和交互体验：
  - 重构了表单布局，从 7 列密集网格改为响应式分组布局（2 列基础信息 + 3 列视觉元素），提升可读性和移动端体验
  - 新增了颜色和图标的实时预览功能，用户输入时即时显示效果
  - 添加了清晰的字段标签和必填标识（红色星号）
  - 优化了表单字段的间距和视觉层次感
  - 改进了渐变背景和阴影效果，增强现代感
  - 保持现有频道列表（拖拽排序部分）不变
  - 所有功能测试通过，确保不破坏现有功能

### Added（新增）

- 新增了固定长度公开标识生成器 `app/app/Support/PublicIdGenerator.php` 与模型能力 `app/app/Models/Concerns/HasPublicId.php`：为频道和文章生成不可变的 16 位十六进制 `public_id`，用于稳定公开链接
- 新增了异步静态构建任务 `app/app/Jobs/ProcessStaticSiteBuildJob.php`：支持将游客静态页重建放入队列后台执行，并通过去重与互斥锁避免重复并发构建
- 新增了静态构建优化回归测试 `app/tests/Feature/Static/StaticBuildOptimizationTest.php`：覆盖增量重建与命令异步调度行为
- 新增了 Docker 镜像构建脚本 `scripts/build.sh`：支持本地缓存模式（默认）和联网模式，通过参数化控制构建行为，避免每次重建都联网下载依赖
- 新增了构建文档 `scripts/BUILD.md`：详细说明构建模式、缓存管理、故障排查和最佳实践

### Changed（变更）

- 更新了频道、文章与频道 RSS 的公开 URL 方案：`app/app/Models/Channel.php`、`app/app/Models/Article.php`、`app/routes/web.php` 相关链路现默认使用固定长度 `public_id` 生成链接，同时继续兼容旧 slug 与数字 ID 访问并自动 301 跳转到新规范 URL
- 优化了静态站与 DevTools 兼容层：`app/app/Support/StaticPageBuilder.php`、`app/app/Console/Commands/BuildStaticSite.php`、`app/app/Http/Controllers/Api/Vibe/ChannelController.php`、`app/app/Http/Controllers/Api/Vibe/ArticleController.php` 现统一识别 `public_id` / slug / 数字 ID，保证增量构建、API 管理和旧链接迁移稳定工作
- 更新了 `README.md` 与 `app/config.toml`：补充固定长度公开链接说明，并将项目版本推进到 `1.27.0`
- 优化了 `app/app/Support/StaticPageBuilder.php`：全量构建改为分块加载，Gzip 压缩级别改为可配置，并新增基于目标页的增量重建、构建产物哈希跳过与旧静态路径清理能力
- 优化了后台与 DevTools 的静态重建链路：文章与评论更新默认走增量重建，频道/站点/用户等结构性修改继续走全量重建，兼顾正确性与性能
- 优化了 Docker 编排与回归脚本：`docker-compose.yml` 现在显式复用 `./scripts/build.sh` 产出的镜像，并新增 `worker` 队列消费者服务；`scripts/test/docker-redeploy.sh` 改为遵循先构建再启动的项目规范
- 更新了 `README.md` 与 `app/config.toml`：补充静态站点异步队列、增量构建和新的 Docker 重部署流程说明，并将项目版本推进到 `1.26.0`
- 优化了 Docker 构建流程：修改 `docker/web/Dockerfile` 和 `auth-service/Dockerfile`，支持通过 BuildKit 缓存挂载使用本地缓存目录，大幅提升构建速度并支持离线构建；缓存目录默认为项目根目录的 `.cache/`，可通过 `CACHE_BASE_DIR` 环境变量自定义，确保 fork 用户开箱即用
- 优化了构建脚本 `scripts/build.sh`：缓存目录从硬编码路径改为默认使用项目内 `.cache/` 目录，支持通过 `CACHE_BASE_DIR` 环境变量自定义，提升跨平台兼容性和 fork 友好性
- 更新了 `AGENTS.md`：新增"项目环境变量"章节，定义 `CHANNEL_CACHE_PATH` 作为第三方包统一托管目录的标准变量名；新增"Docker 镜像构建规范"章节，详细说明构建模式、缓存目录结构（项目内 `.cache/` 和开发者专用 `${CHANNEL_CACHE_PATH}`）、构建流程、修改 Dockerfile 的规范和与 docker-compose 的集成方式
- 更新了 `scripts/BUILD.md`：更新缓存目录路径为项目内 `.cache/` 目录，添加自定义缓存目录的说明
- 更新了 `README.md`：在"快速部署"章节添加构建脚本使用说明，引导用户使用 `scripts/build.sh` 进行镜像构建
- 统一补齐了全站图标按钮的悬停提示：为布局中的移动端频道抽屉按钮等手写图标按钮补充 `title`，并在前端启动时自动为带 `aria-label` 但缺失 `title` 的交互元素兜底同步提示文案，避免用户仅看到图标却不知道按钮用途；同时补充频道页与后台用户管理页的回归测试
- 精简了 Docker Compose 配置链路：`scripts/compose.sh` 不再在 `config/` 下持久化生成 `/.compose.env` 中间文件，现改为运行时临时文件；用户只需维护 `config/.env` 这一份覆盖配置，README 与忽略规则已同步更新
- **重构了配置系统架构**：将 `config/config.toml` 移动到 `app/config.toml` 作为应用基础配置（包含在镜像中），`config/.env` 作为用户自定义配置层（运行时挂载，覆盖 toml），实现配置分层管理
- **删除了 `config.yaml`**：项目元信息（name/version/description）迁移到 `app/config.toml` 的 `[project]` 部分，作为版本号的单一事实来源
- **更新了配置加载代码**：修改 `auth-service/src/load-root-config.js`、`app/bootstrap/load-root-config.php`、`scripts/load-config-env.sh` 以支持新的配置路径
- **更新了 Docker 构建配置**：修改 `docker/web/Dockerfile` 和 `auth-service/Dockerfile` 以正确复制 `app/config.toml`
- **更新了 `AGENTS.md`**：新增"配置系统架构"章节，详细说明配置文件结构、层级优先级、加载机制、版本号管理和最佳实践

### Added（新增）

- 新增了 `AGENTS.md` 的"测试规范"章节：明确 `scripts/test/` 目录用途、测试覆盖要求、执行时机和脚本规范，确保所有功能在交付前通过自动化测试验证
- 新增了集中测试目录 `scripts/test/`：统一托管环境检查、Laravel 回归、auth-service 回归、Docker 重部署、冒烟、稳定性与性能验证脚本，供 AI 与人工复用

### Changed（变更）

- 优化了前后台白天 / 夜间主题切换：将自动主题的首次判定前移到 `html` 级别启动脚本，避免后台设置页因服务端与浏览器重复计算时间而出现先黑后白的闪烁；同时将 `admin/users` 用户运营仪表盘改为真正跟随主题切换的自适应样式，并补充对应回归测试

- 更新了 `README.md`：补充统一测试入口与 `NORMAL / STABLE / EFFICIENT / SAFE_CHANGE` 判定口径，方便改动后快速确认系统是否仍然可靠
- 更新了 `config.yaml`：将项目版本推进到 `1.25.0`，用于记录本次自动化测试基础设施补齐

- 新增了 GitHub Actions 工作流 `.github/workflows/create-release.yml`：支持从 config.yaml 自动读取版本号并创建 GitHub Release，自动从 CHANGELOG.md 提取 Release Notes
- 新增了 GitHub Actions 工作流 `.github/workflows/check-version-sync.yml`：自动检查 config.yaml 版本号与最新 GitHub Release 是否同步，并在 PR 中添加版本状态评论

- 新增了后台用户管理运营仪表盘：用于可视化最近 7 天的登录 / 活跃、评论与发文趋势，帮助管理员快速审查社区状态
- 新增了管理员批量删除普通用户能力与对应回归测试：支持当前页多选批量删除，并在服务端自动跳过管理员账号

- 新增了管理员删除普通用户的实现计划 `docs/plans/2026-03-09-admin-user-governance.md`：用于沉淀后台用户删除、会话清理、评论计数回补与 Docker 验收步骤
- 新增了管理员删除普通用户与 DevTools 用户删除回归测试：用于锁定普通用户资料托管、删除链路、权限边界与关联数据清理行为

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

- 优化了后台 `admin/users` 页面：每个用户卡片现在支持折叠 / 展开、顶部操作区统一为保存 / 删除 / 展开收起图标按钮，并增加当前页多选工具条
- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.24.0`，并补充后台用户管理页的仪表盘、批量删除与折叠交互说明

- 优化了登录页视觉设计与信息层级：在不改动现有登录链路、字段与路由的前提下，将登录界面升级为分栏式玻璃卡片布局，并增强多登录方式切换、验证码流程提示与扫码入口呈现
- 新增了白天 / 夜间主题排程能力：管理员可在站点设置中选择固定主题或按时间段自动切换，前台会按配置实时切换展示
- 更新了后台用户管理与 DevTools 用户接口：管理员现在可维护普通用户头像资料并删除普通用户，删除时会同步清理会话、密码重置令牌并回补受影响文章的评论计数
- 更新了 `config.yaml` 与 `README.md`：将项目版本推进到 `1.23.0`，并补充管理员可删除普通用户、托管头像资料与清理运行时凭据的说明

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
