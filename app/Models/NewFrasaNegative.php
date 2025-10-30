<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model untuk menyimpan frasa negative keywords yang diekstrak dari search terms
 * 
 * @property string $frasa Frasa negative keyword yang diekstrak
 * @property int $parent_term_id ID dari parent term di tabel new_terms_negative_0click
 * @property string|null $status_input_google Status input ke Google Ads: 'sukses', 'gagal', atau 'error'
 * @property int $retry_count Jumlah percobaan ulang (maksimal 3)
 * @property string|null $notif_telegram Status notifikasi Telegram: 'sukses' atau 'gagal'
 */
/**
 * @property string|null $hasil_cek_ai Nilai hasil cek AI: 'indonesia' atau 'luar'
 */
class NewFrasaNegative extends Model {
    protected $table = 'new_frasa_negative';
    
    protected $fillable = [
        'frasa',
        'parent_term_id',
        'status_input_google',
        'hasil_cek_ai',
        'retry_count',
        'notif_telegram',
    ];
    
    protected $casts = [
        'retry_count' => 'integer',
        'parent_term_id' => 'integer',
    ];
    
    // Konstanta untuk enum values
    const STATUS_BERHASIL = 'sukses';
    const STATUS_GAGAL = 'gagal';
    const STATUS_ERROR = 'error';

    const NOTIF_BERHASIL = 'sukses';
    const NOTIF_GAGAL = 'gagal';

    // Nilai enum untuk hasil cek AI
    const HASIL_CEK_AI_INDONESIA = 'indonesia';
    const HASIL_CEK_AI_LUAR = 'luar';
    
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
               in_array($this->status_input_google, [null, self::STATUS_GAGAL]);
    }
    
    /**
     * Increment retry count.
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
        
        if ($this->retry_count >= 3) {
            $this->update(['status_input_google' => self::STATUS_ERROR]);
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
        return $query
            ->where(function ($q) {
                $q->whereNull('status_input_google')
                  ->orWhere('status_input_google', self::STATUS_GAGAL);
            })
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
