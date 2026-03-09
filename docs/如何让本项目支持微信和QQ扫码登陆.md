# 如何让本项目支持微信和QQ扫码登陆

本文档面向**第一次接触微信开放平台 / QQ 互联**的新手，目标是把本项目从“默认演示模式”切换为“真实官方扫码登录”。

## 先说结论

- **现在的软件已经准备好了真实接入能力**：代码、路由、回调、用户绑定、Docker 重部署链路都已补齐。
- **但要真的让微信 / QQ 扫码生效，你仍然必须在站外完成平台申请和审核**。
- 如果你什么都还没申请，项目依然能工作：默认会继续使用**内置演示二维码模式**，不会影响你审查其它功能。

## 本项目当前支持的两种模式

### 演示模式

适用场景：

- 你还没有微信开放平台 / QQ 互联账号
- 你只是想先用 Docker 重部署看功能是否正常
- 你想先审查 UI、登录页流程、用户创建和会话逻辑

默认配置：

- `config/config.toml` 中：
  - `WECHAT_QR_MODE = "demo"`
  - `QQ_QR_MODE = "demo"`

此时登录页会生成本项目自己的演示二维码授权页。

### 真实 OAuth 模式

适用场景：

- 你已经拥有公网可访问域名
- 你愿意在微信开放平台 / QQ 互联完成应用创建与审核
- 你准备让真实用户通过微信 / QQ 官方扫码授权登录

## 你在站外必须准备什么

### 通用前提

- 一个**公网可访问**的网站域名，例如 `https://community.example.com`
- 你的项目部署后，这个域名必须能访问登录页
- 你需要知道项目的公开根地址，也就是 `APP_URL`

建议：

- 先把项目部署到一个稳定的测试域名
- 先确认 `https://你的域名/login` 能正常打开
- 再去微信 / QQ 后台填回调域名

## 微信扫码登录接入步骤

以下说明基于微信开放平台官方文档（2026-03-09 可访问）：

- 微信网站应用微信登录文档：
  - `https://developers.weixin.qq.com/doc/oplatform/Website_App/WeChat_Login/Wechat_Login.html`

官方文档明确说明：

- 需要先在**微信开放平台**注册开发者账号
- 需要有一个**已审核通过的网站应用**
- 需要拿到 **AppID** 和 **AppSecret**
- 网站应用微信登录基于 OAuth2.0
- PC 网站扫码登录使用 `scope=snsapi_login`
- `redirect_uri` 的域名必须和平台审核时填写的授权域名一致，否则会报错

### 第一步：注册微信开放平台开发者账号

进入：

- `https://open.weixin.qq.com/`

你需要：

- 注册并完成开发者认证
- 创建一个“网站应用”
- 按平台要求提交网站信息并等待审核

### 第二步：创建网站应用并申请微信登录

在微信开放平台后台中：

- 创建“网站应用”
- 申请“微信登录”能力
- 填写网站名称、简介、图标、域名等资料
- 等待平台审核通过

审核通过后，你会拿到：

- `AppID`
- `AppSecret`

### 第三步：确认授权域名和回调地址

本项目默认回调地址是：

- `https://你的域名/auth/social/wechat/callback`

你至少要保证：

- `APP_URL=https://你的域名`
- 微信后台配置的授权域名与这个回调地址的域名一致

如果你的域名和默认回调地址不一致，可以手动配置：

- `WECHAT_REDIRECT_URI=https://你的自定义回调地址`

### 第四步：把微信参数写入本项目

编辑 `config/config.toml`：

```toml
WECHAT_QR_MODE = "oauth"
WECHAT_CLIENT_ID = "你的微信AppID"
```

编辑 `config/.env`：

```env
WECHAT_CLIENT_SECRET=你的微信AppSecret
```

如果你要自定义回调地址，再加：

```env
WECHAT_REDIRECT_URI=https://你的域名/auth/social/wechat/callback
```

### 第五步：Docker 重部署

执行：

```bash
./scripts/compose.sh down
./scripts/compose.sh up --build -d
```

然后打开：

- `https://你的域名/login`

如果配置无误，登录页的微信卡片会显示“前往微信扫码登录”，并跳转到微信官方扫码授权页。

### 微信常见问题

#### 提示“该链接无法访问”

高概率原因：

- `redirect_uri` 填错
- 授权域名和平台审核时填写的不一致
- `scope` 不是 `snsapi_login`

#### 回调回来后报登录失败

重点检查：

- `APP_URL` 是否为真实公网域名
- 微信后台配置的域名是否和项目实际访问域名一致
- `WECHAT_CLIENT_ID` / `WECHAT_CLIENT_SECRET` 是否来自同一个应用

