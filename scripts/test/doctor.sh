#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

print_runtime_context
section '基础依赖检查'

for command_name in docker curl git node npm php awk sed tr sort; do
    need_cmd "$command_name"
done

docker info >/dev/null 2>&1 || fail 'Docker daemon 不可用'

[ -f "$ROOT_DIR/app/artisan" ] || fail '缺少 app/artisan'
[ -f "$ROOT_DIR/app/vendor/autoload.php" ] || fail '缺少 app/vendor/autoload.php，请先安装 PHP 依赖'
[ -f "$ROOT_DIR/app/package.json" ] || fail '缺少 app/package.json'
[ -d "$ROOT_DIR/app/node_modules" ] || fail '缺少 app/node_modules，请先安装前端依赖'
[ -f "$ROOT_DIR/auth-service/package.json" ] || fail '缺少 auth-service/package.json'
[ -d "$ROOT_DIR/auth-service/node_modules" ] || fail '缺少 auth-service/node_modules，请先安装认证服务依赖'

section '版本信息'
printf 'docker=%s\n' "$(docker --version)"
printf 'node=%s\n' "$(node --version)"
printf 'npm=%s\n' "$(npm --version)"
printf 'php=%s\n' "$(php --version | head -n 1)"

success '测试环境就绪，可继续执行自动化验证'
