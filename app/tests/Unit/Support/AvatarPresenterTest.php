<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\AvatarPresenter;
use Tests\TestCase;

class AvatarPresenterTest extends TestCase
{
    public function test_generated_avatar_svg_escapes_untrusted_label_characters(): void
    {
        $presenter = new AvatarPresenter();
        $user = User::factory()->make([
            'name' => '<Alice',
            'avatar_type' => 'generated',
            'avatar_style' => 'classic_letter',
        ]);

        $avatar = $presenter->forUser($user);

        $this->assertSame('svg', $avatar['type']);
        $this->assertIsString($avatar['svg']);
        $this->assertStringContainsString('aria-label="&lt;"', $avatar['svg']);
        $this->assertStringContainsString('&lt;</text>', $avatar['svg']);
        $this->assertStringNotContainsString('aria-label="<"', $avatar['svg']);
    }

    public function test_classic_letter_svg_uses_seed_scoped_gradient_ids(): void
    {
        $presenter = new AvatarPresenter();
        $firstUser = User::factory()->make([
            'name' => 'Alice',
            'avatar_type' => 'generated',
            'avatar_style' => 'classic_letter',
        ]);
        $secondUser = User::factory()->make([
            'name' => 'Bob',
            'avatar_type' => 'generated',
            'avatar_style' => 'classic_letter',
        ]);

        $firstSvg = (string) $presenter->forUser($firstUser)['svg'];
        $secondSvg = (string) $presenter->forUser($secondUser)['svg'];

        preg_match('/id="(classic-a-[^"]+)"/', $firstSvg, $firstMatch);
        preg_match('/id="(classic-a-[^"]+)"/', $secondSvg, $secondMatch);

        $this->assertNotEmpty($firstMatch[1] ?? null);
        $this->assertNotEmpty($secondMatch[1] ?? null);
        $this->assertNotSame($firstMatch[1], $secondMatch[1]);
        $this->assertStringContainsString('fill="url(#'.$firstMatch[1].')"', $firstSvg);
        $this->assertStringContainsString('fill="url(#'.$secondMatch[1].')"', $secondSvg);
    }

    public function test_aurora_ring_svg_uses_seed_scoped_gradient_ids(): void
    {
        $presenter = new AvatarPresenter();
        $firstUser = User::factory()->make([
            'name' => 'Charlie',
            'avatar_type' => 'generated',
            'avatar_style' => 'aurora_ring',
        ]);
        $secondUser = User::factory()->make([
            'name' => 'Diana',
            'avatar_type' => 'generated',
            'avatar_style' => 'aurora_ring',
        ]);

        $firstSvg = (string) $presenter->forUser($firstUser)['svg'];
        $secondSvg = (string) $presenter->forUser($secondUser)['svg'];

        preg_match('/id="(aurora-a-[^"]+)"/', $firstSvg, $firstMatch);
        preg_match('/id="(aurora-a-[^"]+)"/', $secondSvg, $secondMatch);

        $this->assertNotEmpty($firstMatch[1] ?? null);
        $this->assertNotEmpty($secondMatch[1] ?? null);
        $this->assertNotSame($firstMatch[1], $secondMatch[1]);
        $this->assertStringContainsString('fill="url(#'.$firstMatch[1].')"', $firstSvg);
        $this->assertStringContainsString('fill="url(#'.$secondMatch[1].')"', $secondSvg);
    }
}
