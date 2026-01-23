<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function edit(): Response
    {
        $settings = SiteSetting::query()->first();

        return Inertia::render('settings/Appearance', [
            'branding' => [
                'siteTitle' => $settings?->site_title ?? config('app.name', 'Laravel'),
                'sidebarTitle' => $settings?->sidebar_title ?? 'Laravel Starter Kit',
                'sidebarIconUrl' => $settings?->sidebar_icon_path ? Storage::url($settings->sidebar_icon_path) : null,
                'faviconUrl' => $settings?->favicon_path ? Storage::url($settings->favicon_path) : null,
                'appleTouchIconUrl' => $settings?->apple_touch_icon_path ? Storage::url($settings->apple_touch_icon_path) : null,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_title' => ['required', 'string', 'max:255'],
            'sidebar_title' => ['required', 'string', 'max:255'],
            'sidebar_icon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg'],
            'apple_touch_icon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg'],
        ]);

        $settings = SiteSetting::query()->firstOrFail();

        $paths = [];

        if ($request->hasFile('sidebar_icon')) {
            $paths['sidebar_icon_path'] = $request->file('sidebar_icon')->store('branding', 'public');
        }

        if ($request->hasFile('favicon')) {
            $paths['favicon_path'] = $request->file('favicon')->store('branding', 'public');
        }

        if ($request->hasFile('apple_touch_icon')) {
            $paths['apple_touch_icon_path'] = $request->file('apple_touch_icon')->store('branding', 'public');
        }

        $settings->fill([
            'site_title' => $validated['site_title'],
            'sidebar_title' => $validated['sidebar_title'],
        ] + $paths)->save();

        return back();
    }
}
