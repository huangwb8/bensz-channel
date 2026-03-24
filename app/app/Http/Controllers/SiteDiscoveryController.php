<?php

namespace App\Http\Controllers;

use App\Support\Seo\SiteDiscoveryService;
use Illuminate\Http\Response;

class SiteDiscoveryController extends Controller
{
    public function robots(SiteDiscoveryService $service): Response
    {
        return response($service->robotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(SiteDiscoveryService $service): Response
    {
        return response($service->sitemapXml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
