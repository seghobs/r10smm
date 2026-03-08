<?php
session_start();
require_once 'config.php';

$stats = [
    'users' => 0,
    'orders' => 0,
    'services' => 1910
];

if (isset($pdo)) {
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($check->rowCount() > 0) {
            $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        }
    } catch (Exception $e) {}

    try {
        $check = $pdo->query("SHOW TABLES LIKE 'orders'");
        if ($check->rowCount() > 0) {
            $stats['orders'] = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        }
    } catch (Exception $e) {}
}

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_LOGO_TEXT; ?> SMM - Dijital Büyüme Platformu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'home_styles.php'; ?>
</head>
<body>

    

    <?php include 'home_navbar.php'; ?>
    <section class="hero-section">
        <div class="container">
            <div class="reveal">
                <span class="hero-badge"><i class="fas fa-star"></i> 2026'nın En Gelişmiş SMM Paneli</span>
                <h1 class="hero-title">
                    Sosyal Medyada <br>
                    <span class="text-gradient">Limitleri Kaldırın</span>
                </h1>
                <p class="hero-desc">
                    Türkiye'nin en hızlı ve güvenilir SMM paneli ile etkileşimlerinizi zirveye taşıyın. 
                    Instagram, TikTok ve YouTube için anlık, garantili ve uygun fiyatlı çözümler.
                </p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <?php if($is_logged_in): ?>
                        <a href="services.php" class="btn btn-primary btn-lg"><i class="fas fa-rocket"></i> Hemen Sipariş Ver</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Ücretsiz Kayıt Ol</a>
                    <?php endif; ?>
                    <a href="services.php" class="btn btn-outline btn-lg"><i class="fas fa-list"></i> Fiyatları Gör</a>
                </div>
            </div>

            <div class="stats-bar reveal">
                <div class="stat-item">
                    <span class="stat-number" data-target="<?php echo $stats['users']; ?>">0</span>
                    <span class="stat-label">Mutlu Müşteri</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="<?php echo $stats['orders']; ?>">0</span>
                    <span class="stat-label">Tamamlanan İşlem</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number" data-target="<?php echo $stats['services']; ?>">0</span>
                    <span class="stat-label">Aktif Servis</span>
                </div>
                <div class="stat-item">
                    <span class="stat-text-static">7/24</span>
                    <span class="stat-label">Canlı Destek</span>
                </div>
            </div>
        </div>
    </section>

    <section class="features-section">
        <div class="container">
            <h2 class="section-title reveal">Neden <span class="text-gradient"><?php echo SITE_LOGO_TEXT; ?> SMM?</span></h2>
            <div class="features-grid">
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Yıldırım Hızı</h3>
                    <p>Tam otomatik sistemimiz sayesinde siparişleriniz saniyeler içinde işleme alınır ve gönderim başlar. Beklemek yok.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>%100 Güvenli</h3>
                    <p>Hesap şifrenizi asla talep etmiyoruz. Tüm işlemler 256-bit SSL sertifikası ve 3D Secure ile korunmaktadır.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <h3>Premium Destek</h3>
                    <p>Sorularınız mı var? Uzman destek ekibimize WhatsApp, Telegram veya panel üzerinden 7/24 ulaşabilirsiniz.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-code"></i></div>
                    <h3>Gelişmiş API</h3>
                    <p>Kendi panelinizi mi kuruyorsunuz? Güçlü ve stabil API altyapımız ile kolayca entegrasyon sağlayın.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-tags"></i></div>
                    <h3>Rekabetçi Fiyatlar</h3>
                    <p>Piyasadaki en uygun fiyatları sunuyoruz. Bayilerimize özel ek indirimler ve avantajlar sağlıyoruz.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-icon"><i class="fas fa-sync"></i></div>
                    <h3>Otomatik Telafi</h3>
                    <p>Garantili servislerimizde düşüş yaşanması durumunda tek tuşla otomatik telafi (refill) yapabilirsiniz.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="steps-section">
        <div class="container">
            <h2 class="section-title reveal">Nasıl Çalışır?</h2>
            <div class="steps-container reveal">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <h4>Ücretsiz Kayıt Ol</h4>
                    <p>Sadece 30 saniyede kayıt formunu doldurarak aramıza katılın.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <h4>Bakiye Yükle</h4>
                    <p>Kredi kartı, havale veya kripto ile güvenli şekilde bakiye yükleyin.</p>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <h4>Sipariş Ver</h4>
                    <p>İhtiyacınız olan servisi seçin, linki girin ve arkanıza yaslanın.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container">
            <div class="cta-box reveal">
                <h2>Büyümeye Hazır Mısınız?</h2>
                <p>Binlerce fenomen ve marka <?php echo SITE_LOGO_TEXT; ?> SMM Panel'i tercih ediyor. Siz de katılın, rakiplerinizin önüne geçin.</p>
                <a href="register.php" class="btn btn-primary btn-lg">Hemen Başla <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <?php include 'home_footer.php'; ?>

    
</body>
</html>