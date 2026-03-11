<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ImageUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $maxKilobytes = $this->resolveMaxKilobytes((string) $request->input('context', 'comment'));

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:'.$maxKilobytes],
            'context' => ['nullable', 'string', Rule::in(['article', 'comment'])],
            'alt' => ['nullable', 'string', 'max:80'],
        ]);

        /** @var UploadedFile $image */
        $image = $validated['image'];
        $context = $validated['context'] ?? 'comment';

        if ($context === 'article' && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $directory = sprintf('media/%s/%s', $context, now()->format('Y/m'));
        $path = $image->storePublicly($directory, 'public');

        abort_if($path === false, 500, '图片保存失败，请稍后重试。');

        $relativeUrl = '/storage/'.ltrim($path, '/');

        return response()->json([
            'alt' => $this->resolveAltText($validated['alt'] ?? null, $image),
            'context' => $context,
            'markdown' => $this->buildMarkdown($validated['alt'] ?? null, $relativeUrl, $image),
            'name' => $image->getClientOriginalName(),
            'path' => $path,
            'url' => asset(ltrim($relativeUrl, '/')),
            'relative_url' => $relativeUrl,
        ], 201);
    }

    private function resolveMaxKilobytes(string $context): int
    {
        $normalized = strtolower(trim($context));

        if ($normalized === 'article') {
            $maxMb = (int) config('community.uploads.article_image_max_mb', 50);
            $maxMb = max(1, min(100, $maxMb));

            return $maxMb * 1024;
        }

        return 10240;
    }

    private function buildMarkdown(?string $alt, string $relativeUrl, UploadedFile $image): string
    {
        return sprintf('![%s](%s)', $this->resolveAltText($alt, $image), $relativeUrl);
    }

    private function resolveAltText(?string $alt, UploadedFile $image): string
    {
        $provided = trim((string) $alt);

        if ($provided !== '') {
            return $provided;
        }

        $original = trim(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
        $normalized = preg_replace('/[\s_-]+/u', ' ', $original) ?: '';
        $normalized = trim($normalized);

        if ($normalized === '' || Str::lower($normalized) === 'image') {
            return '粘贴图片';
        }

        return Str::limit($normalized, 80, '');
    }
}
