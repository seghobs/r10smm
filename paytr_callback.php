<?php
require_once 'config.php';

// POST değerleri
$post = $_POST;

// PayTR ayarları (config.php'den al)
$merchant_key   = PAYTR_MERCHANT_KEY;
$merchant_salt  = PAYTR_MERCHANT_SALT;

if(!isset($post['merchant_oid']) || !isset($post['status']) || !isset($post['hash'])) {
    die('Eksik Parametre: Bad Request');
}

// Güvenlik amaçlı hash kontrolü (PayTR Dokümantasyon Algoritması)
$hash = base64_encode( hash_hmac('sha256', $post['merchant_oid'].$merchant_salt.$post['status'].$post['total_amount'], $merchant_key, true) );

if($hash != $post['hash']) {
    die('PAYTR notification failed: bad hash');
}

$merchant_oid = $post['merchant_oid'];

// Siparişi db'de bul
$stmt = $pdo->prepare("SELECT * FROM payments WHERE payment_id = ? LIMIT 1");
$stmt->execute([$merchant_oid]);
$payment = $stmt->fetch();

if(!$payment) {
    die('PAYTR notification failed: payment not found in db');
}

// Eğer bildirim daha önce geldiyse ve işlendiyse tekrar işlem yapmamak için OK yazıp kapat
if($payment['status'] == 'completed') {
    echo "OK";
    exit;
}

if($post['status'] == 'success') {
    // Ödeme başarılı, statüyü güncelle
    $pdo->prepare("UPDATE payments SET status='completed', updated_at=NOW() WHERE id=?")->execute([$payment['id']]);
    
    // Kullanıcıya bakiyeyi ekle
    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id=?")->execute([$payment['amount'], $payment['user_id']]);
    
    // İşlem / Fatura kaydı oluştur
    $pdo->prepare("INSERT INTO balance_transactions (user_id, amount, type, note, created_at) VALUES (?, ?, 'payment', ?, NOW())")
        ->execute([$payment['user_id'], $payment['amount'], "PayTR Yüklemesi - Kredi Kartı/Havale Oid: " . $merchant_oid]);

    // Bildirim at
    $pdo->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (?, ?, 'payment', 0, NOW())")
        ->execute([$payment['user_id'], "₺" . number_format($payment['amount'], 2) . " tutarındaki PayTR ödemeniz başarıyla eklenmiştir."]);
        
    // PayTR sisteminin durması için OK demeliyiz
    echo "OK";
    exit;
} else {
    // Odeme basarisiz (Banka reddetti vs.)
    $fail_reason = $post['failed_reason_msg'] ?? 'Banka veya sistem tarafından reddedildi.';
    
    $pdo->prepare("UPDATE payments SET status='failed', reject_reason=?, updated_at=NOW() WHERE id=?")->execute([$fail_reason, $payment['id']]);
    
    $pdo->prepare("INSERT INTO notifications (user_id, message, type, is_read, created_at) VALUES (?, ?, 'payment', 0, NOW())")
        ->execute([$payment['user_id'], "₺" . number_format($payment['amount'], 2) . " tutarındaki denemeniz başarısız oldu. Neden: " . $fail_reason]);

    // PayTR'a onay ilet
    echo "OK";
    exit;
}
?>
