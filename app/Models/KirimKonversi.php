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
        'rekap_form_source',
        'tercatat',
        'conversion_action_id',
    ];

    // cast response to json
    protected $casts = [
        'response' => 'json',
        'tercatat' => 'boolean',
    ];

    //relasi dengan model RekapForm
    public function rekap_form()
    {
        return $this->belongsTo(RekapForm::class, 'rekap_form_id', 'id');
    }
}
