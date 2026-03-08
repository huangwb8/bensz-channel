<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Channel;
use App\Support\RssFeedBuilder;
use Illuminate\Http\Response;

class RssFeedController extends Controller
{
    public function all(RssFeedBuilder $builder): Response
    {
        $articles = Article::query()
            ->published()
            ->with(['channel', 'author'])
            ->latestPublished()
            ->limit(30)
            ->get();

        return response($builder->buildForAllChannels($articles), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }

    public function channel(Channel $channel, RssFeedBuilder $builder): Response
    {
        abort_unless($channel->is_public, 404);

        $articles = Article::query()
            ->published()
            ->whereBelongsTo($channel)
            ->with(['channel', 'author'])
            ->latestPublished()
            ->limit(30)
            ->get();

        return response($builder->buildForChannel($channel, $articles), 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
