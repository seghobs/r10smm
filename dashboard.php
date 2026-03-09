<?php
ob_start();
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

$_SESSION['user_role'] = $user['user_role'];
$_SESSION['balance'] = $user['balance'];
$_SESSION['api_key'] = $user['api_key'];

$stats = [
    'total_orders' => 0,
    'active_orders' => 0,
    'completed_orders' => 0,
    'total_spent' => 0
];

$total_system_orders = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_system_orders = $stmt->fetchColumn();
} catch (PDOException $e) {}

$security_logs = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM user_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
    $stmt->execute([$user['id']]);
    $security_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $security_logs = [
        ['ip' => $_SERVER['REMOTE_ADDR'], 'device' => 'Mevcut Cihaz', 'created_at' => date('Y-m-d H:i:s')]
    ];
}

$leaderboard = [];
try {
    $stmt = $pdo->query("
        SELECT u.username, SUM(o.price) as total_spent 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY o.user_id 
        ORDER BY total_spent DESC 
        LIMIT 5
    ");
    $leaderboard = $stmt->fetchAll();
} catch (PDOException $e) {}

$answered_ticket_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status = 'answered'");
    $stmt->execute([$user['id']]);
    $answered_ticket_count = $stmt->fetchColumn();
} catch (PDOException $e) {}

$timeline_data = [];
try {
    $stmt = $pdo->prepare("
        SELECT o.*, s.name as service_name, s.category, s.price_per_1000, s.min, s.max 
        FROM orders o 
        LEFT JOIN services s ON o.service_id = s.id 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC 
        LIMIT 7
    ");
    $stmt->execute([$user['id']]);
    $timeline_data = $stmt->fetchAll();
} catch (PDOException $e) {}

$notifications = [];
$unread_notif_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $unread_notif_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {}

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $stats['total_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as active FROM orders WHERE user_id = ? AND status IN ('processing', 'inprogress', 'pending')");
        $stmt->execute([$user['id']]);
        $stats['active_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT SUM(price) as total_spent FROM orders WHERE user_id = ? AND status = 'completed'");
        $stmt->execute([$user['id']]);
        $total = $stmt->fetchColumn();
        $stats['total_spent'] = $total ?: 0;
    }
} catch (PDOException $e) {}

$popular_services = [];
try {
    $stmt = $pdo->query("SELECT s.*, p.url as provider_url, p.api_key as provider_key 
                         FROM services s 
                         LEFT JOIN api_providers p ON s.provider_id = p.id
                         WHERE s.status = 'active' 
                         ORDER BY RAND() LIMIT 3");
    $popular_services = $stmt->fetchAll();
} catch (PDOException $e) {}

$user_xp = $stats['total_spent'];
$user_rank = "Yeni Üye";
$next_rank = "Bronz Üye";
$next_rank_xp = 500;
$rank_icon = "fas fa-user";

if ($user_xp >= 50000) { $user_rank = "Elit Üye"; $next_rank = "Maksimum"; $next_rank_xp = $user_xp; $rank_icon="fas fa-crown"; }
elseif ($user_xp >= 10000) { $user_rank = "Altın Üye"; $next_rank = "Elit Üye"; $next_rank_xp = 50000; $rank_icon="fas fa-gem"; }
elseif ($user_xp >= 2000) { $user_rank = "Gümüş Üye"; $next_rank = "Altın Üye"; $next_rank_xp = 10000; $rank_icon="fas fa-medal"; }
elseif ($user_xp >= 500) { $user_rank = "Bronz Üye"; $next_rank = "Gümüş Üye"; $next_rank_xp = 2000; $rank_icon="fas fa-shield-alt"; }

$xp_percentage = 0;
if ($next_rank !== "Maksimum") {
    $xp_percentage = ($user_xp / $next_rank_xp) * 100;
    if($xp_percentage > 100) $xp_percentage = 100;
} else {
    $xp_percentage = 100;
}
$remaining_xp = $next_rank_xp - $user_xp;

$platform_status = [
    ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'status' => 'Aktif', 'color' => '#E1306C', 'dot' => '#10B981'],
    ['name' => 'TikTok', 'icon' => 'fab fa-tiktok', 'status' => 'Aktif', 'color' => '#FFFFFF', 'dot' => '#10B981'],
    ['name' => 'YouTube', 'icon' => 'fab fa-youtube', 'status' => 'Yoğunluk', 'color' => '#FF0000', 'dot' => '#F59E0B'],
    ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'status' => 'Aktif', 'color' => '#1DA1F2', 'dot' => '#10B981'],
];

$spending_data = [];
$spending_data_json = json_encode([]);
try {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, SUM(price) as daily_total
        FROM orders 
        WHERE user_id = ? AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at) ORDER BY date ASC
    ");
    $stmt->execute([$user['id']]);
    $raw_data = $stmt->fetchAll();
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $found = false;
        foreach ($raw_data as $row) {
            if ($row['date'] == $date) {
                $spending_data[] = ['date' => date('d.m', strtotime($date)), 'amount' => floatval($row['daily_total'])];
                $found = true;
                break;
            }
        }
        if (!$found) $spending_data[] = ['date' => date('d.m', strtotime($date)), 'amount' => 0];
    }
    $spending_data_json = json_encode($spending_data);
} catch (PDOException $e) {}

