<?php
ob_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

if (!$admin || ($admin['user_role'] !== 'admin' && $admin['user_role'] !== 'super_admin')) {
    header('Location: dashboard.php');
    exit;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_paytr'])) {
    $merchant_id = trim($_POST['paytr_merchant_id']);
    $merchant_key = trim($_POST['paytr_merchant_key']);
    $merchant_salt = trim($_POST['paytr_merchant_salt']);

    try {
        // Fonksiyonel olarak kaydet veya güncelle
        $settings_to_update = [
            'paytr_merchant_id' => $merchant_id,
            'paytr_merchant_key' => $merchant_key,
            'paytr_merchant_salt' => $merchant_salt
        ];

        foreach ($settings_to_update as $s_key => $s_val) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$s_key, $s_val, $s_val]);
        }
        
        $success_msg = "PayTR ayarları başarıyla kaydedildi.";
        
        // Yeniden yüklemek için global değişkenleri güncelle
        $sys_settings['paytr_merchant_id'] = $merchant_id;
        $sys_settings['paytr_merchant_key'] = $merchant_key;
        $sys_settings['paytr_merchant_salt'] = $merchant_salt;
        
    } catch (PDOException $e) {
        $error_msg = "Ayarlar kaydedilirken hata oluştu: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_iyzico'])) {
    $api_key = trim($_POST['iyzico_api_key']);
    $secret_key = trim($_POST['iyzico_secret_key']);

    try {
        $settings_to_update = [
            'iyzico_api_key' => $api_key,
            'iyzico_secret_key' => $secret_key
        ];

        foreach ($settings_to_update as $s_key => $s_val) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$s_key, $s_val, $s_val]);
        }
        
        $success_msg = "Iyzico ayarları başarıyla kaydedildi.";
        
        $sys_settings['iyzico_api_key'] = $api_key;
        $sys_settings['iyzico_secret_key'] = $secret_key;
        
    } catch (PDOException $e) {
        $error_msg = "Ayarlar kaydedilirken hata oluştu: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bank'])) {
    $b_name = trim($_POST['bank_name']);
    $b_recp = trim($_POST['bank_recipient']);
    $b_iban = trim($_POST['bank_iban']);

    try {
        $settings_to_update = [
            'bank_name' => $b_name,
            'bank_recipient' => $b_recp,
            'bank_iban' => $b_iban
        ];

        foreach ($settings_to_update as $s_key => $s_val) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$s_key, $s_val, $s_val]);
        }
        
        $success_msg = "Banka ayarları başarıyla kaydedildi.";
        
        $sys_settings['bank_name'] = $b_name;
        $sys_settings['bank_recipient'] = $b_recp;
        $sys_settings['bank_iban'] = $b_iban;
        
    } catch (PDOException $e) {
        $error_msg = "Ayarlar kaydedilirken hata oluştu: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_logo'])) {
    $l_text = trim($_POST['site_logo_text']);
    $l_image = trim($_POST['site_logo_image']);

    try {
        $settings_to_update = [
            'site_logo_text' => $l_text,
            'site_logo_image' => $l_image
        ];

        foreach ($settings_to_update as $s_key => $s_val) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$s_key, $s_val, $s_val]);
        }
        
        $success_msg = "Logo ayarları başarıyla kaydedildi.";
        
        $sys_settings['site_logo_text'] = $l_text;
        $sys_settings['site_logo_image'] = $l_image;
        
    } catch (PDOException $e) {
        $error_msg = "Ayarlar kaydedilirken hata oluştu: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_announcement'])) {
    $a_active = isset($_POST['announcement_active']) ? '1' : '0';
    $icons = $_POST['ticker_icon'] ?? [];
    $texts = $_POST['ticker_text'] ?? [];
    $links = $_POST['ticker_link'] ?? [];
    $link_texts = $_POST['ticker_link_text'] ?? [];
    
    $tickers = [];
    if (is_array($texts)) {
        for ($i = 0; $i < count($texts); $i++) {
            if (!empty(trim($texts[$i]))) {
                $tickers[] = [
                    'icon' => trim($icons[$i] ?? ''),
                    'text' => trim($texts[$i] ?? ''),
                    'link' => trim($links[$i] ?? ''),
                    'link_text' => trim($link_texts[$i] ?? '')
                ];
            }
        }
    }
    $a_content = json_encode($tickers, JSON_UNESCAPED_UNICODE);

    try {
        $settings_to_update = [
            'announcement_active' => $a_active,
            'announcement_content' => $a_content
        ];

        foreach ($settings_to_update as $s_key => $s_val) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$s_key, $s_val, $s_val]);
        }
        
        $success_msg = "Kayan Duyuru ayarları başarıyla kaydedildi.";
        
        $sys_settings['announcement_active'] = $a_active;
        $sys_settings['announcement_content'] = $a_content;
        
    } catch (PDOException $e) {
        $error_msg = "Ayarlar kaydedilirken hata oluştu: " . $e->getMessage();
    }
}

