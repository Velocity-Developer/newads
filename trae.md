# Sistem Otomasi Negative Keywords

Dokumentasi ringkas untuk sistem otomatis pengambilan, analisis AI, dan pemrosesan negative keywords berbasis data search terms 0-click.

## Ringkasan Alur
- Ambil term 0-click dari API eksternal.
- Simpan unik ke database dan filter kata yang dikecualikan.
- Analisis relevansi dengan AI dan tandai hasil (`relevan` atau `negatif`).
- (Opsional) Pecah term menjadi frasa dan input ke Google Ads — saat ini integrasi input Google Ads dinonaktifkan.

## Perintah Artisan Utama
- `negative-keywords:fetch-terms --limit=100`
  - Mengambil search terms 0-click dari API eksternal dan menyimpannya.
- `negative-keywords:analyze-terms --batch-size=10`
  - Menganalisis term yang belum dinilai AI (scope `needsAiAnalysis`).
- `negative-keywords:process-phrases --batch-size=10`
  - Memecah term yang sudah berhasil diproses menjadi frasa individual dan menyiapkan input.
- `negative-keywords:input-google --batch-size=5`
  - Menginput negative keywords ke Google Ads (saat ini akan di-skip karena integrasi dimatikan).
- `negative-keywords:test-system --component=ai|google-ads|database|telegram|all`
  - Menjalankan tes komponen sistem secara terpisah atau keseluruhan.
- `test:safe-fetch --limit=5`
  - Uji fetch aman tanpa menyimpan ke database (menampilkan sampel terms dan hasil penyaringan).
- `test:google-ads-connection --dry-run`
  - Uji konfigurasi koneksi Google Ads (saat ini dinonaktifkan, hanya menampilkan peringatan).

## Penjadwalan (Contoh)
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Menit ke-5: Fetch terms
    $schedule->command('negative-keywords:fetch-terms')->cron('5 * * * *');

    // Menit ke-6: Analisis AI
    $schedule->command('negative-keywords:analyze-terms')->cron('6 * * * *');

    // Menit ke-7: Input ke Google Ads (opsional; saat ini nonaktif)
    $schedule->command('negative-keywords:input-google')->cron('7 * * * *');

    // Setiap 10 menit: Proses frasa
    $schedule->command('negative-keywords:process-phrases')->cron('*/10 * * * *');
}
```

## Konfigurasi

### API Eksternal (Velocity Ads)
- File: `config/integrations.php` (key `velocity_ads`)
- Env yang diperlukan:
  - `VELOCITY_ADS_API_URL` — default: `https://api.velocitydeveloper.com/new/adsfetch/fetch_terms_negative0click_secure.php`
  - `VELOCITY_ADS_API_TOKEN` — isi token mentah (tanpa "Bearer").

Catatan: Header `Authorization` ditambahkan sebagai Bearer di kode. Pastikan env berisi token mentah untuk menghindari format ganda.

### AI
- Env yang diperlukan:
  - `OPENAI_API_KEY`
  - `OPENAI_MODEL` — isi nama model sesuai penyedia yang Anda gunakan.

### Telegram (opsional)
- Env yang diperlukan:
  - `TELEGRAM_BOT_TOKEN`
  - `TELEGRAM_CHAT_ID` — bisa multi-ID, pisahkan dengan koma.

## Pengujian Cepat
```bash
# 1) Tes komponen AI
php artisan negative-keywords:test-system --component=ai

# 2) Safe fetch (tanpa simpan)
php artisan test:safe-fetch --limit=5

# 3) Fetch dan simpan ke DB
php artisan negative-keywords:fetch-terms --limit=20

# 4) Analisis AI batch kecil
php artisan negative-keywords:analyze-terms --batch-size=10

# 5) Proses frasa
php artisan negative-keywords:process-phrases --batch-size=10
```

## Catatan Penting
- Dedup: Penyimpanan term mengecek eksistensi di DB dan kolom `terms` bersifat unik.
- Filter: Kata yang dikecualikan seperti `jasa`, `harga`, `buat`, `bikin`, `murah`, `pembuatan`, `biaya`, `beli`, `pesan`, `velocity` akan dibuang sebelum simpan.
- Validasi term: Respons API dinormalisasi ke `search_term` dan diverifikasi agar bukan tanggal/waktu.
- Integrasi Google Ads input saat ini dinonaktifkan; perintah akan menampilkan peringatan dan keluar.

