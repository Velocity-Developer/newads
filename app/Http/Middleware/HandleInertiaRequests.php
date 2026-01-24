<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $settings = SiteSetting::query()->first();

        return [
            ...parent::share($request),
            'name' => $settings?->site_title ?? config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'branding' => [
                'siteTitle' => $settings?->site_title ?? config('app.name', 'Laravel'),
                'sidebarTitle' => $settings?->sidebar_title ?? 'Laravel Starter Kit',
                'sidebarIconUrl' => $settings?->sidebar_icon_path ? Storage::url($settings->sidebar_icon_path) : null,
                'faviconUrl' => $settings?->favicon_path ? Storage::url($settings->favicon_path) : null,
                'appleTouchIconUrl' => $settings?->apple_touch_icon_path ? Storage::url($settings->apple_touch_icon_path) : null,
            ],
            'appInfo' => [
                'version' => config('app.version'),
            ],
        ];
    }
}
