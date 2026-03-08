#!/bin/sh

set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
COMPOSE_ENV="$ROOT_DIR/config/.compose.env"
DATA_DIR="$ROOT_DIR/data"

compose_project_name() {
    if [ -n "${COMPOSE_PROJECT_NAME:-}" ]; then
        printf '%s\n' "$COMPOSE_PROJECT_NAME"
        return 0
    fi

    basename "$ROOT_DIR"
}

dir_is_empty() {
    target="$1"
    [ ! -d "$target" ] && return 0
    [ -z "$(ls -A "$target" 2>/dev/null)" ]
}

ensure_data_dirs() {
    mkdir -p \
        "$DATA_DIR/postgres" \
        "$DATA_DIR/redis" \
        "$DATA_DIR/mailpit" \
        "$DATA_DIR/web/storage" \
        "$DATA_DIR/web/bootstrap-cache" \
        "$DATA_DIR/web/static"
}

copy_container_dir() {
    container_id="$1"
    source_path="$2"
    target_path="$3"

    [ -n "$container_id" ] || return 0
    dir_is_empty "$target_path" || return 0

    docker cp "$container_id:$source_path/." "$target_path" 2>/dev/null || true
}

migrate_legacy_postgres_volume() {
    target_path="$DATA_DIR/postgres"
    legacy_volume="$(compose_project_name)_postgres-data"

    dir_is_empty "$target_path" || return 0
    docker volume inspect "$legacy_volume" >/dev/null 2>&1 || return 0

    echo "Migrating legacy PostgreSQL volume '$legacy_volume' to ./data/postgres ..."
    docker run --rm \
        -v "$legacy_volume:/from:ro" \
        -v "$target_path:/to" \
        alpine:3.20 \
        sh -eu -c 'cp -a /from/. /to/'
}

migrate_legacy_mailpit_data() {
    container_id="$1"
    database_path="$2"
    target_path="$DATA_DIR/mailpit"

    [ -n "$container_id" ] || return 0
    [ -n "$database_path" ] || return 0
    dir_is_empty "$target_path" || return 0

    echo "Migrating legacy Mailpit database to ./data/mailpit ..."
    docker cp "$container_id:$database_path" "$target_path/mailpit.db" 2>/dev/null || return 0
    docker cp "$container_id:${database_path}-shm" "$target_path/mailpit.db-shm" 2>/dev/null || true
    docker cp "$container_id:${database_path}-wal" "$target_path/mailpit.db-wal" 2>/dev/null || true
}

migrate_legacy_runtime_data() {
    action="${1:-}"

    case "$action" in
        up|start|restart)
            ;;
        *)
            return 0
            ;;
    esac

    postgres_volume="$(compose_project_name)_postgres-data"
    postgres_needs_migration=0
    docker volume inspect "$postgres_volume" >/dev/null 2>&1 && dir_is_empty "$DATA_DIR/postgres" && postgres_needs_migration=1

    redis_container=$(docker compose --env-file "$COMPOSE_ENV" ps -q redis 2>/dev/null || true)
    redis_needs_migration=0
    [ -n "$redis_container" ] && dir_is_empty "$DATA_DIR/redis" && redis_needs_migration=1

    mailpit_container=$(docker compose --env-file "$COMPOSE_ENV" ps -q mailpit 2>/dev/null || true)
    mailpit_needs_migration=0
    [ -n "$mailpit_container" ] && dir_is_empty "$DATA_DIR/mailpit" && mailpit_needs_migration=1
    mailpit_database_path=
    [ "$mailpit_needs_migration" -eq 1 ] && mailpit_database_path=$(docker exec "$mailpit_container" sh -lc 'ls /tmp/mailpit*.db 2>/dev/null | head -n 1' || true)

    web_container=$(docker compose --env-file "$COMPOSE_ENV" ps -q web 2>/dev/null || true)
    web_storage_needs_migration=0
    [ -n "$web_container" ] && dir_is_empty "$DATA_DIR/web/storage" && web_storage_needs_migration=1
    web_static_needs_migration=0
    [ -n "$web_container" ] && dir_is_empty "$DATA_DIR/web/static" && web_static_needs_migration=1

    needs_migration=$((postgres_needs_migration + redis_needs_migration + mailpit_needs_migration + web_storage_needs_migration + web_static_needs_migration))
    [ "$needs_migration" -gt 0 ] || return 0

    echo "Detected legacy runtime data, stopping services for a safe migration ..."
    docker compose --env-file "$COMPOSE_ENV" stop web auth postgres redis mailpit >/dev/null 2>&1 || true

    migrate_legacy_postgres_volume
    copy_container_dir "$redis_container" "/data" "$DATA_DIR/redis"
    migrate_legacy_mailpit_data "$mailpit_container" "$mailpit_database_path"
    copy_container_dir "$web_container" "/var/www/html/storage" "$DATA_DIR/web/storage"
    copy_container_dir "$web_container" "/var/www/html/public/$(grep '^STATIC_SITE_OUTPUT_DIR=' "$COMPOSE_ENV" 2>/dev/null | tail -n 1 | cut -d= -f2- || printf 'static')" "$DATA_DIR/web/static"
}

umask 077
mkdir -p "$ROOT_DIR/config"
"$ROOT_DIR/scripts/load-config-env.sh" env-file > "$COMPOSE_ENV"
ensure_data_dirs
migrate_legacy_runtime_data "${1:-}"

exec docker compose --env-file "$COMPOSE_ENV" "$@"
