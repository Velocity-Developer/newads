<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// echo "Menghapus tabel dan membuat ulang dengan struktur yang benar...\n\n";

// try {
//     // Cek jumlah data sebelum dihapus
//     $countBefore = DB::table('new_terms_negative_0click')->count();
//     echo "Jumlah data di new_terms_negative_0click sebelum dihapus: " . $countBefore . "\n";
    
//     $countFrasaBefore = DB::table('new_frasa_negative')->count();
//     echo "Jumlah data di new_frasa_negative sebelum dihapus: " . $countFrasaBefore . "\n\n";
    
//     // Nonaktifkan foreign key checks
//     DB::statement('SET FOREIGN_KEY_CHECKS = 0');
//     echo "Foreign key checks dinonaktifkan...\n";
    
//     // Drop kedua tabel
//     DB::statement('DROP TABLE IF EXISTS new_frasa_negative');
//     echo "✅ Tabel new_frasa_negative berhasil dihapus\n";
    
//     DB::statement('DROP TABLE IF EXISTS new_terms_negative_0click');
//     echo "✅ Tabel new_terms_negative_0click berhasil dihapus\n";
    
//     // Aktifkan kembali foreign key checks
//     DB::statement('SET FOREIGN_KEY_CHECKS = 1');
//     echo "Foreign key checks diaktifkan kembali...\n\n";
    
//     // Hapus record migration dari tabel migrations
//     DB::table('migrations')
//         ->whereIn('migration', [
//             '2025_10_18_015739_create_new_terms_negative_0click_table',
//             '2025_10_18_015751_create_new_frasa_negative_table'
//         ])
//         ->delete();
//     echo "✅ Record migration berhasil dihapus dari tabel migrations\n\n";
    
//     echo "🎉 SELESAI! Sekarang jalankan: php artisan migrate\n";
//     echo "Tabel akan dibuat ulang dengan struktur enum yang benar.\n";
    
// } catch (Exception $e) {
//     // Pastikan foreign key checks diaktifkan kembali jika terjadi error
//     try {
//         DB::statement('SET FOREIGN_KEY_CHECKS = 1');
//     } catch (Exception $e2) {
//         // Ignore error saat mengaktifkan kembali
//     }
//     echo "❌ Error: " . $e->getMessage() . "\n";
// }


// FUNGSI LAMA - CEK STRUKTUR TABEL (dikomen untuk backup)
// echo "Mengecek struktur tabel saat ini...\n\n";

// try {
//     // Cek struktur tabel new_terms_negative_0click
//     echo "=== STRUKTUR TABEL new_terms_negative_0click ===\n";
//     $columns = DB::select("DESCRIBE new_terms_negative_0click");
//     foreach ($columns as $column) {
//         echo "- {$column->Field}: {$column->Type} " . 
//              ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . 
//              ($column->Default ? " DEFAULT {$column->Default}" : '') . "\n";
//     }
    
//     echo "\n=== STRUKTUR TABEL new_frasa_negative ===\n";
//     $columns2 = DB::select("DESCRIBE new_frasa_negative");
//     foreach ($columns2 as $column) {
//         echo "- {$column->Field}: {$column->Type} " . 
//              ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . 
//              ($column->Default ? " DEFAULT {$column->Default}" : '') . "\n";
//     }
    
//     echo "\n=== STATUS MIGRATION ===\n";
//     $migrations = DB::table('migrations')->orderBy('batch', 'desc')->get();
//     foreach ($migrations as $migration) {
//         echo "Batch {$migration->batch}: {$migration->migration}\n";
//     }
    
// } catch (Exception $e) {
//     echo "❌ Error: " . $e->getMessage() . "\n";
// }


/*
// FUNGSI LAMA - TRUNCATE TABLE (dikomen untuk backup)
// Uncomment jika ingin menghapus semua data dan reset ID

echo "Menghapus semua data dari tabel new_terms_negative_0click...\n";

try {
    // Cek jumlah data sebelum dihapus
    $countBefore = DB::table('new_terms_negative_0click')->count();
    echo "Jumlah data sebelum dihapus: " . $countBefore . "\n";
    
    // Nonaktifkan foreign key checks sementara
    DB::statement('SET FOREIGN_KEY_CHECKS = 0');
    echo "Foreign key checks dinonaktifkan sementara...\n";
    
    // TRUNCATE akan menghapus semua data dan mereset auto increment ID ke 1
    DB::statement('TRUNCATE TABLE new_terms_negative_0click');
    
    // Aktifkan kembali foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    echo "Foreign key checks diaktifkan kembali...\n";
    
    // Cek jumlah data setelah dihapus
    $countAfter = DB::table('new_terms_negative_0click')->count();
    echo "Jumlah data setelah dihapus: " . $countAfter . "\n";
    
    echo "\n✅ Berhasil menghapus semua data dari tabel new_terms_negative_0click\n";
    echo "✅ Auto increment ID telah direset ke 1\n";
    
} catch (Exception $e) {
    // Pastikan foreign key checks diaktifkan kembali jika terjadi error
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    } catch (Exception $e2) {
        // Ignore error saat mengaktifkan kembali
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
}
*/

// Cek tabel site_settings dan data yang ada
echo "\n=== STRUKTUR TABEL site_settings ===\n";
try {
    $siteSettingsColumns = DB::select("DESCRIBE site_settings");
    foreach ($siteSettingsColumns as $column) {
        echo "- {$column->Field}: {$column->Type} " . 
                ($column->Null === 'YES' ? 'NULL' : 'NOT NULL') . 
                ($column->Default ? " DEFAULT {$column->Default}" : '') . "\n";
    }
    
    echo "\n=== DATA TABEL site_settings ===\n";
    $siteSettings = DB::table('site_settings')->get();
    if ($siteSettings->isEmpty()) {
        echo "❌ Tidak ada data di tabel site_settings\n";
    } else {
        foreach ($siteSettings as $setting) {
            echo "- ID: {$setting->id}\n";
            echo "- Site Title: " . ($setting->site_title ?? 'NULL') . "\n";
            echo "- Sidebar Title: " . ($setting->sidebar_title ?? 'NULL') . "\n";
            echo "- Sidebar Icon: " . ($setting->sidebar_icon_path ?? 'NULL') . "\n";
            echo "- Favicon: " . ($setting->favicon_path ?? 'NULL') . "\n";
            echo "- Apple Touch Icon: " . ($setting->apple_touch_icon_path ?? 'NULL') . "\n";
            echo "- Created: {$setting->created_at}\n";
            echo "- Updated: {$setting->updated_at}\n\n";
        }
    }
} catch (Exception $siteSettingsError) {
    echo "❌ Tabel site_settings tidak ditemukan atau error: " . $siteSettingsError->getMessage() . "\n";
}