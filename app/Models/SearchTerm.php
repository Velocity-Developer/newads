<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchTerm extends Model
{
    protected $fillable = [
        'term',
        'ai_result',
        'google_ads_status',
        'telegram_send',
        'waktu',
    ];

    protected $casts = [
        'waktu' => 'datetime',
        'telegram_send' => 'boolean',
        'google_ads_status' => 'boolean',
    ];
}
