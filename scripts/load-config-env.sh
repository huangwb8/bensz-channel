#!/bin/sh

set -eu

MODE="${1:-env-file}"
ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
CONFIG_TOML="$ROOT_DIR/app/config.toml"
CONFIG_ENV="$ROOT_DIR/config/.env"

trim() {
    printf '%s' "$1" | sed 's/^[[:space:]]*//; s/[[:space:]]*$//'
}

unquote() {
    value=$(trim "$1")

    case "$value" in
        \"*)
            value=${value#\"}
            value=${value%\"}
            ;;
        \'*)
            value=${value#\'}
            value=${value%\'}
            ;;
    esac

    printf '%s' "$value"
}

append_pair() {
    key="$1"
    value="$2"

    [ -n "$key" ] || return 0

    if [ -n "${MERGED_KEYS:-}" ] && printf '%s\n' "$MERGED_KEYS" | grep -Fx "$key" >/dev/null 2>&1; then
        MERGED_VALUES=$(printf '%s\n' "$MERGED_VALUES" | awk -F= -v target="$key" '$1 != target { print $0 }')
        MERGED_KEYS=$(printf '%s\n' "$MERGED_KEYS" | awk -v target="$key" '$0 != target { print $0 }')
    fi

    MERGED_KEYS=$(printf '%s\n%s' "${MERGED_KEYS:-}" "$key" | sed '/^$/d')
    MERGED_VALUES=$(printf '%s\n%s=%s' "${MERGED_VALUES:-}" "$key" "$value" | sed '/^$/d')
}

parse_toml() {
    [ -f "$CONFIG_TOML" ] || return 0

    in_env=0
    while IFS= read -r raw_line || [ -n "$raw_line" ]; do
        line=$(trim "$raw_line")

        [ -n "$line" ] || continue
        case "$line" in
            \#*) continue ;;
            '[env]') in_env=1; continue ;;
            \[*\]) in_env=0; continue ;;
        esac

        [ "$in_env" -eq 1 ] || continue

        case "$line" in
            *=*) ;;
            *) continue ;;
        esac

        key=$(trim "${line%%=*}")
        value=${line#*=}
        append_pair "$key" "$(unquote "$value")"
    done < "$CONFIG_TOML"
}

parse_dotenv() {
    [ -f "$CONFIG_ENV" ] || return 0

    while IFS= read -r line || [ -n "$line" ]; do
        line=$(trim "$line")

        [ -n "$line" ] || continue
        case "$line" in
            \#*) continue ;;
            export\ *) line=$(trim "${line#export }") ;;
        esac

        case "$line" in
            *=*) ;;
            *) continue ;;
        esac

        key=$(trim "${line%%=*}")
        value=${line#*=}
        append_pair "$key" "$(unquote "$value")"
    done < "$CONFIG_ENV"
}

get_value() {
    key="$1"
    printf '%s\n' "${MERGED_VALUES:-}" | awk -F= -v target="$key" '$1 == target { sub(/^[^=]*=/, "", $0); print $0 }' | tail -n 1
}

derive_defaults() {
    web_host=$(get_value WEB_HOST)
    web_port=$(get_value WEB_PORT)
    app_url=$(get_value APP_URL)
    auth_host=$(get_value AUTH_HOST)
    auth_port=$(get_value AUTH_PORT)
    better_auth_url=$(get_value BETTER_AUTH_URL)
    db_name=$(get_value DB_DATABASE)
    db_user=$(get_value DB_USERNAME)
    db_password=$(get_value DB_PASSWORD)
    app_name=$(get_value APP_NAME)

    [ -n "$app_url" ] || [ -z "$web_host" ] || [ -z "$web_port" ] || append_pair APP_URL "http://$web_host:$web_port"
    [ -n "$better_auth_url" ] || [ -z "$auth_host" ] || [ -z "$auth_port" ] || append_pair BETTER_AUTH_URL "http://$auth_host:$auth_port"
    [ -n "$(get_value BETTER_AUTH_BASE_URL)" ] || [ -z "$(get_value BETTER_AUTH_URL)" ] || append_pair BETTER_AUTH_BASE_URL "$(get_value BETTER_AUTH_URL)"
    [ -n "$(get_value BETTER_AUTH_TRUSTED_ORIGINS)" ] || [ -z "$(get_value APP_URL)" ] || append_pair BETTER_AUTH_TRUSTED_ORIGINS "$(get_value APP_URL)"
    [ -n "$(get_value MAIL_FROM_NAME)" ] || [ -z "$app_name" ] || append_pair MAIL_FROM_NAME "$app_name"
    [ -n "$(get_value PORT)" ] || [ -z "$auth_port" ] || append_pair PORT "$auth_port"
    [ -n "$(get_value POSTGRES_DB)" ] || [ -z "$db_name" ] || append_pair POSTGRES_DB "$db_name"
    [ -n "$(get_value POSTGRES_USER)" ] || [ -z "$db_user" ] || append_pair POSTGRES_USER "$db_user"
    [ -n "$(get_value POSTGRES_PASSWORD)" ] || [ -z "$db_password" ] || append_pair POSTGRES_PASSWORD "$db_password"
}

emit_env_file() {
    printf '%s\n' "${MERGED_VALUES:-}" | sed '/^$/d'
}

emit_shell_exports() {
    printf '%s\n' "${MERGED_VALUES:-}" | sed '/^$/d' | while IFS='=' read -r key value; do
        escaped=$(printf '%s' "$value" | sed "s/'/'\\''/g")
        printf "export %s='%s'\n" "$key" "$escaped"
    done
}

parse_toml
parse_dotenv
derive_defaults

case "$MODE" in
    env-file)
        emit_env_file
        ;;
    shell)
        emit_shell_exports
        ;;
    *)
        echo "Unsupported mode: $MODE" >&2
        exit 1
        ;;
esac
