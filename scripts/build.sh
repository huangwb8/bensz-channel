#!/bin/sh

set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
CACHE_BASE_DIR="${CACHE_BASE_DIR:-/Volumes/2T01/Test/bensz-channel}"

show_usage() {
    cat <<EOF
用法: $0 [选项] [服务名...]

构建 Docker 镜像，默认使用本地缓存目录以避免联网下载依赖。

选项:
  --online          联网模式，不使用本地缓存（每次都从网络下载依赖）
  --no-cache        不使用 Docker 层缓存，强制重新构建所有层
  --pull            构建前拉取最新的基础镜像
  -h, --help        显示此帮助信息

服务名:
  web               构建 Web 服务镜像
  auth              构建 Auth 服务镜像
  (留空)            构建所有服务

示例:
  $0                      # 使用本地缓存构建所有服务
  $0 web                  # 仅构建 web 服务
  $0 --online             # 联网模式构建所有服务
  $0 --no-cache web       # 强制重新构建 web 服务

本地缓存目录:
  Composer: $CACHE_BASE_DIR/app/composer-cache
  npm (web): $CACHE_BASE_DIR/app/npm-cache
  npm (auth): $CACHE_BASE_DIR/auth-service/npm-cache

EOF
}

USE_CACHE=1
DOCKER_BUILD_ARGS=""
SERVICES=""

while [ $# -gt 0 ]; do
    case "$1" in
        --online)
            USE_CACHE=0
            shift
            ;;
        --no-cache)
            DOCKER_BUILD_ARGS="$DOCKER_BUILD_ARGS --no-cache"
            shift
            ;;
        --pull)
            DOCKER_BUILD_ARGS="$DOCKER_BUILD_ARGS --pull"
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        -*)
            echo "错误: 未知选项 '$1'" >&2
            echo "运行 '$0 --help' 查看帮助信息" >&2
            exit 1
            ;;
        *)
            SERVICES="$SERVICES $1"
            shift
            ;;
    esac
done

ensure_cache_dirs() {
    if [ "$USE_CACHE" -eq 1 ]; then
        echo "==> 确保本地缓存目录存在..."
        mkdir -p \
            "$CACHE_BASE_DIR/app/composer-cache" \
            "$CACHE_BASE_DIR/app/npm-cache" \
            "$CACHE_BASE_DIR/auth-service/npm-cache"
        echo "    ✓ 缓存目录已就绪"
    fi
}

build_service() {
    service="$1"

    case "$service" in
        web)
            dockerfile="docker/web/Dockerfile"
            ;;
        auth)
            dockerfile="auth-service/Dockerfile"
            ;;
        *)
            echo "错误: 未知服务 '$service'" >&2
            return 1
            ;;
    esac

    echo ""
    echo "==> 构建服务: $service"
    echo "    Dockerfile: $dockerfile"
    echo "    缓存模式: $([ "$USE_CACHE" -eq 1 ] && echo "本地缓存" || echo "联网下载")"

    build_cmd="docker build"
    build_cmd="$build_cmd -f \"$dockerfile\""
    build_cmd="$build_cmd -t bensz-channel-$service:latest"

    if [ "$USE_CACHE" -eq 1 ]; then
        build_cmd="$build_cmd --build-arg COMPOSER_CACHE_DIR=$CACHE_BASE_DIR/app/composer-cache"
        build_cmd="$build_cmd --build-arg NPM_CACHE_DIR_WEB=$CACHE_BASE_DIR/app/npm-cache"
        build_cmd="$build_cmd --build-arg NPM_CACHE_DIR_AUTH=$CACHE_BASE_DIR/auth-service/npm-cache"
    fi

    if [ -n "$DOCKER_BUILD_ARGS" ]; then
        build_cmd="$build_cmd $DOCKER_BUILD_ARGS"
    fi

    build_cmd="$build_cmd ."

    cd "$ROOT_DIR"
    eval "$build_cmd"

    echo "    ✓ 服务 $service 构建完成"
}

ensure_cache_dirs

if [ -z "$SERVICES" ]; then
    SERVICES="web auth"
fi

for service in $SERVICES; do
    build_service "$service"
done

echo ""
echo "==> 所有服务构建完成！"
echo ""
echo "下一步:"
echo "  1. 启动服务: ./scripts/compose.sh up -d"
echo "  2. 查看日志: ./scripts/compose.sh logs -f"
echo "  3. 运行测试: ./scripts/test/all.sh"
