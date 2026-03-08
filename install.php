<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kurulum Kilidi: Eğer config.php varsa ve DB_NAME tanımlıysa kurulum tamamlanmıştır.
if (file_exists('config.php')) {
    include 'config.php';
    if (defined('DB_NAME') && !empty(DB_NAME)) {
        // Veritabanı bağlantısını test et, eğer başarılıysa kuruluma izin verme
        try {
            $test_pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            // Bağlantı hatası varsa (db silinmişse vb.) kuruluma devam edebilir
        }
    }
}

$step = 1;
if (isset($_GET['step'])) $step = (int)$_GET['step'];
if (isset($_POST['step'])) $step = (int)$_POST['step'];

$error = '';
$success = '';

// Step 2 Submission (Database)
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_user'])) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = $_POST['db_name'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci");
        $pdo->exec("USE `$db_name` ");
        
        $_SESSION['install_db'] = [
            'host' => $db_host,
            'user' => $db_user,
            'pass' => $db_pass,
            'name' => $db_name
        ];
        
        if (!file_exists('database_base.sql')) {
            throw new Exception("database_base.sql dosyası bulunamadı!");
        }
        
        $sql = file_get_contents('database_base.sql');
        $sql = preg_replace('/--.*$/m', '', $sql);
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($queries as $query) {
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        
        header("Location: install.php?step=3");
        exit;
    } catch (Exception $e) {
        $error = "Veritabanı Hatası: " . $e->getMessage();
    }
}

// Step 3 Submission (Admin & Config)
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_user'])) {
    $db = $_SESSION['install_db'] ?? null;
    if (!$db) {
        $error = "Oturum zaman aşımına uğradı veya veritabanı bilgileri kayboldu. Lütfen 2. adıma dönün.";
        $step = 2;
    } else {
        $admin_user = $_POST['admin_user'];
        $admin_pass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
        $admin_email = $_POST['admin_email'];
        $site_name = $_POST['site_name'];
        $site_url = rtrim($_POST['site_url'], '/');

        try {
            $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("INSERT INTO users (username, password, email, user_role, status, email_verified) VALUES (?, ?, ?, 'admin', 'active', 1)");
            $stmt->execute([$admin_user, $admin_pass, $admin_email]);

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value) VALUES ('site_logo_text', ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$site_name, $site_name]);

            // Save config.php
            $config_tpl = "<?php
