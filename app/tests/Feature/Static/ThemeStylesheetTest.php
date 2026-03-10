<?php

namespace Tests\Feature\Static;

use Tests\TestCase;

class ThemeStylesheetTest extends TestCase
{
    public function test_app_stylesheet_provides_dark_theme_overrides_for_common_surface_utilities(): void
    {
        $stylesheet = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .bg-white", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .bg-gray-50", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .text-gray-900", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .border-gray-200", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .from-blue-50", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .bg-orange-50", $stylesheet);
    }

    public function test_auth_stylesheet_provides_dark_theme_overrides_for_login_experience(): void
    {
        $stylesheet = file_get_contents(resource_path('css/auth.css'));

        $this->assertIsString($stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .auth-wrap", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .login-card", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .method-panel", $stylesheet);
        $this->assertStringContainsString("[data-theme='dark'] .method-tab", $stylesheet);
    }
}
