# Changelog

All notable changes to `bensz-channel-devtools` will be documented in this file.

The format is based on Keep a Changelog.

## [Unreleased]

### Added（新增）
- 在 `BdcEnv` 数据类中新增 `env_file_path` 字段，记录实际使用的 .env 文件路径，确保工作函数能够明确知道配置来源。
- 在 `env_check.py` 输出中显示实际使用的 .env 文件路径，便于调试和验证配置来源。

### Changed（变更）
- 更新 `resolve_bdc_env()` 函数，在查找配置时记录实际使用的 .env 文件路径，并将其传递给 `BdcEnv` 对象。
- 更新 `client.py` 中创建 `BdcEnv` 对象的代码，确保 `env_file_path` 字段正确传递。

### Fixed（修复）
- 修复 DevTools API 中频道创建省略 `slug` 时触发 500 的问题，并在中文名称无法生成 ASCII slug 时提供非空 fallback。
- 修复 `channels` / `articles` 子命令按文档传数值 `--id` 时与服务端 `slug` 路由绑定不一致的问题，现已支持 `id` 与 `slug` 双解析。
- 修复频道 / 文章 / 用户更新接口只能全量提交的问题,现已支持与 `client.py` 一致的 partial update 语义。
- 修复文章与评论列表接口对 `published=false` / `visible=false` 的布尔过滤误判问题，避免字符串 `'false'` 被当成真值。

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
