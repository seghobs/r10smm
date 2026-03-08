<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

$google_client_id = 'YOUR_GOOGLE_CLIENT_ID';
$google_client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
$google_redirect_url = 'https://darqsmm.com/google-login.php';

echo "<h3>Google Login Durum Kontrolü</h3>";

// Hata ayıklama için GET parametrelerini gör
echo "GET Parametreleri: <pre>";
print_r($_GET);
echo "</pre>";

// Session başlatıldı mı kontrol et
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

if (isset($_GET['code'])) {
    echo "✅ Google'dan CODE geldi: " . htmlspecialchars($_GET['code']) . "<br>";
    echo "CODE uzunluğu: " . strlen($_GET['code']) . " karakter<br>";
    
    // Token almak için istek yap
    $token_url = 'https://oauth2.googleapis.com/token';
    $token_data = [
        'code' => $_GET['code'],
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_url,
        'grant_type' => 'authorization_code'
    ];
    
    echo "Token isteği gönderiliyor...<br>";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $token_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($token_data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)){
        echo "❌ CURL Hatası: " . curl_error($ch) . "<br>";
        curl_close($ch);
        exit;
    }
    curl_close($ch);
    
    echo "Token Yanıt Kodu: $http_code<br>";
    echo "Token Yanıtı: <pre>";
    print_r(json_decode($response, true));
    echo "</pre>";
    
    $token_info = json_decode($response, true);
    
    if (isset($token_info['access_token'])) {
        // ... devamı aynı ...
    } else {
        echo "❌ Token alınamadı!<br>";
        
        // Google OAuth hata mesajlarını kontrol et
        if (isset($_GET['error'])) {
            echo "Google OAuth Hatası: " . htmlspecialchars($_GET['error']) . "<br>";
            echo "Hata Açıklaması: " . (isset($_GET['error_description']) ? htmlspecialchars($_GET['error_description']) : 'Yok') . "<br>";
        }
    }
    
} elseif (isset($_GET['error'])) {
    echo "❌ Google'dan Hata Geldi:<br>";
    echo "Hata: " . htmlspecialchars($_GET['error']) . "<br>";
    echo "Açıklama: " . (isset($_GET['error_description']) ? htmlspecialchars($_GET['error_description']) : 'Yok') . "<br>";
    
} else {
    echo "🔵 Google'a yönlendiriliyor...<br>";
    
    $scope = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile';
    $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'scope' => $scope,
        'access_type' => 'online',
        'include_granted_scopes' => 'true',
        'response_type' => 'code',
        'state' => 'state_parameter_passthrough_value',
        'redirect_uri' => $google_redirect_url,
        'client_id' => $google_client_id,
        'prompt' => 'consent' // Kullanıcıdan her seferinde izin iste
    ]);
    
    echo "Yönlendirme URL: " . htmlspecialchars($auth_url) . "<br>";
    header('Location: ' . $auth_url);
    exit;
}
?>