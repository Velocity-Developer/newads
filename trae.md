# Sistem Negative Keywords Otomatis - Google Ads

Dokumentasi lengkap untuk sistem otomatis pengelolaan negative keywords dari Google Ads search terms dengan 0 clicks.

## Overview Sistem

Sistem ini secara otomatis:
1. Mengambil search terms dari Google Ads dengan 0 clicks
2. Menganalisis relevansi menggunakan AI
3. Menambahkan negative keywords ke Google Ads
4. Mengirim notifikasi via Telegram
5. Memecah terms menjadi frasa individual untuk coverage lebih luas

## Alur Eksekusi (Setiap 5 Menit)

### Menit ke-5: Fetch Search Terms
- **Command**: `FetchZeroClickTermsCommand`
- **Target**: Google Ads API (GAQL Query)
- **Filter**: 
  - `clicks = 0`
  - `added_excluded = NONE`
  - Tidak mengandung kata: `jasa`, `harga`, `buat`, `bikin`, `murah`, `pembuatan`, `biaya`, `beli`, `pesan`, `velocity`
- **Output**: Insert ke tabel `new_terms_negative_0click`
- **Duplikasi**: Skip jika terms sudah ada

### Menit ke-6: Analisis AI
- **Command**: `AnalyzeTermsWithAICommand`
- **Target**: Records dengan `hasil_cek_ai = null` dan `status_input_google IN (null, 'gagal')`
- **AI Prompt**: Tentukan apakah terms relevan atau negative
- **Output**: Update field `hasil_cek_ai` (`relevan`/`negatif`)

### Menit ke-7: Input Negative Keywords
- **Command**: `InputNegativeKeywordsCommand`
- **Target**: Records dengan `hasil_cek_ai = 'negatif'` dan `status_input_google IN (null, 'gagal')`
- **Match Type**: `EXACT`
- **Retry Logic**: Maksimal 3x, setelah itu status = `error`
- **Output**: 
  - Update `status_input_google` (`berhasil`/`gagal`/`error`)
  - Kirim notifikasi Telegram
  - Insert ke `new_frasa_negative` (pecah per kata, skip jika frasa sudah ada)

### Proses Tambahan: Frasa Individual
- **Command**: `ProcessIndividualPhrasesCommand`
- **Target**: Records dari `new_frasa_negative` dengan `status_input_google IN (null, 'gagal')`
- **Duplikasi**: Skip jika frasa sudah ada di tabel `new_frasa_negative`
- **Blacklist**: `web`, `website`, `company`, `profile`, `tour`, `travel`, `property`
- **Match Type**: `PHRASE`
- **Retry Logic**: Maksimal 3x, setelah itu status = `error`

## Struktur Database

### Tabel: `new_terms_negative_0click`
```sql
CREATE TABLE new_terms_negative_0click (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    terms VARCHAR(500) NOT NULL UNIQUE,
    hasil_cek_ai ENUM('relevan', 'negatif') NULL,
    status_input_google ENUM('berhasil', 'gagal', 'error') NULL,
    retry_count INT DEFAULT 0,
    notif_telegram BOOLEAN DEFAULT FALSE,
    INDEX idx_ai_status (hasil_cek_ai, status_input_google),
    INDEX idx_created_at (created_at)
);
```

### Tabel: `new_frasa_negative`
```sql
CREATE TABLE new_frasa_negative (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    frasa VARCHAR(255) NOT NULL UNIQUE,
    parent_term_id BIGINT UNSIGNED NULL,
    status_input_google ENUM('berhasil', 'gagal', 'error') NULL,
    retry_count INT DEFAULT 0,
    notif_telegram BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (parent_term_id) REFERENCES new_terms_negative_0click(id),
    INDEX idx_status (status_input_google),
    INDEX idx_frasa (frasa)
);
```

## Komponen Sistem

### 1. Models
- `App\Models\NewTermsNegative0Click`
- `App\Models\NewFrasaNegative`

