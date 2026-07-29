# Channel 公网域名故障调查报告

## 调查结论

截至 2026-07-29 08:14（Asia/Shanghai），预期公网地址 `https://channel.benszresearch.com` 无法正常访问的直接技术根因是：

- Cloudflare 权威 DNS 中没有 `channel.benszresearch.com` 的 A、AAAA 或 CNAME 记录，公共递归解析器返回 `NXDOMAIN`。
- rn3 的 Nginx Proxy Manager 只配置了 `channel.hwb0307.com`，没有 `channel.benszresearch.com` 对应的代理主机和 TLS 证书入口。
- Channel 容器内的公开地址配置仍为 `https://channel.hwb0307.com`；项目 DevTools 的只读健康检查也仍连接该旧域名。

因此，故障发生在“浏览器 → DNS → HTTPS 反向代理”入口链路，尚未到达 Channel 应用。Channel 应用、认证服务和数据库本身没有宕机；旧域名 `https://channel.hwb0307.com` 在调查时仍可正常返回 HTTP 200。

仅凭当前公共 DNS 和 rn3 主机证据，无法判断该 DNS 记录是从未创建，还是近期被删除。若需要定位变更时间和操作者，还需查看 Cloudflare DNS Audit Log；本次未访问 Cloudflare 控制面。

## 影响范围

- 访问 `https://channel.benszresearch.com` 的用户无法完成域名解析，无法建立 TLS 连接，也无法到达 Channel 页面。
- 直接使用旧域名 `https://channel.hwb0307.com` 的访问仍然正常。
- rn3 内部的 Channel Web、Auth、PostgreSQL、Redis、Worker 与 Mailpit 服务仍在运行。

## 调查范围与安全边界

本次调查仅执行诊断操作；除 DevTools `doctor` 正常产生的 connect、heartbeat、disconnect 短暂连接闭环外，没有业务写入：

- 从本地和 rn3 检查公共 DNS、HTTPS、容器状态、健康检查、近期错误日志与反向代理配置。
- 使用项目规定的 `./self/remote.env` 参数执行 DevTools `ping` 和 `doctor`；未读取、输出或修改该文件内容。
- 未修改 Cloudflare、Nginx Proxy Manager、Channel 配置或业务数据。
- 未重启容器，未拉取镜像，未部署代码，未删除或覆盖远端文件。

## 关键证据

### 公共 DNS

Cloudflare DNS over HTTPS 与 Google Public DNS 对以下查询给出一致结果：

| 查询 | 结果 |
|---|---|
| `channel.benszresearch.com A` | `Status: 3`，即 `NXDOMAIN` |
| `channel.benszresearch.com AAAA` | `Status: 3`，即 `NXDOMAIN` |
| `channel.benszresearch.com CNAME` | `Status: 3`，即 `NXDOMAIN` |
| `benszresearch.com NS` | `braelyn.ns.cloudflare.com`、`hal.ns.cloudflare.com` |
| `channel.hwb0307.com A` | 正常返回 Cloudflare 边缘地址 |

本地网络使用了 Fake-IP DNS，因此普通 `dig` 曾显示 `198.18.0.0/15` 保留网段地址；该结果不是站点的真实公网记录，最终结论以两家 DoH 公共解析器的权威响应为准。

### HTTPS 与应用健康

| 检查项 | 结果 |
|---|---|
| `https://channel.benszresearch.com` | 域名不存在，无法进入正常 TLS/HTTP 链路 |
| `https://channel.hwb0307.com` | HTTP/2 200 |
| 旧域名响应头 | `x-served-by: channel.hwb0307.com` |
| Channel 内部首页 | HTTP 200 |
| Channel 内部 `/api/vibe/ping` | `ok: true` |
| DevTools `ping` | HTTP 200，`ok: true` |
| DevTools `doctor` | Ping、connect、heartbeat、disconnect 闭环成功，`terminate: false` |

### rn3 容器与主机

调查时 6 个 Channel 容器已连续运行约 41 小时，均无重启、OOM 或异常退出：

