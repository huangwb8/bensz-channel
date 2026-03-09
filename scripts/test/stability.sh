#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

rounds=${STABILITY_RUNS:-5}
sleep_seconds=${STABILITY_SLEEP_SECONDS:-2}

section "稳定性检查（${rounds} 轮）"

current_round=1
while [ "$current_round" -le "$rounds" ]; do
    info "第 ${current_round}/${rounds} 轮：检查 web、auth 与管理员登录链路"
    wait_for_http_ok "$WEB_BASE_URL/up" 'web /up' 30
    auth_health_smoke
    admin_login_smoke
    current_round=$((current_round + 1))

    if [ "$current_round" -le "$rounds" ]; then
        sleep "$sleep_seconds"
    fi
done

success '稳定性检查通过'