## QQ 扫码登录接入步骤

以下说明基于 QQ 互联官方文档（2026-03-09 可访问）：

- QQ 登录文档入口：
  - `https://wiki.connect.qq.com/qq登录`
- 使用 Authorization Code 获取 Access Token：
  - `https://wiki.connect.qq.com/使用authorization_code获取access_token`
- 获取用户 OpenID：
  - `https://wiki.connect.qq.com/获取用户openid_oauth2-0`
- 获取用户信息：
  - `https://wiki.connect.qq.com/get_user_info`

官方文档明确说明：

- 需要先申请接入，获取 `appid` 和 `appkey`
- 回调地址必须在注册应用时填写的主域名下
- `state` 必须严格校验，防止 CSRF
- PC 网站可通过 OAuth2.0 授权流程完成登录

### 第一步：注册 QQ 互联开发者账号

进入：

- `https://connect.qq.com/`

你需要：

- 注册开发者账号
- 创建“网站应用”
- 按要求填写网站资料、域名与回调信息
- 等待审核

审核通过后，你会拿到：

- `AppID`
- `AppKey`

> 在本项目里，QQ 的 `AppKey` 就是 `QQ_CLIENT_SECRET`。

### 第二步：确认回调地址

本项目默认回调地址是：

- `https://你的域名/auth/social/qq/callback`

QQ 文档要求回调地址必须位于你注册应用时填写的主域名下，所以请确保：

- `APP_URL=https://你的域名`
- 回调地址也在同一主域名下

如果你要自定义回调地址，可以设置：

- `QQ_REDIRECT_URI=https://你的自定义回调地址`

### 第三步：把 QQ 参数写入本项目

编辑 `config/config.toml`：

```toml
QQ_QR_MODE = "oauth"
QQ_CLIENT_ID = "你的QQ AppID"
```

编辑 `config/.env`：

```env
QQ_CLIENT_SECRET=你的QQ AppKey
```

如果需要自定义回调地址，再加：

```env
QQ_REDIRECT_URI=https://你的域名/auth/social/qq/callback
```

### 第四步：Docker 重部署

执行：

```bash
./scripts/compose.sh down
./scripts/compose.sh up --build -d
```

然后打开：

- `https://你的域名/login`

如果配置无误，QQ 卡片会显示“前往 QQ 扫码登录”，并跳转到 QQ 官方扫码授权页。

### QQ 常见问题

#### 登录页按钮显示“QQ 扫码配置不完整”

说明：

- 你已经把 `QQ_QR_MODE` 切到了 `oauth`
- 但 `QQ_CLIENT_ID` 或 `QQ_CLIENT_SECRET` 还没填完整

#### QQ 回调失败

重点检查：

- `QQ_CLIENT_ID` / `QQ_CLIENT_SECRET` 是否匹配
- `APP_URL` 和实际访问域名是否一致
- QQ 后台登记的主域名、回调地址是否和项目当前域名一致

## 本项目里你需要改的配置总表

### `config/config.toml`

```toml
APP_URL = "https://community.example.com"

WECHAT_QR_MODE = "oauth"
WECHAT_CLIENT_ID = "你的微信AppID"

QQ_QR_MODE = "oauth"
QQ_CLIENT_ID = "你的QQ AppID"
```

### `config/.env`

```env
WECHAT_CLIENT_SECRET=你的微信AppSecret
QQ_CLIENT_SECRET=你的QQ AppKey
```

可选：

```env
WECHAT_REDIRECT_URI=https://community.example.com/auth/social/wechat/callback
QQ_REDIRECT_URI=https://community.example.com/auth/social/qq/callback
```

## 最后检查清单

切换到真实模式前，请逐项确认：

- [ ] `APP_URL` 是真实公网域名
- [ ] 登录页能通过公网域名正常打开
- [ ] 微信开放平台网站应用已审核通过
- [ ] QQ 互联网站应用已审核通过
- [ ] 微信 `AppID` / `AppSecret` 已填入项目配置
- [ ] QQ `AppID` / `AppKey` 已填入项目配置
- [ ] 微信授权域名 / QQ 主域名与项目当前域名一致
- [ ] 执行过 `./scripts/compose.sh up --build -d`
- [ ] 打开登录页后，按钮显示的是“前往微信扫码登录”或“前往 QQ 扫码登录”

## 如果你暂时不想折腾平台申请

完全没问题。

你可以继续使用默认演示模式：

- 微信卡片会生成演示二维码
- QQ 卡片会生成演示二维码
- Docker 重部署后也能直接审查完整登录交互

等你将来把微信开放平台 / QQ 互联都申请好，再切到 `oauth` 即可，无需再改代码。