| 服务 | 状态 |
|---|---|
| `channel-web` | running、healthy、restart count 0 |
| `channel-auth` | running、healthy、restart count 0 |
| `channel-postgres` | running、healthy、restart count 0 |
| `channel-redis` | running、restart count 0 |
| `channel-worker` | running、restart count 0 |
| `channel-mailpit` | running、healthy、restart count 0 |

主机根分区使用率约 57%，可用内存约 3.6 GiB；没有磁盘满或内存耗尽证据。最近 48 小时的 Channel Web、Worker 与 Auth 日志未匹配到 `error`、`exception`、`fatal`、`timeout`、`refused` 等错误关键字。

### 反向代理与运行配置

- Nginx Proxy Manager 的代理主机配置只包含 `server_name channel.hwb0307.com`，上游为 `channel-web:80`。
- 对 `channel.benszresearch.com` 搜索代理主机和 stream 配置无结果。
- Nginx 配置语法检查通过。
- `channel-web` 的 `APP_URL` 为 `https://channel.hwb0307.com`。

这些证据说明旧域名链路完整，而新域名尚未进入 rn3 的反向代理配置。

## 根因链

1. 用户访问 `channel.benszresearch.com` 时无法进入网站，是因为公共解析器返回 `NXDOMAIN`。
2. DNS 不存在后，浏览器无法获得可连接的源站或 Cloudflare 边缘地址，因此无法建立 TLS 和 HTTP 会话。
3. 即使临时绕过 DNS，rn3 的 Nginx Proxy Manager 也没有该主机名和对应证书配置，请求仍不能按预期路由到 `channel-web`。
4. Channel 的 `APP_URL` 与 DevTools 目标仍是旧域名，表明域名迁移或新增域名配置没有在 DNS、代理和应用三层完整落地。

技术根因可归纳为：`channel.benszresearch.com` 的公网入口配置缺失，而不是 Channel 应用栈故障。

## 次要风险

rn3 的 `/docker/bensz-channel` 不是干净、可复现的 Git 工作区：

- 远端 HEAD 停留在 2026-03-09 的提交。
- 工作区中大量受 Git 跟踪的源文件显示为已删除，只保留部署所需的 `.env`、`data/` 与经过本地修改的 `docker-compose.yml`。
- 当前容器使用远程 `latest` 镜像运行，因此该状态没有造成这次域名故障；但它会让以后直接执行 `git pull`、基于源码构建、对比版本或回滚变得不可靠。

不建议在未备份当前 compose 文件和持久化数据、未确认部署约定前对该目录执行 Git 清理或强制还原。

## 建议恢复步骤

以下步骤均会改变线上状态，本次调查没有执行：

1. 在 Cloudflare 的 `benszresearch.com` zone 中创建 `channel` DNS 记录，使其指向 rn3 当前反向代理入口；先确认与旧域名相同的源站策略和代理模式。
2. 在 rn3 的 Nginx Proxy Manager 中新增 `channel.benszresearch.com` 代理主机，转发到 `channel-web:80`，申请覆盖该域名的有效证书并启用 HTTPS。
3. 将 Channel 的公开 URL、DevTools URL、OAuth 回调地址和其他依赖绝对 URL 的配置统一迁移到新域名；按项目部署流程进行受控重启。
4. 新域名验证完成后，再决定是否把旧域名 301 重定向到新域名，避免双域名产生 canonical、Cookie 与登录回调不一致。
5. 单独整理 rn3 部署目录：将生产 compose 覆盖配置纳入受控版本管理，并明确“镜像部署目录”与“源码仓库”的边界。

## 恢复验收标准

- 两家独立公共 DNS 解析器均能解析 `channel.benszresearch.com`。
- TLS 证书包含 `channel.benszresearch.com`，证书链验证通过。
- 首页、`/robots.txt`、`/sitemap.xml` 与 `/api/vibe/ping` 均返回预期状态。
- DevTools `doctor` 闭环成功。
- 登录、OAuth 回调、文章页、RSS、静态页面和 canonical URL 均使用新域名。
- Channel 容器保持 healthy，代理与应用日志无新增错误。

## 当前状态

根因已定位，线上配置尚未修改，`channel.benszresearch.com` 尚未恢复。旧域名和 Channel 应用栈在调查结束时保持正常。