ob_start();
error_reporting(0);
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);
    session_start();
}
date_default_timezone_set('Europe/Istanbul');
define('DB_HOST', '{$db['host']}');
define('DB_USER', '{$db['user']}');
define('DB_PASS', '{$db['pass']}');
define('DB_NAME', '{$db['name']}');
define('SITE_NAME', '$site_name');
define('SITE_URL', '$site_url');
define('EXCHANGE_RATE_USD_TRY', 32.50);
try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\", DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    \$sys_settings = [];
    try {
        \$stmt_settings = \$pdo->query(\"SELECT setting_key, value FROM settings\");
        while (\$row = \$stmt_settings->fetch()) { \$sys_settings[\$row['setting_key']] = \$row['value']; }
    } catch (PDOException \$e) {}
    define('PAYTR_MERCHANT_ID', \$sys_settings['paytr_merchant_id'] ?? '');
    define('PAYTR_MERCHANT_KEY', \$sys_settings['paytr_merchant_key'] ?? '');
    define('PAYTR_MERCHANT_SALT', \$sys_settings['paytr_merchant_salt'] ?? '');
    define('IYZICO_API_KEY', \$sys_settings['iyzico_api_key'] ?? '');
    define('IYZICO_SECRET_KEY', \$sys_settings['iyzico_secret_key'] ?? '');
    define('BANK_NAME', \$sys_settings['bank_name'] ?? 'Banka');
    define('BANK_RECIPIENT', \$sys_settings['bank_recipient'] ?? 'Alıcı');
    define('BANK_IBAN', \$sys_settings['bank_iban'] ?? 'TR...');
    define('SITE_LOGO_TEXT', \$sys_settings['site_logo_text'] ?? '$site_name');
    define('SITE_LOGO_IMAGE', \$sys_settings['site_logo_image'] ?? '');
} catch(PDOException \$e) {
    if (file_exists('install.php')) { header(\"Location: install.php\"); exit; }
    die(\"DB Error\");
}
function sanitize(\$d) { return is_array(\$d) ? array_map('sanitize', \$d) : htmlspecialchars(trim(\$d), ENT_QUOTES, 'UTF-8'); }
function generate_csrf_token() { if (empty(\$_SESSION['csrf_token'])) \$_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return \$_SESSION['csrf_token']; }
function validate_csrf_token(\$t) { return isset(\$_SESSION['csrf_token']) && hash_equals(\$_SESSION['csrf_token'], \$t); }
function check_session() { if (!isset(\$_SESSION['user_id'])) { header('Location: login.php'); exit; } }
function get_user_role(\$p, \$u) { \$s = \$p->prepare(\"SELECT user_role FROM users WHERE id = ?\"); \$s->execute([\$u]); \$r = \$s->fetch(); return \$r['user_role'] ?? 'user'; }
function get_system_stats(\$p) { return ['users' => 0, 'orders' => 0, 'services' => 0]; }
?>";
            file_put_contents('config.php', $config_tpl);
            header("Location: install.php?step=4");
            exit;
        } catch (Exception $e) {
            $error = "Kurulum Hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Kurulum Sihirbazı</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;800&family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #8B5CF6; --primary-dark: #7C3AED; --bg: #020617; --card: #0f172a; --text: #f8fafc; --muted: #94a3b8; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--text); display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; }
        .card { width: 100%; max-width: 500px; background: var(--card); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; padding: 40px; }
        .btn { width: 100%; padding: 14px; border-radius: 12px; border:none; font-weight:700; cursor:pointer; transition:0.3s; background: var(--primary); color:white; display:flex; align-items:center; justify-content:center; gap:10px; text-decoration:none; }
        .btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .form-group { margin-bottom: 20px; }
        .form-control { width: 100%; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color:white; }
        .alert { padding:15px; border-radius:10px; margin-bottom:20px; background: rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); }
    </style>
</head>
<body>

<div class="card">
    <div style="text-align:center; margin-bottom:30px;">
        <h1 style="font-family:'Outfit'; font-size:2rem;"><i class="fas fa-bolt" style="color:var(--primary);"></i> SMM PANEL</h1>
        <p style="color:var(--muted);">Adım <?php echo $step; ?> / 4</p>
    </div>

    <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <div style="text-align:center;">
            <i class="fas fa-magic" style="font-size:3rem; color:var(--primary); margin-bottom:20px;"></i>
            <h2>Hoş Geldiniz</h2>
            <p style="color:var(--muted); margin-bottom:30px;">Kuruluma hazırsanız başlayalım.</p>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                <button type="submit" class="btn">Kuruluma Başla <i class="fas fa-arrow-right"></i></button>
            </form>
        </div>
    <?php elseif ($step === 2): ?>
        <form method="POST">
            <input type="hidden" name="step" value="2">
            <div class="form-group">
                <label>Veritabanı Sunucusu</label>
                <input type="text" name="db_host" class="form-control" value="localhost" required>
            </div>
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="db_user" class="form-control" value="root" required>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="db_pass" class="form-control">
            </div>
            <div class="form-group">
                <label>Veritabanı Adı</label>
                <input type="text" name="db_name" class="form-control" value="smm" required>
            </div>
            <button type="submit" class="btn">Bağlan ve Tabloları Oluştur</button>
        </form>
    <?php elseif ($step === 3): ?>
        <form method="POST">
            <input type="hidden" name="step" value="3">
            <div class="form-group">
                <label>Site Adı</label>
                <input type="text" name="site_name" class="form-control" value="SMM Panel" required>
            </div>
            <div class="form-group">
                <label>Site URL</label>
                <input type="text" name="site_url" class="form-control" value="<?php echo 'http://'.$_SERVER['HTTP_HOST'].str_replace('/install.php', '', $_SERVER['REQUEST_URI']); ?>" required>
            </div>
            <hr style="opacity:0.1; margin:20px 0;">
            <div class="form-group">
                <label>Admin Kullanıcı Adı</label>
                <input type="text" name="admin_user" class="form-control" value="admin" required>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="admin_pass" class="form-control" required>
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="admin_email" class="form-control" value="admin@site.com" required>
            </div>
            <button type="submit" class="btn">Kurulumu Bitir</button>
        </form>
    <?php elseif ($step === 4): ?>
        <div style="text-align:center;">
            <i class="fas fa-check-circle" style="font-size:4rem; color:#10b981; margin-bottom:20px;"></i>
            <h2>Kurulum Tamamlandı!</h2>
            <p style="color:var(--muted); margin-bottom:30px;">Config.php dosyası yazıldı ve her şey hazır.</p>
            <a href="login.php" class="btn">Giriş Yap</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
