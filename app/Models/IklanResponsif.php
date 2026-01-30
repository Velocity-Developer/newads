<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IklanResponsif extends Model
{
    protected $fillable = [
        'group_iklan',
        'kata_kunci',
        'search_term_id',
        'nomor_group_iklan',
        'nomor_kata_kunci',
        'status',
    ];

    // relasi dengan model SearchTerm
    public function search_term()
    {
        return $this->hasOne(SearchTerm::class, 'search_term_id', 'id');
    }
}
