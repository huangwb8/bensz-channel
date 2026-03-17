<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\StaticPageBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Tag::query()
                ->withCount(['articles', 'emailSubscriptions'])
                ->ordered()
                ->get(),
        ]);
    }

    public function store(Request $request, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $tag = Tag::query()->create($this->validateTag($request));
        $staticPageBuilder->rebuildAll();

        return response()->json(['tag' => $tag], 201);
    }

    public function update(Request $request, string $tag, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $tagModel = $this->resolveTag($tag);
        $tagModel->update($this->validateTag($request, $tagModel));
        $staticPageBuilder->rebuildAll();

        return response()->json(['tag' => $tagModel->fresh()]);
    }

    public function destroy(string $tag, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $this->resolveTag($tag)->delete();
        $staticPageBuilder->rebuildAll();

        return response()->json(['ok' => true]);
    }

    private function validateTag(Request $request, ?Tag $tag = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('tags', 'name')->ignore($tag?->id)],
            'slug' => ['nullable', 'string', 'max:80', Rule::unique('tags', 'slug')->ignore($tag?->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['slug'] = $this->makeTagSlug($validated['slug'] ?: $validated['name'], $tag);

        return $validated;
    }

    private function resolveTag(string $identifier): Tag
    {
        return Tag::query()->wherePublicReference($identifier)->firstOrFail();
    }

    private function makeTagSlug(?string $source, ?Tag $tag = null): string
    {
        $slug = Str::slug((string) $source);

        if ($slug !== '') {
            return $slug;
        }

        if ($tag instanceof Tag && $tag->slug !== '') {
            return $tag->slug;
        }

        return 'tag-'.Str::lower(Str::random(8));
    }
}
