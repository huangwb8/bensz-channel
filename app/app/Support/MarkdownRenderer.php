<?php

namespace App\Support;

use Illuminate\Support\Str;

class MarkdownRenderer
{
    public function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        return trim((string) Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    public function excerpt(?string $markdown, int $limit = 120): string
    {
        $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($this->toHtml($markdown))));

        return Str::limit($plainText, $limit);
    }
}
