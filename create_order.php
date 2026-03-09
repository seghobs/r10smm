<?php
ob_start();
session_start();
require_once 'config.php';

// Determine where to redirect back on error.
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'new_order.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents('order_error_log.txt', date('Y-m-d H:i:s'). " POST HIT: ".json_encode($_POST)."\n", FILE_APPEND);
    $service_api_id = isset($_POST['service']) ? $_POST['service'] : 0;
    $service_db_id  = isset($_POST['service_db_id']) ? intval($_POST['service_db_id']) : (isset($_POST['service_id']) ? intval($_POST['service_id']) : 0);
    $link           = isset($_POST['link']) ? trim($_POST['link']) : '';
    $quantity       = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

    if (!$service_db_id && $service_api_id) {
        $service_db_id = $service_api_id; 
    }

    if (empty($service_db_id) || empty($link) || $quantity < 1) {
        $_SESSION['error'] = "Lütfen tüm alanları doldurun.";
        header("Location: $referer");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    $s = $pdo->prepare("SELECT s.*, p.url as p_url, p.api_key as p_key FROM services s LEFT JOIN api_providers p ON s.provider_id = p.id WHERE s.id = ?");
    $s->execute([$service_db_id]);
    $selected_service = $s->fetch();

    if (!$selected_service) {
        $_SESSION['error'] = "Servis bulunamadı.";
        header("Location: $referer");
        exit;
    }

    $price_per_1000 = floatval($selected_service['price']);
    $cost_per_1000 = floatval($selected_service['cost']);
    $min = intval($selected_service['min_quantity']);
    $max = intval($selected_service['max_quantity']);

    $total_price = round(($quantity / 1000) * $price_per_1000, 2);
    $cost_total = round(($quantity / 1000) * $cost_per_1000, 2);
    $profit = round($total_price - $cost_total, 2);

    if ($quantity < $min || $quantity > $max) {
        $_SESSION['error'] = "Miktar aralığı: {$min} - {$max}";
        header("Location: $referer");
        exit;
    } elseif (empty($selected_service['api_service_id']) || $selected_service['api_service_id'] == '0') {
        $_SESSION['error'] = "Bu servisin API bağlantısı yapılandırılmamış. Lütfen yönetici ile iletişime geçin.";
        file_put_contents('order_error_log.txt', date('Y-m-d H:i:s'). " VAL ERROR: NO API SERVICE ID\n", FILE_APPEND);
        header("Location: $referer");
        exit;
    } elseif (empty($selected_service['p_url']) || empty($selected_service['p_key'])) {
        $_SESSION['error'] = "Bu servisin API sağlayıcısı tanımlı değil. Lütfen yönetici ile iletişime geçin.";
        file_put_contents('order_error_log.txt', date('Y-m-d H:i:s'). " VAL ERROR: NO PROVIDER\n", FILE_APPEND);
        header("Location: $referer");
        exit;
    } elseif ($user['balance'] < $total_price) {
        $_SESSION['error'] = "Yetersiz bakiye! Lütfen bakiye yükleyin.";
        file_put_contents('order_error_log.txt', date('Y-m-d H:i:s'). " VAL ERROR: INSUFFICIENT BALANCE\n", FILE_APPEND);
        header("Location: $referer");
        exit;
    }

    try {
        $order_api_url = $selected_service['p_url'];
        $order_api_key = $selected_service['p_key'];
        
        $api_order_data = [
            'key' => $order_api_key,
            'action' => 'add',
            'service' => $selected_service['api_service_id'],
            'link' => $link,
            'quantity' => $quantity
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $order_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($api_order_data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $api_response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        
        curl_close($ch);
        
        $api_result = json_decode($api_response, true);
        
        if (isset($api_result['order'])) {
            $api_order_id = $api_result['order'];
            $internal_order_id = $api_order_id;
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO orders (order_id, api_order_id, api_service_id, service_id, user_id, service_name, category, link, quantity, price, total_price, profit_try, start_count, remains, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending', NOW())");
            $stmt->execute([
                $internal_order_id, 
                $api_order_id, 
                $selected_service['api_service_id'], 
                $selected_service['id'], 
                $user['id'], 
                $selected_service['name'], 
                $selected_service['category'], 
                $link, 
                $quantity, 
                $total_price, 
                $total_price, 
                $profit, 
                $quantity
            ]);
            
            $new_balance = $user['balance'] - $total_price;
            $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $user['id']]);
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'success', NOW())");
            $notif_title = "Sipariş Alındı ✅";
            $notif_msg = "Siparişiniz (#{$internal_order_id}) başarıyla oluşturuldu. Tutar: ₺{$total_price}";
            $stmt->execute([$user['id'], $notif_title, $notif_msg]);
            
            $pdo->commit();
            
            $_SESSION['success'] = 'Siparişiniz başarıyla oluşturuldu! Sipariş No: #' . $internal_order_id;
            header('Location: orders.php');
            exit;
            
        } else {
            $error_code = isset($api_result['error']) ? $api_result['error'] : 'Bilinmeyen API hatası: ' . $api_response;
            $error_msg = $error_code;
            if ($error_code == 'neworder.error.not_enough_funds') {
                $error_msg = "Sistemin (Sağlayıcının) bakiyesi yetersiz olduğu için şu anda sipariş verilemiyor.";
            }
            $_SESSION['error'] = "Sipariş verilemedi: " . $error_msg;
            file_put_contents('order_error_log.txt', date('Y-m-d H:i:s'). " API ERROR: ".$api_response."\n", FILE_APPEND);
            header("Location: $referer");
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Sistem hatası: " . $e->getMessage();
        file_put_contents('order_error_log.txt', date('Y-m-d H:i:s'). " SYS ERROR: ".$e->getMessage()."\n", FILE_APPEND);
        header("Location: $referer");
        exit;
    }

} else {
    header("Location: $referer");
    exit;
}
?>