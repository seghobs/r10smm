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
</body>
</html>
