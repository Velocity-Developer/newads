<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewFrasaNegative extends Model
{
    protected $table = 'new_frasa_negative';
    
    protected $fillable = [
        'frasa',
        'parent_term_id',
        'status_input_google',
        'retry_count',
        'notif_telegram',
    ];
    
    protected $casts = [
        'notif_telegram' => 'boolean',
        'retry_count' => 'integer',
        'parent_term_id' => 'integer',
    ];
    
    /**
     * Get the parent term that owns this frasa.
     */
    public function parentTerm(): BelongsTo
    {
        return $this->belongsTo(NewTermsNegative0Click::class, 'parent_term_id');
    }
    
    /**
     * Check if this frasa can be retried for Google Ads input.
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
     * Check if a frasa is allowed (not in blacklist).
     */
    public static function isAllowedFrasa(string $frasa): bool
    {
        $blacklistedWords = [
            'web',
            'website', 
            'company',
            'profile',
            'tour',
            'travel',
            'property'
        ];
        
        $frasa = strtolower(trim($frasa));
        
        return !in_array($frasa, $blacklistedWords);
    }
    
    /**
     * Scope for frasa that need Google Ads input.
     */
    public function scopeNeedsGoogleAdsInput($query)
    {
        return $query->whereIn('status_input_google', [null, 'gagal'])
                    ->where('retry_count', '<', 3);
    }
    
    /**
     * Extract individual words from a term and return allowed frasa.
     */
    public static function extractAllowedFrasa(string $term): array
    {
        // Split by spaces and clean up
        $words = array_filter(array_map('trim', explode(' ', $term)));
        
        $allowedFrasa = [];
        foreach ($words as $word) {
            $cleanWord = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $word));
            if (!empty($cleanWord) && self::isAllowedFrasa($cleanWord)) {
                $allowedFrasa[] = $cleanWord;
            }
        }
        
        return array_unique($allowedFrasa);
    }
}