## Troubleshooting Singkat
- 401 API eksternal: Pastikan `VELOCITY_ADS_API_TOKEN` terisi token mentah dan `VELOCITY_ADS_API_URL` benar.
- "No zero-click terms found": Periksa payload API (biasanya berada di `data.search_terms`) dan pastikan ada item yang lolos validasi serta tidak termasuk kata yang dikecualikan.
- Konfigurasi tidak terbaca: Jalankan `php artisan config:clear` sebelum pengujian.

# Proyek NewAds — Workflow di Trae

Dokumen ini merangkum alur kerja pengembangan dan build paket produksi, termasuk opsi packaging ZIP dan variasi build.

## Ringkasan
- Build menggunakan command `build:production-structure` yang membentuk struktur `dist/laravel` dan `dist/public_html`.
- Setelah struktur terbentuk, sistem membuat ZIP otomatis. ZIP bisa disimpan di `dist/zip/` dan dapat dikonfigurasi namanya.
- Build “full” dapat menyertakan `vendor` dan `storage`; proses packaging melakukan pruning konten tidak esensial supaya ukuran tetap kecil.

## Pengembangan
- Jalankan server pengembangan:
```bash
composer dev
```

- Atau jalankan server secara manual:
```bash
php artisan serve --host 127.0.0.1 --port 8000
```

## Build & Packaging
- Full build (vendor + storage) langsung menjadi ZIP dan menghapus folder `dist` setelah ZIP dibuat:
```bash
composer run build:production-full
```

- Build standar (tanpa vendor/storage), ZIP-only:
```bash
composer run build:production
```

- Build dengan vendor saja (tanpa storage), ZIP-only:
```bash
composer run build:production-with-vendor
```

- Build minimal (exclude non-esensial), ZIP-only:
```bash
composer run build:production-full-minimal
```

Catatan: Skrip di atas menjalankan `@php artisan build:production-structure --zip --zip-only --zip-output=production.zip` di belakang layar. Jika perlu variasi manual, kamu bisa menjalankan artisan command sendiri.

## Opsi Command Build (Artisan)
Command: `php artisan build:production-structure`

Opsi umum:
- `--include-vendor`: sertakan folder `vendor` dalam build.
- `--include-storage`: sertakan folder `storage` dalam build.
- `--minimal`: build minimal (exclude beberapa file non-esensial).
- `--development`: build pengembangan (biasanya untuk debugging).

Opsi ZIP:
- `--zip`: buat artefak ZIP.
- `--zip-only`: hapus folder `dist/laravel` dan `dist/public_html` setelah ZIP dibuat.
- `--zip-output=production.zip`: nama file ZIP output.
- `--zip-dir=zip`: subfolder di `dist` untuk menyimpan ZIP (default: `zip` → `dist/zip/production.zip`).
- `--max-compress`: gunakan kompresi ZIP maksimum (lebih kecil, sedikit lebih lambat).

Contoh lengkap:
```bash
php artisan build:production-structure --include-vendor --include-storage --zip --zip-only --zip-output=production.zip --zip-dir=release --max-compress
```

## Struktur Output
- Folder kerja: `dist/`
  - `dist/laravel/` → aplikasi Laravel (app, bootstrap, config, routes, dll)
  - `dist/public_html/` → dokumen root untuk web server (index.php, .htaccess, assets)
  - ZIP: `dist/zip/production.zip` (atau `dist/<zip-dir>/<nama-zip>` jika diset)

## Ukuran & Pruning
- Saat `--include-storage` aktif, build akan memotong konten runtime berat:
  - `storage/logs`, `storage/framework/cache`, `storage/framework/sessions`, `storage/framework/testing` dibuat kosong (stub `.gitkeep`).
