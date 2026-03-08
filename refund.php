<?php
require_once 'config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İade ve İptal Koşulları - 𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'home_styles.php'; ?>
</head>
<body>

    

    <?php include 'home_navbar.php'; ?>

    <section class="refund-hero">
        <div class="container">
            <h1 class="hero-title">İade ve İptal <span class="text-gradient">Koşulları</span></h1>
            <p class="hero-desc">
                𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM Panel olarak, PayTR güvencesiyle sunduğumuz hizmetlerde adil ve şeffaf bir iade politikası uyguluyoruz.
            </p>
        </div>
    </section>

    <section class="refund-content">
        <div class="container">
            <div class="section-header">
                <h2>İade ve İptal Politikası</h2>
                <p>Son Güncelleme: 1 Ocak 2026</p>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-info-circle"></i> Genel İade Politikası</h3>
                <p>𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM Panel olarak, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Satış Sözleşmeleri Yönetmeliği'ne uygun şekilde aşağıdaki iade ve iptal politikasını uygulamaktayız.</p>
                
                <div class="warning-box">
                    <h4><i class="fas fa-exclamation-triangle"></i> Önemli Not:</h4>
                    <p>Dijital hizmetler kapsamında, teslim edilen ve tamamlanan hizmetlerin iadesi genellikle mümkün değildir. Ancak teknik sorunlar veya teslim edilemeyen hizmetler için iade garantisi sunuyoruz.</p>
                </div>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-check-circle"></i> İade Edilebilir Durumlar</h3>
                <p>Aşağıdaki durumlarda %100 iade garantisi sunuyoruz:</p>
                <ul>
                    <li><strong>Teslimat Başarısızlığı:</strong> Siparişiniz 24 saat içinde işleme alınmaz veya teknik bir sorun nedeniyle tamamlanmazsa.</li>
                    <li><strong>Eksik Gönderim:</strong> Siparişiniz eksik tamamlanırsa, kalan miktar için kısmi iade yapılır.</li>
                    <li><strong>Hatalı Sipariş (Başlamadan Önce):</strong> Eğer siparişiniz henüz sistem tarafından işleme alınmadıysa (Beklemede durumunda) iptal edip bakiyenizi iade alabilirsiniz.</li>
                </ul>
            </div>

            <div class="policy-box" style="border-left-color: var(--primary);">
                <h3 style="color: white;"><i class="fas fa-times-circle" style="color: var(--primary); background: rgba(139,92,246,0.1);"></i> İade Edilemeyen Durumlar</h3>
                <p>Aşağıdaki durumlarda iade talebi kabul edilmez:</p>
                <ul>
                    <li>Hizmet başarıyla tamamlandıktan sonra yapılan "vazgeçtim" talepleri.</li>
                    <li>Yanlış link veya gizli profil nedeniyle oluşan hatalar (Kullanıcı sorumluluğundadır).</li>
                    <li>Aynı linke, işlem devam ederken başka bir panelden veya tekrar sipariş girilmesi durumunda oluşan çakışmalar.</li>
                    <li>Sosyal medya platformlarının (Instagram, TikTok vb.) yaptığı genel güncellemelerden kaynaklı geçici aksaklıklar.</li>
                </ul>
            </div>

            <div class="info-box">
                <h4><i class="fas fa-clock"></i> İade Süreçleri ve PayTR</h4>
                <p><strong>Bakiye İadesi:</strong> Onaylanan iadeler anında 𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM panel bakiyenize yansıtılır.</p>
                <p><strong>Kart İadesi:</strong> Kredi kartınıza iade talepleriniz PayTR üzerinden işleme alınır ve bankanıza bağlı olarak 3-7 iş günü içinde kartınıza yansır.</p>
            </div>

            <div class="policy-box">
                <h3><i class="fas fa-list-ol"></i> İade Talep Adımları</h3>
                <div class="process-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <h4>Talep Oluştur</h4>
                        <p>Destek sayfasından veya canlı destekten sipariş numaranızla birlikte iade talebi oluşturun.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <h4>İnceleme</h4>
                        <p>Teknik ekibimiz sipariş durumunu ve logları 24 saat içinde inceler.</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <h4>Sonuç</h4>
                        <p>İade onaylanırsa bakiyeniz hesabınıza, reddedilirse sebebi tarafınıza bildirilir.</p>
                    </div>
                </div>
            </div>

            <div class="contact-card">
                <h3>İade Talebi İçin İletişim</h3>
                <p>Bir sorun mu yaşıyorsunuz? Hemen bizimle iletişime geçin, çözelim.</p>
                <p><strong>E-posta:</strong> p4ssword35@gmail.com</p>
                <p><strong>WhatsApp:</strong> +212 721 490 727</p>
                <p><strong>Telegram:</strong> @darq_support</p>
                
                <div class="contact-buttons">
                    <a href="https://wa.me/+212721490727" target="_blank" class="btn btn-primary">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://t.me/darq_support" target="_blank" class="btn btn-outline">
                        <i class="fab fa-telegram"></i> Telegram
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'home_footer.php'; ?>

    
</body>
</html>