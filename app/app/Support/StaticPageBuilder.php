<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Channel;
use Illuminate\Filesystem\Filesystem;

class StaticPageBuilder
{
    public function __construct(
        private readonly CommunityViewData $viewData,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function buildAll(): void
    {
        if (! config('community.static.enabled')) {
            return;
        }

        $outputRoot = public_path(config('community.static.output_dir'));

        if ($this->filesystem->exists($outputRoot)) {
            $this->filesystem->cleanDirectory($outputRoot);
        }

        $this->store('index.html', view('home', [...$this->viewData->home(), 'staticPage' => true])->render());

        Channel::query()->ordered()->get()->each(function (Channel $channel): void {
            $this->store(
                'channels/'.$channel->slug.'/index.html',
                view('channels.show', [...$this->viewData->channel($channel), 'staticPage' => true])->render(),
            );
        });

        Article::query()->published()->with('channel')->latestPublished()->get()->each(function (Article $article): void {
            $this->store(
                'channels/'.$article->channel->slug.'/articles/'.$article->slug.'/index.html',
                view('articles.show', [...$this->viewData->article($article), 'staticPage' => true])->render(),
            );
        });
    }

    private function store(string $relativePath, string $html): void
    {
        $path = public_path(trim(config('community.static.output_dir'), '/').'/'.$relativePath);

        $this->filesystem->ensureDirectoryExists(dirname($path));

        $minified = $this->minify($html);

        $this->filesystem->put($path, $minified);
        $this->filesystem->put($path.'.gz', gzencode($minified, 9));
    }

    private function minify(string $html): string
    {
        return trim((string) preg_replace('/>\s+</', '><', $html));
    }
}