- Saat packaging, konten non-esensial di `vendor` difilter:
  - `tests`, `docs`, `examples`, `benchmarks`, repo metadata (`.git`, `.github`, dsb.), file tooling (`phpunit.xml`, `psalm.xml`, dsb.)
- Untuk ukuran lebih kecil lagi:
  - Jalankan di server: `composer install --no-dev --optimize-autoloader --classmap-authoritative`.

## Troubleshooting
- Error “The `--zip` option does not exist.” → Pastikan command `BuildProductionStructure` sudah punya opsi ZIP seperti di atas.
- `composer.json` tidak valid → jalankan validasi:
```bash
composer validate
```
- ZIP tidak muncul di `dist/zip/` → pastikan gunakan `--zip` dan `--zip-dir` sesuai kebutuhan.

## Deployment Singkat
- Jika ZIP menyertakan `vendor`:
```bash
php artisan key:generate
```
```bash
php artisan migrate --force
```
- Jika ZIP tidak menyertakan `vendor`:
```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```
```bash
php artisan key:generate
```
```bash
php artisan migrate --force
```
```bash
php artisan storage:link
```

Pastikan `APP_ENV=production` dan `APP_DEBUG=false` di `.env`. Atur permission `storage` dan `bootstrap/cache` agar bisa ditulis oleh web server.

# Integrasi Input Negative Keywords (Velocity API)

Tujuan
- Mengirim negative keywords ke `https://api.velocitydeveloper.com/new/adsfetch/input_keywords_negative.php` dengan mode `validate`/`execute`.
- Sumber:
  - `terms` dari `new_terms_negative_0click` dengan `hasil_cek_ai = negatif` → `match_type = EXACT`
  - `frasa` dari `new_frasa_negative` → `match_type = PHRASE`

Konfigurasi
- Tambahkan ke `.env`:
  - `VELOCITY_ADS_API_TOKEN="<token>"` (wajib, dikirim sebagai `Authorization: Bearer <token>`)
  - `VELOCITY_ADS_INPUT_API_URL="https://api.velocitydeveloper.com/new/adsfetch/input_keywords_negative.php"`
  - (opsional) `VELOCITY_ADS_MATCH_TYPE_TERMS=EXACT`
  - (opsional) `VELOCITY_ADS_MATCH_TYPE_FRASA=PHRASE` (ubah ke `PHARSE` jika API memang memerlukan)
- Konfigurasi di `config/integrations.php` akan membaca variabel ini.

Perintah Artisan (tidak dieksekusi sekarang)
- Validasi terms saja:
  - `php artisan negative-keywords:input-velocity --source=terms --mode=validate --batch-size=50`
- Eksekusi terms ke API:
  - `php artisan negative-keywords:input-velocity --source=terms --mode=execute --batch-size=50`
- Validasi frasa saja:
  - `php artisan negative-keywords:input-velocity --source=frasa --mode=validate --batch-size=50`
- Eksekusi frasa ke API:
  - `php artisan negative-keywords:input-velocity --source=frasa --mode=execute --batch-size=50`
- Validasi keduanya (dua panggilan API terpisah):
  - `php artisan negative-keywords:input-velocity --source=both --mode=validate --batch-size=50`
- Eksekusi keduanya:
  - `php artisan negative-keywords:input-velocity --source=both --mode=execute --batch-size=50`

Catatan Operasional
- Pada `mode=validate`, perintah hanya mengetes API, tidak mengubah status di DB.
- Pada `mode=execute`, status akan diperbarui:
  - Berhasil → `status_input_google = 'sukses'`, `notif_telegram = 'sukses'`
  - Gagal → `status_input_google = 'gagal'`, `retry_count++` (max 3 lalu `error`)
- Response API yang berisi `{"success": true/false}` akan diprioritaskan untuk menentukan keberhasilan.
- Jika API tidak mengembalikan JSON, fallback ke status HTTP (`2xx` dianggap sukses).

Troubleshooting
- Pastikan token `VELOCITY_ADS_API_TOKEN` aktif.
- Pastikan nilai `VELOCITY_ADS_MATCH_TYPE_FRASA` adalah `PHRASE`.
- Gunakan `--batch-size` kecil saat pertama kali coba mode `execute`.