<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col">
                <a href="index.php" class="logo" style="margin-bottom: 20px; display: block;">
                    <?php if(!empty(SITE_LOGO_IMAGE)): ?><img src="<?php echo htmlspecialchars(SITE_LOGO_IMAGE); ?>" alt="Logo" style="height: 32px; vertical-align: middle;"><?php else: ?><i class="fas fa-bolt"></i> <?php echo htmlspecialchars(SITE_LOGO_TEXT); ?><?php endif; ?>
                </a>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">
                    Sosyal medya hesaplarınızı büyütmenin en profesyonel, hızlı ve güvenilir adresi. <br>2026 © Tüm hakları saklıdır.
                </p>
                <div class="social-icons" style="margin-top: 25px;">
                    <a href="https://t.me/PrimalTriad" target="_blank"><i class="fab fa-telegram"></i></a>
                    <a href="https://www.instagram.com/PrimalTriad" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/+212721490727" target="_blank"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <div class="footer-col">
                <h4>Hızlı Erişim</h4>
                <div class="footer-links">
                    <a href="index.php">Ana Sayfa</a>
                    <a href="services.php">Hizmetler</a>
                    <a href="about.php">Hakkımızda</a>
                    <a href="contact.php">İletişim</a>
                </div>
            </div>
            
            <div class="footer-col">
                <h4>Destek & Yasal</h4>
                <div class="footer-links">
                    <a href="faq.php">Sıkça Sorulan Sorular</a>
                    <a href="tos.php">Kullanım Şartları</a>
                    <a href="privacy.php">Gizlilik Politikası</a>
                    <a href="refund.php">İade Politikası</a>
                </div>
            </div>
            
            <div class="footer-col">
                <h4>İletişim</h4>
                <div class="footer-links">
                    <a href="mailto:info@darqsmm.com">info@darqsmm.com</a>
                    <a href="https://t.me/darq_support">Telegram Destek</a>
                    <span style="color: var(--text-muted); font-size: 0.9rem; margin-top: 10px; display: block;">
                        Vergi No: 288******34
                    </span>
                </div>
            </div>
        </div>
        <div style="text-align: center; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.05); color: var(--text-muted); font-size: 0.85rem;">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_LOGO_TEXT; ?>. Tüm hakları saklıdır.
        </div>
    </div>
</footer>
