#!/bin/sh

set -eu

. "$(dirname "$0")/common.sh"

section '公开页面冒烟检查'
wait_for_http_ok "$WEB_BASE_URL/up" 'web /up' 120

home_page="$TEST_TMP_DIR/home.html"
login_page="$TEST_TMP_DIR/login-public.html"
feed_file="$TEST_TMP_DIR/feed.xml"

curl -fsSL "$WEB_BASE_URL/" > "$home_page"
grep -Eq '<!DOCTYPE html|<html' "$home_page" || fail '首页未返回预期 HTML 内容'
success '首页可访问'

curl -fsSL "$WEB_BASE_URL/login" > "$login_page"
grep -q '欢迎回来' "$login_page" || fail '登录页未返回预期内容'
success '登录页可访问'

curl -fsSL "$WEB_BASE_URL/feeds/articles.xml" > "$feed_file"
grep -q '<rss\|<feed' "$feed_file" || fail 'RSS 订阅源未返回预期内容'
success 'RSS 订阅源可访问'

section '认证与后台冒烟检查'
auth_health_smoke
admin_login_smoke

success 'Docker 冒烟测试通过'
