<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(): View
    {
        return view('admin.tags.index', [
            'tags' => Tag::query()
                ->withCount(['articles', 'emailSubscriptions'])
                ->ordered()
                ->get(),
        ]);
    }

    public function store(Request $request, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $validated = $this->validateTag($request);

        Tag::query()->create($validated);
        $staticPageBuilder->rebuildAll();

        return to_route('admin.tags.index')->with('status', '标签已创建。');
    }

    public function update(Request $request, Tag $tag, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $tag->update($this->validateTag($request, $tag));
        $staticPageBuilder->rebuildAll();

        return to_route('admin.tags.index')->with('status', '标签已更新。');
    }

    public function destroy(Tag $tag, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $tag->delete();
        $staticPageBuilder->rebuildAll();

        return to_route('admin.tags.index')->with('status', '标签已删除。');
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
