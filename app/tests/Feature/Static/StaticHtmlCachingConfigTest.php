<?php

namespace Tests\Feature\Static;

use Tests\TestCase;

class StaticHtmlCachingConfigTest extends TestCase
{
    public function test_static_html_routes_require_revalidation_and_vary_on_cookie(): void
    {
        $configPath = dirname(base_path()).'/docker/nginx/default.conf';

        $this->assertFileExists($configPath);

        $config = (string) file_get_contents($configPath);

        $locations = [
            'home' => 'location = /',
            'article' => 'location ~ ^/channels/.+/articles/.+/?$',
            'channel' => 'location ~ ^/channels/.+/?$',
        ];

        foreach ($locations as $name => $signature) {
            $block = $this->extractLocationBlock($config, $signature);

            $this->assertStringContainsString(
                'add_header Cache-Control "no-cache, no-store, must-revalidate" always;',
                $block,
                "{$name} 静态 HTML 路由必须禁用浏览器缓存。"
            );
            $this->assertStringContainsString(
                'add_header Vary "Cookie" always;',
                $block,
                "{$name} 静态 HTML 路由必须按 Cookie 区分缓存语义。"
            );
        }
    }

    public function test_static_html_routes_bypass_snapshot_when_a_refresh_is_pending(): void
    {
        $configPath = dirname(base_path()).'/docker/nginx/default.conf';

        $this->assertFileExists($configPath);

        $config = (string) file_get_contents($configPath);

        $this->assertStringContainsString(
            'if (-f /var/www/html/storage/app/static-build.pending) {',
            $config,
            '静态快照待刷新时必须禁用静态直出。'
        );
        $this->assertStringContainsString(
            'set $serve_static 0;',
            $config,
            '待刷新标记命中后必须立即回源动态页。'
        );
    }

    private function extractLocationBlock(string $config, string $signature): string
    {
        $offset = strpos($config, $signature);

        $this->assertNotFalse($offset, "未找到 location 块：{$signature}");

        $block = substr($config, $offset);
        $nextLocation = strpos($block, "\n    location ", 1);

        if ($nextLocation === false) {
            return $block;
        }

        return substr($block, 0, $nextLocation);
    }
}
