# Changelog

All notable changes to `bensz-channel-devtools` will be documented in this file.

The format is based on Keep a Changelog.

## [Unreleased]

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
