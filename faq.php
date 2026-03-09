<?php
require_once 'config.php';
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sıkça Sorulan Sorular - 𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php include 'home_styles.php'; ?>
</head>
<body>

    

    <?php include 'home_navbar.php'; ?>

    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Sıkça Sorulan <span class="text-gradient">Sorular</span></h1>
            <p class="hero-desc">
                𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM Panel hizmetleri, ödemeler ve süreçler hakkında merak ettiğiniz tüm soruların cevapları burada.
            </p>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="faqSearch" placeholder="Sorunuzu buraya yazın...">
            </div>

            <div class="faq-categories">
                <button class="cat-btn active" data-category="all">Tümü</button>
                <button class="cat-btn" data-category="general">Genel</button>
                <button class="cat-btn" data-category="payment">Ödeme & PayTR</button>
                <button class="cat-btn" data-category="orders">Siparişler</button>
                <button class="cat-btn" data-category="technical">Teknik</button>
            </div>
        </div>
    </section>

    <section class="faq-content">
        <div class="container">
            <div class="faq-grid">
                <div class="faq-item" data-category="general">
                    <div class="faq-question">
                        <span>𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM Panel nedir?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        𝐏𝐫𝐢𝐦𝐚𝐥𝐓𝐫𝐢𝐚𝐝 SMM Panel, Instagram, TikTok, YouTube ve diğer sosyal medya platformları için takipçi, beğeni, izlenme gibi etkileşim hizmetleri sağlayan tam otomatik bir bayilik panelidir.
                    </div>
                </div>

                <div class="faq-item" data-category="general">
                    <div class="faq-question">
                        <span>Hizmetleriniz güvenli mi?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Kesinlikle. Tüm işlemlerimiz 256-bit SSL sertifikası ile korunmaktadır. Ayrıca sosyal medya hesaplarınızın şifresini asla talep etmiyoruz.
                    </div>
                </div>

                <div class="faq-item" data-category="payment">
                    <div class="faq-question">
                        <span>PayTR ile ödeme güvenli mi?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Evet, PayTR Türkiye'nin en güvenilir ve BDDK lisanslı ödeme kuruluşlarından biridir. Kart bilgileriniz sunucularımızda saklanmaz, işlemler 3D Secure ile şifrelenerek bankanız üzerinden gerçekleşir.
                    </div>
                </div>

                <div class="faq-item" data-category="payment">
                    <div class="faq-question">
                        <span>Bakiye ne zaman hesabıma geçer?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        PayTR (Kredi Kartı) ile yapılan yüklemeler 7/24 anında ve otomatik olarak bakiyenize yansır. Havale/EFT işlemlerinde ise ödeme bildirimi sonrası kısa sürede onaylanır.
                    </div>
                </div>

                <div class="faq-item" data-category="orders">
                    <div class="faq-question">
                        <span>Siparişler ne zaman başlar?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Çoğu servisimiz "Anlık" olarak çalışır. Sipariş verdiğiniz anda sistem otomatik olarak işleme alır ve gönderim başlar. Yoğunluğa göre nadiren gecikmeler olabilir.
                    </div>
                </div>

                <div class="faq-item" data-category="orders">
                    <div class="faq-question">
                        <span>Telafi (Refill) garantisi nedir?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Garantili servislerimizde, satın alınan miktarda düşüş yaşanması durumunda, siparişler sayfasındaki "Telafi Et" butonunu kullanarak ücretsiz dolum talep edebilirsiniz.
                    </div>
                </div>

                <div class="faq-item" data-category="technical">
                    <div class="faq-question">
                        <span>API desteği var mı?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Evet, gelişmiş API desteğimiz mevcuttur. Kendi panelinizi kurabilir veya toplu işlemler için sistemimize entegre olabilirsiniz. API dokümantasyonuna menüden ulaşabilirsiniz.
                    </div>
                </div>
            </div>

            <div class="help-box">
                <h3>Aradığınız cevabı bulamadınız mı?</h3>
                <p>Destek ekibimiz size yardımcı olmak için 7/24 hazır.</p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="contact.php" class="btn btn-primary">İletişime Geç</a>
                    <a href="https://t.me/darq_support" target="_blank" class="btn btn-outline">Telegram Destek</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'home_footer.php'; ?>

    <script>
        // FAQ Katlanabilir (Accordion) Menü
        const faqItems = document.querySelectorAll('.faq-item');
        
        faqItems.forEach(item => {
            const question = item.querySelector('.faq-question');
            question.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                
                // Diğer açık olanları kapat (İsteğe bağlı - İstersen bu kısmı silebilirsin)
                faqItems.forEach(otherItem => {
                    otherItem.classList.remove('active');
                });
                
                // Tıklananı aç/kapat
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });

        // Kategori Filtresi
        const catBtns = document.querySelectorAll('.cat-btn');
        
        catBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Aktif sınıfı güncelle
                catBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const category = btn.getAttribute('data-category');
                
                faqItems.forEach(item => {
                    if (category === 'all' || item.getAttribute('data-category') === category) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                        item.classList.remove('active'); 
                    }
                });
                
                // Aramayı sıfırla
                document.getElementById('faqSearch').value = '';
            });
        });

        // Canlı Arama (Search)
        const searchInput = document.getElementById('faqSearch');
        
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            // Arama yaparken tüm katagorileri sıfırla, hepsinde ara.
            catBtns.forEach(b => b.classList.remove('active'));
            document.querySelector('.cat-btn[data-category="all"]').classList.add('active');

            faqItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                    item.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>