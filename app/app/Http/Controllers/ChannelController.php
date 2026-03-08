<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Support\CommunityViewData;
use Illuminate\Contracts\View\View;

class ChannelController extends Controller
{
    public function show(Channel $channel, CommunityViewData $viewData): View
    {
        abort_unless($channel->is_public, 404);

        return view('channels.show', $viewData->channel($channel));
    }
}
