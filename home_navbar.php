<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);
?>
<nav class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="index.php" class="logo">
            <?php if(!empty(SITE_LOGO_IMAGE)): ?><img src="<?php echo htmlspecialchars(SITE_LOGO_IMAGE); ?>" alt="Logo" style="height: 32px; vertical-align: middle;"><?php else: ?><i class="fas fa-bolt"></i> <?php echo htmlspecialchars(SITE_LOGO_TEXT); ?><?php endif; ?>
        </a>
        
        <div class="nav-links" id="navLinks">
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Ana Sayfa</a>
            <a href="services.php" class="<?php echo ($current_page == 'services.php') ? 'active' : ''; ?>">Hizmetler</a>
            <a href="about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">Hakkımızda</a>
            <a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">İletişim</a>
            <a href="faq.php" class="<?php echo ($current_page == 'faq.php') ? 'active' : ''; ?>">SSS</a>
            <a href="tos.php" class="<?php echo ($current_page == 'tos.php') ? 'active' : ''; ?>">Kullanım Şartları</a>
            <a href="privacy.php" class="<?php echo ($current_page == 'privacy.php') ? 'active' : ''; ?>">Gizlilik Politikası</a>
            
            <div id="mobile-auth" style="display: none; flex-direction: column; gap: 15px; width: 100%; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px;">
                 <?php if($is_logged_in): ?>
                    <a href="dashboard.php" class="btn btn-primary" style="justify-content: center;">Dashboard</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline" style="justify-content: center;">Giriş Yap</a>
                    <a href="register.php" class="btn btn-primary" style="justify-content: center;">Kayıt Ol</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="nav-actions">
            <?php if($is_logged_in): ?>
                <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-columns"></i> Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline">Giriş Yap</a>
                <a href="register.php" class="btn btn-primary">Kayıt Ol</a>
            <?php endif; ?>
        </div>
        
        <div class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</nav>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const navLinks = document.getElementById('navLinks');
    const navbar = document.getElementById('navbar');
    const mobileAuth = document.getElementById('mobile-auth');

    if(menuToggle) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            if (window.innerWidth <= 992) {
                mobileAuth.style.display = navLinks.classList.contains('active') ? 'flex' : 'none';
            }
        });
    }

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Reveal Animation logic
    function reveal() {
        var reveals = document.querySelectorAll(".reveal");
        for (var i = 0; i < reveals.length; i++) {
            var windowHeight = window.innerHeight;
            var elementTop = reveals[i].getBoundingClientRect().top;
            var elementVisible = 150;
            if (elementTop < windowHeight - elementVisible) {
                reveals[i].classList.add("active");
            }
        }
    }
    window.addEventListener("scroll", reveal);
    window.addEventListener("load", reveal);
</script>
