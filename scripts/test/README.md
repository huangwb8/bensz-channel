# scripts/test

这里集中托管供 AI / 人类统一调用的自动化验证入口，目标是把“改动后是否还能正常、稳定、高效工作”变成可重复执行的脚本，而不是依赖临场猜测。

## 判定标准

- **正常（NORMAL）**：环境检查通过，`auth-service` 回归、Laravel 全量回归、前端构建验证与 Docker 冒烟检查全部通过
- **稳定（STABLE）**：连续多轮检查 `web /up`、`auth /health` 与管理员登录链路都成功
- **高效（EFFICIENT）**：首页、登录页、RSS 订阅源在默认预算内完成响应
- **未破坏现有功能（SAFE_CHANGE）**：`NORMAL + STABLE + EFFICIENT` 全部通过

## 脚本说明

- `scripts/test/doctor.sh`：检查 Docker、Node、PHP、依赖目录与基础运行环境
- `scripts/test/auth-regression.sh`：统一托管 `auth-service` 现有 Node 单元测试入口
- `scripts/test/app-regression.sh`：统一托管 Laravel 全量测试与前端构建验证
- `scripts/test/docker-redeploy.sh`：通过 `scripts/compose.sh` 重新构建并部署 Docker 栈，再等待健康检查
- `scripts/test/docker-smoke.sh`：验证首页、登录页、RSS、认证服务健康与管理员登录后台
- `scripts/test/stability.sh`：重复执行关键链路，确认服务不是“偶尔成功”
- `scripts/test/performance.sh`：用轻量 `curl` 采样判断关键页面是否仍在预算内
- `scripts/test/all.sh`：一键执行完整判定流程，并输出最终四项结论

## 推荐用法

```bash
# 一键完整验证
./scripts/test/all.sh

# 只跑某一类验证
./scripts/test/app-regression.sh
./scripts/test/docker-smoke.sh
./scripts/test/performance.sh
```

## 可调参数

- `WEB_BASE_URL`：默认 `http://127.0.0.1:6542`
- `ADMIN_EMAIL` / `ADMIN_PASSWORD`：默认使用 README 中的管理员账号
- `STABILITY_RUNS`：稳定性轮次，默认 `5`
- `PERF_SAMPLES`：性能采样次数，默认 `5`
- `HOME_AVG_BUDGET_MS`、`LOGIN_AVG_BUDGET_MS`、`FEED_AVG_BUDGET_MS`：平均响应时间预算
- `HOME_P95_BUDGET_MS`、`LOGIN_P95_BUDGET_MS`、`FEED_P95_BUDGET_MS`：P95 响应时间预算

## 设计取舍

- 现有测试文件继续留在各自服务目录，避免破坏原有测试生态
- `scripts/test/` 只负责集中编排、统一出口与结果判定
- 默认优先覆盖“最能说明系统仍可工作”的关键链路：回归、构建、部署、冒烟、稳定性、性能
