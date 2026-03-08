<?php

namespace App\Http\Controllers;

use App\Support\CommunityViewData;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(CommunityViewData $viewData): View
    {
        return view('home', $viewData->home());
    }
}
