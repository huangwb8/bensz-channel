<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VideoUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user() !== null, 403);

        $context = (string) $request->input('context', 'comment');
        $maxKilobytes = $this->resolveMaxKilobytes($context);

        $validated = $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:'.$maxKilobytes],
            'context' => ['nullable', 'string', Rule::in(['article', 'comment'])],
            'title' => ['nullable', 'string', 'max:80'],
        ], [
            'video.mimetypes' => '仅支持 MP4、WebM 或 Ogg 视频。',
            'video.max' => sprintf('视频大小不能超过 %dMB。', (int) floor($maxKilobytes / 1024)),
        ]);

        /** @var UploadedFile $video */
        $video = $validated['video'];
        $context = $validated['context'] ?? 'comment';

        if ($context === 'article' && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $directory = sprintf('media/%s/%s', $context, now()->format('Y/m'));
        $path = $video->storePublicly($directory, 'public');

        abort_if($path === false, 500, '视频保存失败，请稍后重试。');

        $relativeUrl = '/storage/'.ltrim($path, '/');
        $title = $this->resolveTitle($validated['title'] ?? null, $video);

        return response()->json([
            'context' => $context,
            'markdown' => $this->buildMarkdown($title, $relativeUrl),
            'mime_type' => $video->getMimeType(),
            'name' => $video->getClientOriginalName(),
            'path' => $path,
            'title' => $title,
            'url' => asset(ltrim($relativeUrl, '/')),
            'relative_url' => $relativeUrl,
        ], 201);
    }

    private function resolveMaxKilobytes(string $context): int
    {
        $normalized = strtolower(trim($context));
        $configured = (int) config('community.uploads.video_max_mb', 500);
        $maxMb = max(1, min(10240, $configured));

        if ($normalized === 'article') {
            return $maxMb * 1024;
        }

        return $maxMb * 1024;
    }

    private function buildMarkdown(string $title, string $relativeUrl): string
    {
        return sprintf('[视频：%s](%s)', $title, $relativeUrl);
    }

    private function resolveTitle(?string $title, UploadedFile $video): string
    {
        $provided = trim((string) $title);

        if ($provided !== '') {
            return Str::limit($provided, 80, '');
        }

        $original = trim(pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME));
        $normalized = preg_replace('/[\s_-]+/u', ' ', $original) ?: '';
        $normalized = trim($normalized);

        if ($normalized === '' || Str::lower($normalized) === 'video') {
            return '粘贴视频';
        }

        return Str::limit($normalized, 80, '');
    }
}