$live_notifs = [];
try {
    $stmt = $pdo->query("SELECT * FROM live_notifications ORDER BY RAND() LIMIT 20");
    $live_notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_LOGO_TEXT; ?>  SMM Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .navbar { padding: 20px 0; position: fixed; width: 100%; top: 0; z-index: 1000; background: rgba(2, 6, 23, 0.7); backdrop-filter: blur(15px); border-bottom: var(--glass-border); transition: 0.3s; }
        .navbar.scrolled { padding: 15px 0; background: rgba(2, 6, 23, 0.95); }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; }
        
        .logo { display: flex; align-items: center; gap: 12px; font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 800; text-decoration: none; color: white; letter-spacing: -0.5px; }
        .logo i { color: var(--primary); font-size: 1.8rem; filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.5)); }

        .nav-menu { display: flex; gap: 20px; align-items: center; }
        .nav-menu a { text-decoration: none; color: var(--text-muted); font-weight: 500; transition: 0.3s; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 12px; }
        .nav-menu a:hover, .nav-menu a.active { color: white; background: rgba(255,255,255,0.05); }
        .nav-menu a.active { background: rgba(139, 92, 246, 0.1); color: var(--primary); }

        .user-menu { display: flex; align-items: center; gap: 15px; position: relative; }
        .balance-badge { background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 6px 12px; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 5px; font-size: 0.9rem; border: 1px solid rgba(16, 185, 129, 0.2); }
        
        .menu-toggle { display: none; font-size: 1.5rem; color: white; cursor: pointer; background: none; border: none; }

        .notif-wrapper { position: relative; margin-right: 10px; cursor: pointer; }
        .notif-bell { font-size: 1.2rem; color: var(--text-muted); transition: 0.3s; }
        .notif-bell:hover { color: white; }
        .notif-badge { position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; font-size: 0.6rem; padding: 2px 5px; border-radius: 50%; border: 1px solid var(--bg-body); }
        
        .notif-dropdown {
            position: absolute; top: 50px; right: 0; width: 320px; background: #1e293b; border: var(--glass-border); border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5); display: none; flex-direction: column; z-index: 1001; overflow: hidden; animation: slideDown 0.3s ease;
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .notif-dropdown.active { display: flex; }
        .notif-header { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-weight: 600; color: white; display: flex; justify-content: space-between; align-items: center; }
        .notif-list { max-height: 300px; overflow-y: auto; }
        .notif-item { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); transition: 0.2s; }
        .notif-item:hover { background: rgba(255,255,255,0.02); }
        .notif-item.unread { background: rgba(139, 92, 246, 0.05); border-left: 3px solid var(--primary); }
        .notif-title { font-size: 0.9rem; font-weight: 600; color: white; margin-bottom: 3px; }
        .notif-msg { font-size: 0.8rem; color: var(--text-muted); }
        .notif-time { font-size: 0.7rem; color: var(--text-muted); margin-top: 5px; text-align: right; opacity: 0.7; }
        .notif-empty { padding: 20px; text-align: center; color: var(--text-muted); font-size: 0.9rem; }

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
        .btn-full { width: 100%; justify-content: flex-start; margin-bottom: 10px; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 20px; padding: 25px; transition: 0.3s; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: var(--glow); }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: var(--gradient-main); }
        .stat-value { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 700; color: white; line-height: 1; margin-bottom: 5px; }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; }
        .stat-icon { width: 50px; height: 50px; background: rgba(139, 92, 246, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 1.5rem; }

        .dashboard-sections { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .content-card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 24px; padding: 30px; margin-bottom: 30px; }
        .card-title { font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 700; color: white; margin-bottom: 25px; }
        
        .services-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .service-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; cursor: pointer; transition: 0.3s; }
        .service-card:hover { border-color: var(--primary); background: rgba(139, 92, 246, 0.05); transform: translateY(-5px); }
        .s-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: white; background: var(--gradient-main); }
        .s-price { font-size: 1.2rem; font-weight: 700; color: var(--primary); }

        .list-item { display: flex; justify-content: space-between; padding: 15px; background: rgba(255,255,255,0.02); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.05); transition: 0.2s; align-items: flex-start; flex-direction: column; gap: 5px; }
        .announcement-tag { font-size: 0.7rem; padding: 2px 8px; border-radius: 6px; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; display: inline-block; }
        .tag-info { background: rgba(59, 130, 246, 0.2); color: #3B82F6; }
        .tag-warning { background: rgba(245, 158, 11, 0.2); color: #F59E0B; }
        .tag-danger { background: rgba(239, 68, 68, 0.2); color: #EF4444; }
        .tag-success { background: rgba(16, 185, 129, 0.2); color: #10B981; }

        .progress-container { background: rgba(255,255,255,0.1); border-radius: 10px; height: 12px; width: 100%; overflow: hidden; margin-top: 10px; }
        .progress-bar { height: 100%; background: var(--gradient-main); border-radius: 10px; transition: width 0.5s ease; }

        .chart-container { position: relative; height: 250px; width: 100%; margin-top: 15px; }

        .rank-info-overlay {
            position: absolute; top: 0; right: 0; padding: 20px; font-size: 5rem; color: var(--primary); opacity: 0.05; transform: rotate(15deg); pointer-events: none;
        }

        .platform-status-item {
            display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(255,255,255,0.02); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.03);
        }
        .platform-status-dot { width: 10px; height: 10px; border-radius: 50%; box-shadow: 0 0 8px currentColor; }

        .timeline { position: relative; padding-left: 20px; margin-top: 10px; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: rgba(255,255,255,0.1); border-radius: 2px; }
        .timeline-item { position: relative; margin-bottom: 20px; padding-left: 15px; }
        .timeline-dot { position: absolute; left: -25px; top: 0; width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--bg-body); box-shadow: 0 0 10px rgba(0,0,0,0.5); }
        .timeline-date { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 3px; }
        .timeline-content { font-size: 0.9rem; color: white; }

        .ticker-wrap { position: fixed; top: 76px; left: 0; width: 100%; overflow: hidden; height: 40px; background: rgba(2, 6, 23, 0.9); border-bottom: 1px solid rgba(255,255,255,0.05); z-index: 900; line-height: 40px; }
        .ticker { display: inline-block; white-space: nowrap; padding-right: 100%; box-sizing: content-box; animation: ticker 40s linear infinite; }
        .ticker-item { display: inline-block; padding: 0 20px; font-size: 0.9rem; color: white; }
        .ticker-item a { color: var(--primary); text-decoration: none; font-weight: 700; transition:0.3s; }
        .ticker-item a:hover { color: white; }
        @keyframes ticker { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }
        
        .stories-container { display: flex; gap: 20px; overflow-x: auto; padding: 15px 0 25px 0; margin-bottom: 20px; scrollbar-width: none; }
        .stories-container::-webkit-scrollbar { display: none; }
        
        .story-item { display: flex; flex-direction: column; align-items: center; gap: 10px; cursor: pointer; min-width: 85px; transition: 0.3s; }
        .story-item:hover .story-ring { transform: scale(1.08); box-shadow: 0 0 20px rgba(244, 114, 182, 0.4); }
        .story-item:hover .story-title { color: white; }
        
        .story-ring { width: 74px; height: 74px; border-radius: 50%; padding: 3px; position: relative; background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); box-shadow: 0 4px 10px rgba(0,0,0,0.3); transition: 0.3s; }
        .story-ring.seen { background: rgba(255,255,255,0.2); }
        
        .story-img { width: 100%; height: 100%; border-radius: 50%; background: #1e293b; display: flex; align-items: center; justify-content: center; border: 3px solid var(--bg-body); overflow: hidden; position: relative; }
        .story-img i { font-size: 1.8rem; background: var(--gradient-main); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 0 5px rgba(139, 92, 246, 0.5)); }
        
        .story-title { font-size: 0.85rem; color: var(--text-muted); text-align: center; max-width: 85px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; transition: 0.3s; }
        
        .story-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 3000; align-items: center; justify-content: center; backdrop-filter: blur(10px); }
        .story-content { width: 100%; max-width: 400px; height: 80vh; background: #111; border-radius: 24px; position: relative; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 0 50px rgba(0,0,0,0.8); border: 1px solid rgba(255,255,255,0.1); }
        
        .story-progress { display: flex; gap: 5px; padding: 15px; position: absolute; top: 0; width: 100%; z-index: 10; }
        .story-bar { height: 3px; background: rgba(255,255,255,0.3); flex: 1; border-radius: 2px; overflow: hidden; }
        .story-fill { height: 100%; background: white; width: 0%; transition: width 0.1s linear; }
        
        .story-body { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: white; padding: 40px; text-align: center; position: relative; background-size: cover; background-position: center; }
        .story-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8)); z-index: 1; }
        
        .story-inner-content { position: relative; z-index: 2; width: 100%; display: flex; flex-direction: column; align-items: center; height: 100%; justify-content: center; }
        .story-icon-large { font-size: 4rem; margin-bottom: 20px; color: white; filter: drop-shadow(0 0 20px rgba(255,255,255,0.5)); }
        
        .story-btn { margin-top: 30px; padding: 12px 30px; background: white; color: black; border-radius: 30px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 10px; transition: 0.3s; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        .story-btn:hover { transform: scale(1.05); background: #f0f0f0; }
        
        .close-story { position: absolute; top: 20px; right: 20px; color: white; font-size: 2rem; cursor: pointer; z-index: 20; opacity: 0.7; transition: 0.3s; }
        .close-story:hover { opacity: 1; }

        .live-toast { position: fixed; bottom: 20px; left: 20px; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.1); border-left: 4px solid var(--primary); padding: 15px; border-radius: 16px; display: flex; align-items: center; gap: 15px; box-shadow: 0 15px 40px rgba(0,0,0,0.6); transform: translateX(-150%); transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); z-index: 9999; max-width: 320px; }
        .live-toast.show { transform: translateX(0); }
        .lt-img { width: 45px; height: 45px; border-radius: 50%; background: var(--bg-body); overflow: hidden; border: 2px solid rgba(255,255,255,0.1); }
        .lt-content div { font-size: 0.9rem; color: white; font-weight: 700; margin-bottom: 2px; }
        .lt-content small { font-size: 0.8rem; color: #10B981; display: flex; align-items: center; gap: 5px; }
        .lt-content small i { font-size: 0.7rem; }

        @media(max-width:992px){ .ticker-wrap{ top: 70px; } }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 2000; align-items: center; justify-content: center; }
        .modal-content { background: var(--bg-card); border: var(--glass-border); padding: 30px; border-radius: 24px; width: 90%; max-width: 500px; position: relative; animation: slideUp 0.3s ease; }

        .form-control { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; margin-bottom: 15px; }

        .footer { padding: 40px 0; border-top: var(--glass-border); margin-top: 50px; text-align: center; color: var(--text-muted); font-size: 0.9rem; }

        @media (max-width: 992px) {
            .dashboard-sections { grid-template-columns: 1fr; }
            .nav-menu { display: none; position: fixed; top: 70px; left: 0; width: 100%; background: rgba(2,6,23,0.98); flex-direction: column; padding: 20px; height: calc(100vh - 70px); }
            .nav-menu.active { display: flex; }
            .menu-toggle { display: block; }
        }
    </style>
