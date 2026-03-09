#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

section 'auth-service 回归测试'
run_cmd sh -c "cd '$ROOT_DIR/auth-service' && npm test"
success 'auth-service 单元回归通过'
