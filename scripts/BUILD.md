# Docker 镜像构建指南

## 快速开始

```bash
# 使用本地缓存构建所有服务（推荐，默认）
./scripts/build.sh

# 仅构建特定服务
./scripts/build.sh web
./scripts/build.sh auth

# 联网模式（从网络下载所有依赖）
./scripts/build.sh --online

# 强制重新构建（不使用 Docker 层缓存）
./scripts/build.sh --no-cache
```

## 构建模式

### 本地缓存模式（默认）

**优点**：
- 构建速度快，避免重复下载依赖
- 离线环境可用（依赖已缓存）
- 节省网络带宽

**缓存目录**：
```
项目根目录/.cache/
├── app/
│   ├── composer-cache/    # PHP Composer 缓存
│   └── npm-cache/         # Web 前端 npm 缓存
└── auth-service/
    └── npm-cache/         # Auth 服务 npm 缓存
```

**自定义缓存目录**：

```bash
# 使用自定义缓存目录
export CACHE_BASE_DIR=/path/to/your/cache
./scripts/build.sh
```

**使用场景**：
- 日常开发构建
- CI/CD 流水线（配置缓存目录）
- 离线环境部署

### 联网模式

**使用方式**：
```bash
./scripts/build.sh --online
```

**特点**：
- 每次都从网络下载最新依赖
- 不依赖本地缓存目录
- 适合首次构建或验证依赖完整性

**使用场景**：
- 首次在新机器上构建
- 验证依赖包的可用性
- 清理缓存后的重新构建

## 构建选项

| 选项 | 说明 | 示例 |
|------|------|------|
| `--online` | 联网模式，不使用本地缓存 | `./scripts/build.sh --online` |
| `--no-cache` | 不使用 Docker 层缓存 | `./scripts/build.sh --no-cache` |
| `--pull` | 构建前拉取最新基础镜像 | `./scripts/build.sh --pull` |
| `-h, --help` | 显示帮助信息 | `./scripts/build.sh --help` |

## 构建流程

### Web 服务构建流程

1. **Composer 依赖安装**（PHP 后端）
   - 缓存目录：`/Volumes/2T01/Test/bensz-channel/app/composer-cache`
   - 依赖文件：`app/composer.json`, `app/composer.lock`

2. **npm 依赖安装**（前端资源）
   - 缓存目录：`/Volumes/2T01/Test/bensz-channel/app/npm-cache`
   - 依赖文件：`app/package.json`, `app/package-lock.json`

3. **前端资源构建**
   - 使用 Vite 构建前端资源
   - 输出到 `public/build/`

4. **系统包安装**
   - Alpine 系统包（curl, nginx, postgresql-dev 等）
   - PHP 扩展（intl, opcache, pdo_pgsql, zip, redis）

### Auth 服务构建流程

1. **npm 依赖安装**
   - 缓存目录：`/Volumes/2T01/Test/bensz-channel/auth-service/npm-cache`
   - 依赖文件：`auth-service/package.json`, `auth-service/package-lock.json`

2. **配置文件复制**
   - 应用基础配置：`app/config.toml`
   - 用户自定义配置：`config/.env`

## 缓存管理

### 查看缓存大小

```bash
# 默认缓存目录
du -sh .cache/*/composer-cache
du -sh .cache/*/npm-cache

# 自定义缓存目录
du -sh $CACHE_BASE_DIR/*/composer-cache
du -sh $CACHE_BASE_DIR/*/npm-cache
```

### 清理缓存

```bash
# 清理默认缓存目录
rm -rf .cache/app/composer-cache/*
rm -rf .cache/app/npm-cache/*
rm -rf .cache/auth-service/npm-cache/*

# 清理后重新构建
./scripts/build.sh --online
```

## 与 docker-compose 集成

构建完成后，使用 `compose.sh` 启动服务：

```bash
# 构建镜像
./scripts/build.sh

# 启动服务
./scripts/compose.sh up -d

# 查看日志
./scripts/compose.sh logs -f

# 停止服务
./scripts/compose.sh down
```

## 故障排查

### 构建失败：缓存目录权限问题

**症状**：
```
ERROR: failed to solve: failed to compute cache key: failed to copy: permission denied
```

**解决方案**：
```bash
# 检查缓存目录权限
ls -la /Volumes/2T01/Test/bensz-channel/

# 修复权限
chmod -R 755 /Volumes/2T01/Test/bensz-channel/
```

### 构建失败：依赖下载超时

**症状**：
```
npm ERR! network request to https://registry.npmjs.org/... failed
```

**解决方案**：
```bash
# 使用联网模式重试
./scripts/build.sh --online

# 或配置 npm 镜像源
export NPM_CONFIG_REGISTRY=https://registry.npmmirror.com
./scripts/build.sh --online
```

### 构建失败：Docker BuildKit 未启用

**症状**：
```
ERROR: failed to solve: failed to read dockerfile: unknown instruction: RUN --mount
```

**解决方案**：
```bash
# 启用 Docker BuildKit
export DOCKER_BUILDKIT=1

# 或在 Docker 配置中永久启用
# ~/.docker/config.json
{
  "features": {
    "buildkit": true
  }
}
```

## 环境变量

| 变量 | 说明 | 默认值 |
|------|------|--------|
| `CACHE_BASE_DIR` | 缓存基础目录 | `项目根目录/.cache` |
| `DOCKER_BUILDKIT` | 启用 Docker BuildKit | `1`（推荐） |

## 最佳实践

1. **日常开发**：使用默认的本地缓存模式
2. **首次构建**：使用 `--online` 模式确保依赖完整
3. **依赖更新**：修改 `package.json` 或 `composer.json` 后使用 `--online` 模式
4. **CI/CD**：配置缓存目录挂载，使用本地缓存模式
5. **定期清理**：每月清理一次缓存，避免占用过多磁盘空间
