<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (isset($_POST['action']) && $_POST['action'] == 'read_notifications') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    echo 'ok';
    exit;
}

// Provider bilgisini her zaman DB'den çek
$default_provider = $pdo->query("SELECT url, api_key FROM api_providers ORDER BY id ASC LIMIT 1")->fetch();
if (!$default_provider) {
    $api_url = '';
    $api_key = '';
} else {
    $api_url = $default_provider['url'];
    $api_key = $default_provider['api_key'];
}

// --- VERİTABANI GÜNCELLEME VE KONTROL ALANI ---
try {
    $stmt = $pdo->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('api_order_id', $columns)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN api_order_id VARCHAR(50) DEFAULT NULL AFTER order_id");
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL, 
        title VARCHAR(255) NOT NULL, 
        message TEXT NOT NULL, 
        type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info', 
        is_read BOOLEAN DEFAULT FALSE, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        INDEX idx_user (user_id)
    )");

    $stmt = $pdo->query("DESCRIBE notifications");
    $notif_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('title', $notif_columns)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN title VARCHAR(255) NOT NULL AFTER user_id");
    }

} catch (Exception $e) {}
// ----------------------------------------------

$notifications = [];
$unread_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $unread_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll();
} catch (Exception $e) {}

$categories = [
    'all' => 'Tüm Hizmetler',
    'Instagram' => 'Instagram',
    'TikTok' => 'TikTok',
    'YouTube' => 'YouTube',
    'Twitter' => 'Twitter',
    'Facebook' => 'Facebook',
    'Spotify' => 'Spotify',
    'Telegram' => 'Telegram',
    'Twitch' => 'Twitch',
    'Other' => 'Diğer'
];

$services = [];
$api_success = false;

// --- Servisleri Veritabanından Çek ---
try {
    $db_services = $pdo->query("SELECT s.*, p.url as provider_url, p.api_key as provider_key, p.name as provider_name 
                                 FROM services s 
                                 LEFT JOIN api_providers p ON s.provider_id = p.id 
                                 WHERE s.status = 'active' 
                                 ORDER BY s.category, s.name")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($db_services)) {
        $api_success = true;
        foreach ($db_services as $svc) {
            $category_name = $svc['category'] ?: 'Other';
            
            $est_time = 'Normal Hız';
            $service_name_lower = strtolower($svc['name']);
            if (strpos($service_name_lower, 'anlık') !== false || strpos($service_name_lower, 'instant') !== false) {
                $est_time = 'Anlık Başlama';
            } elseif (strpos($service_name_lower, 'hızlı') !== false || strpos($service_name_lower, 'fast') !== false) {
                $est_time = 'Hızlı Teslimat';
            } elseif (strpos($service_name_lower, 'yavaş') !== false || strpos($service_name_lower, 'slow') !== false) {
                $est_time = '0-24 Saat';
            }
            
            // Gruplama (Platform) Belirleme
            $cat_lower = strtolower($category_name);
            $group = 'Other';
            if (strpos($cat_lower, 'instagram') !== false || strpos($cat_lower, 'ig') !== false) $group = 'Instagram';
            elseif (strpos($cat_lower, 'tiktok') !== false) $group = 'TikTok';
            elseif (strpos($cat_lower, 'youtube') !== false) $group = 'YouTube';
            elseif (strpos($cat_lower, 'twitter') !== false || strpos($cat_lower, 'x.com') !== false) $group = 'Twitter';
            elseif (strpos($cat_lower, 'facebook') !== false) $group = 'Facebook';
            elseif (strpos($cat_lower, 'spotify') !== false) $group = 'Spotify';
            elseif (strpos($cat_lower, 'telegram') !== false) $group = 'Telegram';
            elseif (strpos($cat_lower, 'twitch') !== false) $group = 'Twitch';

            $services[] = [
                'db_id' => $svc['id'],
                'api_id' => $svc['api_service_id'],
                'name' => $svc['name'],
                'category' => $category_name,
                'group' => $group,
                'price_per_1000' => round(floatval($svc['price']), 2),
                'cost_per_1000' => round(floatval($svc['cost']), 2),
                'min' => max(intval($svc['min_quantity']), 10),
                'max' => intval($svc['max_quantity']),
                'description' => $svc['description'] ?: ($svc['name'] . ' - Yüksek kalite garantili'),
                'rate' => $svc['cost'],
                'time' => $est_time,
                'provider_url' => $svc['provider_url'],
                'provider_key' => $svc['provider_key'],
                'provider_name' => $svc['provider_name'],
                'provider_id' => $svc['provider_id'],
            ];
        }
    }
} catch (Exception $e) {}


