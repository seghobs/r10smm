<?php
require_once 'config.php';

$error = '';
$success = false;
$new_api_key = '';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Güvenlik hatası! Lütfen sayfayı yenileyin.';
    } else {
        $full_name = sanitize(trim($_POST['full_name'] ?? ''));
        $username = sanitize(trim($_POST['username'] ?? ''));
        $email = sanitize(trim($_POST['email'] ?? ''));
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $country = sanitize($_POST['country'] ?? 'TR');
        
        $agree_terms = isset($_POST['agree_terms']);
        $agree_privacy = isset($_POST['agree_privacy']);
        $agree_age = isset($_POST['agree_age']);

        if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password)) {
            $error = 'Lütfen tüm zorunlu alanları doldurun.';
        }
        elseif (!$agree_terms || !$agree_privacy || !$agree_age) {
            $error = 'Kayıt olabilmek için yasal şartları ve yaş sınırını kabul etmelisiniz.';
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Lütfen geçerli bir e-posta adresi girin.';
        }
        elseif (!preg_match('/^5[0-9]{9}$/', $phone)) {
            $error = 'Telefon numarası 5 ile başlamalı ve toplam 10 haneli olmalıdır (Örn: 5444444444).';
        }
        elseif (strlen($password) < 6) {
            $error = 'Şifre en az 6 karakter olmalıdır.';
        }
        elseif ($password !== $confirm_password) {
            $error = 'Şifreler eşleşmiyor.';
        }
        else {
            $stmt = $pdo->prepare("SELECT username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ?");
            $stmt->execute([$username, $email, $phone]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_user) {
                if ($existing_user['email'] == $email) {
                    $error = 'Bu e-posta adresi zaten kullanımda.';
                } elseif ($existing_user['username'] == $username) {
                    $error = 'Bu kullanıcı adı başkası tarafından alınmış.';
                } elseif ($existing_user['phone'] == $phone) {
                    $error = 'Bu telefon numarası zaten sisteme kayıtlı.';
                } else {
                    $error = 'Bu bilgilerle daha önce kayıt olunmuş.';
                }
            } else {
                try {
                    $new_api_key = bin2hex(random_bytes(16));
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $user_own_ref_code = SITE_LOGO_TEXT . strtoupper(substr(md5($username . time()), 0, 6));
                    
                    $balance = 0;

                    $kvkk_consent = json_encode([
                        'terms' => true,
                        'privacy' => true,
                        'age' => true,
                        'ip' => $_SERVER['REMOTE_ADDR'],
                        'date' => date('Y-m-d H:i:s')
                    ]);

                    $sql = "INSERT INTO users (username, email, password, full_name, phone, country, api_key, referral_code, referred_by, balance, kvkk_consent, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'active')";
                    $stmt = $pdo->prepare($sql);
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $country, $new_api_key, $user_own_ref_code, $balance, $kvkk_consent])) {
                        $success = true;
                    } else {
                        $error = 'Kayıt sırasında bir veritabanı hatası oluştu.';
                    }
                } catch (Exception $e) {
                    $error = 'Sistem hatası: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - <?php echo SITE_LOGO_TEXT; ?> SMM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'home_styles.php'; ?>
</head>
<body>

    

    <?php if ($success): ?>
    <div class="popup-overlay">
        <div class="popup-card">
            <div class="popup-icon"><i class="fas fa-check"></i></div>
            <h2 class="popup-title">Kayıt Başarılı!</h2>
            <p class="popup-text">Aramıza hoş geldin! Hesabın başarıyla oluşturuldu.</p>
            <div class="api-key-box">
                API Key: <?php echo $new_api_key; ?>
            </div>
            <p class="countdown">Login sayfasına yönlendiriliyorsunuz... <span id="timer">3</span></p>
        </div>
    </div>
    <script>
        let timeLeft = 3;
        const timer = document.getElementById('timer');
        setInterval(() => {
            timeLeft--;
            timer.innerText = timeLeft;
            if(timeLeft <= 0) window.location.href = 'login.php';
        }, 1000);
    </script>
    <?php endif; ?>

    <a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Ana Sayfa</a>

    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-area">
                <a href="index.php" class="logo">
                    <?php if(!empty(SITE_LOGO_IMAGE)): ?><img src="<?php echo htmlspecialchars(SITE_LOGO_IMAGE); ?>" alt="Logo" style="height: 32px; vertical-align: middle;"><?php else: ?><i class="fas fa-bolt"></i> <?php echo htmlspecialchars(SITE_LOGO_TEXT); ?><?php endif; ?>
                </a>
                <p class="auth-desc">Hemen ücretsiz hesabını oluştur</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Ad Soyad</label>
                        <div class="input-wrapper">
                            <input type="text" name="full_name" class="form-control" placeholder="Adınız" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                            <i class="fas fa-id-card"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Kullanıcı Adı</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" class="form-control" placeholder="Kullanıcı adı" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>E-posta Adresi</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-control" placeholder="ornek@mail.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Telefon Numarası</label>
                    <div class="input-wrapper">
                        <input type="tel" name="phone" class="form-control" placeholder="5XXXXXXXXX" required 
                               maxlength="10" pattern="5[0-9]{9}" title="5 ile başlayan 10 haneli numara giriniz"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        <i class="fas fa-phone"></i>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Şifre</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required minlength="6">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Şifre Tekrar</label>
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>
                </div>

                <div class="legal-box">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="agree_age" id="agree_age" required>
                        <span class="checkmark"></span>
                        18 yaşından büyük olduğumu beyan ederim.
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" name="agree_terms" id="agree_terms" required>
                        <span class="checkmark"></span>
                        <span><a href="tos.php" target="_blank">Kullanım Şartları</a>'nı okudum ve kabul ediyorum.</span>
                    </label>
                    <label class="custom-checkbox">
                        <input type="checkbox" name="agree_privacy" id="agree_privacy" required>
                        <span class="checkmark"></span>
                        <span><a href="privacy.php" target="_blank">Gizlilik Politikası</a>'nı okudum ve kabul ediyorum.</span>
                    </label>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn" disabled>
                    Kayıt Ol <i class="fas fa-user-plus" style="margin-left: 5px;"></i>
                </button>

                <div class="auth-footer">
                    Zaten hesabın var mı? <a href="login.php">Giriş Yap</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const submitBtn = document.getElementById('submitBtn');

        function checkConsents() {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            if (allChecked) {
                submitBtn.removeAttribute('disabled');
                submitBtn.style.opacity = "1";
            } else {
                submitBtn.setAttribute('disabled', 'true');
                submitBtn.style.opacity = "0.6";
            }
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', checkConsents);
        });
    </script>
</body>
</html>