// Mevcut ayarları al
$paytr_mid = $sys_settings['paytr_merchant_id'] ?? '';
$paytr_key = $sys_settings['paytr_merchant_key'] ?? '';
$paytr_salt = $sys_settings['paytr_merchant_salt'] ?? '';

$iy_api_key = $sys_settings['iyzico_api_key'] ?? '';
$iy_secret_key = $sys_settings['iyzico_secret_key'] ?? '';

$bnk_name = $sys_settings['bank_name'] ?? 'VakıfBank';
$bnk_recp = $sys_settings['bank_recipient'] ?? 'Furkan Demirkıran';
$bnk_iban = $sys_settings['bank_iban'] ?? 'TR17 0001 5001 5800 7350 4899 83';

$l_text = $sys_settings['site_logo_text'] ?? 'Darq SMM';
$l_image = $sys_settings['site_logo_image'] ?? '';

$a_active = $sys_settings['announcement_active'] ?? '1';
$a_content = $sys_settings['announcement_content'] ?? '<div class="ticker-item"><i class="fab fa-telegram"></i> Güncel duyurular için Telegram kanalımıza katılın: <a href="https://t.me/PrimalTriad" target="_blank">@PrimalTriad</a></div><div class="ticker-item"><i class="fas fa-bolt"></i> Yeni servisler eklendi! Fiyatlar güncellendi. Hemen inceleyin.</div>';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ayarları - <?= htmlspecialchars($l_text) ?> YÖNETİM</title>
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
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --glow: 0 0 30px rgba(139, 92, 246, 0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-body); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        .background-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        .glow-blob { position: absolute; filter: blur(90px); opacity: 0.3; border-radius: 50%; animation: float 10s infinite ease-in-out alternate; }
        .blob-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: var(--primary); }
        .blob-2 { bottom: 10%; right: -10%; width: 500px; height: 500px; background: #059669; animation-delay: -5s; }
        @keyframes float { 0% { transform: translate(0,0); } 100% { transform: translate(30px,30px); } }
.container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
.main-content { padding: 120px 0 40px; }
        
        .page-header { margin-bottom: 30px; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 700; color: white; }
        .page-desc { color: var(--text-muted); }

        .card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 24px; padding: 30px; margin-bottom: 30px; max-width: 800px; }
        .card-header { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: white; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-family: 'Plus Jakarta Sans', sans-serif; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); background: rgba(255,255,255,0.05); }

        .btn { padding: 12px 20px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer; font-size: 0.95rem; }
        .btn-primary { background: var(--gradient-main); color: white; box-shadow: var(--glow); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(139, 92, 246, 0.5); }
    </style>
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>

    <div class="background-glow">
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>
    </div>

    <?php $current_page = 'admin_settings.php'; include 'admin_navbar.php'; ?>

    <div class="main-content container">
        <div class="page-header">
            <h1 class="page-title">Sistem Ayarları</h1>
            <p class="page-desc">Sitenizin genel modüllerini ve ödeme yöntemlerini yapılandırın.</p>
        </div>

        <?php if ($success_msg): ?>
            <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({icon: 'success', title: 'Başarılı', text: '<?php echo $success_msg; ?>'}));</script>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <script>document.addEventListener('DOMContentLoaded', () => Swal.fire({icon: 'error', title: 'Hata', text: '<?php echo $error_msg; ?>'}));</script>
        <?php endif; ?>

        <div class="card" style="border-color: rgba(236, 72, 153, 0.3);">
            <div class="card-header">
                <i class="fas fa-image" style="color: #EC4899;"></i> Logo & Site Ayarları
            </div>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Logo Yazısı (Text)</label>
                    <input type="text" name="site_logo_text" class="form-control" value="<?php echo htmlspecialchars($l_text); ?>" placeholder="Örn: Darq SMM" required>
                    <small style="color:var(--text-muted); font-size: 0.8rem; display:block; margin-top:5px;">Eğer görsel logo yoksa bu yazı gösterilecektir.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Logo Görsel URL (İsteğe Bağlı)</label>
                    <input type="text" name="site_logo_image" class="form-control" value="<?php echo htmlspecialchars($l_image); ?>" placeholder="Örn: https://site.com/logo.png">
                    <small style="color:var(--text-muted); font-size: 0.8rem; display:block; margin-top:5px;">Eğer bir logo resmi linki girerseniz, tüm panellerde bu logo resim olarak çıkacaktır.</small>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" name="save_logo" class="btn btn-primary" style="width: auto; background: linear-gradient(135deg, #EC4899 0%, #BE185D 100%);">
                        <i class="fas fa-save"></i> Logo Ayarlarını Kaydet
                    </button>
                </div>
            </form>
        </div>

        <div class="card" style="border-color: rgba(245, 158, 11, 0.3);">
            <div class="card-header">
                <i class="fas fa-bullhorn" style="color: #F59E0B;"></i> Kayan Duyuru (Marquee) Ayarları
            </div>
            <form method="POST">
                <div class="form-group" style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                    <label class="form-label" style="margin-bottom: 0;">Kayan Duyuru Aktif mi?</label>
                    <label style="position: relative; display: inline-block; width: 50px; height: 24px;">
                        <input type="checkbox" name="announcement_active" value="1" <?php echo $a_active == '1' ? 'checked' : ''; ?> style="opacity: 0; width: 0; height: 0; position: absolute;" onchange="this.nextElementSibling.style.backgroundColor = this.checked ? 'var(--primary)' : 'rgba(255,255,255,0.1)'; this.nextElementSibling.firstElementChild.style.transform = this.checked ? 'translateX(26px)' : 'translateX(0)';">
                        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: <?php echo $a_active == '1' ? 'var(--primary)' : 'rgba(255,255,255,0.1)'; ?>; border-radius: 24px; transition: .4s; display: flex; align-items: center; padding: 0 4px;">
                            <span style="display: inline-block; width: 16px; height: 16px; background-color: white; border-radius: 50%; transition: .4s; transform: translateX(<?php echo $a_active == '1' ? '26px' : '0'; ?>);"></span>
                        </span>
                    </label>
                </div>

                <div class="form-group" id="ticker-container">
                    <label class="form-label">Duyurular (Sınırsız ekleyebilirsiniz)</label>
                    <?php
                    $tickers = json_decode($a_content, true);
                    if (!is_array($tickers)) {
                        // Eski HTML veya boşsa varsayılan
                        $tickers = [
                            ['icon' => 'fab fa-telegram', 'text' => 'Güncel duyurular için Telegram kanalımıza katılın:', 'link' => 'https://t.me/PrimalTriad', 'link_text' => '@PrimalTriad'],
                            ['icon' => 'fas fa-bolt', 'text' => 'Yeni servisler eklendi! Fiyatlar güncellendi. Hemen inceleyin.', 'link' => '', 'link_text' => '']
                        ];
                    }
                    foreach ($tickers as $t):
                    ?>
                    <div class="ticker-row" style="display: flex; gap: 10px; margin-bottom: 15px; align-items: start; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);">
                        <div style="flex: 1;">
                            <div class="icon-picker-btn" onclick="openIconModal(this)" style="margin-bottom: 10px; cursor: pointer; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 15px; color: white; display: flex; align-items: center; justify-content: space-between;">
                                <span class="selected-icon-preview">
                                    <?php if (!empty($t['icon'])): ?>
                                        <i class="<?php echo htmlspecialchars($t['icon']); ?>" style="margin-right:8px; color:var(--primary);"></i> <?php echo htmlspecialchars($t['icon']); ?>
                                    <?php else: ?>
                                        İkon Seç (İsteğe Bağlı)
                                    <?php endif; ?>
                                </span>
                                <i class="fas fa-chevron-down" style="color: var(--text-muted); font-size: 0.8rem;"></i>
                            </div>
                            <input type="hidden" name="ticker_icon[]" class="ticker-icon-input" value="<?php echo htmlspecialchars($t['icon'] ?? ''); ?>">
                            <input type="text" name="ticker_text[]" class="form-control" value="<?php echo htmlspecialchars($t['text'] ?? ''); ?>" placeholder="Duyuru Metni (Zorunlu)" style="margin-bottom: 10px;" required>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="ticker_link_text[]" class="form-control" value="<?php echo htmlspecialchars($t['link_text'] ?? ''); ?>" placeholder="Tıklanabilir Yazı (Örn: Tıklayın)">
                                <input type="text" name="ticker_link[]" class="form-control" value="<?php echo htmlspecialchars($t['link'] ?? ''); ?>" placeholder="Bağlantı Linki (https://...)">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline" style="color: #ef4444; border-color: rgba(239,68,68,0.3); padding: 12px; height: 100%; border-radius: 12px;" onclick="this.parentElement.remove()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="btn btn-outline" onclick="addTickerRow()" style="font-size: 0.85rem; padding: 8px 15px;"><i class="fas fa-plus"></i> Yeni Duyuru Satırı Ekle</button>

                <div style="margin-top: 30px;">
                    <button type="submit" name="save_announcement" class="btn btn-primary" style="width: auto; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                        <i class="fas fa-save"></i> Duyuru Ayarlarını Kaydet
                    </button>
                </div>
            </form>
        </div>

        <script>
        function addTickerRow() {
            const container = document.getElementById('ticker-container');
            const row = document.createElement('div');
            row.className = 'ticker-row';
            row.style.cssText = 'display: flex; gap: 10px; margin-bottom: 15px; align-items: start; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 1px dashed rgba(255,255,255,0.1);';
            row.innerHTML = `
                <div style="flex: 1;">
                    <div class="icon-picker-btn" onclick="openIconModal(this)" style="margin-bottom: 10px; cursor: pointer; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 12px 15px; color: white; display: flex; align-items: center; justify-content: space-between;">
                        <span class="selected-icon-preview">İkon Seç (İsteğe Bağlı)</span>
                        <i class="fas fa-chevron-down" style="color: var(--text-muted); font-size: 0.8rem;"></i>
                    </div>
                    <input type="hidden" name="ticker_icon[]" class="ticker-icon-input" value="">
                    <input type="text" name="ticker_text[]" class="form-control" placeholder="Duyuru Metni (Zorunlu)" style="margin-bottom: 10px;" required>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="ticker_link_text[]" class="form-control" placeholder="Tıklanabilir Yazı (Örn: Tıklayın)">
                        <input type="text" name="ticker_link[]" class="form-control" placeholder="Bağlantı Linki (https://...)">
                    </div>
                </div>
                <button type="button" class="btn btn-outline" style="color: #ef4444; border-color: rgba(239,68,68,0.3); padding: 12px; height: 100%; border-radius: 12px;" onclick="this.parentElement.remove()">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(row);
        }
        </script>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-credit-card" style="color: var(--primary);"></i> PayTR POS Ayarları
            </div>
            <form method="POST">
                
                <div class="form-group">
                    <label class="form-label">Mağaza No (Merchant ID)</label>
                    <input type="text" name="paytr_merchant_id" class="form-control" value="<?php echo htmlspecialchars($paytr_mid); ?>" placeholder="Örn: 123456" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mağaza Parolası (Merchant Key)</label>
                    <input type="text" name="paytr_merchant_key" class="form-control" value="<?php echo htmlspecialchars($paytr_key); ?>" placeholder="API Entegrasyon bilgisi alanındaki parolanızı girin" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mağaza Gizli Anahtarı (Merchant Salt)</label>
                    <input type="text" name="paytr_merchant_salt" class="form-control" value="<?php echo htmlspecialchars($paytr_salt); ?>" placeholder="API Entegrasyon bilgisi alanındaki salt değerini girin" required>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" name="save_paytr" class="btn btn-primary" style="width: auto;">
                        <i class="fas fa-save"></i> Ayarları Kaydet
                    </button>
                </div>

                <div style="margin-top: 25px; padding: 15px; background: rgba(16, 185, 129, 0.1); border: 1px dashed rgba(16, 185, 129, 0.3); border-radius: 12px; font-size: 0.85rem; color: var(--text-muted);">
                    <strong style="color: #10B981;">Bilgi:</strong> PayTR panelinde ayarlamanız gereken Bildirim URL (Callback URL):<br>
                    <code style="color: white; user-select: all;"><?php echo SITE_URL; ?>/paytr_callback</code>
                </div>
            </form>
        </div>

        <div class="card" style="border-color: rgba(59, 130, 246, 0.3);">
            <div class="card-header">
                <i class="fas fa-credit-card" style="color: #3B82F6;"></i> Iyzico POS Ayarları
            </div>
            <form method="POST">
                
                <div class="form-group">
                    <label class="form-label">API Key</label>
                    <input type="text" name="iyzico_api_key" class="form-control" value="<?php echo htmlspecialchars($iy_api_key); ?>" placeholder="Iyzico panelindeki API Key inizi girin" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Secret Key</label>
                    <input type="text" name="iyzico_secret_key" class="form-control" value="<?php echo htmlspecialchars($iy_secret_key); ?>" placeholder="Iyzico panelindeki Secret Key inizi girin" required>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" name="save_iyzico" class="btn btn-primary" style="width: auto; background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);">
                        <i class="fas fa-save"></i> Iyzico Ayarlarını Kaydet
                    </button>
                </div>

                <div style="margin-top: 25px; padding: 15px; background: rgba(59, 130, 246, 0.1); border: 1px dashed rgba(59, 130, 246, 0.3); border-radius: 12px; font-size: 0.85rem; color: var(--text-muted);">
                    <strong style="color: #3B82F6;">Not:</strong> Iyzico ödeme sayfası başarılı/başarısız olduğunda sistem otomatik olarak arkaplandaki Bildirim URL'sini form çağrısıyla dinler.<br>
                    Bu yüzden Iyzico'da PayTR gibi ek olarak Callback girmeye gerek yoktur.
                </div>
            </form>
        </div>

        <div class="card" style="border-color: rgba(16, 185, 129, 0.3);">
            <div class="card-header">
                <i class="fas fa-university" style="color: #10B981;"></i> Banka Havale / EFT Ayarları
            </div>
            <form method="POST">
                
                <div class="form-group">
                    <label class="form-label">Banka Adı</label>
                    <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($bnk_name); ?>" placeholder="Örn: Garanti BBVA" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Alıcı Adı Soyadı</label>
                    <input type="text" name="bank_recipient" class="form-control" value="<?php echo htmlspecialchars($bnk_recp); ?>" placeholder="Örn: Ahmet Yılmaz" required>
                </div>

                <div class="form-group">
                    <label class="form-label">IBAN Numarası</label>
                    <input type="text" name="bank_iban" class="form-control" value="<?php echo htmlspecialchars($bnk_iban); ?>" placeholder="Örn: TR00 0000 0000 0000 0000 0000 00" required>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" name="save_bank" class="btn btn-primary" style="width: auto; background: linear-gradient(135deg, #10B981 0%, #059669 100%);">
                        <i class="fas fa-save"></i> Banka Bilgilerini Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Icon Picker Modal -->
    <div id="iconPickerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; justify-content:center; align-items:center; background:rgba(0,0,0,0.7); backdrop-filter:blur(5px);">
        <div style="background:var(--bg-card); border:var(--glass-border); padding:25px; border-radius:30px; width:90%; max-width:430px; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="color:white; font-family:'Outfit'; font-size:1.4rem;">İkon Seçin</h3>
                <button type="button" onclick="closeIconModal()" style="background:rgba(255,255,255,0.1); border:none; color:white; border-radius:50%; width:35px; height:35px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1rem; transition:0.3s;" onmouseover="this.style.background='rgba(239,68,68,0.5)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'"><i class="fas fa-times"></i></button>
            </div>
            <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:12px; max-height:280px; overflow-y:auto; padding-right:5px; margin-bottom:5px;" class="custom-scrollbar">
                <style>
                    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
                    .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); border-radius: 10px; }
                    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
                    .icon-btn:hover { background:var(--primary); transform:translateY(-2px); box-shadow:0 5px 15px rgba(139, 92, 246, 0.4); border-color:transparent; }
                </style>
                <script>
                    const iconsList = ['fas fa-bullhorn', 'fas fa-star', 'fas fa-bolt', 'fas fa-info-circle', 'fab fa-telegram', 'fab fa-whatsapp', 'fab fa-instagram', 'fab fa-tiktok', 'fab fa-twitter', 'fab fa-youtube', 'fas fa-gift', 'fas fa-fire', 'fas fa-bell', 'fas fa-gem', 'fas fa-heart', 'fas fa-crown', 'fas fa-rocket', 'fas fa-check-circle', 'fas fa-exclamation-triangle', 'fas fa-thumbs-up', 'fas fa-laugh-wink', 'fas fa-money-bill-wave', 'fas fa-chart-line', 'fas fa-lock', 'fas fa-truck', 'fas fa-shield-alt', 'fas fa-users', 'fas fa-comment-dots', 'fas fa-hashtag', 'fas fa-video'];
                    document.write('<button type="button" class="icon-btn" onclick="selectIcon(\'\')" style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:16px; padding:18px 0; color:#ef4444; font-size:1.2rem; cursor:pointer; transition:0.3s;" title="Hiçbiri / İkonu Kaldır"><i class="fas fa-ban"></i></button>');
                    iconsList.forEach(icon => {
                        document.write('<button type="button" class="icon-btn" onclick="selectIcon(\''+icon+'\')" style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:16px; padding:18px 0; color:white; font-size:1.3rem; cursor:pointer; transition:0.3s;"><i class="'+icon+'"></i></button>');
                    });
                </script>
            </div>
        </div>
    </div>

    <script>
    let currentTargetIconBtn = null;

    function openIconModal(btn) {
        currentTargetIconBtn = btn;
        document.getElementById('iconPickerModal').style.display = 'flex';
    }

    function closeIconModal() {
        document.getElementById('iconPickerModal').style.display = 'none';
        currentTargetIconBtn = null;
    }

    function selectIcon(iconClass) {
        if (!currentTargetIconBtn) return;
        
        // Find hidden input which is right after the btn
        const hiddenInput = currentTargetIconBtn.nextElementSibling;
        hiddenInput.value = iconClass;
        
        const previewSpan = currentTargetIconBtn.querySelector('.selected-icon-preview');
        
        if (iconClass === '') {
            previewSpan.innerHTML = 'İkon Seç (İsteğe Bağlı)';
        } else {
            previewSpan.innerHTML = '<i class="' + iconClass + '" style="margin-right:8px; color:var(--primary);"></i> ' + iconClass;
        }
        
        closeIconModal();
    }
    </script>
</body>
</html>
