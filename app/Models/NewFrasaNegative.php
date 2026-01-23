<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

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
class NewFrasaNegative extends Model
{
    protected $table = 'new_frasa_negative';

    protected $fillable = [
        'frasa',
        'parent_term_id',
        'status_input_google',
        'hasil_cek_ai',
        'retry_count',
        'notif_telegram',
        'campaign_id',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'parent_term_id' => 'integer',
        'campaign_id' => 'integer',
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
     * Scope: frasa yang perlu dianalisis AI (hasil_cek_ai masih null)
     */
    public function scopeNeedsAiAnalysis($query)
    {
        return $query->whereNull('hasil_cek_ai');
    }

    /**
     * Scope: frasa yang perlu diinput ke Google Ads (hasil AI 'luar')
     * - status_input_google masih null atau gagal
     * - retry_count di bawah 3
     */
    public function scopeNeedsGoogleAdsInput($query)
    {
        return $query->where('hasil_cek_ai', self::HASIL_CEK_AI_LUAR)
            ->where(function ($q) {
                $q->whereNull('status_input_google')
                    ->orWhere('status_input_google', self::STATUS_GAGAL);
            })
            ->where('retry_count', '<', 3);
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
     * Ambil set blacklist aktif dari cache (case-insensitive)
     */
    public static function getBlacklistSet(): array
    {
        $words = Cache::remember('blacklist_words_active', 300, function () {
            return BlacklistWord::active()->pluck('word')->all();
        });

        $set = [];
        foreach ($words as $w) {
            // Normalize ke lowercase untuk case-insensitive matching
            $set[strtolower($w)] = true;
        }

        return $set;
    }

    public static function isAllowedFrasa(string $frasa): bool
    {
        $frasa = trim($frasa);
        if ($frasa === '') {
            return false;
        }

        $set = self::getBlacklistSet();

        // Compare dengan lowercase untuk case-insensitive
        return ! isset($set[strtolower($frasa)]);
    }

    public static function extractAllowedFrasa(string $term): array
    {
        // Split by whitespace, pertahankan case asli, boleh bersihkan spasi
        $words = array_filter(array_map('trim', preg_split('/\s+/', $term)));

        $set = self::getBlacklistSet();
        $allowedFrasa = [];

        foreach ($words as $word) {
            $cleanWord = trim($word);
            if ($cleanWord === '') {
                continue;
            }

            // Buang jika case-insensitive match ada di blacklist
            if (! isset($set[strtolower($cleanWord)])) {
                $allowedFrasa[] = $cleanWord;
            }
        }

        // Dedup case-insensitive dengan mempertahankan case asli
        $seen = [];
        $result = [];
        foreach ($allowedFrasa as $frasa) {
            $lower = strtolower($frasa);
            if (! isset($seen[$lower])) {
                $seen[$lower] = true;
                $result[] = $frasa;
            }
        }

        return array_values($result);
    }

    public function setHasilCekAiAttribute($value): void
    {
        $v = is_string($value) ? strtolower(trim($value)) : null;
        $this->attributes['hasil_cek_ai'] = in_array($v, [self::HASIL_CEK_AI_INDONESIA, self::HASIL_CEK_AI_LUAR], true)
            ? $v
            : null;
    }
}
