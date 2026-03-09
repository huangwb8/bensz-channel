#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

section 'Laravel 回归测试'
run_cmd sh -c "cd '$ROOT_DIR/app' && php artisan test"

section '前端构建验证'
run_cmd sh -c "cd '$ROOT_DIR/app' && npm run build"

success 'Laravel 回归与前端构建验证通过'
