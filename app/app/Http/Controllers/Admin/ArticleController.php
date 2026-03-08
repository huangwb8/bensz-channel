<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(): View
    {
        return view('admin.articles.index', [
            'articles' => Article::query()->with(['channel', 'author'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.articles.form', [
            'article' => new Article([
                'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
                'is_published' => true,
                'published_at' => now(),
            ]),
            'channels' => Channel::query()->ordered()->get(),
            'formAction' => route('admin.articles.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(
        Request $request,
        MarkdownRenderer $markdownRenderer,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $validated = $this->validateArticle($request);

        Article::query()->create([
            ...$validated,
            'author_id' => $request->user()->id,
            'excerpt' => $validated['excerpt'] ?: $markdownRenderer->excerpt($validated['markdown_body']),
            'html_body' => $markdownRenderer->toHtml($validated['markdown_body']),
        ]);

        $staticPageBuilder->buildAll();

        return to_route('admin.articles.index')->with('status', '文章已发布。');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', [
            'article' => $article,
            'channels' => Channel::query()->ordered()->get(),
            'formAction' => route('admin.articles.update', $article),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(
        Request $request,
        Article $article,
        MarkdownRenderer $markdownRenderer,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $validated = $this->validateArticle($request, $article);

        $article->update([
            ...$validated,
            'excerpt' => $validated['excerpt'] ?: $markdownRenderer->excerpt($validated['markdown_body']),
            'html_body' => $markdownRenderer->toHtml($validated['markdown_body']),
        ]);

        $staticPageBuilder->buildAll();

        return to_route('admin.articles.index')->with('status', '文章已更新。');
    }

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $validated = $request->validate([
            'channel_id' => ['required', 'exists:channels,id'],
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:200'],
            'markdown_body' => ['required', 'string'],
            'cover_gradient' => ['required', 'string', 'max:128'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? false);
        $validated['published_at'] = $validated['published_at'] ?? now();

        return $validated;
    }
}
