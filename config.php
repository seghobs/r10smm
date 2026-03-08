<?php
ob_start();
error_reporting(0);
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);
    session_start();
}
date_default_timezone_set('Europe/Istanbul');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smm');
define('SITE_NAME', 'r10 smm');
define('SITE_URL', 'http://localhost/smm?step=3');
define('EXCHANGE_RATE_USD_TRY', 32.50);
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $sys_settings = [];
    try {
        $stmt_settings = $pdo->query("SELECT setting_key, value FROM settings");
        while ($row = $stmt_settings->fetch()) { $sys_settings[$row['setting_key']] = $row['value']; }
    } catch (PDOException $e) {}
    define('PAYTR_MERCHANT_ID', $sys_settings['paytr_merchant_id'] ?? '');
    define('PAYTR_MERCHANT_KEY', $sys_settings['paytr_merchant_key'] ?? '');
    define('PAYTR_MERCHANT_SALT', $sys_settings['paytr_merchant_salt'] ?? '');
    define('IYZICO_API_KEY', $sys_settings['iyzico_api_key'] ?? '');
    define('IYZICO_SECRET_KEY', $sys_settings['iyzico_secret_key'] ?? '');
    define('BANK_NAME', $sys_settings['bank_name'] ?? 'Banka');
    define('BANK_RECIPIENT', $sys_settings['bank_recipient'] ?? 'Alıcı');
    define('BANK_IBAN', $sys_settings['bank_iban'] ?? 'TR...');
    define('SITE_LOGO_TEXT', $sys_settings['site_logo_text'] ?? 'r10 smm');
    define('SITE_LOGO_IMAGE', $sys_settings['site_logo_image'] ?? '');
} catch(PDOException $e) {
    if (file_exists('install.php')) { header("Location: install.php"); exit; }
    die("DB Error");
}
function sanitize($d) { return is_array($d) ? array_map('sanitize', $d) : htmlspecialchars(trim($d), ENT_QUOTES, 'UTF-8'); }
function generate_csrf_token() { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function validate_csrf_token($t) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t); }
function check_session() { if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; } }
function get_user_role($p, $u) { $s = $p->prepare("SELECT user_role FROM users WHERE id = ?"); $s->execute([$u]); $r = $s->fetch(); return $r['user_role'] ?? 'user'; }
function get_system_stats($p) { return ['users' => 0, 'orders' => 0, 'services' => 0]; }
?>