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

section '图片上传冒烟检查'
cookie_jar="$TEST_TMP_DIR/upload.cookies.txt"
login_page="$TEST_TMP_DIR/upload-login.html"
article_create_page="$TEST_TMP_DIR/article-create.html"
upload_response="$TEST_TMP_DIR/upload-response.json"
upload_image="$TEST_TMP_DIR/paste-large.jpg"

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

curl -fsSL -b "$cookie_jar" "$WEB_BASE_URL/admin/articles/create" > "$article_create_page"

page_csrf_token=$(tr '\n' ' ' < "$article_create_page" | sed -n 's/.*meta name="csrf-token" content="\([^"]*\)".*/\1/p' | head -n 1)
[ -n "$page_csrf_token" ] || fail '未能从文章编辑页提取 CSRF token'

python3 - <<'PY' > "$upload_image"
import base64
import sys

payload = base64.b64decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBAQEBAPDw8PDw8PDw8PDw8PDw8QFREWFhURFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMsNygtLisBCgoKDg0OGhAQGi0fHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAgMBIgACEQEDEQH/xAAXAAADAQAAAAAAAAAAAAAAAAAAAQID/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEAMQAAAB6gD/xAAZEAEAAgMAAAAAAAAAAAAAAAABABEhMUH/2gAIAQEAAT8AuM0n/8QAFBEBAAAAAAAAAAAAAAAAAAAAEP/aAAgBAgEBPwCf/8QAFBEBAAAAAAAAAAAAAAAAAAAAEP/aAAgBAwEBPwCf/9k=')
sys.stdout.buffer.write(payload)
sys.stdout.buffer.write(b'\0' * (2 * 1024 * 1024))
PY

curl -fsSL \
    -b "$cookie_jar" \
    -H "X-CSRF-TOKEN: $page_csrf_token" \
    -F "image=@$upload_image;type=image/jpeg;filename=clipboard-large.jpg" \
    -F 'context=article' \
    "$WEB_BASE_URL/uploads/images" \
    > "$upload_response"

grep -q '"context":"article"' "$upload_response" || fail '文章图片上传未返回预期上下文'
grep -q '"markdown":"!\[' "$upload_response" || fail '文章图片上传未返回 Markdown 链接'
success '大体积文章图片上传正常'

success 'Docker 冒烟测试通过'
