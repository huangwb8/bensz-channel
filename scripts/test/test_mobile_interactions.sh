#!/usr/bin/env bash
#
# 测试移动端交互功能
# 验证频道选择按钮和登录/注册按钮在静态快照模式下的正常工作
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"

echo "========================================="
echo "移动端交互功能测试"
echo "========================================="
echo ""

# 颜色定义
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 测试结果统计
TESTS_PASSED=0
TESTS_FAILED=0

# 测试函数
test_case() {
    local test_name="$1"
    local test_command="$2"

    echo -n "测试: ${test_name} ... "

    if eval "${test_command}" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ 通过${NC}"
        ((TESTS_PASSED++))
        return 0
    else
        echo -e "${RED}✗ 失败${NC}"
        ((TESTS_FAILED++))
        return 1
    fi
}

# 1. 检查 JavaScript 文件是否存在
echo "1. 检查前端资源文件"
echo "-----------------------------------"
test_case "app.js 文件存在" "test -f '${PROJECT_ROOT}/app/resources/js/app.js'"
test_case "app.css 文件存在" "test -f '${PROJECT_ROOT}/app/resources/css/app.css'"
echo ""

# 2. 检查 JavaScript 代码中的移动端事件绑定
echo "2. 检查 JavaScript 移动端事件绑定"
echo "-----------------------------------"
test_case "包含 touchend 事件监听" "grep -q 'addEventListener.*touchend' '${PROJECT_ROOT}/app/resources/js/app.js'"
test_case "包含 preventDefault 调用" "grep -q 'preventDefault' '${PROJECT_ROOT}/app/resources/js/app.js'"
test_case "包含 stopPropagation 调用" "grep -q 'stopPropagation' '${PROJECT_ROOT}/app/resources/js/app.js'"
test_case "包含 toggleDrawer 函数" "grep -q 'toggleDrawer' '${PROJECT_ROOT}/app/resources/js/app.js'"
echo ""

# 3. 检查 CSS 移动端优化
echo "3. 检查 CSS 移动端优化"
echo "-----------------------------------"
test_case "btn-login 包含 touch-action" "grep -A 5 'btn-login' '${PROJECT_ROOT}/app/resources/css/app.css' | grep -q 'touch-action'"
test_case "mobile-channel-trigger 包含 touch-action" "grep -A 10 'mobile-channel-trigger' '${PROJECT_ROOT}/app/resources/css/app.css' | grep -q 'touch-action'"
test_case "mobile-channel-link 包含 min-height" "grep -A 5 'mobile-channel-link' '${PROJECT_ROOT}/app/resources/css/app.css' | grep -q 'min-height'"
echo ""

# 4. 检查 StaticPageBuilder 的 minify 方法
echo "4. 检查 HTML 压缩逻辑"
echo "-----------------------------------"
test_case "minify 方法保护 script 标签" "grep -A 15 'private function minify' '${PROJECT_ROOT}/app/app/Support/StaticPageBuilder.php' | grep -q 'script|style'"
test_case "minify 方法保留单个空格" "grep -A 15 'private function minify' '${PROJECT_ROOT}/app/app/Support/StaticPageBuilder.php' | grep -q \"'> <'\""
echo ""

# 5. 检查布局文件中的按钮元素
echo "5. 检查布局文件中的交互元素"
echo "-----------------------------------"
test_case "包含 mobile-channel-trigger 按钮" "grep -q 'data-mobile-channel-trigger' '${PROJECT_ROOT}/app/resources/views/layouts/app.blade.php'"
test_case "包含 mobile-channel-drawer 抽屉" "grep -q 'data-mobile-channel-drawer' '${PROJECT_ROOT}/app/resources/views/layouts/app.blade.php'"
test_case "包含登录/注册按钮" "grep -q 'btn-login' '${PROJECT_ROOT}/app/resources/views/layouts/app.blade.php'"
echo ""

# 6. 检查 Docker 构建脚本
echo "6. 检查 Docker 构建配置"
echo "-----------------------------------"
test_case "build.sh 脚本存在" "test -x '${PROJECT_ROOT}/scripts/build.sh'"
test_case "compose.sh 脚本存在" "test -x '${PROJECT_ROOT}/scripts/compose.sh'"
echo ""

# 输出测试结果摘要
echo "========================================="
echo "测试结果摘要"
echo "========================================="
echo -e "通过: ${GREEN}${TESTS_PASSED}${NC}"
echo -e "失败: ${RED}${TESTS_FAILED}${NC}"
echo -e "总计: $((TESTS_PASSED + TESTS_FAILED))"
echo ""

if [ ${TESTS_FAILED} -eq 0 ]; then
    echo -e "${GREEN}✓ 所有测试通过！${NC}"
    exit 0
else
    echo -e "${RED}✗ 有 ${TESTS_FAILED} 个测试失败${NC}"
    exit 1
fi
