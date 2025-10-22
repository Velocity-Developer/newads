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