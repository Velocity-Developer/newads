<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekapForm extends Model
{
    protected $table = 'rekap_forms';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'source',
        'source_id',
        'nama',
        'no_whatsapp',
        'jenis_website',
        'ai_result',
        'via',
        'utm_content',
        'utm_medium',
        'greeting',
        'status',
        'gclid',
        'cek_konversi_ads',
        'cek_konversi_nominal',
        'kategori_konversi_nominal',
        'tanggal',
        'created_at',
    ];

    protected $casts = [
        'cek_konversi_ads' => 'boolean',
        'cek_konversi_nominal' => 'boolean',
        'tanggal' => 'datetime',
        'created_at' => 'datetime',
    ];
}
