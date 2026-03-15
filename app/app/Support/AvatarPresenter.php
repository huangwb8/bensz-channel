<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AvatarPresenter
{
    /**
     * @return array<int, array{id: string, name: string, description: string}>
     */
    public function styles(): array
    {
        return [
            [
                'id' => 'classic_letter',
                'name' => '经典首字母',
                'description' => '保留熟悉的文字头像，但用更柔和的渐变和描边提升质感。',
            ],
            [
                'id' => 'aurora_ring',
                'name' => '极光环',
                'description' => '彩色渐变搭配环形光晕，适合社区默认头像。',
            ],
            [
                'id' => 'orbit_burst',
                'name' => '轨道脉冲',
                'description' => '轨道线与能量点组合，观感更像论坛徽章。',
            ],
            [
                'id' => 'pixel_patch',
                'name' => '像素拼图',
                'description' => '用像素块构成独特纹理，每位用户都不一样。',
            ],
            [
                'id' => 'paper_cut',
                'name' => '剪纸层叠',
                'description' => '通过多层纸片形状做出更立体的默认头像。',
            ],
        ];
    }

    public function defaultStyle(): string
    {
        return 'classic_letter';
    }

    public function resolveStyle(?string $style): string
    {
        $styleId = trim((string) $style);
        $styleIds = array_column($this->styles(), 'id');

        return in_array($styleId, $styleIds, true)
            ? $styleId
            : $this->defaultStyle();
    }

    /**
     * @return array{type: string, url?: string, svg?: string}
     */
    public function forUser(User $user, ?string $overrideStyle = null): array
    {
        $style = $this->resolveStyle($overrideStyle ?? $user->avatar_style);

        if (in_array($user->avatar_type, ['external', 'uploaded'], true) && filled($user->avatar_url)) {
            return [
                'type' => 'image',
                'url' => (string) $user->avatar_url,
            ];
        }

        return [
            'type' => 'svg',
            'svg' => $this->svgForSeed(
                seed: $this->seedForUser($user),
                label: $this->initialFor($user->name),
                style: $style,
            ),
        ];
    }

    public function preview(User $user, string $style): string
    {
        return $this->svgForSeed(
            seed: $this->seedForUser($user),
            label: $this->initialFor($user->name),
            style: $this->resolveStyle($style),
        );
    }

    private function seedForUser(User $user): string
    {
        return sha1(implode('|', [
            $user->user_id ?: $user->id,
            $user->name,
            $user->email,
            $user->phone,
        ]));
    }

    private function initialFor(string $name): string
    {
        return Str::upper(Str::substr(trim($name), 0, 1) ?: 'U');
    }

    private function svgForSeed(string $seed, string $label, string $style): string
    {
        $palette = $this->palette($seed);
        $safeLabel = $this->escapeSvgValue($label);

        return match ($style) {
            'aurora_ring' => $this->auroraRingSvg($seed, $safeLabel, $palette),
            'orbit_burst' => $this->orbitBurstSvg($seed, $safeLabel, $palette),
            'pixel_patch' => $this->pixelPatchSvg($seed, $safeLabel, $palette),
            'paper_cut' => $this->paperCutSvg($seed, $safeLabel, $palette),
            default => $this->classicLetterSvg($seed, $safeLabel, $palette),
        };
    }

    private function escapeSvgValue(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function svgId(string $prefix, string $seed): string
    {
        return $prefix.'-'.substr($seed, 0, 12);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function palette(string $seed): array
    {
        $palettes = [
            ['#0f766e', '#14b8a6', '#99f6e4', '#042f2e'],
            ['#1d4ed8', '#38bdf8', '#dbeafe', '#082f49'],
            ['#be123c', '#fb7185', '#ffe4e6', '#4c0519'],
            ['#7c3aed', '#c084fc', '#f3e8ff', '#2e1065'],
            ['#c2410c', '#fb923c', '#ffedd5', '#431407'],
        ];

        return $palettes[hexdec(substr($seed, 0, 2)) % count($palettes)];
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string}  $palette
     */
    private function classicLetterSvg(string $seed, string $label, array $palette): string
    {
        $fontSize = 42 + (hexdec(substr($seed, 2, 2)) % 6);
        $gradientId = $this->svgId('classic-a', $seed);

        return <<<SVG
<svg viewBox="0 0 96 96" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{$label}">
  <defs>
    <linearGradient id="{$gradientId}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$palette[0]}"/>
      <stop offset="100%" stop-color="{$palette[1]}"/>
    </linearGradient>
  </defs>
  <rect width="96" height="96" rx="28" fill="url(#{$gradientId})"/>
  <circle cx="76" cy="22" r="10" fill="{$palette[2]}" fill-opacity="0.35"/>
  <circle cx="18" cy="74" r="16" fill="{$palette[3]}" fill-opacity="0.18"/>
  <text x="48" y="57" text-anchor="middle" font-size="{$fontSize}" font-weight="700" fill="#ffffff" font-family="'Plus Jakarta Sans', 'PingFang SC', sans-serif">{$label}</text>
</svg>
SVG;
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string}  $palette
     */
    private function auroraRingSvg(string $seed, string $label, array $palette): string
    {
        $ringOpacity = 0.18 + ((hexdec(substr($seed, 4, 2)) % 20) / 100);
        $gradientId = $this->svgId('aurora-a', $seed);

        return <<<SVG
<svg viewBox="0 0 96 96" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{$label}">
  <defs>
    <radialGradient id="{$gradientId}" cx="30%" cy="20%" r="90%">
      <stop offset="0%" stop-color="{$palette[2]}"/>
      <stop offset="55%" stop-color="{$palette[1]}"/>
      <stop offset="100%" stop-color="{$palette[0]}"/>
    </radialGradient>
  </defs>
  <rect width="96" height="96" rx="28" fill="url(#{$gradientId})"/>
  <circle cx="48" cy="48" r="30" fill="none" stroke="#ffffff" stroke-opacity="{$ringOpacity}" stroke-width="8"/>
  <circle cx="48" cy="48" r="18" fill="none" stroke="{$palette[3]}" stroke-opacity="0.25" stroke-width="4"/>
  <circle cx="72" cy="26" r="7" fill="#ffffff" fill-opacity="0.28"/>
  <text x="48" y="57" text-anchor="middle" font-size="42" font-weight="700" fill="#ffffff" font-family="'Plus Jakarta Sans', 'PingFang SC', sans-serif">{$label}</text>
</svg>
SVG;
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string}  $palette
     */
    private function orbitBurstSvg(string $seed, string $label, array $palette): string
    {
        $rotation = hexdec(substr($seed, 6, 2)) % 360;

        return <<<SVG
<svg viewBox="0 0 96 96" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{$label}">
  <rect width="96" height="96" rx="28" fill="{$palette[3]}"/>
  <g transform="rotate({$rotation} 48 48)">
    <ellipse cx="48" cy="48" rx="32" ry="14" fill="none" stroke="{$palette[1]}" stroke-width="4" stroke-opacity="0.65"/>
    <ellipse cx="48" cy="48" rx="18" ry="30" fill="none" stroke="{$palette[2]}" stroke-width="3" stroke-opacity="0.5"/>
  </g>
  <circle cx="26" cy="31" r="5" fill="{$palette[1]}"/>
  <circle cx="70" cy="66" r="6" fill="{$palette[2]}" fill-opacity="0.9"/>
  <circle cx="54" cy="18" r="4" fill="#ffffff" fill-opacity="0.7"/>
  <text x="48" y="58" text-anchor="middle" font-size="40" font-weight="700" fill="#ffffff" font-family="'Plus Jakarta Sans', 'PingFang SC', sans-serif">{$label}</text>
</svg>
SVG;
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string}  $palette
     */
    private function pixelPatchSvg(string $seed, string $label, array $palette): string
    {
        $blocks = '';

        foreach (range(0, 8) as $index) {
            $x = 10 + (($index % 3) * 22);
            $y = 10 + (intdiv($index, 3) * 18);
            $color = Arr::get($palette, $index % count($palette), '#ffffff');
            $opacity = 0.18 + ((hexdec(substr($seed, $index * 2, 2)) % 45) / 100);
            $blocks .= '<rect x="'.$x.'" y="'.$y.'" width="18" height="14" rx="4" fill="'.$color.'" fill-opacity="'.$opacity.'"/>';
        }

        return <<<SVG
<svg viewBox="0 0 96 96" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{$label}">
  <rect width="96" height="96" rx="28" fill="{$palette[0]}"/>
  {$blocks}
  <circle cx="48" cy="58" r="26" fill="{$palette[3]}" fill-opacity="0.32"/>
  <text x="48" y="63" text-anchor="middle" font-size="38" font-weight="700" fill="#ffffff" font-family="'Plus Jakarta Sans', 'PingFang SC', sans-serif">{$label}</text>
</svg>
SVG;
    }

    /**
     * @param  array{0: string, 1: string, 2: string, 3: string}  $palette
     */
    private function paperCutSvg(string $seed, string $label, array $palette): string
    {
        $offset = hexdec(substr($seed, 8, 2)) % 10;
        $circleY = $offset + 18;

        return <<<SVG
<svg viewBox="0 0 96 96" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="{$label}">
  <rect width="96" height="96" rx="28" fill="{$palette[2]}"/>
  <path d="M8 68C22 44 34 36 56 32C68 30 80 20 88 8V96H8V68Z" fill="{$palette[0]}"/>
  <path d="M8 78C22 58 42 46 68 40C76 38 84 30 88 22V96H8V78Z" fill="{$palette[1]}" fill-opacity="0.85"/>
  <path d="M8 88C20 74 36 62 58 58C72 56 82 48 88 38V96H8V88Z" fill="{$palette[3]}" fill-opacity="0.45"/>
  <circle cx="72" cy="{$circleY}" r="7" fill="#ffffff" fill-opacity="0.45"/>
  <text x="32" y="62" text-anchor="middle" font-size="34" font-weight="700" fill="#ffffff" font-family="'Plus Jakarta Sans', 'PingFang SC', sans-serif">{$label}</text>
</svg>
SVG;
    }
}
