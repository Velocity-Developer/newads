<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistWord extends Model
{
    protected $table = 'blacklist_words';

    protected $fillable = ['word', 'active', 'notes'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}