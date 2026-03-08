# Changelog

All notable changes to `bensz-channel-devtools` will be documented in this file.

The format is based on Keep a Changelog.

## [Unreleased]

### Fixed（修复）
- 修复 DevTools API 中频道创建省略 `slug` 时触发 500 的问题，并在中文名称无法生成 ASCII slug 时提供非空 fallback。
- 修复 `channels` / `articles` 子命令按文档传数值 `--id` 时与服务端 `slug` 路由绑定不一致的问题，现已支持 `id` 与 `slug` 双解析。
- 修复频道 / 文章 / 用户更新接口只能全量提交的问题，现已支持与 `client.py` 一致的 partial update 语义。
- 修复文章与评论列表接口对 `published=false` / `visible=false` 的布尔过滤误判问题，避免字符串 `'false'` 被当成真值。

### Changed（变更）
- 更新 `config.yaml` 中的 `skill_version` 为 `1.0.2`，使版本号与本轮 API 兼容性修复保持一致。
- 新增 `tests/v202603082316/_scripts/live_smoke.sh` 与对应 `_artifacts/`：用于在真实 Docker 服务上覆盖 `ping`、`doctor`、频道、文章、评论、用户和环境脚本的全链路 smoke test。

## [1.0.1] - 2026-03-08

### Fixed（修复）
- 修复 `scripts/client.py` 中 `ping` 命令错误要求 `BENSZ_CHANNEL_KEY` 的问题，使首次连通性检查与 `/api/vibe/ping` 的“无需鉴权”约定一致。
- 修复列表查询通过字符串拼接构造 URL 的问题，改为统一 URL 编码，避免搜索词包含空格、`&` 等字符时请求被截断或污染。
- 修复 `doctor` 对 heartbeat `terminate: true` 信号未及时终止的问题，确保在服务端要求中止时立即退出并由连接上下文完成 disconnect。
- 修复 `--url` 覆盖参数未统一标准化的问题，支持像 `localhost:6542` 这样的裸地址自动补全协议。

### Changed（变更）
- 更新 `config.yaml` 中的 `skill_version` 为 `1.0.1`，保持版本号与本次修复同步。
- 新增 `env_search_max_depth` 配置项，并让 `_bdc_env.py` 与 `env_check.py` 共同读取 `config.yaml` 中的搜索深度和候选文件，减少文档/脚本硬编码漂移。
- 更新 `SKILL.md` 的健康检查说明，明确 `ping` 无需 KEY，而 `doctor` 仍需要 KEY 并执行完整连接闭环。
