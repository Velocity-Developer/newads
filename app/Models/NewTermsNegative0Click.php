<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewTermsNegative0Click extends Model
{
    protected $table = 'new_terms_negative_0click';
    
    protected $fillable = [
        'terms',
        'hasil_cek_ai',
        'status_input_google',
        'retry_count',
        'notif_telegram',
    ];
    
    protected $casts = [
        'notif_telegram' => 'boolean',
        'retry_count' => 'integer',
    ];
    
    /**
     * Get the frasa records associated with this term.
     */
    public function frasa(): HasMany
    {
        return $this->hasMany(NewFrasaNegative::class, 'parent_term_id');
    }
    
    /**
     * Check if this term can be retried for Google Ads input.
     */
    public function canRetry(): bool
    {
        return $this->retry_count < 3 && 
               in_array($this->status_input_google, [null, 'gagal']);
    }
    
    /**
     * Increment retry count.
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        
        if ($this->retry_count >= 3) {
            $this->update(['status_input_google' => 'error']);
        }
    }
    
    /**
     * Scope for terms that need AI analysis.
     */
    public function scopeNeedsAiAnalysis($query)
    {
        return $query->whereNull('hasil_cek_ai')
                    ->whereIn('status_input_google', [null, 'gagal']);
    }
    
    /**
     * Scope for negative terms that need Google Ads input.
     */
    public function scopeNeedsGoogleAdsInput($query)
    {
        return $query->where('hasil_cek_ai', 'negatif')
                    ->whereIn('status_input_google', [null, 'gagal'])
                    ->where('retry_count', '<', 3);
    }
}