</head>
<body>

    <div class="background-glow">
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>
    </div>

    <?php include 'user_navbar.php'; ?>

    
    </div>

    <div class="main-content container">
        
        <div class="stories-container">
            <div class="story-item" onclick="openStory('kampanya')">
                <div class="story-ring">
                    <div class="story-img"><i class="fas fa-fire"></i></div>
                </div>
                <div class="story-title">Kampanya</div>
            </div>
            <div class="story-item" onclick="openStory('duyuru')">
                <div class="story-ring">
                    <div class="story-img"><i class="fas fa-bullhorn"></i></div>
                </div>
                <div class="story-title">Duyurular</div>
            </div>
            <div class="story-item" onclick="openStory('yeni')">
                <div class="story-ring">
                    <div class="story-img"><i class="fas fa-rocket"></i></div>
                </div>
                <div class="story-title">Yeni Servisler</div>
            </div>
            <div class="story-item" onclick="openStory('bakiye')">
                <div class="story-ring">
                    <div class="story-img"><i class="fas fa-wallet"></i></div>
                </div>
                <div class="story-title">Bakiye Yükle</div>
            </div>
            <div class="story-item" onclick="openStory('api')">
                <div class="story-ring">
                    <div class="story-img"><i class="fas fa-code"></i></div>
                </div>
                <div class="story-title">API Çek</div>
            </div>
        </div>

        <div class="dashboard-header">
            <div>
                <h1>Hoş Geldin, <span class="gradient-text"><?php echo htmlspecialchars($user['username']); ?></span>! 👋</h1>
                <p style="color: var(--text-muted);">Hesap durumun ve panel aktivitelerin aşağıdadır.</p>
            </div>
            <a href="new_order.php" class="btn btn-primary"><i class="fas fa-rocket"></i> Hızlı Sipariş</a>
        </div>

        <?php if($answered_ticket_count > 0): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 20px; border-radius: 20px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; animation: slideDown 0.5s ease;">
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="width: 50px; height: 50px; background: rgba(16, 185, 129, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #10B981; font-size: 1.5rem;">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; margin-bottom: 2px; color: white;">Destek Talebiniz Yanıtlandı!</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Destek ekibimiz <strong><?php echo $answered_ticket_count; ?></strong> talebine yanıt verdi.</p>
                </div>
            </div>
            <a href="support.php" class="btn btn-primary" style="background: #10B981; box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);">
                Yanıtı Gör <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div><div class="stat-value">₺<?php echo number_format($user['balance'], 2); ?></div><div class="stat-label">Bakiye</div></div>
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div><div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div><div class="stat-label">Sipariş</div></div>
                    <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div><div class="stat-value"><?php echo number_format($stats['active_orders']); ?></div><div class="stat-label">Aktif</div></div>
                    <div class="stat-icon"><i class="fas fa-sync fa-spin"></i></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <div><div class="stat-value">₺<?php echo number_format($stats['total_spent'], 2); ?></div><div class="stat-label">Harcama</div></div>
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>

        <div class="dashboard-sections">
            <div class="left-col">
                <div class="content-card" style="background: var(--gradient-card); position: relative; overflow: hidden; margin-bottom: 30px;">
                    <i class="<?php echo $rank_icon; ?> rank-info-overlay"></i>
                    <div class="card-title">Hesap Durumu <span style="background:var(--primary); font-size:0.7rem; padding:2px 8px; border-radius:6px; margin-left:10px;"><?php echo $user_rank; ?></span></div>
                    
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.9rem; color:var(--text-muted);">
                        <span>Mevcut: ₺<?php echo number_format($user_xp, 2); ?></span>
                        <span>Hedef: ₺<?php echo number_format($next_rank_xp, 2); ?></span>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $xp_percentage; ?>%;"></div>
                    </div>
                    <div style="margin-top:10px; font-size:0.85rem; color:var(--text-muted);">
                        <?php if($next_rank !== "Maksimum"): ?>
                            <i class="fas fa-info-circle"></i> <strong><?php echo $next_rank; ?></strong> olmak için ₺<?php echo number_format($remaining_xp, 2); ?> daha harca.
                        <?php else: ?>
                            <i class="fas fa-crown"></i> Zirvedesin! En yüksek rütbeye ulaştın.
                        <?php endif; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>İşlem Zaman Çizelgesi</span>
                        <a href="orders.php" style="font-size: 0.85rem; color: var(--primary); text-decoration: none;">Tümü <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <div class="timeline">
                        <?php if(empty($timeline_data)): ?>
                            <div style="color:var(--text-muted); font-size:0.9rem;">Henüz bir işlem kaydı bulunmuyor.</div>
                        <?php else: ?>
                            <?php foreach($timeline_data as $item): ?>
                            <div class="timeline-item">
                                <?php 
                                    $dotColor = '#94A3B8';
                                    if($item['status'] == 'completed') $dotColor = '#10B981';
                                    elseif($item['status'] == 'pending') $dotColor = '#F59E0B';
                                    elseif($item['status'] == 'canceled') $dotColor = '#EF4444';
                                ?>
                                <div class="timeline-dot" style="background: <?php echo $dotColor; ?>;"></div>
                                <div class="timeline-date"><?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?></div>
                                <div class="timeline-content">
                                    <div style="font-weight: 600; margin-bottom: 2px;">
                                        Sipariş #<?php echo $item['id']; ?>
                                        <span style="font-size: 0.75rem; color: <?php echo $dotColor; ?>; background: <?php echo $dotColor; ?>20; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">
                                            <?php echo strtoupper($item['status']); ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars(mb_substr($item['service_name'], 0, 30)) . '...'; ?> - ₺<?php echo number_format($item['price'], 2); ?>
                                    </div>
                                    <button onclick='reOrder(<?php echo htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8'); ?>)' style="margin-top: 5px; background: none; border: 1px solid rgba(255,255,255,0.1); color: var(--primary); padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 0.75rem; transition: 0.2s;">
                                        <i class="fas fa-redo-alt"></i> Tekrarla
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($popular_services)): ?>
                <div class="content-card">
                    <div class="card-title">Popüler Hizmetler</div>
                    <div class="services-grid">
                        <?php foreach ($popular_services as $service): ?>
                        <?php 
                            $service_data = [
                                'db_id' => $service['id'],
                                'api_id' => $service['api_service_id'],
                                'name' => $service['name'],
                                'category' => $service['category'],
                                'price_per_1000' => round(floatval($service['price_per_1000']), 2),
                                'min' => intval($service['min_quantity']),
                                'max' => intval($service['max_quantity']),
                                'description' => $service['description']
                            ];
                        ?>
                        <div class="service-card" onclick="openOrderModal(<?php echo htmlspecialchars(json_encode($service_data)); ?>)">
                            <div class="service-header">
                                <div class="s-icon">
                                    <?php 
                                    $p_icon = 'fas fa-bolt';
                                    if(strpos($service['category'], 'Instagram') !== false) $p_icon = 'fab fa-instagram';
                                    elseif(strpos($service['category'], 'TikTok') !== false) $p_icon = 'fab fa-tiktok';
                                    elseif(strpos($service['category'], 'YouTube') !== false) $p_icon = 'fab fa-youtube';
                                    ?>
                                    <i class="<?php echo $p_icon; ?>"></i>
                                </div>
                                <div>
                                    <div class="s-name" style="color:white; font-weight:600;"><?php echo htmlspecialchars($service['name']); ?></div>
                                    <div class="s-cat" style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($service['category']); ?></div>
                                </div>
                            </div>
                            <div class="s-price">₺<?php echo number_format($service['price_per_1000'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="right-col">
                <div class="content-card" style="background: linear-gradient(135deg, #FF6B6B 0%, #C33764 100%); text-align: center; padding: 40px 20px;">
                    <i class="fas fa-shopping-cart" style="font-size: 4rem; color: rgba(255,255,255,0.2); margin-bottom: 20px;"></i>
                    <div style="font-family: 'Outfit'; font-size: 3rem; font-weight: 800; color: white; line-height: 1;">
                        <?php echo number_format($total_system_orders); ?>
                    </div>
                    <div style="color: rgba(255,255,255,0.9); font-size: 1rem; margin-top: 10px; font-weight: 500;">
                        Değerli Müşterilerimizin Toplam Sipariş Sayısı
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-title">Topluluk ve Destek</div>
                    <a href="https://t.me/PrimalTriad" target="_blank" class="btn btn-outline btn-full" style="color:#38bdf8; border-color:rgba(56,189,248,0.3);">
                        <i class="fab fa-telegram-plane"></i> Telegram Kanalı
                    </a>
                    <a href="support.php" class="btn btn-outline btn-full">
                        <i class="fas fa-headset"></i> Destek Talebi
                    </a>
                    <a href="https://wa.me/+212721490727" target="_blank" class="btn btn-outline btn-full" style="color:#25D366; border-color:rgba(37,211,102,0.3);">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <?php if ($user['user_role'] === 'admin' || $user['user_role'] === 'super_admin'): ?>
                    <a href="admin_dashboard.php" class="btn btn-primary btn-full" style="margin-top:10px; background:linear-gradient(135deg, #ef4444, #b91c1c);">
                        <i class="fas fa-user-shield"></i> Admin Paneline Git
                    </a>
                    <?php endif; ?>
                </div>

                <div class="content-card">
                    <div class="card-title"><i class="fas fa-trophy" style="color:#F59E0B; margin-right:10px;"></i> Haftanın En'leri</div>
                    <?php if(empty($leaderboard)): ?>
                        <div style="text-align:center; color:var(--text-muted); padding:10px;">Henüz veri yok.</div>
                    <?php else: ?>
                        <?php foreach($leaderboard as $index => $lb): ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid rgba(255,255,255,0.05);">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:30px; height:30px; border-radius:50%; background:rgba(255,255,255,0.1); display:flex; align-items:center; justify-content:center; font-weight:700; color:<?php echo $index == 0 ? '#F59E0B' : ($index == 1 ? '#94A3B8' : '#CD7F32'); ?>;">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div style="font-weight:600;">
                                        <?php 
                                            $name = $lb['username'];
                                            echo substr($name, 0, 1) . '***' . substr($name, -1); 
                                        ?>
                                    </div>
                                </div>
                                <div style="color:#10B981; font-weight:700; font-size:0.9rem;">
                                    ₺<?php echo number_format($lb['total_spent'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="content-card">
                    <div class="card-title"><i class="fas fa-sync-alt" style="color:#10B981; margin-right:10px;"></i> Servis Güncellemeleri</div>
                    <div style="max-height: 250px; overflow-y: auto; padding-right: 5px;">
                        <div class="list-item" style="border-left: 3px solid #EF4444;">
                            <div style="display:flex; justify-content:space-between; width:100%;">
                                <small style="color:#EF4444; font-weight:700;">FİYAT ARTTI</small>
                                <small style="color:var(--text-muted);">10 dk önce</small>
                            </div>
                            <div style="font-size:0.85rem;">ID: 102 - Instagram Beğeni</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">₺5.00 -> ₺6.20</div>
                        </div>

                        <div class="list-item" style="border-left: 3px solid #10B981;">
                            <div style="display:flex; justify-content:space-between; width:100%;">
                                <small style="color:#10B981; font-weight:700;">FİYAT DÜŞTÜ</small>
                                <small style="color:var(--text-muted);">1 saat önce</small>
                            </div>
                            <div style="font-size:0.85rem;">ID: 405 - Tiktok İzlenme</div>
                            <div style="font-size:0.8rem; color:var(--text-muted);">₺1.50 -> ₺0.85</div>
                        </div>

                        <div class="list-item" style="border-left: 3px solid #F59E0B;">
                            <div style="display:flex; justify-content:space-between; width:100%;">
                                <small style="color:#F59E0B; font-weight:700;">YENİ SERVİS</small>
                                <small style="color:var(--text-muted);">2 saat önce</small>
                            </div>
                            <div style="font-size:0.85rem;">ID: 500 - Twitter Blue Tikli Takipçi</div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-title"><i class="fas fa-shield-alt" style="color:#8B5CF6; margin-right:10px;"></i> Son Erişim Kayıtları</div>
                    <div style="font-size: 0.85rem;">
                        <?php foreach($security_logs as $log): ?>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 10px 0;">
                            <div>
                                <div style="color: white; font-weight: 500;"><?php echo htmlspecialchars($log['ip']); ?></div>
                                <div style="color: var(--text-muted); font-size: 0.75rem;"><?php echo htmlspecialchars($log['device']); ?></div>
                            </div>
                            <div style="color: #10B981; font-size: 0.75rem; text-align: right;">
                                <?php echo date('d.m H:i', strtotime($log['created_at'])); ?><br>
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-title"><i class="fas fa-hourglass-half" style="color:#F59E0B; margin-right:10px;"></i> Ortalama Süre</div>
                    <div style="margin-bottom: 10px; font-size: 0.85rem; color: var(--text-muted);">Servis ID'si girerek tahmini tamamlanma süresini öğren.</div>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" id="avgTimeId" class="form-control" placeholder="Servis ID (Örn: 101)" style="margin-bottom: 0;">
                        <button onclick="checkAvgTime()" class="btn btn-primary" style="padding: 0 15px;"><i class="fas fa-search"></i></button>
                    </div>
                    <div id="avgTimeResult" style="margin-top: 10px; font-size: 0.9rem; font-weight: 600; color: white; display: none;"></div>
                </div>

                <div class="content-card">
                    <div class="card-title"><i class="fas fa-poll" style="color:#3B82F6; margin-right:10px;"></i> Sırada Ne Gelsin?</div>
                    <div id="pollArea">
                        <div style="margin-bottom: 15px; font-size: 0.9rem;">Sizce panele hangi kategoriye ağırlık vermeliyiz?</div>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px;">
                            <input type="radio" name="poll" value="1"> Twitter/X Hizmetleri
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px;">
                            <input type="radio" name="poll" value="2"> LinkedIn Hizmetleri
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; padding: 10px; background: rgba(255,255,255,0.03); border-radius: 8px;">
                            <input type="radio" name="poll" value="3"> Spotify Hizmetleri
                        </label>
                        <button onclick="submitPoll()" class="btn btn-outline" style="width: 100%; justify-content: center; margin-top: 5px;">Oy Ver</button>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-title">Harcama Analizi</div>
                    <div class="chart-container"><canvas id="spendingChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <div id="liveToast" class="live-toast">
        <div class="lt-img">
            <img src="https://ui-avatars.com/api/?name=User&background=random" id="ltImage" width="45" height="45">
        </div>
        <div class="lt-content">
            <div id="ltTitle">Kullanıcı</div>
            <small id="ltText"><i class="fas fa-check-circle"></i> Sipariş verdi</small>
        </div>
    </div>

    <div class="story-modal" id="storyModal">
        <div class="story-content">
            <div class="story-progress">
                <div class="story-bar"><div class="story-fill" id="storyFill"></div></div>
            </div>
            <div class="close-story" onclick="closeStory()">&times;</div>
            
            <div class="story-body" id="storyBody">
                </div>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2026 <?php echo SITE_LOGO_TEXT; ?>  SMM Panel. Tüm hakları saklıdır.</p>
    </footer>

    <div class="modal" id="orderModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeOrderModal()" style="position:absolute; top:20px; right:20px; background:none; border:none; color:white; cursor:pointer;"><i class="fas fa-times"></i></button>
            <h2 style="margin-bottom:20px;">Sipariş Oluştur</h2>
            <form action="create_order" method="POST">
                <input type="hidden" id="modalServiceId" name="service_id">
                <input type="text" id="modalServiceName" class="form-control" readonly>
                <input type="text" name="link" id="modalLink" class="form-control" placeholder="Link (https://..) veya Username" required>
                <input type="number" id="quantity" name="quantity" class="form-control" placeholder="Miktar" required oninput="calculatePrice()">
                <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:12px; margin-bottom:20px; display:flex; justify-content:space-between;">
                    <span>Toplam:</span><span id="totalPrice" style="font-weight:700; color:var(--primary);">₺0.00</span>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Onayla</button>
            </form>
        </div>
    </div>

    <script>
        

        

        

        

        

        let currentService = null;
        function openOrderModal(service, prefillLink = '', prefillQty = '') {
            currentService = service;
            document.getElementById('modalServiceName').value = service.name || service.service_name;
            document.getElementById('modalServiceId').value = service.id || service.service_id;
            
            if(prefillLink) document.getElementById('modalLink').value = prefillLink;
            else document.getElementById('modalLink').value = '';

            if(prefillQty) document.getElementById('quantity').value = prefillQty;
            else document.getElementById('quantity').value = service.min;

            calculatePrice();
            document.getElementById('orderModal').style.display = 'flex';
        }
        function closeOrderModal() { document.getElementById('orderModal').style.display = 'none'; }
        
        function reOrder(item) {
            const serviceData = {
                id: item.service_id,
                name: item.service_name,
                price_per_1000: item.price_per_1000,
                min: item.min,
                max: item.max
            };
            openOrderModal(serviceData, item.link, item.quantity);
        }

        function calculatePrice() {
            if(!currentService) return;
            let qty = document.getElementById('quantity').value;
            let price = (qty / 1000) * currentService.price_per_1000;
            document.getElementById('totalPrice').innerText = '₺' + price.toFixed(2);
        }

        const ctx = document.getElementById('spendingChart').getContext('2d');
        const spendingData = <?php echo $spending_data_json; ?>;
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: spendingData.map(d => d.date),
                datasets: [{
                    label: 'Harcama',
                    data: spendingData.map(d => d.amount),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        function checkAvgTime() {
            const id = document.getElementById('avgTimeId').value;
            const resDiv = document.getElementById('avgTimeResult');
            if(!id) return;
            
            resDiv.style.display = 'block';
            resDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Hesaplanıyor...';
            
            setTimeout(() => {
                const hours = Math.floor(Math.random() * 5) + 1;
                const mins = Math.floor(Math.random() * 59);
                resDiv.innerHTML = `<span style="color:#10B981;">Tahmini Süre: ${hours} saat ${mins} dakika</span>`;
            }, 1000);
        }

        function submitPoll() {
            const pollArea = document.getElementById('pollArea');
            pollArea.innerHTML = `
                <div style="text-align:center; padding:20px; animation:fadeIn 0.5s;">
                    <i class="fas fa-check-circle" style="font-size:3rem; color:#10B981; margin-bottom:10px;"></i>
                    <div style="font-weight:600;">Oyunuz Alındı!</div>
                    <div style="color:var(--text-muted); font-size:0.85rem;">Geri bildiriminiz için teşekkürler.</div>
                </div>
            `;
        }

        let storyTimer;
        
        const storyData = {
            'kampanya': {
                bg: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?w=600&q=80',
                icon: 'fas fa-fire',
                title: 'Hafta Sonu İndirimi!',
                text: 'Tüm Instagram servislerinde %20 indirim başladı.',
                btnText: 'Servisleri Gör',
                btnLink: 'services.php'
            },
            'duyuru': {
                bg: 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=600&q=80',
                icon: 'fas fa-bullhorn',
                title: 'Sistem Güncellemesi',
                text: 'Daha hızlı sipariş gönderimi için sunucularımızı güçlendirdik.',
                btnText: 'Detaylar',
                btnLink: '#'
            },
            'yeni': {
                bg: 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=600&q=80',
                icon: 'fas fa-rocket',
                title: 'TikTok Keşfet',
                text: 'Yeni TikTok Keşfet etkili izlenme servisi aktif!',
                btnText: 'Hemen Dene',
                btnLink: 'services.php'
            },
            'bakiye': {
                bg: 'https://images.unsplash.com/photo-1580519542036-c47de6196ba5?w=600&q=80',
                icon: 'fas fa-wallet',
                title: 'Bonus Bakiye',
                text: '1000 TL ve üzeri yüklemelerde %5 bonus hediye ediyoruz.',
                btnText: 'Yükle',
                btnLink: 'balance.php'
            },
            'api': {
                bg: 'https://images.unsplash.com/photo-1516259762381-22954d7d3ad2?w=600&q=80',
                icon: 'fas fa-code',
                title: 'Gelişmiş API',
                text: 'Kendi sitenizi bağlayın, otomatik satış yapmaya başlayın.',
                btnText: 'Döküman',
                btnLink: 'api.php'
            }
        };

        function openStory(type) {
            const modal = document.getElementById('storyModal');
            const body = document.getElementById('storyBody');
            const fill = document.getElementById('storyFill');
            const data = storyData[type];
            
            if(!data) return;

            body.style.backgroundImage = `url('${data.bg}')`;

            let content = `
                <div class="story-overlay"></div>
                <div class="story-inner-content">
                    <i class="${data.icon} story-icon-large"></i>
                    <h2 style="font-family:'Outfit'; font-size:2rem; margin-bottom:10px;">${data.title}</h2>
                    <p style="font-size:1.1rem; opacity:0.9;">${data.text}</p>
                    <a href="${data.btnLink}" class="story-btn">${data.btnText} <i class="fas fa-arrow-right"></i></a>
                </div>
            `;

            body.innerHTML = content;
            modal.style.display = 'flex';
            fill.style.width = '0%';
            
            document.querySelector(`.story-item[onclick="openStory('${type}')"] .story-ring`).classList.add('seen');

            setTimeout(() => { fill.style.width = '100%'; }, 100);
            
            storyTimer = setTimeout(() => {
                closeStory();
            }, 5000);
        }

        function closeStory() {
            document.getElementById('storyModal').style.display = 'none';
            clearTimeout(storyTimer);
            document.getElementById('storyFill').style.width = '0%';
        }

        const liveNotifs = <?php echo json_encode($live_notifs); ?>;
        let notifIndex = 0;
        
        function showLiveToast() {
            if (liveNotifs.length === 0) return;
            
            const toast = document.getElementById('liveToast');
            const nameEl = document.getElementById('ltTitle');
            const textEl = document.getElementById('ltText');
            const imgEl = document.getElementById('ltImage');

            const currentNotif = liveNotifs[notifIndex];
            
            nameEl.innerText = currentNotif.name;
            textEl.innerHTML = `<i class="fas fa-check-circle"></i> ${currentNotif.quantity} ${currentNotif.service_name} satın aldı`;
            imgEl.src = `https://ui-avatars.com/api/?name=${currentNotif.name}&background=random&color=fff`;

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 5000);
            
            notifIndex = (notifIndex + 1) % liveNotifs.length;
        }

        if (liveNotifs.length > 0) {
            setTimeout(showLiveToast, 10000);
            setInterval(showLiveToast, 30000); // Her 30 saniyede bir göster
        }

    </script>
</body>
</html>