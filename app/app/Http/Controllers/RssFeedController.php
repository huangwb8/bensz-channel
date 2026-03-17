<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Tag;
use App\Support\RssFeedBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class RssFeedController extends Controller
{
    public function all(RssFeedBuilder $builder): Response
    {
        $articles = Article::query()
            ->published()
            ->with(['channel', 'author', 'tags'])
            ->latestPublished()
            ->limit(30)
            ->get();

        return response($builder->buildForAllChannels($articles), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    public function channel(Channel $channel, RssFeedBuilder $builder): Response|RedirectResponse
    {
        abort_unless($channel->is_public, 404);

        if (request()->segment(3) !== $channel->public_id.'.xml') {
            return to_route('feeds.channels.show', $channel, 301);
        }

        $articles = Article::query()
            ->published()
            ->when(
                $channel->isFeaturedChannel(),
                fn ($query) => $query->featured(),
                fn ($query) => $query->whereBelongsTo($channel),
            )
            ->with(['channel', 'author', 'tags'])
            ->latestPublished()
            ->limit(30)
            ->get();

        return response($builder->buildForChannel($channel, $articles), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    public function tag(Tag $tag, RssFeedBuilder $builder): Response|RedirectResponse
    {
        if (request()->segment(3) !== $tag->public_id.'.xml') {
            return to_route('feeds.tags.show', $tag, 301);
        }

        $articles = Article::query()
            ->published()
            ->whereHas('tags', fn ($query) => $query->whereKey($tag->id))
            ->with(['channel', 'author', 'tags'])
            ->latestPublished()
            ->limit(30)
            ->get();

        return response($builder->buildForTag($tag, $articles), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
