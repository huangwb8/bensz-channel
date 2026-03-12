# CDN 配置指南

本文档说明如何在 Bensz Channel 中配置两种 CDN 模式：回源型 CDN 与对象存储型 CDN。

## 模式说明

- 回源型 CDN：静态资源继续保存在当前 Web 服务，CDN 节点按需回源拉取，适合 Cloudflare、DogeCloud CDN 等方案
- 对象存储型 CDN：将 `public/` 下的公开资源同步到对象存储，再通过 CDN 域名对外提供访问，适合国内加速或源站减负场景

## 后台入口

管理员登录后，进入 `后台 → CDN 设置`：

- 可切换 `回源型 CDN` / `对象存储型 CDN`
- 可保存公开资源域名
- 可测试对象存储连接
- 可查看同步差异、立即同步、清空远程文件
- 可查看最近 20 次同步日志

## 回源型 CDN

适用场景：保留当前静态资源分发方式，只让 CDN 缓存 `build/`、`storage/`、图片、字体等公开资源。

推荐步骤：

1. 配置 CDN 域名，如 `https://cdn.example.com`
2. 在后台 `CDN 设置` 中选择 `回源型 CDN`
3. 将 `公开资源域名` 设置为 `https://cdn.example.com`
4. 保存后等待静态页重建完成

效果：

- 页面链接继续使用 `APP_URL`
- `asset()` 生成的 CSS / JS / 图片 URL 改为 `https://cdn.example.com/...`
- 无需手动上传文件

## 对象存储型 CDN

适用场景：需要把公开资源同步到对象存储，并通过 CDN 域名对外访问。

推荐步骤：

1. 在对象存储服务商处创建存储桶
2. 准备 Access Key、Secret Key、存储桶、区域、兼容 S3 的端点
3. 在后台 `CDN 设置` 中选择 `对象存储型 CDN`
4. 填写 `公开资源域名`、服务商、桶、区域、端点、凭证
5. 勾选 `启用自动同步`
6. 如需每次静态构建后自动推送资源，勾选 `构建后自动同步`
7. 先点击 `测试连接`，再点击 `立即同步`

### 以多吉云为例

多吉云存储桶的底层服务为 COS，参数从控制台 **存储空间 → SDK 参数** 页面获取：

| 表单字段 | 填写内容 | 参数来源 |
|---------|---------|---------|
| 公开资源域名 | 多吉云分配的加速域名（建议绑定自定义域名后填自定义域名，测试阶段可用 `*.oss.dogecdn.com`） | 控制台 → 加速域名 |
| 存储服务商 | 多吉云 | — |
| 存储桶 | SDK 参数页的 `s3Bucket` 完整值 | SDK 参数 |
| 区域 | `auto`（或根据端点填 `ap-chengdu` 等） | SDK 参数 → s3Endpoint 中推断 |
| 端点 | SDK 参数页的 `s3Endpoint`，如 `https://cos.ap-chengdu.myqcloud.com` | SDK 参数 |
| Access Key | 多吉云控制台 **用户中心 → 密钥管理** 的 SecretId | 密钥管理 |
| Secret Key | 多吉云控制台 **用户中心 → 密钥管理** 的 SecretKey | 密钥管理 |

> **注意**：Access Key / Secret Key 是**多吉云平台级 API 密钥**，不是腾讯云 COS 的密钥，两者不可混用。

默认同步目录：

- `public/build`
- `public/storage`
- `public/images`
- `public/fonts`

默认不会同步：

- `*.map`
- `.DS_Store`

## 命令行同步

项目提供 Artisan 命令：

```bash
cd app
php artisan cdn:sync
php artisan cdn:sync --queue
php artisan cdn:sync --clear
```

说明：

- `cdn:sync`：立即同步本地差异
- `cdn:sync --queue`：将同步任务放入队列
- `cdn:sync --clear`：清空远程已同步文件

## 环境变量

除了后台配置，也支持通过环境变量提供默认值：

```env
CDN_MODE=origin
CDN_ASSET_URL=https://cdn.example.com
CDN_STORAGE_PROVIDER=dogecloud
CDN_STORAGE_ACCESS_KEY=
CDN_STORAGE_SECRET_KEY=
CDN_STORAGE_BUCKET=
CDN_STORAGE_REGION=auto
CDN_STORAGE_ENDPOINT=https://oss.example.com
CDN_STORAGE_PUBLIC_URL=https://assets.example.com
CDN_SYNC_ENABLED=false
CDN_SYNC_ON_BUILD=true
```

优先级：后台设置高于环境变量默认值。

## 推荐实践

- 优先使用后台 `CDN 设置` 页面管理运行时配置
- 首次切换到对象存储型 CDN 前，先执行一次 `测试连接`
- 首次全量同步后，再开启 `构建后自动同步`
- 在 Docker 重部署后，通过 `./scripts/test/docker-smoke.sh` 与后台日志确认资源链接是否正确
