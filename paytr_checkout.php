<?php
require_once 'config.php';
check_session();

if (!isset($_REQUEST['amount']) || floatval($_REQUEST['amount']) < 75) {
    header('Location: balance');
    exit;
}

$user_id = $_SESSION['user_id'];
$amount_tl = floatval($_REQUEST['amount']);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

// PayTR API Değerleri (config.php'den alınır)
$merchant_id    = PAYTR_MERCHANT_ID;
$merchant_key   = PAYTR_MERCHANT_KEY;
$merchant_salt  = PAYTR_MERCHANT_SALT;

// Müşterinin email, telefon vb. bilgileri PayTR için zorunludur.
$email = !empty($user['email']) ? $user['email'] : 'test@example.com';
$user_name = !empty($user['username']) ? $user['username'] : 'Müşteri';
$user_address = 'Belirtilmedi';
$user_phone = !empty($user['phone']) ? $user['phone'] : '05555555555';

$merchant_oid = 'PTR' . $user_id . time() . rand(100,999);
$payment_amount = $amount_tl * 100; // PayTR kuruş hesabı çalışır (Örn: 10 TL = 1000 kuruş)
$payment_amount_str = (string) $payment_amount; 

// Sepet içeriği
$user_basket = base64_encode(json_encode([
    ['Bakiye Yukleme - ' . $amount_tl . ' TL', trim($payment_amount_str), 1],
]));

$paytr_token_url = "https://www.paytr.com/odeme/api/get-token";
$merchant_ok_url = SITE_URL . "/paytr_success";
$merchant_fail_url = SITE_URL . "/paytr_fail";

$user_ip = get_client_ip();
$timeout_limit = "30";
$debug_on = 1;
$test_mode = 0; // Canlıda 0, Test aşamasında 1 yapınız
$no_installment = 0; // Taksit yapılsın mı? 0: Evet
$max_installment = 0; // Maximum taksit sayısı, kartın durumuna göre 0 bırakın limitsizdir veya 12 yapın
$currency = "TL";

// Hash oluşturma işlemi (DOKÜMANTASYONA UYGUN SIRALAMAYLA)
$hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount_str . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
$paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

$post_vals = [
    'merchant_id' => $merchant_id,
    'user_ip' => $user_ip,
    'merchant_oid' => $merchant_oid,
    'email' => $email,
    'payment_amount' => $payment_amount_str,
    'paytr_token' => $paytr_token,
    'user_basket' => $user_basket,
    'debug_on' => $debug_on,
    'no_installment' => $no_installment,
    'max_installment' => $max_installment,
    'user_name' => $user_name,
    'user_address' => $user_address,
    'user_phone' => $user_phone,
    'merchant_ok_url' => $merchant_ok_url,
    'merchant_fail_url' => $merchant_fail_url,
    'timeout_limit' => $timeout_limit,
    'currency' => $currency,
    'test_mode' => $test_mode
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paytr_token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

// Proxy kapatmak için vs. local ortamda SSL bypass gerekebilir.
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$result = @curl_exec($ch);

if(curl_errno($ch)) {
    die("PAYTR API BAĞLANTI HATASI: " . curl_error($ch));
}
curl_close($ch);

$result = json_decode($result, 1);

if($result['status'] == 'success') {
    $token = $result['token'];
    
    // Bekleyen ödemeyi veritabanına ekle
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, payment_id, amount, payment_method, status, created_at) VALUES (?, ?, ?, 'paytr', 'pending', NOW())");
    $stmt->execute([$user_id, $merchant_oid, $amount_tl]);
} else {
    die("PAYTR HATA DÖNDÜRDÜ: " . ($result['reason'] ?? 'Bilinmeyen Hata') . " (Lütfen API bilgilerinizi kontrol edin)");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Güvenli Ödeme Çözümü</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #020617; color: white; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .payment-box { width: 100%; max-width: 600px; background: rgba(30, 41, 59, 0.6); padding: 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; margin-bottom: 20px; transition: 0.3s; }
        .back-btn:hover { color: white; }
    </style>
</head>
<body>

    <div class="payment-box">
        <a href="balance.php" class="back-btn"><i class="fas fa-arrow-left"></i> Önceki Sayfaya Dön</a>
        <h3 style="text-align: center; margin-bottom: 20px;">PayTR Güvenli Ödeme (Kredi Kartı / Havale)</h3>
        
        <!-- Ödeme formunun açılması için gereken iframe -->
        <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
        <iframe src="https://www.paytr.com/odeme/guvenli/<?php echo $token;?>" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%;"></iframe>
        <script>iFrameResize({},'#paytriframe');</script>
        
    </div>

</body>
</html>
