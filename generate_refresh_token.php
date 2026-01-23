<?php

require_once 'vendor/autoload.php';

use Google\Auth\OAuth2;

// Konfigurasi dari .env
$clientId = '578767108904-8fks738q1f2cfp7j7rjhv0bj9nld77f0.apps.googleusercontent.com';
$clientSecret = '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';
$redirectUri = 'urn:ietf:wg:oauth:2.0:oob';
$scope = 'https://www.googleapis.com/auth/adwords';

echo "=== Google Ads API - Generate Refresh Token ===\n\n";

// Step 1: Generate authorization URL
$oauth2 = new OAuth2([
    'clientId' => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri' => $redirectUri,
    'scope' => $scope,
    'accessType' => 'offline',
    'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
    'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
]);

$authUrl = $oauth2->buildFullAuthorizationUri([
    'prompt' => 'consent',
]);

echo "1. Buka URL berikut di browser:\n";
echo $authUrl."\n\n";

echo "2. Login dengan akun Google yang memiliki akses ke Google Ads\n";
echo "3. Berikan izin akses\n";
echo "4. Copy authorization code dari halaman yang muncul\n\n";

echo 'Masukkan authorization code: ';
$authorizationCode = trim(fgets(STDIN));

if (empty($authorizationCode)) {
    echo "Error: Authorization code tidak boleh kosong!\n";
    exit(1);
}

try {
    // Step 2: Exchange authorization code for refresh token
    $oauth2->setCode($authorizationCode);
    $accessToken = $oauth2->fetchAuthToken();

    if (! isset($accessToken['refresh_token'])) {
        echo "Error: Gagal mendapatkan refresh token!\n";
        echo "Pastikan:\n";
        echo "- Authorization code benar\n";
        echo "- Akun Google memiliki akses ke Google Ads\n";
        echo "- Client ID dan Client Secret benar\n";
        echo "- Menggunakan 'consent' prompt untuk mendapatkan refresh token\n";
        exit(1);
    }

    $refreshToken = $accessToken['refresh_token'];

    // Step 3: Save refresh token
    $tokenDir = 'storage/app/private/google_ads';
    if (! is_dir($tokenDir)) {
        mkdir($tokenDir, 0755, true);
    }

    $tokenPath = $tokenDir.'/refresh_token.txt';
    file_put_contents($tokenPath, $refreshToken);

    echo "\n✅ Refresh token berhasil disimpan!\n";
    echo "📁 Lokasi: $tokenPath\n";
    echo "🔑 Token: $refreshToken\n\n";

    echo "Langkah selanjutnya:\n";
    echo "1. Update .env file:\n";
    echo '   GOOGLE_ADS_REFRESH_TOKEN_PATH='.realpath($tokenPath)."\n\n";
    echo "2. Test koneksi:\n";
    echo "   php artisan test:google-ads-connection\n\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo "\nTroubleshooting:\n";
    echo "- Pastikan authorization code masih valid (tidak expired)\n";
    echo "- Cek konfigurasi Client ID dan Client Secret\n";
    echo "- Pastikan akun Google memiliki akses ke Google Ads\n";
    exit(1);
}
