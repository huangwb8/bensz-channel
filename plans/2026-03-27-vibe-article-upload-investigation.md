# DevTools 文章上传阻塞排查

## 背景

2026-03-27 对 `bensz-channel` 项目与 `/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config` 的文章发布链路做了静态排查，目标是判断“文章上传不顺畅、随后容易重复上传”究竟更像项目代码问题，还是 skill 代码问题。

本次没有修改业务代码，只做证据收集、测试验证与结论归纳。

## 检查范围

- `bensz-channel` 服务端 DevTools API：
  `app/app/Http/Controllers/Api/Vibe/ArticleController.php`
  `app/app/Support/ArticleSubscriptionNotifier.php`
  `app/app/Notifications/ArticlePublishedNotification.php`
  `app/app/Support/StaticPageBuilder.php`
  `app/config/mail.php`
- `bensz-channel-vibe-config` skill：
  `/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config/scripts/client.py`
  `/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config/scripts/_http_json.py`
  `/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config/config.yaml`
  `/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config/SKILL.md`

## 已验证事实

### 服务端在文章创建请求里同步做了额外工作

`app/app/Http/Controllers/Api/Vibe/ArticleController.php:55-80` 显示，`POST /api/vibe/articles` 在写入文章后，还会在同一个请求里继续做两件事：

- 对已发布文章执行 `ArticleSubscriptionNotifier->send(...)`
- 调用 `StaticPageBuilder->rebuildArticle(...)`

其中静态页重建默认走异步队列，`app/app/Support/StaticPageBuilder.php:325-345` 与 `app/config.toml:97-98` 说明默认是 `STATIC_SITE_ENABLED=true`、`STATIC_SITE_ASYNC=true`，因此它更像“轻量排队”，不是最可疑的阻塞点。

真正更可疑的是邮件通知：

- `app/app/Support/ArticleSubscriptionNotifier.php:12-27` 会同步找出所有订阅命中的用户，然后直接 `Notification::send(...)`
- `app/app/Notifications/ArticlePublishedNotification.php:10-42` 只使用了 `Queueable` trait，但没有实现 `ShouldQueue`，所以这不是排队通知，而是当前请求里直接发邮件
- `app/config/mail.php:40-49` 里的 SMTP `timeout` 还是 `null`

这意味着只要订阅用户变多、SMTP 响应慢、邮件链路偶发抖动，`articles create` 的 HTTP 响应就可能明显变慢，甚至在客户端看来像“卡住了”

### skill 端会对非幂等写操作自动重试

`/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config/scripts/client.py:306-333` 显示，`cmd_articles_create(...)` 对 `POST /api/vibe/articles` 调用时设置了 `retries=2`。

`/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config/scripts/_http_json.py:28-93` 显示，底层 HTTP 客户端会在以下情况下自动重试：

- 任意异常
- HTTP `408`
- HTTP `429`
- HTTP `5xx`

这里没有按 HTTP 方法区分幂等性，也没有对 `POST /articles` 这类真实发布操作做“失败后先回查”的保护。

### skill 说明文档与实际实现存在冲突

`/Volumes/2T01/winE/Starup/bensz-devtools/skills/bensz-channel-vibe-config/SKILL.md:45-56` 与 `:114-123` 明确要求：

- 高风险文章发布结果不确定时不要重试
- 正确做法是等待，然后用 `articles list/show` 回查

但代码实际对 `articles create` 开了自动重试，这与文档约束不一致。

### 服务端没有为 DevTools 文章创建提供幂等保护

本项目当前没有看到这些保护手段：

- `Idempotency-Key` 一类的请求去重键
- 按请求指纹做“相同文章短时间内只接受一次”的保护
- 在 skill 连接级别记录“本次 create 是否已经成功落库”的机制

所以只要第一次请求已经在服务端成功创建文章，但响应返回前客户端超时或断链，后续重试就可能再次发起真实创建。

### 中文标题场景会放大重复创建风险

`app/app/Http/Controllers/Api/Vibe/ArticleController.php:208-220` 的 `makeArticleSlug(...)` 逻辑是：

- 能 `Str::slug(...)` 出结果就用 slug
- 否则新建文章时退化成随机 `article-xxxxxxxx`

本地在 2026-03-27 23:27:11 +0800 做了快速验证：

```bash
php -r "require 'app/vendor/autoload.php'; var_export(Illuminate\\Support\\Str::slug('测试文章标题'));"
php -r "require 'app/vendor/autoload.php'; var_export(Illuminate\\Support\\Str::slug('仅中文'));"
```

两个结果都为 `''`。

这说明如果标题主要是中文且没有显式传 `slug`，重复的 `articles create` 请求不会因为 slug 相同而被唯一约束挡住；每次都会生成新的随机 slug，从而更容易形成多篇重复文章。

## 测试结果

本次补跑了两组现有测试，结果都通过：

- `./scripts/test/devtools-skill.sh`
- `php artisan test tests/Feature/Api/Vibe/DevtoolsApiTest.php`

这说明当前问题不是“常规 happy path 直接坏掉”，而更像是现有测试未覆盖到的时序/超时类问题。

## 结论

结论不是“只有一边有问题”，而是两边叠加：

- **更像根源的问题在本项目服务端**
  `articles create` 请求把文章创建后的邮件通知同步放在响应链路里执行，而且 SMTP timeout 还是空值；这很容易造成“服务端已经在工作，但客户端长时间收不到结果”的阻塞感
- **更像放大器的问题在 `bensz-channel-vibe-config`**
  skill 对非幂等的文章创建请求启用了自动重试，和文档里“结果不确定时不要重试”的规则相冲突；一旦第一次请求实际上已经成功，这个自动重试就会制造重复发文风险
- **服务端还存在一个重复保护薄弱点**
  中文标题默认会走随机 slug 回退，同一请求被重复提交时更容易真的生成多篇内容，而不是被唯一键挡下

综合判断：

`文章上传经常不顺畅` 更像是 **本项目服务端同步副作用过重**。

`随后出现多次重复上传` 更像是 **本项目缺少幂等保护** 与 **skill 端自动重试非幂等写操作** 一起造成的结果。

如果必须二选一，我会判断：

- “阻塞感”的主因更偏 **本项目代码问题**
- “重复上传”的直接触发更偏 **skill 重试策略问题**
- 从系统设计角度看，**两边都有值得修的缺陷**

## 当前建议

这次先不强行改代码，但如果后续要修，优先级建议如下：

1. 先改项目服务端：把文章发布通知彻底改成队列异步，并为 SMTP 配置明确 timeout
2. 再改 skill：禁止对 `articles create` 这类非幂等请求自动重试，失败时改成只读回查
3. 最后补服务端幂等保护：例如支持 `Idempotency-Key`，或至少对短时间内相同作者、相同频道、相同标题/正文的请求做防重
4. 顺手收紧 slug 回退：避免中文标题场景每次都生成新的随机 slug，导致重复请求更容易落成多篇文章
