#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

section 'Docker 重新部署'
if ! "$COMPOSE_SH" up --build -d; then
    warn 'docker compose up 返回了非零状态，继续等待容器恢复并以最终健康状态为准'
fi

section '等待容器健康'
wait_for_service_healthy postgres 120
wait_for_service_healthy auth 180
wait_for_service_healthy web 180
wait_for_http_ok "$WEB_BASE_URL/up" 'web /up 健康检查' 120

section '当前容器状态'
run_cmd "$COMPOSE_SH" ps

success 'Docker 已完成重部署并通过健康检查'
