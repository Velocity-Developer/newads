# Workflow Otomasi Negative Keywords

Dokumentasi alur kerja sistem untuk mengambil search terms 0-click, analisis AI, input negative keywords via Velocity API, ekspor Excel, dan notifikasi Telegram.

## Ringkasan Alur
- Ambil search terms 0-click dari API eksternal (Velocity).
- Simpan unik ke database dan saring kata-kata yang dikecualikan.
- Analisis relevansi dengan AI (`relevan` atau `negatif`).
- Untuk yang `negatif`, input sebagai negative keywords via Velocity API (terms dan frasa).
- Update status di database (berhasil/gagal) dan kirim notifikasi Telegram.
- Ekspor data ke Excel dari UI menggunakan parameter `export=excel` di route yang sama.

## Model & Status
- `App\Models\NewTermsNegative0Click`
  - Relasi: `frasa()` → ke `NewFrasaNegative`.
  - Status: `status_input_google` (`berhasil`/`gagal`/`null`), `retry_count`, `notif_telegram`.
  - Scope: `needsGoogleAdsInput()` → pilih terms dengan `hasil_cek_ai = negatif`, `status_input_google` `null/gagal`, `retry_count < 3`.
- `App\Models\NewFrasaNegative`
  - Relasi: `parentTerm()` → ke `NewTermsNegative0Click`.
  - Status: sama pola dengan terms.
  - Scope: `needsGoogleAdsInput()` untuk frasa (pola sama).

## Perintah Artisan (Commands)
- `negative-keywords:fetch-terms --limit=100`
  - Ambil terms dari API eksternal Velocity (via `SearchTermFetcher`), simpan unik, saring kata-kata `excludedWords`.
- `negative-keywords:analyze-terms --batch-size=10`
  - Analisis AI untuk terms yang belum dinilai (via `TermAnalyzer`), set `hasil_cek_ai` menjadi `relevan` atau `negatif`.
- `negative-keywords:input-velocity --source=both|terms|frasa --mode=validate|execute --batch-size=50`
  - Kirim terms/frasa ke Velocity API (via `NegativeKeywordInputService::send()`).
  - Jika `mode=execute`, update status DB:
    - Berhasil → set `status_input_google = berhasil`, `notif_telegram = berhasil`.
    - Gagal → increment `retry_count`, set `status_input_google = gagal`.
  - Kirim notifikasi Telegram (berhasil/gagal) dengan daftar item (maks 50).
- `negative-keywords:process-phrases --batch-size=10`
  - Pecah terms yang sudah diproses menjadi frasa individual (menyiapkan input frasa), update status sesuai hasil.
- `negative-keywords:input-google --batch-size=5`
  - Integrasi Google Ads dinonaktifkan saat ini (stub `addNegativeKeyword()` selalu `false`), jadwal default masih memanggil ini.
- `negative-keywords:test-system --component=ai|google-ads|database|telegram|all`
  - Uji tiap komponen sistem (konfigurasi, koneksi, layanan).

## Layanan (Services)
- `App\Services\Velocity\NegativeKeywordInputService`
  - `send(array $terms, string $matchType, string $mode)` → mengembalikan `{ success, status, json, error }`.
  - Header `Authorization` menggunakan token dari env (`config('integrations.velocity_ads.api_token')` berformat `Bearer <token>`).
  - `getMatchTypeForSource('terms'|'frasa')` → diatur via env (`EXACT` untuk terms, `PHRASE` untuk frasa).
  - `Http::retry(3, 200)` dengan `timeout(30)`, logging saat gagal.
- `App\Services\GoogleAds\SearchTermFetcher`
  - Mengambil terms 0-click dari Velocity API (konfigurasi `api_url`, `api_token`).
  - Memiliki daftar kata yang dikecualikan (mis. `jasa`, `harga`, `murah`, dll.).
  - `addNegativeKeyword()` adalah stub (integrasi Google Ads mati).
- `App\Services\AI\TermAnalyzer`
  - Dipakai di `negative-keywords:analyze-terms` untuk klasifikasi `relevan` atau `negatif`.
- `App\Services\Telegram\NotificationService`
  - `sendMessage()` ke beberapa chat ID (HTML `parse_mode`).
  - Helper: `notifyNegativeKeywordSuccess/Failure` (per keyword), `notifyBatchResults`, `notifyAiAnalysisResults`, `notifyNewTermsFetched`, `notifySystemError`, `notifyDailySummary`.
  - Command Velocity menggunakan `sendMessage()` dengan pesan batch yang memuat jumlah, match-type, mode, status API, dan daftar item.

## Kontroler & Ekspor Excel
- `TermsController@index`
  - Filter: pencarian, hasil AI, status Google, notif Telegram, sort, rentang tanggal (`date_from`/`date_to`).
  - Jika request mengandung `export=excel`, return `Excel::download(new TermsExport($query), 'terms_export.xlsx')`.
