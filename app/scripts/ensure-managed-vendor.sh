#!/bin/sh

set -eu

APP_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
MANAGED_ROOT="/Volumes/2T01/Test/bensz-channel/app"
MANAGED_DIR="$MANAGED_ROOT/vendor"
LOCAL_DIR="$APP_DIR/vendor"

mkdir -p "$MANAGED_ROOT"

if [ -L "$LOCAL_DIR" ]; then
    TARGET=$(readlink "$LOCAL_DIR" || true)
    if [ "$TARGET" = "$MANAGED_DIR" ]; then
        exit 0
    fi
    rm -f "$LOCAL_DIR"
fi

if [ -d "$LOCAL_DIR" ]; then
    rm -rf "$MANAGED_DIR"
    mv "$LOCAL_DIR" "$MANAGED_DIR"
fi

if [ ! -d "$MANAGED_DIR" ]; then
    mkdir -p "$MANAGED_DIR"
fi

rm -rf "$LOCAL_DIR"
ln -s "$MANAGED_DIR" "$LOCAL_DIR"
