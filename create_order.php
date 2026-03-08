<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $service_api_id = $_POST['service'];  // external API service ID
    $service_db_id   = intval($_POST['service_db_id'] ?? 0); // local DB id
    $service_name    = $_POST['service_name'];
    $category        = $_POST['service_category'];
    $link            = trim($_POST['link']);
    $quantity        = intval($_POST['quantity']);
    $price_per_1000  = floatval($_POST['price_per_1000']);

    // Look up the service to find its provider
    $svc_row = null;
    if ($service_db_id) {
        $s = $pdo->prepare("SELECT s.*, p.url as p_url, p.api_key as p_key FROM services s LEFT JOIN api_providers p ON s.provider_id = p.id WHERE s.id = ?");
        $s->execute([$service_db_id]);
        $svc_row = $s->fetch();
    }

    if (empty($service_api_id) || empty($link) || $quantity < 1) {
        $_SESSION['error'] = "Lütfen tüm alanları doldurun.";
        header('Location: services.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    $total_price = ($quantity / 1000) * $price_per_1000;

    if ($user['balance'] < $total_price) {
        $_SESSION['error'] = "Yetersiz bakiye! Lütfen bakiye yükleyin.";
        header('Location: services.php');
        exit;
    }

    // Determine API credentials - dynamic provider system
    if ($svc_row && !empty($svc_row['p_url']) && !empty($svc_row['p_key'])) {
        $api_url = $svc_row['p_url'];
        $api_key = $svc_row['p_key'];
        // Use the real API service ID from local db
        if (!empty($svc_row['api_service_id'])) $service_api_id = $svc_row['api_service_id'];
    } else {
        // Fallback to legacy hardcoded provider
        $api_url = 'https://takipcinizbizden.com/api/v2';
        $api_key = '14fd5712a199e44cdd0412ec5e33d744';
    }

    $postData = [
        'key' => $api_key,
        'action' => 'add',
        'service' => $service_api_id,
        'link' => $link,
        'quantity' => $quantity
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $_SESSION['error'] = "API Bağlantı Hatası: " . curl_error($ch);
        header('Location: services.php');
        exit;
    }
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['order'])) {
        $api_order_id = $result['order'];

        try {
            $pdo->beginTransaction();

            $new_balance = $user['balance'] - $total_price;
            $updateStmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $updateStmt->execute([$new_balance, $user['id']]);

            $local_order_id = date('Ymd') . rand(1000, 9999);
            
            $insertStmt = $pdo->prepare("INSERT INTO orders (order_id, api_order_id, user_id, service_name, category, link, quantity, price, start_count, remains, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $insertStmt->execute([
                $local_order_id,
                $api_order_id,
                $user['id'],
                $service_name,
                $category,
                $link,
                $quantity,
                $total_price,
                0,
                $quantity
            ]);

            $pdo->commit();
            
            $_SESSION['success'] = "Siparişiniz alındı! ID: #" . $local_order_id;
            header('Location: orders.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Sistem hatası: " . $e->getMessage();
            header('Location: services.php');
            exit;
        }

    } else {
        $error_msg = isset($result['error']) ? $result['error'] : 'Bilinmeyen API hatası';
        $_SESSION['error'] = "Sipariş verilemedi: " . $error_msg;
        header('Location: services.php');
        exit;
    }

} else {
    header('Location: services.php');
    exit;
}
?>