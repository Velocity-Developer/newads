<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KirimKonversi extends Model
{
    //
    protected $table = 'kirim_konversis';
    protected $fillable = [
        'gclid',
        'jobid',
        'waktu',
        'status',
        'response',
        'source',
        'rekap_form_id',
    ];
}
