# 版本管理指南

## 概述

本项目实现了 Docker 镜像版本号与 GitHub Release 版本号的自动同步机制。

## 工作流程

### 1. 创建新版本

**方式一：手动创建 Release（推荐）**

1. 在 GitHub 仓库页面点击 "Releases" → "Draft a new release"
2. 创建新的 tag（格式：`v1.24.0`）
3. 填写 Release 标题和描述
4. 点击 "Publish release"

**方式二：使用自动化工作流**

1. 更新 [config.yaml](../config.yaml) 中的版本号
2. 更新 [CHANGELOG.md](../CHANGELOG.md) 中的变更内容
3. 在 GitHub Actions 页面手动触发 `Create Release from config.yaml` 工作流

### 2. 自动构建 Docker 镜像

创建 GitHub Release 后，`publish-release-images` 工作流会：

- 每 12 小时自动检查一次最新 Release
- 检查 Docker Hub 是否已存在该版本的镜像
- 如果不存在，自动构建并推送：
  - `{namespace}/{repo}-web:{version}` 和 `:latest`
  - `{namespace}/{repo}-auth:{version}` 和 `:latest`

### 3. 版本同步检查

`check-version-sync` 工作流会在以下情况自动运行：

- 修改 config.yaml 或 CHANGELOG.md 时
- 创建 Pull Request 时

检查内容：
- config.yaml 版本号与最新 Release 是否一致
- CHANGELOG.md 是否包含当前版本的变更记录

## 配置要求

### GitHub Repository Variables

- `DOCKERHUB_NAMESPACE`：Docker Hub 命名空间（必填）
- `DOCKERHUB_WEB_REPOSITORY`：Web 镜像仓库名（可选）
- `DOCKERHUB_AUTH_REPOSITORY`：Auth 镜像仓库名（可选）

### GitHub Repository Secrets

- `DOCKERHUB_USERNAME`：Docker Hub 用户名
- `DOCKERHUB_TOKEN`：Docker Hub Access Token

## 版本号规范

遵循 [语义化版本](https://semver.org/lang/zh-CN/) 规范：

- **主版本号**：不兼容的 API 修改
- **次版本号**：向下兼容的功能性新增
- **修订号**：向下兼容的问题修正

示例：
- `v1.0.0` → `v1.0.1`：Bug 修复
- `v1.0.0` → `v1.1.0`：新增功能
- `v1.0.0` → `v2.0.0`：破坏性变更

## 最佳实践

1. **先更新 CHANGELOG.md**：在创建 Release 前，确保 CHANGELOG.md 包含完整的变更记录
2. **使用语义化版本号**：遵循 semver 规范，让版本号有意义
3. **Release Notes 要详细**：帮助用户了解每个版本的变化
4. **定期检查 Docker Hub**：确保镜像已成功推送

## 故障排查

### Docker 镜像未自动构建

1. 检查 GitHub Actions 工作流是否成功运行
2. 确认 Docker Hub 凭证是否正确配置
3. 查看工作流日志中的错误信息

### 版本号不一致

1. 运行 `check-version-sync` 工作流查看详细状态
2. 手动同步 config.yaml 和最新 Release 的版本号
3. 更新 CHANGELOG.md 中的版本记录
