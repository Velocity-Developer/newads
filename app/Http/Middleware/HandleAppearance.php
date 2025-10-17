<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('appearance', $request->cookie('appearance') ?? 'system');

        $settings = SiteSetting::query()->first();

        View::share('siteTitle', $settings?->site_title ?? config('app.name', 'Laravel'));
        View::share('faviconUrl', $settings?->favicon_path ? Storage::url($settings->favicon_path) : null);
        View::share('appleTouchIconUrl', $settings?->apple_touch_icon_path ? Storage::url($settings->apple_touch_icon_path) : null);

        return $next($request);
    }
}
