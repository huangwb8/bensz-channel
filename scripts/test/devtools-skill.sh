#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

section 'DevTools skill 回归测试'
run_cmd python3 "$ROOT_DIR/scripts/test/test_bensz_channel_devtools.py"
success 'DevTools skill 回归测试通过'
