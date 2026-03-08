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

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['update_profile'])) {
        $full_name = htmlspecialchars(trim($_POST['full_name']));
        $email = htmlspecialchars(trim($_POST['email']));
        $phone = htmlspecialchars(trim($_POST['phone']));

        if (empty($full_name) || empty($email)) {
            $error_msg = "İsim ve E-posta boş bırakılamaz.";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user['id']]);
            if ($stmt->rowCount() > 0) {
                $error_msg = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                if ($stmt->execute([$full_name, $email, $phone, $user['id']])) {
                    $success_msg = "Profil bilgileriniz başarıyla güncellendi.";
                    $user['full_name'] = $full_name;
                    $user['email'] = $email;
                    $user['phone'] = $phone;
                } else {
                    $error_msg = "Güncelleme sırasında bir hata oluştu.";
                }
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (!password_verify($current_pass, $user['password'])) {
            $error_msg = "Mevcut şifreniz hatalı.";
        } elseif (strlen($new_pass) < 6) {
            $error_msg = "Yeni şifre en az 6 karakter olmalıdır.";
        } elseif ($new_pass !== $confirm_pass) {
            $error_msg = "Yeni şifreler birbiriyle uyuşmuyor.";
        } else {
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user['id']])) {
                $success_msg = "Şifreniz başarıyla değiştirildi.";
            }
        }
    }
}

$gravatar_hash = md5(strtolower(trim($user['email'])));
$avatar_url = "https://www.gravatar.com/avatar/$gravatar_hash?s=200&d=mp";

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - <?php echo SITE_LOGO_TEXT; ?> SMM Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); background: var(--bg-body); line-height: 1.6; overflow-x: hidden; }

        .background-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; pointer-events: none; }
        .glow-blob { position: absolute; filter: blur(90px); opacity: 0.3; border-radius: 50%; animation: float 15s infinite alternate ease-in-out; }
        .blob-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: var(--primary); }
        .blob-2 { bottom: 10%; right: -10%; width: 500px; height: 500px; background: #059669; animation-delay: -5s; }
        @keyframes float { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(40px, 40px) scale(1.05); } }

        .navbar { padding: 20px 0; position: fixed; width: 100%; top: 0; z-index: 1000; background: rgba(2, 6, 23, 0.9); backdrop-filter: blur(15px); border-bottom: var(--glass-border); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav-inner { display: flex; justify-content: space-between; align-items: center; }
        
        .logo { display: flex; align-items: center; gap: 12px; font-family: 'Outfit', sans-serif; font-size: 1.6rem; font-weight: 800; text-decoration: none; color: white; letter-spacing: -0.5px; }
        .logo i { color: var(--primary); font-size: 1.8rem; filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.5)); }

        .nav-menu { display: flex; gap: 20px; align-items: center; }
        .nav-menu a { text-decoration: none; color: var(--text-muted); font-weight: 500; transition: 0.3s; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 12px; }
        .nav-menu a:hover, .nav-menu a.active { color: white; background: rgba(255,255,255,0.05); }
        .nav-menu a.active { background: rgba(139, 92, 246, 0.1); color: var(--primary); }

        .main-content { padding: 120px 0 40px; }
        
        .page-header { margin-bottom: 30px; }
        .page-title { font-family: 'Outfit', sans-serif; font-size: 2rem; font-weight: 700; color: white; }
        .page-desc { color: var(--text-muted); }

        .settings-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        
        .card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 24px; padding: 30px; margin-bottom: 30px; }
        .card-header { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: white; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 10px; }
        
        .profile-summary { text-align: center; }
        .avatar-box { width: 120px; height: 120px; border-radius: 50%; border: 3px solid var(--primary); padding: 5px; margin: 0 auto 15px; position: relative; }
        .avatar-box img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .user-role-badge { background: rgba(139, 92, 246, 0.1); color: var(--primary); padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; display: inline-block; margin-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-family: 'Plus Jakarta Sans', sans-serif; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); background: rgba(255,255,255,0.05); }
        .form-control[readonly] { opacity: 0.7; cursor: not-allowed; }

        .btn { padding: 12px 20px; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; cursor: pointer; font-size: 0.95rem; }
        .btn-primary { background: var(--gradient-main); color: white; box-shadow: var(--glow); width: 100%; }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(139, 92, 246, 0.5); }
        
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.2); }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.2); }

        .menu-toggle { display: none; font-size: 1.5rem; color: white; cursor: pointer; background: none; border: none; }

        @media (max-width: 992px) {
            .settings-grid { grid-template-columns: 1fr; }
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

    <div class="main-content container">
        <div class="page-header">
            <h1 class="page-title">Hesap Ayarları</h1>
            <p class="page-desc">Profil bilgilerinizi ve güvenlik ayarlarınızı buradan yönetebilirsiniz.</p>
        </div>

        <?php if($success_msg): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <div class="left-col">
                <div class="card profile-summary">
                    <div class="avatar-box">
                        <img src="<?php echo $avatar_url; ?>" alt="Avatar">
                    </div>
                    <h2 style="font-size: 1.4rem; color: white; margin-bottom: 5px;"><?php echo htmlspecialchars($user['username']); ?></h2>
                    <span class="user-role-badge"><?php echo strtoupper($user['user_role']); ?></span>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Üyelik Tarihi: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?></p>
                    
                    <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span style="color: var(--text-muted);">Bakiye:</span>
                            <span style="color: #10B981; font-weight: 700;">₺<?php echo number_format($user['balance'], 2); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Referans:</span>
                            <span style="color: white; font-family: monospace;"><?php echo $user['referral_code']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="fas fa-shield-alt"></i> API Erişimi</div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">API anahtarınızı üçüncü taraf yazılımlarla panelimizi kullanmak için kullanabilirsiniz.</p>
                    
                    <div class="form-group">
                        <label class="form-label">Mevcut API Anahtarı</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="apiField" class="form-control" value="<?php echo $user['api_key']; ?>" readonly style="font-family: monospace; color: var(--primary);">
                            <button type="button" onclick="copyApi()" class="btn" style="background:rgba(255,255,255,0.1); width:auto;"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="card">
                    <div class="card-header"><i class="fas fa-user-edit"></i> Profil Bilgileri</div>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Kullanıcı Adı (Değiştirilemez)</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">Ad Soyad</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Telefon</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">E-posta Adresi</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Değişiklikleri Kaydet
                        </button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header"><i class="fas fa-lock"></i> Şifre Değiştir</div>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Mevcut Şifre</label>
                            <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">Yeni Şifre</label>
                                <input type="password" name="new_password" class="form-control" placeholder="En az 6 karakter" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Yeni Şifre (Tekrar)</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" name="change_password" class="btn btn-primary" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); box-shadow: 0 0 20px rgba(245, 158, 11, 0.3);">
                            <i class="fas fa-key"></i> Şifreyi Güncelle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        

        function copyApi() {
            var copyText = document.getElementById("apiField");
            copyText.select();
            copyText.setSelectionRange(0, 99999); 
            navigator.clipboard.writeText(copyText.value);
            alert("API Anahtarı kopyalandı!");
        }
    </script>
</body>
</html>