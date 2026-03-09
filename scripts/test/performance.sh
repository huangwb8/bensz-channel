#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

samples=${PERF_SAMPLES:-5}

home_avg_budget=${HOME_AVG_BUDGET_MS:-800}
home_p95_budget=${HOME_P95_BUDGET_MS:-1500}
login_avg_budget=${LOGIN_AVG_BUDGET_MS:-900}
login_p95_budget=${LOGIN_P95_BUDGET_MS:-1800}
feed_avg_budget=${FEED_AVG_BUDGET_MS:-900}
feed_p95_budget=${FEED_P95_BUDGET_MS:-1800}

measure_endpoint() {
    label=$1
    url=$2
    avg_budget=$3
    p95_budget=$4
    data_file="$TEST_TMP_DIR/${label}.times"

    : > "$data_file"

    count=1
    while [ "$count" -le "$samples" ]; do
        measure_curl_time "$url" >> "$data_file"
        count=$((count + 1))
    done

    avg_ms=$(average_ms "$data_file")
    p95_ms=$(percentile_ms "$data_file" 95)

    printf '%s avg=%sms p95=%sms budget(avg<=%sms,p95<=%sms)\n' \
        "$label" "$avg_ms" "$p95_ms" "$avg_budget" "$p95_budget"

    [ "$avg_ms" -le "$avg_budget" ] || fail "$label 平均响应时间超出预算"
    [ "$p95_ms" -le "$p95_budget" ] || fail "$label P95 响应时间超出预算"
}

section "性能检查（每个端点 ${samples} 次）"
measure_endpoint home "$WEB_BASE_URL/" "$home_avg_budget" "$home_p95_budget"
measure_endpoint login "$WEB_BASE_URL/login" "$login_avg_budget" "$login_p95_budget"
measure_endpoint feed "$WEB_BASE_URL/feeds/articles.xml" "$feed_avg_budget" "$feed_p95_budget"

success '性能检查通过'