$total_services = count($services);
$categories_count = [
    'all' => $total_services,
    'Instagram' => 0, 'TikTok' => 0, 'YouTube' => 0, 'Twitter' => 0,
    'Facebook' => 0, 'Spotify' => 0, 'Telegram' => 0, 'Twitch' => 0, 'Other' => 0
];
foreach ($services as $service) {
    $grp = $service['group'];
    if (isset($categories_count[$grp])) {
        $categories_count[$grp]++;
    } else {
        $categories_count['Other']++;
    }
}

$order_error = null;
$order_success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $service_api_id = $_POST['service_id'];
    $link = trim($_POST['link']);
    $quantity = intval($_POST['quantity']);
    
    $selected_service = null;
    foreach ($services as $service) {
        if ($service['db_id'] == $service_api_id) {
            $selected_service = $service;
            break;
        }
    }
    
    if ($selected_service) {
        $price_per_1000 = $selected_service['price_per_1000'];
        $total_price = ($quantity / 1000) * $price_per_1000;
        $total_price = round($total_price, 2);
        
        $cost_total = round(($quantity / 1000) * $selected_service['cost_per_1000'], 2);
        $profit = round($total_price - $cost_total, 2);
        
        if ($quantity < $selected_service['min'] || $quantity > $selected_service['max']) {
            $order_error = "Miktar aralığı: {$selected_service['min']} - {$selected_service['max']}";
        } elseif (empty($selected_service['api_id']) || $selected_service['api_id'] == '0') {
            $order_error = "Bu servisin API bağlantısı yapılandırılmamış. Lütfen yönetici ile iletişime geçin.";
        } elseif (empty($selected_service['provider_url']) || empty($selected_service['provider_key'])) {
            $order_error = "Bu servisin API sağlayıcısı tanımlı değil. Lütfen yönetici ile iletişime geçin.";
        } elseif ($user['balance'] >= $total_price) {
            try {
                // Use dynamic provider credentials
                $order_api_url = $selected_service['provider_url'];
                $order_api_key = $selected_service['provider_key'];
                
                $api_order_data = [
                    'key' => $order_api_key,
                    'action' => 'add',
                    'service' => $selected_service['api_id'],
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
                    $internal_order_id = date('Ymd') . rand(1000, 9999);
                    
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("INSERT INTO orders (order_id, api_order_id, api_service_id, service_id, user_id, service_name, category, link, quantity, price, total_price, profit_try, start_count, remains, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending', NOW())");
                    $stmt->execute([$internal_order_id, $api_order_id, $selected_service['api_id'], $selected_service['db_id'], $user['id'], $selected_service['name'], $selected_service['category'], $link, $quantity, $total_price, $total_price, $profit, $quantity]);
                    
                    $new_balance = $user['balance'] - $total_price;
                    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                    $stmt->execute([$new_balance, $user['id']]);
                    
                    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'success', NOW())");
                    $notif_title = "Sipariş Alındı ✅";
                    $notif_msg = "Siparişiniz (#{$internal_order_id}) başarıyla oluşturuldu. Tutar: ₺{$total_price}";
                    $stmt->execute([$user['id'], $notif_title, $notif_msg]);
                    
                    $pdo->commit();
                    
                    $order_success = 'Siparişiniz başarıyla oluşturuldu! Sipariş No: #' . $internal_order_id;
                    $user['balance'] = $new_balance;
                    
                    $unread_count++;
                    array_unshift($notifications, [
                        'title' => $notif_title,
                        'message' => $notif_msg,
                        'created_at' => date('Y-m-d H:i:s'),
                        'type' => 'success',
                        'is_read' => 0
                    ]);
                    
                } else {
                    $raw_error = $api_result['error'] ?? 'Bilinmeyen hata';
                    if ($raw_error == 'neworder.error.not_enough_funds') {
                        $order_error = 'SİSTEM HATASI: Ana sağlayıcıda bakiye yetersiz.';
                    } else {
                        $order_error = 'Sipariş oluşturulamadı: ' . $raw_error;
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $order_error = 'Sistemsel hata: ' . $e->getMessage();
            }
        } else {
            $order_error = 'Yetersiz bakiye!';
        }
    } else {
        $order_error = 'Servis bulunamadı!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hizmetler - <?php echo SITE_LOGO_TEXT; ?> SMM Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary: #8B5CF6;
            --primary-dark: #7C3AED;
            --secondary: #10B981;
            --accent: #F59E0B;
            --danger: #EF4444;
            --bg-body: #020617;
            --bg-card: rgba(30, 41, 59, 0.6);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --gradient-main: linear-gradient(135deg, #8B5CF6 0%, #4F46E5 100%);
            --gradient-card: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(15, 23, 42, 0.4) 100%);
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --glow: 0 0 30px rgba(139, 92, 246, 0.3);
            --radius: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); background: var(--bg-body); line-height: 1.6; overflow-x: hidden; }

        .background-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; pointer-events: none; }
        .glow-blob { position: absolute; filter: blur(90px); opacity: 0.3; border-radius: 50%; animation: float 15s infinite alternate ease-in-out; }
        .blob-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: var(--primary); }
        .blob-2 { bottom: 10%; right: -10%; width: 500px; height: 500px; background: #059669; animation-delay: -5s; }
        @keyframes float { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(40px, 40px) scale(1.05); } }

        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .main-content { padding: 150px 0 40px; }

        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .dashboard-header h1 { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 700; }
        .gradient-text { 
            background: linear-gradient(135deg, #F59E0B 0%, #FFD700 100%); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }

        .btn { padding: 10px 20px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; font-size: 0.95rem; }
        .btn-primary { background: var(--gradient-main); color: white; box-shadow: var(--glow); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(139, 92, 246, 0.5); }
        .btn-outline { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.15); color: white; }
        .btn-outline:hover { background: rgba(255,255,255,0.1); border-color: white; transform: translateY(-3px); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 20px; padding: 25px; transition: 0.3s; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: var(--glow); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-main); }
        .stat-value { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 700; color: white; line-height: 1; margin-bottom: 5px; }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; }
        .stat-icon { width: 50px; height: 50px; background: rgba(139, 92, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.5rem; }

        .content-card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 24px; padding: 30px; margin-bottom: 30px; }
        
        .filters-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .filter-group label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 0.85rem; font-weight: 600; }
        .form-control-filter { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; transition: 0.3s; font-size: 0.95rem; outline: none; }
        .form-control-filter:focus { border-color: var(--primary); background: rgba(255,255,255,0.07); }

        .category-tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 15px; margin-bottom: 30px; scrollbar-width: none; }
        .category-tabs::-webkit-scrollbar { display: none; }
        .category-tab { padding: 10px 20px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 50px; color: var(--text-muted); cursor: pointer; white-space: nowrap; transition: 0.3s; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .category-tab:hover, .category-tab.active { background: var(--primary); color: white; border-color: var(--primary); transform: translateY(-2px); }
        .category-tab .count { background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; }

        .services-grid-layout { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .service-card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 24px; padding: 25px; transition: 0.3s; display: flex; flex-direction: column; position: relative; min-height: 480px; }
        .service-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: var(--glow); }
        
        .service-header { display: flex; gap: 15px; align-items: center; margin-bottom: 20px; }
        .s-icon { width: 50px; height: 50px; background: var(--gradient-main); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; box-shadow: var(--glow); flex-shrink: 0; }
        .service-name { font-weight: 700; color: white; font-size: 1.05rem; line-height: 1.3; }
        .service-cat { color: var(--text-muted); font-size: 0.8rem; }
        .service-desc { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px; line-height: 1.5; flex-grow: 1; }

        .price-box { background: rgba(139, 92, 246, 0.05); padding: 15px; border-radius: 16px; margin-bottom: 20px; border: 1px dashed rgba(139, 92, 246, 0.2); text-align: center; }
        .price-amount { font-size: 1.8rem; font-weight: 800; color: var(--primary); font-family: 'Outfit'; }
        .price-per { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 25px; }
        .detail-item { background: rgba(255,255,255,0.02); padding: 10px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05); }
        .detail-label { font-size: 0.7rem; color: var(--text-muted); }
        .detail-val { font-size: 0.85rem; color: white; font-weight: 600; margin-top: 2px; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
        .modal-content { background: #1e293b; border: var(--glass-border); border-radius: 24px; width: 100%; max-width: 500px; position: relative; overflow: hidden; animation: slideUp 0.3s ease; text-align: left; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .close-modal-modern { position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.05); border: none; color: white; width: 35px; height: 35px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10; }
        .modal-header-bg { background: var(--gradient-card); padding: 40px 30px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .modal-body { padding: 30px; }
        .form-control { width: 100%; padding: 12px 15px; background: rgba(15, 23, 42, 0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; margin-bottom: 15px; }
        .footer { padding: 80px 0 40px; background: rgba(2, 6, 23, 0.5); border-top: var(--glass-border); margin-top: 100px; position: relative; z-index: 10; }
        .footer-grid { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 50px; text-align: left; }
        .footer-col h4 { color: white; margin-bottom: 25px; font-size: 1.1rem; font-weight: 700; font-family: 'Outfit', sans-serif; }
        .footer-links a { display: block; color: var(--text-muted); text-decoration: none; margin-bottom: 12px; transition: 0.3s; font-size: 0.9rem; }
        .footer-links a:hover { color: var(--primary); transform: translateX(5px); }
        .social-icons { display: flex; gap: 10px; margin-top: 20px; }
        .social-icons a { display: inline-flex; width: 40px; height: 40px; background: rgba(255,255,255,0.05); border-radius: 12px; align-items: center; justify-content: center; color: white; text-decoration: none; transition: 0.3s; border: 1px solid rgba(255,255,255,0.05); }
        .social-icons a:hover { background: var(--primary); transform: translateY(-5px); box-shadow: var(--glow); }

        @media (max-width: 992px) {
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 576px) {
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
    <div class="background-glow">
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>
    </div>
</head>
<body>

    

    <?php include 'user_navbar.php'; ?>

    <div class="main-content container">
        <div class="dashboard-header">
            <div>
                <h1>Hizmet <span class="gradient-text">Listesi</span></h1>
                <p style="color: var(--text-muted);">API üzerinden anlık güncellenen yüksek kaliteli servisler.</p>
                <div style="margin-top: 10px; display: inline-flex; align-items: center; gap: 10px; font-size: 0.9rem; background: rgba(255,255,255,0.05); padding: 5px 15px; border-radius: 20px;">
                    <i class="fas fa-circle" style="color: <?php echo $api_success ? '#10B981' : '#EF4444'; ?>; font-size: 0.7rem;"></i>
                    <?php echo $api_success ? 'API Bağlantısı Aktif' : 'API Bağlantı Hatası'; ?>
                </div>
            </div>
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Listeyi Yenile
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div><div class="stat-value" id="total-visible-services"><?php echo $total_services; ?></div><div class="stat-label">Toplam Hizmet</div></div>
                    <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div><div class="stat-value">9</div><div class="stat-label">Platform</div></div>
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div><div class="stat-value">API</div><div class="stat-label">Entegrasyon</div></div>
                    <div class="stat-icon"><i class="fas fa-code-branch"></i></div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="search"><i class="fas fa-search"></i> Hizmet Ara</label>
                    <input type="text" id="search" class="form-control-filter" placeholder="Hizmet adı veya ID..." onkeyup="filterServices()">
                </div>
                <div class="filter-group">
                    <label for="category"><i class="fas fa-filter"></i> Kategori</label>
                    <select id="category" class="form-control-filter" onchange="filterServices()">
                        <?php foreach ($categories as $key => $name): ?>
                            <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="sort"><i class="fas fa-sort"></i> Sıralama</label>
                    <select id="sort" class="form-control-filter" onchange="sortServices()">
                        <option value="default">Varsayılan</option>
                        <option value="price_low">Fiyat (Düşük > Yüksek)</option>
                        <option value="price_high">Fiyat (Yüksek > Düşük)</option>
                        <option value="name">İsim (A-Z)</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="button" class="btn btn-primary" onclick="filterServices()"><i class="fas fa-filter"></i> Uygula</button>
                <button type="button" class="btn btn-outline" onclick="resetFilters()"><i class="fas fa-undo"></i> Sıfırla</button>
            </div>
        </div>

        <?php if ($total_services > 0): ?>
        <div class="category-tabs" id="categoryTabsContainer">
            <?php foreach ($categories as $key => $name): ?>
                <?php if ($key !== 'all'): ?>
                    <div class="category-tab" data-cat="<?php echo $key; ?>" onclick="selectCategoryTab('<?php echo $key; ?>')">
                        <?php 
                        $icon = 'fas fa-star';
                        if(strpos($key, 'Instagram') !== false) $icon = 'fab fa-instagram';
                        elseif(strpos($key, 'TikTok') !== false) $icon = 'fab fa-tiktok';
                        elseif(strpos($key, 'YouTube') !== false) $icon = 'fab fa-youtube';
                        elseif(strpos($key, 'Twitter') !== false) $icon = 'fab fa-twitter';
                        elseif(strpos($key, 'Telegram') !== false) $icon = 'fab fa-telegram';
                        ?>
                        <i class="<?php echo $icon; ?>"></i>
                        <?php echo $name; ?>
                        <?php if (isset($categories_count[$key])): ?>
                            <span class="count"><?php echo $categories_count[$key]; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="services-grid-layout" id="servicesGrid">
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <div class="service-card" 
                         data-category="<?php echo htmlspecialchars($service['category']); ?>" 
                         data-group="<?php echo htmlspecialchars($service['group']); ?>" 
                         data-name="<?php echo htmlspecialchars(strtolower($service['name'])); ?>"
                         data-price="<?php echo $service['price_per_1000']; ?>">
                         
                        <?php if ($service['price_per_1000'] < 30): ?>
                            <span class="service-tag"><i class="fas fa-fire"></i> POPÜLER</span>
                        <?php endif; ?>

                        <div class="service-header">
                            <div class="s-icon">
                                <?php 
                                $cat_icon = 'fas fa-bolt';
                                if($service['category'] == 'Instagram') $cat_icon = 'fab fa-instagram';
                                elseif($service['category'] == 'TikTok') $cat_icon = 'fab fa-tiktok';
                                elseif($service['category'] == 'YouTube') $cat_icon = 'fab fa-youtube';
                                elseif($service['category'] == 'Twitter') $cat_icon = 'fab fa-twitter';
                                elseif($service['category'] == 'Facebook') $cat_icon = 'fab fa-facebook';
                                elseif($service['category'] == 'Spotify') $cat_icon = 'fab fa-spotify';
                                elseif($service['category'] == 'Telegram') $cat_icon = 'fab fa-telegram';
                                elseif($service['category'] == 'Twitch') $cat_icon = 'fab fa-twitch';
                                ?>
                                <i class="<?php echo $cat_icon; ?>"></i>
                            </div>
                            <div>
                                <div class="service-name"><?php echo htmlspecialchars($service['name']); ?></div>
                                <span class="service-cat"><?php echo htmlspecialchars($service['category']); ?></span>
                            </div>
                        </div>

                        <div class="service-desc">
                            <?php echo htmlspecialchars(mb_substr($service['description'], 0, 100)) . (mb_strlen($service['description']) > 100 ? '...' : ''); ?>
                        </div>

                        <div class="price-box">
                            <div class="price-amount">₺<?php echo number_format($service['price_per_1000'], 2); ?></div>
                            <div class="price-per">1000 Adet Fiyatı</div>
                        </div>

                        <div class="detail-grid">
                            <div class="detail-item">
                                <div class="detail-label">Min. Sipariş</div>
                                <div class="detail-val"><?php echo number_format($service['min']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Maks. Sipariş</div>
                                <div class="detail-val"><?php echo number_format($service['max']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Tahmini Süre</div>
                                <div class="detail-val"><?php echo $service['time']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">ID</div>
                                <div class="detail-val">#<?php echo $service['api_id']; ?></div>
                            </div>
                        </div>

                        <button class="btn btn-primary" style="justify-content: center; margin-top: auto;" onclick="openOrderModal(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                            <i class="fas fa-cart-plus"></i> Sipariş Ver
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: var(--bg-card); border-radius: 20px; border: var(--glass-border);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                    <h3 style="color: white; margin-bottom: 10px;">Servis Bulunamadı</h3>
                    <p style="color: var(--text-muted);">API bağlantısı kurulamadı veya servis listesi boş.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'home_footer.php'; ?>

    <div class="modal" id="orderModal">
        <div class="modal-content">
            <button class="close-modal-modern" onclick="closeOrderModal()"><i class="fas fa-times"></i></button>
            
            <div class="modal-header-bg">
                <h2 class="modal-title">Sipariş Oluştur</h2>
                <div class="selected-service-card">
                    <div class="ss-icon"><i class="fas fa-star" id="modalIcon"></i></div>
                    <div class="ss-info">
                        <h4 id="modalServiceNameDisplay">Servis Adı Yükleniyor...</h4>
                        <span id="modalServiceCat">Kategori</span>
                    </div>
                </div>
            </div>

            <form id="orderForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="place_order" value="1">
                    <input type="hidden" id="modalServiceId" name="service_id">
                    
                    <div class="modern-input-group">
                        <label>Hedef Link</label>
                        <div class="modern-input-wrapper">
                            <i class="fas fa-link"></i>
                            <input type="url" name="link" class="modern-input" placeholder="https://instagram.com/..." required>
                        </div>
                    </div>

                    <div class="modern-input-group">
                        <label>Miktar</label>
                        <div class="modern-input-wrapper">
                            <div class="qty-stepper">
                                <button type="button" class="qty-btn-step" onclick="changeQuantity(-100)"><i class="fas fa-minus"></i></button>
                                <input type="number" id="quantity" name="quantity" class="modern-input qty" placeholder="1000" required oninput="calculatePrice()">
                                <button type="button" class="qty-btn-step" onclick="changeQuantity(100)"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="qty-limits">
                            Limitler: <span id="minQuantity">100</span> - <span id="maxQuantity">10.000</span>
                        </div>
                    </div>

                    <div class="receipt-card">
                        <div class="receipt-row">
                            <span>Birim Fiyat (1000 Adet)</span>
                            <span id="unitPrice">₺0.00</span>
                        </div>
                        <div class="receipt-row">
                            <span>Miktar</span>
                            <span id="summaryQuantity">0 adet</span>
                        </div>
                        <div class="receipt-row total">
                            <span>Ödenecek Tutar</span>
                            <span class="big-price" id="totalPrice">₺0.00</span>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; font-size: 0.85rem; color: var(--text-muted); text-align: center;">
                        Mevcut Bakiye: <span style="color: #10B981; font-weight: 600;">₺<?php echo number_format($user['balance'], 2); ?></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-rocket"></i> Siparişi Onayla
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        

        

        

        

        

        let currentService = null;
        let userBalance = <?php echo $user['balance']; ?>;

        const swalWithTheme = Swal.mixin({
            background: '#1e293b',
            color: '#F8FAFC',
            confirmButtonColor: '#8B5CF6',
            cancelButtonColor: '#EF4444',
            customClass: {
                popup: 'swal2-popup',
                title: 'swal2-title',
                confirmButton: 'swal2-confirm',
                cancelButton: 'swal2-cancel'
            }
        });

        <?php if ($order_error): ?>
            swalWithTheme.fire({ icon: 'error', title: 'Hata!', text: "<?php echo addslashes($order_error); ?>" });
        <?php endif; ?>
        
        <?php if ($order_success): ?>
            swalWithTheme.fire({ icon: 'success', title: 'Başarılı!', text: "<?php echo addslashes($order_success); ?>" }).then(() => { window.location.href = 'orders.php'; });
        <?php endif; ?>

        function filterServices() {
            const searchInput = document.getElementById('search').value.toLowerCase();
            const categorySelect = document.getElementById('category').value;
            const cards = document.querySelectorAll('.service-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const group = card.getAttribute('data-group');
                const category = card.getAttribute('data-category').toLowerCase();
                
                const matchesSearch = name.includes(searchInput) || category.includes(searchInput);
                const matchesCategory = categorySelect === 'all' || group === categorySelect;

                if (matchesSearch && matchesCategory) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('active');
                if(tab.getAttribute('data-cat') === categorySelect) {
                    tab.classList.add('active');
                }
            });
            
            const counter = document.getElementById('total-visible-services');
            if(counter) counter.innerText = visibleCount;
        }

        function selectCategoryTab(category) {
            document.getElementById('category').value = category;
            filterServices();
        }

        function resetFilters() {
            document.getElementById('search').value = '';
            document.getElementById('category').value = 'all';
            document.getElementById('sort').value = 'default';
            filterServices();
            sortServices();
        }

        function sortServices() {
            const grid = document.getElementById('servicesGrid');
            const cards = Array.from(grid.getElementsByClassName('service-card'));
            const sortValue = document.getElementById('sort').value;

            cards.sort((a, b) => {
                const priceA = parseFloat(a.getAttribute('data-price'));
                const priceB = parseFloat(b.getAttribute('data-price'));
                const nameA = a.getAttribute('data-name');
                const nameB = b.getAttribute('data-name');

                if (sortValue === 'price_low') return priceA - priceB;
                if (sortValue === 'price_high') return priceB - priceA;
                if (sortValue === 'name') return nameA.localeCompare(nameB);
                return 0;
            });

            cards.forEach(card => grid.appendChild(card));
        }

        function openOrderModal(service) {
            currentService = service;
            document.getElementById('modalServiceNameDisplay').textContent = service.name;
            document.getElementById('modalServiceCat').textContent = service.category;
            
            let iconClass = 'fas fa-star';
            if(service.category.includes('Instagram')) iconClass = 'fab fa-instagram';
            else if(service.category.includes('TikTok')) iconClass = 'fab fa-tiktok';
            else if(service.category.includes('YouTube')) iconClass = 'fab fa-youtube';
            else if(service.category.includes('Twitter')) iconClass = 'fab fa-twitter';
            else if(service.category.includes('Telegram')) iconClass = 'fab fa-telegram';
            document.getElementById('modalIcon').className = iconClass;

            // --- LINK PLACEHOLDER GÜNCELLEME KISMI ---
            const linkInput = document.querySelector('input[name="link"]');
            let linkPlaceholder = 'https://linkiniz.com';
            const cat = service.category.toLowerCase();

            if (cat.includes('instagram')) {
                linkPlaceholder = 'https://instagram.com/kullaniciadi (veya gönderi linki)';
            } else if (cat.includes('tiktok')) {
                linkPlaceholder = 'https://tiktok.com/@kullaniciadi/video/...';
            } else if (cat.includes('youtube')) {
                linkPlaceholder = 'https://youtube.com/watch?v=... (veya kanal linki)';
            } else if (cat.includes('twitter') || cat.includes('x.com')) {
                linkPlaceholder = 'https://twitter.com/kullaniciadi/status/...';
            } else if (cat.includes('facebook')) {
                linkPlaceholder = 'https://facebook.com/kullaniciadi';
            } else if (cat.includes('spotify')) {
                linkPlaceholder = 'https://open.spotify.com/track/...';
            } else if (cat.includes('twitch')) {
                linkPlaceholder = 'https://twitch.tv/kullaniciadi';
            } else if (cat.includes('telegram')) {
                linkPlaceholder = 'https://t.me/kanaladi';
            }
            linkInput.placeholder = linkPlaceholder;
            // -----------------------------------------

            document.getElementById('modalServiceId').value = service.db_id;
            
            const min = service.min || 100;
            const max = service.max || 10000;
            
            document.getElementById('minQuantity').textContent = min.toLocaleString();
            document.getElementById('maxQuantity').textContent = max.toLocaleString();
            document.getElementById('quantity').min = min;
            document.getElementById('quantity').max = max;
            document.getElementById('quantity').value = min;
            document.getElementById('unitPrice').innerHTML = '₺' + (service.price_per_1000 || 0).toFixed(2);
            
            calculatePrice();
            document.getElementById('orderModal').style.display = 'flex';
        }

        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
            currentService = null;
        }

        function changeQuantity(amount) {
            const qtyInput = document.getElementById('quantity');
            let val = parseInt(qtyInput.value) || 0;
            val += amount;
            if(currentService) {
                 if(val < currentService.min) val = currentService.min;
                 if(val > currentService.max) val = currentService.max;
            }
            qtyInput.value = val;
            calculatePrice();
        }

        function calculatePrice() {
            if (!currentService) return;
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const pricePer1000 = currentService.price_per_1000 || 0;
            const total = (quantity / 1000) * pricePer1000;
            
            document.getElementById('summaryQuantity').textContent = quantity.toLocaleString() + ' adet';
            document.getElementById('totalPrice').textContent = '₺' + total.toFixed(2);
        }

        document.getElementById('orderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!currentService) return;

            const quantity = parseInt(document.getElementById('quantity').value);
            const total = (quantity / 1000) * currentService.price_per_1000;

            if (total > userBalance) {
                swalWithTheme.fire({
                    icon: 'warning',
                    title: 'Yetersiz Bakiye!',
                    text: 'Bu işlem için bakiyeniz yetersiz. Lütfen bakiye yükleyin.',
                    confirmButtonText: 'Bakiye Yükle',
                    showCancelButton: true,
                    cancelButtonText: 'İptal'
                }).then((res) => {
                    if (res.isConfirmed) window.location.href = 'balance.php';
                });
                return;
            }

            swalWithTheme.fire({
                title: 'Onaylıyor musunuz?',
                html: `<b>${quantity} adet</b> sipariş için<br><b style="font-size:1.5rem; color:#F59E0B">₺${total.toFixed(2)}</b><br>bakiyenizden düşülecektir.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Onayla',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });

        window.onclick = function(event) {
            if (event.target == document.getElementById('orderModal')) {
                closeOrderModal();
            }
        }
    </script>
</body>
</html>