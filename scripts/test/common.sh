#!/bin/sh

set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
COMPOSE_SH="$ROOT_DIR/scripts/compose.sh"

WEB_SCHEME=${WEB_SCHEME:-http}
WEB_HOST=${WEB_HOST:-127.0.0.1}
WEB_PORT=${WEB_PORT:-6542}
WEB_BASE_URL=${WEB_BASE_URL:-$WEB_SCHEME://$WEB_HOST:$WEB_PORT}

ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.com}
ADMIN_PASSWORD=${ADMIN_PASSWORD:-admin123456}

TEST_TMP_DIR=${TEST_TMP_DIR:-$(mktemp -d "${TMPDIR:-/tmp}/bensz-channel-test.XXXXXX")}

section() {
    printf '\n==> %s\n' "$*"
}

info() {
    printf '[INFO] %s\n' "$*"
}

success() {
    printf '[OK] %s\n' "$*"
}

warn() {
    printf '[WARN] %s\n' "$*" >&2
}

fail() {
    printf '[FAIL] %s\n' "$*" >&2
    exit 1
}

need_cmd() {
    command -v "$1" >/dev/null 2>&1 || fail "缺少命令：$1"
}

run_cmd() {
    info "运行：$*"
    "$@"
}

compose_service_id() {
    "$COMPOSE_SH" ps -q "$1" 2>/dev/null | head -n 1
}

compose_service_health() {
    container_id=$(compose_service_id "$1")
    [ -n "$container_id" ] || return 1

    docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container_id"
}

wait_for_service_healthy() {
    service_name=$1
    timeout_seconds=${2:-180}
    started_at=$(date +%s)

    while :; do
        status=$(compose_service_health "$service_name" 2>/dev/null || true)

        if [ "$status" = "healthy" ] || [ "$status" = "running" ]; then
            success "服务已就绪：$service_name ($status)"
            return 0
        fi

        now=$(date +%s)
        if [ $((now - started_at)) -ge "$timeout_seconds" ]; then
            "$COMPOSE_SH" ps || true
            fail "等待服务超时：$service_name（最后状态：${status:-unknown}）"
        fi

        sleep 2
    done
}

wait_for_http_ok() {
    url=$1
    label=${2:-$1}
    timeout_seconds=${3:-120}
    started_at=$(date +%s)

    while :; do
        if curl -fsS -o /dev/null "$url"; then
            success "HTTP 可用：$label"
            return 0
        fi

        now=$(date +%s)
        if [ $((now - started_at)) -ge "$timeout_seconds" ]; then
            fail "等待 HTTP 超时：$label <$url>"
        fi

        sleep 2
    done
}

require_login_form_token() {
    html_file=$1

    token=$(tr '\n' ' ' < "$html_file" | sed -n 's/.*name="_token" value="\([^"]*\)".*/\1/p' | head -n 1)
    [ -n "$token" ] || fail '未能从登录页提取 CSRF token'

    printf '%s' "$token"
}

admin_login_smoke() {
    cookie_jar="$TEST_TMP_DIR/admin.cookies.txt"
    login_page="$TEST_TMP_DIR/login.html"
    admin_page="$TEST_TMP_DIR/admin-site-settings.html"

    curl -fsSL -c "$cookie_jar" "$WEB_BASE_URL/login" > "$login_page"
    csrf_token=$(require_login_form_token "$login_page")

    curl -fsSL \
        -b "$cookie_jar" \
        -c "$cookie_jar" \
        --data-urlencode "_token=$csrf_token" \
        --data-urlencode 'login_method=email-password' \
        --data-urlencode "email=$ADMIN_EMAIL" \
        --data-urlencode "password=$ADMIN_PASSWORD" \
        "$WEB_BASE_URL/auth/password" \
        > /dev/null

    curl -fsSL -b "$cookie_jar" "$WEB_BASE_URL/admin/site-settings" > "$admin_page"

    grep -q '站点设置' "$admin_page" || fail '管理员登录后未能访问站点设置页'
    success '管理员密码登录与后台鉴权正常'
}

auth_health_smoke() {
    "$COMPOSE_SH" exec -T auth node -e "fetch('http://127.0.0.1:3001/health').then((response) => process.exit(response.ok ? 0 : 1)).catch(() => process.exit(1))"
    success 'auth 服务健康检查正常'
}

measure_curl_time() {
    url=$1
    curl -fsS -o /dev/null -w '%{time_total}\n' "$url"
}

percentile_ms() {
    input_file=$1
    percentile=${2:-95}

    total=$(wc -l < "$input_file" | tr -d ' ')
    [ "$total" -gt 0 ] || {
        printf '0'
        return 0
    }

    index=$((percentile * total / 100))
    if [ $((percentile * total % 100)) -ne 0 ]; then
        index=$((index + 1))
    fi
    [ "$index" -ge 1 ] || index=1
    [ "$index" -le "$total" ] || index=$total

    sort -n "$input_file" | awk -v target="$index" 'NR == target { printf "%.0f", $1 * 1000; exit }'
}

average_ms() {
    input_file=$1

    awk '
        { sum += $1; count += 1 }
        END {
            if (count == 0) {
                print 0;
                exit;
            }

            printf "%.0f", (sum / count) * 1000;
        }
    ' "$input_file"
}

print_runtime_context() {
    section '运行上下文'
    printf 'ROOT_DIR=%s\n' "$ROOT_DIR"
    printf 'WEB_BASE_URL=%s\n' "$WEB_BASE_URL"
    printf 'TEST_TMP_DIR=%s\n' "$TEST_TMP_DIR"
}
