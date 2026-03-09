<?php

namespace Tests\Feature\Static;

use App\Support\CanonicalUrlManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CdnCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_configured_public_app_url_and_asset_url(): void
    {
        config([
            'app.url' => 'https://community.example.com',
            'app.asset_url' => 'https://cdn.example.com',
        ]);

        app(CanonicalUrlManager::class)->apply();

        $response = $this->get('http://origin.internal/');

        $response->assertOk();
        $response->assertSee('href="https://community.example.com"', false);
        $response->assertSee('href="https://community.example.com/login"', false);
        $response->assertSee('href="https://cdn.example.com/build/assets/', false);
        $response->assertSee('src="https://cdn.example.com/build/assets/', false);
    }

    public function test_static_build_uses_configured_public_app_url_and_asset_url(): void
    {
        config([
            'app.url' => 'https://community.example.com',
            'app.asset_url' => 'https://cdn.example.com',
        ]);

        app(CanonicalUrlManager::class)->apply();

        $this->artisan('site:build-static')->assertSuccessful();

        $index = file_get_contents(public_path('static/index.html'));

        $this->assertIsString($index);
        $this->assertStringContainsString('https://community.example.com', $index);
        $this->assertStringContainsString('https://cdn.example.com/build/assets/', $index);
    }
}
