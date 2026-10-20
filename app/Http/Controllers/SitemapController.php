<?php

namespace App\Http\Controllers;

use App\Support\SitemapBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(SitemapBuilder $builder): Response
    {
        $xml = Cache::remember(SitemapBuilder::CACHE_KEY, now()->addHour(), fn () => $builder->toXml());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