- `FrasaController@index`
  - Filter: mirip dengan terms, rentang tanggal juga.
  - Jika `export=excel`, return `Excel::download(new FrasaExport($query), 'frasa_export.xlsx')`.
- `App\Exports\TermsExport` dan `App\Exports\FrasaExport`
  - Implement `FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`.
  - Mapping kolom sesuai kebutuhan (ID, isi, parent term, status, retry, notif, created_at).
- UI (Vue)
  - `TermsFilters.vue` / `FrasaFilters.vue` memiliki tombol "Export to Excel" yang mengakses route index yang sama dengan `?export=excel&<filters>`.

## Penjadwalan (Kernel)
- Siklus 7-menit:
  - Menit 1 → `negative-keywords:fetch-terms --limit=50`.
  - Menit 2 → `negative-keywords:analyze-terms --batch-size=10`.
  - Menit 3 → Saat ini: `negative-keywords:input-google --batch-size=5` (integrasi Google Ads nonaktif).
    - Rekomendasi: ganti ke `negative-keywords:input-velocity --source=both --mode=execute --batch-size=50`.
  - Menit 7 (mod 0) → `negative-keywords:process-phrases --batch-size=10`.
- Perawatan:
  - Ringkasan harian pukul 23:00 → memanggil `NotificationService` (perlu penyesuaian agar memakai `notifyDailySummary(stats)`).
  - Pembersihan mingguan (Minggu 02:00) → hapus data berhasil lebih dari 30 hari (terms dan frasa).
  - Setiap jam → reset `status_input_google` ke `null` untuk item `gagal` dengan `retry_count < 3` (agar bisa diproses ulang).

## Alur Data (Detail)
1. Fetch → simpan unik → saring kata dikecualikan.
2. Analisis AI → set `hasil_cek_ai`.
3. Input Velocity:
   - Petakan sumber (`terms`/`frasa`) via scope `needsGoogleAdsInput`.
   - Kumpulkan array item (limit batch).
   - Kirim ke Velocity API (`send()`), bentuk pesan Telegram (berhasil/gagal) berisi ringkasan dan daftar item, kirim via `sendMessage()`.
   - Jika `mode=execute`, update status DB sesuai hasil; yang gagal `retry_count++`.
4. Phrases (opsional) → pecah terms menjadi frasa, persiapan input frasa, update status sesuai hasil.
5. UI → pengguna dapat ekspor Excel dari halaman `terms`/`frasa`.

## Konfigurasi (env)
- Velocity:
  - `VELOCITY_ADS_API_URL` (fetch terms), `VELOCITY_ADS_API_TOKEN` (token mentah; akan di-prefix `Bearer ` di config).
  - `VELOCITY_ADS_INPUT_API_URL` (input negative keywords).
  - `VELOCITY_ADS_MATCH_TYPE_TERMS` default `EXACT`, `VELOCITY_ADS_MATCH_TYPE_FRASA` default `PHRASE`.
- Telegram:
  - `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID` (bisa multi-ID, pisahkan dengan koma).
- AI:
  - `OPENAI_API_KEY`, `OPENAI_MODEL`.

## Error Handling & Retry
- Velocity `send()` melakukan retry 3x dengan delay 200ms, timeout 30s, dan logging detail saat gagal.
- Command Velocity:
  - Saat gagal, `retry_count` bertambah dan status diset `gagal`.
  - Scheduler hourly mengosongkan status `gagal` untuk `retry_count < 3` agar bisa dicoba lagi.
- Notifikasi `notifySystemError()` tersedia untuk error sistem yang perlu diangkat.

## Eksekusi Manual (Opsional)
- Jalankan komponen tertentu untuk verifikasi cepat:
  - `php artisan negative-keywords:fetch-terms --limit=50`
  - `php artisan negative-keywords:analyze-terms --batch-size=10`
  - `php artisan negative-keywords:input-velocity --source=both --mode=execute --batch-size=50`
  - `php artisan negative-keywords:process-phrases --batch-size=10`
  - `php artisan negative-keywords:test-system --component=all`

## Catatan & Rekomendasi
- Update Kernel menit ke-3 ke `input-velocity` untuk menjalankan alur baru (Google Ads input saat ini nonaktif).
- Daily summary di Kernel memanggil metode yang tidak ada (`sendDailySummary()`), perlu diganti ke pemanggilan `notifyDailySummary(stats)` dengan penyusunan `stats`.
- Jika ingin notifikasi Telegram per item (bukan batch), `NotificationService` sudah menyediakan `notifyNegativeKeywordSuccess/Failure`, namun saat ini command Velocity mengirim batch summary agar tidak spam.