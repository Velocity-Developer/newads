<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model untuk menyimpan search terms zero-click dari Google Ads
 * 
 * @property string $terms Search term yang diambil dari Google Ads
 * @property string|null $hasil_cek_ai Hasil analisis AI: 'relevan' atau 'negatif'
 * @property string|null $status_input_google Status input ke Google Ads: 'sukses', 'gagal', atau 'error'
 * @property int $retry_count Jumlah percobaan ulang (maksimal 3)
 * @property string|null $notif_telegram Status notifikasi Telegram: 'sukses' atau 'gagal'
 */
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
        'retry_count' => 'integer',
    ];
    
    // Konstanta untuk enum values
    const HASIL_AI_RELEVAN = 'relevan';
    const HASIL_AI_NEGATIF = 'negatif';
    
    const STATUS_BERHASIL = 'sukses';
    const STATUS_GAGAL = 'gagal';
    const STATUS_ERROR = 'error';
    
    const NOTIF_BERHASIL = 'sukses';
    const NOTIF_GAGAL = 'gagal';
    
    /**
     * Get the frasa records associated with this term.
     */
    public function frasa(): HasMany
    {
        return $this->hasMany(NewFrasaNegative::class, 'parent_term_id', 'id');
    }
    
    /**
     * Check if this term can be retried for Google Ads input.
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
     * Scope for terms that need AI analysis.
     */
    public function scopeNeedsAiAnalysis($query)
    {
        return $query->whereNull('hasil_cek_ai');
    }
    
    /**
     * Scope for negative terms that need Google Ads input.
     */
    public function scopeNeedsGoogleAdsInput($query)
    {
        return $query->where('hasil_cek_ai', self::HASIL_AI_NEGATIF)
                    ->whereIn('status_input_google', [null, self::STATUS_GAGAL])
                    ->where('retry_count', '<', 3);
    }
}