### 2. Services
- `App\Services\GoogleAds\SearchTermFetcher` - Fetch & input ke Google Ads
- `App\Services\AI\TermAnalyzer` - Analisis AI untuk terms
- `App\Services\Telegram\NotificationService` - Kirim notifikasi
- `App\Services\NegativeKeywords\TermProcessor` - Logic pemrosesan terms

### 3. Console Commands
- `App\Console\Commands\FetchZeroClickTermsCommand`
- `App\Console\Commands\AnalyzeTermsWithAICommand`
- `App\Console\Commands\InputNegativeKeywordsCommand`
- `App\Console\Commands\ProcessIndividualPhrasesCommand`

### 4. Jobs (Optional - untuk queue)
- `App\Jobs\FetchZeroClickTermsJob`
- `App\Jobs\AnalyzeTermsJob`
- `App\Jobs\InputNegativeKeywordsJob`

### 5. Scheduler Configuration
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Menit ke-5: Fetch terms
    $schedule->command('negative-keywords:fetch-terms')
             ->cron('5 * * * *');
    
    // Menit ke-6: Analisis AI
    $schedule->command('negative-keywords:analyze-terms')
             ->cron('6 * * * *');
    
    // Menit ke-7: Input ke Google Ads
    $schedule->command('negative-keywords:input-keywords')
             ->cron('7 * * * *');
    
    // Setiap 10 menit: Process frasa individual
    $schedule->command('negative-keywords:process-phrases')
             ->cron('*/10 * * * *');
}
```

## Konfigurasi Environment

### Google Ads API
```env
GOOGLE_ADS_CLIENT_ID=your_client_id
GOOGLE_ADS_CLIENT_SECRET=your_client_secret
GOOGLE_ADS_DEVELOPER_TOKEN=your_developer_token
GOOGLE_ADS_CUSTOMER_ID=your_customer_id
GOOGLE_ADS_CAMPAIGN_ID=your_campaign_id
GOOGLE_ADS_REFRESH_TOKEN_PATH=storage/app/private/google_ads/refresh_token.txt
```

### AI Integration (GPT-5)
```env
OPENAI_API_KEY=your_openai_key
OPENAI_MODEL=gpt-4  # atau model yang diinginkan
```

### Telegram Notification
```env
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
```

## Error Handling & Monitoring

### Retry Logic
- Maksimal 3x retry untuk setiap operasi Google Ads
- Setelah 3x gagal, status berubah menjadi `error`
- Records dengan status `error` tidak diproses lagi

### Logging
- Semua operasi dicatat di Laravel log
- Error Google Ads API dicatat dengan detail
- Notifikasi Telegram untuk error kritis

### Monitoring Points
- Jumlah terms baru per hari
- Success rate input negative keywords
- Response time Google Ads API
- Akurasi analisis AI

## Testing

### Unit Tests
- Test filter kata yang dikecualikan
- Test logic retry dan error handling
- Test parsing dan pemecahan frasa

### Integration Tests
- Test Google Ads API connection
- Test AI analysis workflow
- Test Telegram notification

### Manual Testing Commands
```bash
# Test fetch terms
php artisan negative-keywords:fetch-terms --dry-run

# Test AI analysis
php artisan negative-keywords:analyze-terms --limit=10

# Test input keywords
php artisan negative-keywords:input-keywords --dry-run
```

## Deployment Checklist

1. ✅ Setup environment variables
2. ✅ Run migrations
3. ✅ Setup Google Ads refresh token
4. ✅ Test API connections (Google Ads, OpenAI, Telegram)
5. ✅ Configure cron scheduler
6. ✅ Setup monitoring & alerting
7. ✅ Test end-to-end workflow

## Troubleshooting

### Google Ads API Issues
- Cek quota dan rate limits
- Validasi refresh token
- Cek permission campaign

### AI Analysis Issues
- Monitor OpenAI API quota
- Validasi prompt effectiveness
- Fallback mechanism jika AI tidak available

### Telegram Notification Issues
- Cek bot token dan chat ID
- Handle rate limiting Telegram API

## Performance Considerations

- Batch processing untuk operasi database
- Rate limiting untuk Google Ads API calls
- Queue system untuk heavy operations
- Database indexing untuk query performance