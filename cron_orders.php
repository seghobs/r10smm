<?php
require_once __DIR__ . '/config.php';

// Güvenlik ve Cron Ayarları
// Bu dosya sunucuda cron job olarak eklenebilir veya admin panelinden çağrılabilir.
set_time_limit(0); 

try {
    // Sadece henüz tamamlanmamış (pending, processing, inprogress) olan ve API ID'si bulunan siparişleri seç
    $stmt = $pdo->prepare("SELECT o.*, p.url as p_url, p.api_key as p_key FROM orders o 
                           LEFT JOIN services s ON o.service_id = s.id 
                           LEFT JOIN api_providers p ON s.provider_id = p.id 
                           WHERE o.status IN ('pending', 'processing', 'inprogress') AND o.api_order_id IS NOT NULL AND o.api_order_id != ''");
    $stmt->execute();
    $active_orders = $stmt->fetchAll();

    if (empty($active_orders)) {
        die("Senkronize edilecek aktif siparis bulunamadi.\n");
    }

    $updated_count = 0;

    foreach ($active_orders as $order) {
        // Provider bilgisi yoksa bu siparişi atla - hardcode yok!
        if (empty($order['p_url']) || empty($order['p_key'])) {
            continue;
        }
        $api_url = $order['p_url'];
        $api_key = $order['p_key'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'key' => $api_key,
            'action' => 'status',
            'order' => $order['api_order_id']
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['status'])) {
            $api_status = strtolower($result['status']);
            $start_count = isset($result['start_count']) ? intval($result['start_count']) : $order['start_count'];
            $remains = isset($result['remains']) ? intval($result['remains']) : $order['remains'];
            
            $new_status = 'pending';
            if ($api_status == 'completed') $new_status = 'completed';
            elseif ($api_status == 'processing' || $api_status == 'in progress') $new_status = 'processing';
            elseif ($api_status == 'partial') $new_status = 'partial';
            elseif ($api_status == 'canceled' || $api_status == 'cancelled') $new_status = 'cancelled';
            
            // Refund logic var mi?
            $did_change = ($new_status != strtolower($order['status']) || $start_count != $order['start_count'] || $remains != $order['remains']);
            
            if ($did_change) {
                
                if ($new_status == 'cancelled' && strtolower($order['status']) != 'cancelled') {
                    $pdo->beginTransaction();
                    $refund_amount = floatval($order['total_price'] > 0 ? $order['total_price'] : $order['price']);
                    
                    $updateUser = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $updateUser->execute([$refund_amount, $order['user_id']]);
                    
                    $updateOrder = $pdo->prepare("UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE id = ?");
                    $updateOrder->execute([$new_status, $start_count, $remains, $order['id']]);
                    
                    // Bildirim Gonder
                    $notifMsg = "Sipariş (#{$order['order_id']}) API tarafindan iptal edildi ve ₺{$refund_amount} iade edildi.";
                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, 'Siparis Iptali', ?, 'error', NOW())")->execute([$order['user_id'], $notifMsg]);
                    
                    $pdo->commit();
                    $updated_count++;
                } 
                elseif ($new_status == 'partial' && strtolower($order['status']) != 'partial') {
                     $pdo->beginTransaction();
                     $unit_price = ($order['total_price'] > 0 ? $order['total_price'] : $order['price']) / max(1, $order['quantity']);
                     $refund_amount = $remains * $unit_price;
                     
                     if ($refund_amount > 0) {
                         $updateUser = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                         $updateUser->execute([$refund_amount, $order['user_id']]);
                     }
                     
                     $updateOrder = $pdo->prepare("UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE id = ?");
                     $updateOrder->execute([$new_status, $start_count, $remains, $order['id']]);
                     
                     // Bildirim Gonder
                     $notifMsg = "Sipariş (#{$order['order_id']}) kismen tamamlandi. Kalan ₺{$refund_amount} bakiye iade edildi.";
                     $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, 'Kısmi Tamamlanma', ?, 'info', NOW())")->execute([$order['user_id'], $notifMsg]);
                     
                     $pdo->commit();
                     $updated_count++;
                }
                else {
                    $stmtUpdate = $pdo->prepare("UPDATE orders SET status = ?, start_count = ?, remains = ?, updated_at = NOW() WHERE id = ?");
                    $stmtUpdate->execute([$new_status, $start_count, $remains, $order['id']]);
                    $updated_count++;
                   
                    // Eger tamamlandiysa bildirim at
                    if($new_status == 'completed' && strtolower($order['status']) != 'completed'){
                       $notifMsg = "Siparișiniz (#{$order['order_id']}) başariyla tamamlandi!";
                       $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, 'Siparis Tamamlandi', ?, 'success', NOW())")->execute([$order['user_id'], $notifMsg]);
                    }
                }
            }
        }
    }

    echo "Senkronizasyon Basarili. Guncellenen Siparis Sayisi: " . $updated_count . "\n";

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
         $pdo->rollBack();
    }
    echo "Hata: " . $e->getMessage() . "\n";
}
?>
