<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_title',
        'sidebar_title',
        'sidebar_icon_path',
        'favicon_path',
        'apple_touch_icon_path',
    ];
}