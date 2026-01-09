<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KirimKonversi extends Model
{
    use HasFactory;

    protected $fillable = [
        'gclid',
        'jobid',
        'waktu',
        'status',
        'response',
        'source',
        'rekap_form_id',
        'tercatat',
    ];

    // cast response to json
    protected $casts = [
        'response' => 'json',
        'tercatat' => 'boolean',
    ];
}
