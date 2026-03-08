<?php
require_once 'config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda - Darq SMM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'home_styles.php'; ?>
</head>
<body>

    

    <?php include 'home_navbar.php'; ?>

    <section class="about-hero">
        <div class="container">
            <h1 class="hero-title">Biz Kimiz? <br> <span class="text-gradient">Darq SMM Hikayesi</span></h1>
            <p class="hero-desc">
                2025 yılında kurulan Darq SMM, dijital dünyada markaların ve bireylerin sesini duyurmasına yardımcı olan, teknoloji odaklı bir büyüme platformudur.
            </p>
        </div>
    </section>

    <section class="content-section">
        <div class="container">
            <div class="about-grid">
                <div class="about-text">
                    <h2>Vizyonumuz ve Misyonumuz</h2>
                    <p>
                        Darq SMM olarak amacımız, karmaşık dijital pazarlama süreçlerini herkes için erişilebilir, hızlı ve uygun maliyetli hale getirmektir. Sosyal medya platformlarında organik ve güvenilir büyümeyi destekleyen algoritmalarımızla sektörde fark yaratıyoruz.
                    </p>
                    <p>
                        Müşterilerimize sadece bir hizmet değil, aynı zamanda bir büyüme ortağı olmayı taahhüt ediyoruz. Şeffaflık, güvenlik ve hız, iş modelimizin temel taşlarını oluşturur.
                    </p>
                    <div style="margin-top: 30px;">
                        <a href="register.php" class="btn btn-primary">Aramıza Katıl</a>
                    </div>
                </div>
                <div class="about-card">
                    <h3 style="margin-bottom: 20px; font-family: 'Outfit';">Neden Biz?</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                            <i class="fas fa-check-circle" style="color: var(--secondary);"></i> 
                            <span>%100 Müşteri Memnuniyeti Odaklı</span>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                            <i class="fas fa-check-circle" style="color: var(--secondary);"></i> 
                            <span>Tam Otomatik ve Anlık İşlemler</span>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                            <i class="fas fa-check-circle" style="color: var(--secondary);"></i> 
                            <span>7/24 Kesintisiz Canlı Destek</span>
                        </li>
                        <li style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center;">
                            <i class="fas fa-check-circle" style="color: var(--secondary);"></i> 
                            <span>PayTR ile Güvenli Ödeme Altyapısı</span>
                        </li>
                        <li style="display: flex; gap: 10px; align-items: center;">
                            <i class="fas fa-check-circle" style="color: var(--secondary);"></i> 
                            <span>Rekabetçi ve Uygun Fiyatlar</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="values-grid">
                <div class="value-item">
                    <div class="value-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Hız</h3>
                    <p>Zamanın değerini biliyoruz. Siparişleriniz sistemimiz tarafından saniyeler içinde işleme alınır.</p>
                </div>
                <div class="value-item">
                    <div class="value-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Güven</h3>
                    <p>Verileriniz 256-bit SSL ile korunur. Asla şifre talep etmeyiz ve gizliliğinize saygı duyarız.</p>
                </div>
                <div class="value-item">
                    <div class="value-icon"><i class="fas fa-headset"></i></div>
                    <h3>Destek</h3>
                    <p>Uzman ekibimiz, her türlü sorunuz ve ihtiyacınız için günün her saati yanınızdadır.</p>
                </div>
            </div>

            <div class="stats-bar">
                <div class="stat-box">
                    <span class="stat-num">400+</span>
                    <span class="stat-lbl">Mutlu Müşteri</span>
                </div>
                <div class="stat-box">
                    <span class="stat-num">2400+</span>
                    <span class="stat-lbl">Sipariş</span>
                </div>
                <div class="stat-box">
                    <span class="stat-num">1200+</span>
                    <span class="stat-lbl">Servis</span>
                </div>
                <div class="stat-box">
                    <span class="stat-num">50+</span>
                    <span class="stat-lbl">Platform</span>
                </div>
            </div>
        </div>
    </section>

    <?php include 'home_footer.php'; ?>

    
</body>
</html>