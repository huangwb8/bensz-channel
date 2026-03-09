#!/bin/sh

set -u

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)

run_stage() {
    label=$1
    shift

    printf '\n>>>> %s\n' "$label"

    if "$@"; then
        printf '[PASS] %s\n' "$label"
        return 0
    fi

    printf '[FAIL] %s\n' "$label" >&2
    return 1
}

doctor_ok=0
auth_ok=0
app_ok=0
smoke_ok=0
stability_ok=0
performance_ok=0

run_stage '环境检查' "$SCRIPT_DIR/doctor.sh" && doctor_ok=1

if [ "$doctor_ok" -eq 1 ]; then
    run_stage 'auth-service 回归' "$SCRIPT_DIR/auth-regression.sh" && auth_ok=1
    run_stage 'Laravel 回归 + 前端构建' "$SCRIPT_DIR/app-regression.sh" && app_ok=1
    run_stage 'Docker 重部署' "$SCRIPT_DIR/docker-redeploy.sh"
    run_stage 'Docker 冒烟' "$SCRIPT_DIR/docker-smoke.sh" && smoke_ok=1
    run_stage '稳定性检查' "$SCRIPT_DIR/stability.sh" && stability_ok=1
    run_stage '性能检查' "$SCRIPT_DIR/performance.sh" && performance_ok=1
fi

normal='FAIL'
stable='FAIL'
efficient='FAIL'
safe_change='FAIL'

if [ "$doctor_ok" -eq 1 ] && [ "$auth_ok" -eq 1 ] && [ "$app_ok" -eq 1 ] && [ "$smoke_ok" -eq 1 ]; then
    normal='PASS'
fi

if [ "$stability_ok" -eq 1 ]; then
    stable='PASS'
fi

if [ "$performance_ok" -eq 1 ]; then
    efficient='PASS'
fi

if [ "$normal" = 'PASS' ] && [ "$stable" = 'PASS' ] && [ "$efficient" = 'PASS' ]; then
    safe_change='PASS'
fi

printf '\n==== 测试结论 ====\n'
printf 'NORMAL=%s\n' "$normal"
printf 'STABLE=%s\n' "$stable"
printf 'EFFICIENT=%s\n' "$efficient"
printf 'SAFE_CHANGE=%s\n' "$safe_change"

[ "$safe_change" = 'PASS' ]
