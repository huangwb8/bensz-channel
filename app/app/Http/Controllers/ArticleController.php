<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Channel;
use App\Support\CommunityViewData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ArticleController extends Controller
{
    public function show(Channel $channel, Article $article, CommunityViewData $viewData): View|RedirectResponse
    {
        abort_unless($article->channel_id === $channel->id, 404);

        if (request()->segment(2) !== $channel->public_id || request()->segment(4) !== $article->public_id) {
            $article->loadMissing('channel');

            return to_route('articles.show', [$article->channel, $article], 301);
        }

        if (! $article->is_published || blank($article->published_at) || $article->published_at->isFuture()) {
            abort_unless(request()->user()?->isAdmin(), 404);
        }

        return view('articles.show', $viewData->article($article));
    }
}
