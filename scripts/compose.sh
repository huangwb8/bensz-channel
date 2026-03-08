#!/bin/sh

set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
COMPOSE_ENV="$ROOT_DIR/config/.compose.env"

umask 077
mkdir -p "$ROOT_DIR/config"
"$ROOT_DIR/scripts/load-config-env.sh" env-file > "$COMPOSE_ENV"

exec docker compose --env-file "$COMPOSE_ENV" "$@"
