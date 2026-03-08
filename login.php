<?php
require_once 'config.php';

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Güvenlik hatası! Sayfayı yenileyip tekrar deneyin.';
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);
        
        if (empty($username) || empty($password)) {
            $error = 'Lütfen kullanıcı adı ve şifrenizi girin.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $user_role = $user['user_role'] ?? 'user';
                $is_admin = ($user_role == 'admin' || $user_role == 'super_admin');
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user_role;
                $_SESSION['api_key'] = $user['api_key'];
                $_SESSION['is_admin'] = $is_admin;
                $_SESSION['balance'] = $user['balance'];
                
                if (!isset($user['user_role'])) {
                    $stmt = $pdo->prepare("UPDATE users SET user_role = 'user' WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }
                
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + (30 * 24 * 60 * 60);
                    setcookie('remember_token', $token, $expires, '/');
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                }
                
                $success = 'Giriş başarılı! Yönlendiriliyorsunuz...';
                echo '<meta http-equiv="refresh" content="2;url=dashboard.php">';
                
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı!';
            }
        }
    }
}

if (isset($_COOKIE['remember_token']) && !isset($_SESSION['user_id'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND status = 'active'");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['user_role'] ?? 'user';
        $_SESSION['api_key'] = $user['api_key'];
        $_SESSION['balance'] = $user['balance'];
        
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - <?php echo SITE_LOGO_TEXT; ?> SMM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'home_styles.php'; ?>
</head>
<body>

    

    <a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Ana Sayfa</a>

    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-area">
                <a href="index.php" class="logo">
                    <?php if(!empty(SITE_LOGO_IMAGE)): ?><img src="<?php echo htmlspecialchars(SITE_LOGO_IMAGE); ?>" alt="Logo" style="height: 32px; vertical-align: middle;"><?php else: ?><i class="fas fa-bolt"></i> <?php echo htmlspecialchars(SITE_LOGO_TEXT); ?><?php endif; ?>
                </a>
                <p class="auth-desc">Hesabınıza giriş yaparak devam edin</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="form-control" placeholder="Kullanıcı adınız" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Şifre</label>
                    <div class="input-wrapper">
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="form-options">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="remember">
                        <span class="checkmark"></span>
                        Beni Hatırla
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Şifremi Unuttum?</a>
                </div>

                <button type="submit" class="btn-submit">
                    Giriş Yap <i class="fas fa-arrow-right" style="margin-left: 5px;"></i>
                </button>

                <div class="divider"><span>veya</span></div>

                <div class="social-login">
                    <a href="https://t.me/PrimalTriad" target="_blank" class="social-btn">
                        <i class="fab fa-telegram"></i> Telegram
                    </a>
                    <a href="https://wa.me/+212721490727" target="_blank" class="social-btn">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>

                <div class="auth-footer">
                    Hesabınız yok mu? <a href="register.php">Hemen Kayıt Ol</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>