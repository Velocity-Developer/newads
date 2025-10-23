# Panduan Deploy — NewAds

Panduan ini menjelaskan langkah deploy paket ZIP yang dihasilkan oleh command `build:production-structure`. ZIP dibuat langsung di subfolder `dist/zip/` (bisa dikonfigurasi) dan dapat menyertakan `vendor` serta `storage` yang sudah dipangkas agar ukuran lebih kecil.

## Artefak Build
- Lokasi ZIP: `dist/zip/production.zip` (atau `dist/<zip-dir>/<nama-zip>` jika dikonfigurasi).
- Struktur di dalam ZIP:
  - `laravel/` → aplikasi Laravel (app, bootstrap, config, routes, dll)
  - `public_html/` → dokumen root untuk web server (index.php, .htaccess, assets)

## Variasi Build
- Full build (vendor + storage): siap jalan setelah ekstrak dan konfigurasi `.env`.
- Build tanpa vendor: lebih ringan, memerlukan `composer install` di server.
- Minimal build: eksklusi file non-esensial dan pruning storage runtime (logs, cache, sessions, testing).

## Pra-Deploy
1. Siapkan server dengan PHP, Composer, ekstensi `zip`, database, dan akses shell.
2. Buat folder tujuan, misal `/var/www/newads` (atau path sesuai server kamu).
3. Upload ZIP ke server.

## Deploy — Vendor Disertakan
1. Ekstrak ZIP ke lokasi target.
2. Konfigurasi `.env` di `laravel/.env` (buat dari `.env.example` jika belum ada).
3. Generate key:
```bash
php artisan key:generate
```
4. Migrasi database:
```bash
php artisan migrate --force
```
5. Link storage (jika diperlukan untuk akses public):
```bash
php artisan storage:link
```
6. Set permission write untuk `laravel/storage` dan `laravel/bootstrap/cache` sesuai web server.
7. Arahkan vhost/web server ke `public_html/`.

## Deploy — Vendor Tidak Disertakan
1. Ekstrak ZIP ke lokasi target.
2. Konfigurasi `.env` di `laravel/.env`.
3. Install dependencies:
```bash
composer install --no-dev --optimize-autoloader --classmap-authoritative
```
4. Generate key:
```bash
php artisan key:generate
```
5. Migrasi database:
```bash
php artisan migrate --force
```
6. Link storage:
```bash
php artisan storage:link
```
7. Set permission write untuk `laravel/storage` dan `laravel/bootstrap/cache`.
8. Arahkan vhost/web server ke `public_html/`.

## Konfigurasi Produksi
- Set `APP_ENV=production`, `APP_DEBUG=false` di `.env`.
- Logging: gunakan channel `daily` dan batasi `max_files` di `config/logging.php`.
- Cache: jalankan optimisasi setelah deploy (opsional):
```bash
php artisan config:cache
```
```bash
php artisan route:cache
```
```bash
php artisan view:cache
```

## Catatan Ukuran & Pruning
- Saat `--include-storage` dipakai, build memotong konten runtime:
  - `storage/logs`, `storage/framework/cache`, `storage/framework/sessions`, `storage/framework/testing` dibuat kosong.
- Saat packaging, file non-esensial di `vendor` (tests/docs/examples dsb.) dieksklusi.
- Jika menginginkan artefak sangat kecil, gunakan build tanpa `vendor` dan lakukan `composer install` di server.

## Troubleshooting
- Aset tidak muncul: pastikan `public_html/build` ada dan vhost mengarah ke `public_html/`.
- Autoload error tanpa vendor: jalankan `composer install` dengan opsi produksi seperti di atas.
- Permission error pada `storage`: pastikan web server punya hak tulis ke `laravel/storage` dan `laravel/bootstrap/cache`.