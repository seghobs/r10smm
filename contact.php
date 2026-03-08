<?php
require_once 'config.php';
error_reporting(0);
ini_set('display_errors', 0);

session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Geçersiz işlem. Lütfen sayfayı yenileyip tekrar deneyin.';
    } else {
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $email = htmlspecialchars(trim($_POST['email'] ?? ''));
        $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
        $message = htmlspecialchars(trim($_POST['message'] ?? ''));
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'Lütfen tüm alanları doldurun.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Lütfen geçerli bir e-posta adresi girin.';
        } else {
            $success = 'Mesajınız başarıyla gönderildi! En kısa sürede size dönüş yapacağız.';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $csrf_token = $_SESSION['csrf_token'];
            $_POST = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - <?php echo SITE_LOGO_TEXT; ?> SMM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'home_styles.php'; ?>
</head>
<body>

    

    <?php include 'home_navbar.php'; ?>

    <section class="contact-hero">
        <div class="container">
            <h1 class="hero-title">İletişime Geçin <br> <span class="text-gradient">Yardıma Hazırız</span></h1>
            <p class="hero-desc">
                Sorularınız, önerileriniz veya destek talepleriniz için bize ulaşın. Ekibimiz en kısa sürede size dönüş yapacaktır.
            </p>
        </div>
    </section>

    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-headset"></i></div>
                    <div class="contact-details">
                        <h3>Canlı Destek</h3>
                        <p>Panel üzerinden veya Telegram'dan 7/24 anlık destek alabilirsiniz.</p>
                        <a href="https://t.me/darq_support" target="_blank" class="contact-link">Sohbeti Başlat &rarr;</a>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon"><i class="fab fa-whatsapp"></i></div>
                    <div class="contact-details">
                        <h3>WhatsApp Hattı</h3>
                        <p>Acil durumlar ve hızlı iletişim için WhatsApp hattımızı kullanabilirsiniz.</p>
                        <a href="https://wa.me/+212721490727" target="_blank" class="contact-link">+212 721 490 727 &rarr;</a>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                    <div class="contact-details">
                        <h3>E-posta Desteği</h3>
                        <p>Detaylı sorularınız ve işbirlikleri için bize mail atabilirsiniz.</p>
                        <a href="mailto:p4ssword35@gmail.com" class="contact-link">p4ssword35@gmail.com &rarr;</a>
                    </div>
                </div>

                <div class="contact-card">
                    <div class="contact-icon"><i class="fab fa-telegram-plane"></i></div>
                    <div class="contact-details">
                        <h3>Telegram Kanalı</h3>
                        <p>Güncel duyurular, kampanyalar ve hizmet güncellemeleri için katılın.</p>
                        <a href="https://t.me/PrimalTriad" target="_blank" class="contact-link">Kanala Katıl &rarr;</a>
                    </div>
                </div>
            </div>

            <div class="form-container">
                <div class="form-header">
                    <h2>Bize Yazın</h2>
                    <p>Aşağıdaki formu doldurarak bize mesaj gönderebilirsiniz. En geç 24 saat içinde yanıtlayacağız.</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ad Soyad</label>
                            <input type="text" name="name" class="form-control" placeholder="Adınız" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>E-posta Adresi</label>
                            <input type="email" name="email" class="form-control" placeholder="Mail adresiniz" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Konu</label>
                        <input type="text" name="subject" class="form-control" placeholder="Mesajınızın konusu" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Mesajınız</label>
                        <textarea name="message" class="form-control" placeholder="Size nasıl yardımcı olabiliriz?" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">Mesajı Gönder <i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'home_footer.php'; ?>

    
</body>
</html>