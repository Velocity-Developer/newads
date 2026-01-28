<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchTerm extends Model
{
    // timestamp
    public $timestamps = true;

    protected $fillable = [
        'term',
        'check_ai',
        'iklan_dibuat',
        'failure_count',
        'waktu',
        'source',
    ];

    protected $casts = [
        'waktu' => 'datetime',
        'iklan_dibuat' => 'boolean',
        'failure_count' => 'integer',
    ];

    // append
    protected $appends = [
        'waktu_local',
    ];

    // tampilkan waktu dalam format local
    public function getWaktuLocalAttribute()
    {
        $waktu = $this->waktu ?? $this->created_at;
        $waktu = $waktu->timezone('Asia/Jakarta')->format('Y-m-d H:i:s');

        return $waktu;
    }
}
