<?php
if (!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF']);

// Fetch notifications if not already fetched in the parent script
if (!isset($unread_notif_count) || !isset($notifications)) {
    $notifications = [];
    $unread_notif_count = 0;
    if (isset($user['id'])) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['id']]);
            $unread_notif_count = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            $stmt->execute([$user['id']]);
            $notifications = $stmt->fetchAll();
        } catch (PDOException $e) {}
    }
}
?>
<style>
    .navbar { padding: 20px 0; position: fixed; width: 100%; top: 0; z-index: 1000; background: rgba(2, 6, 23, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.08); transition: 0.3s; }
    .navbar.scrolled { padding: 15px 0; background: rgba(2, 6, 23, 0.95); }
    .nav-container-inner { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
    
    .nav-logo { display: flex; align-items: center; gap: 8px; font-family: 'Outfit', sans-serif; font-size: 1.4rem; font-weight: 800; text-decoration: none; color: white; letter-spacing: -0.5px; white-space: nowrap; }
    .nav-logo i { color: #8B5CF6; font-size: 1.6rem; filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.5)); }

    .nav-menu { display: flex; gap: 10px; align-items: center; }
    .nav-menu a { text-decoration: none; color: #94A3B8; font-weight: 500; transition: 0.3s; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 12px; white-space: nowrap; }
    .nav-menu a:hover, .nav-menu a.active { color: white; background: rgba(255,255,255,0.05); }
    .nav-menu a.active { background: rgba(139, 92, 246, 0.1); color: #8B5CF6; }

    .user-menu { display: flex; align-items: center; gap: 10px; position: relative; white-space: nowrap; }
    .balance-badge { background: rgba(16, 185, 129, 0.1); color: #10B981; padding: 6px 12px; border-radius: 20px; font-weight: 600; display: flex; align-items: center; gap: 5px; font-size: 0.9rem; border: 1px solid rgba(16, 185, 129, 0.2); }
    
    .menu-toggle { display: none; font-size: 1.5rem; color: white; cursor: pointer; background: none; border: none; }

    .notif-wrapper { position: relative; margin-right: 10px; cursor: pointer; }
    .notif-bell { font-size: 1.2rem; color: #94A3B8; transition: 0.3s; }
    .notif-bell:hover { color: white; }
    .notif-badge { position: absolute; top: -5px; right: -5px; background: #EF4444; color: white; font-size: 0.6rem; padding: 2px 5px; border-radius: 50%; border: 1px solid #020617; }
    
    .notif-dropdown {
        position: absolute; top: 50px; right: 0; width: 320px; background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5); display: none; flex-direction: column; z-index: 1001; overflow: hidden; animation: slideDownNav 0.3s ease;
    }
    @keyframes slideDownNav { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .notif-dropdown.active { display: flex; }
    .notif-header { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); font-weight: 600; color: white; display: flex; justify-content: space-between; align-items: center; }
    .notif-list { max-height: 300px; overflow-y: auto; }
    .notif-item { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); transition: 0.2s; text-align: left; }
    .notif-item:hover { background: rgba(255,255,255,0.02); }
    .notif-item.unread { background: rgba(139, 92, 246, 0.05); border-left: 3px solid #8B5CF6; }
    .notif-title { font-size: 0.9rem; font-weight: 600; color: white; margin-bottom: 3px; }
    .notif-msg { font-size: 0.8rem; color: #94A3B8; }
    .notif-time { font-size: 0.7rem; color: #94A3B8; margin-top: 5px; text-align: right; opacity: 0.7; }
    .notif-empty { padding: 20px; text-align: center; color: #94A3B8; font-size: 0.9rem; }

    .btn-outline-nav { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.15); color: white; padding: 6px 15px; font-size: 0.8rem; border-radius: 12px; font-weight: 600; text-decoration: none; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; }
    .btn-outline-nav:hover { background: rgba(255,255,255,0.1); border-color: white; transform: translateY(-3px); }

    .ticker-wrap { position: fixed; top: 76px; left: 0; width: 100%; overflow: hidden; height: 40px; background: rgba(2, 6, 23, 0.9); border-bottom: 1px solid rgba(255,255,255,0.05); z-index: 900; line-height: 40px; }
    .ticker { display: inline-block; white-space: nowrap; padding-right: 100%; box-sizing: content-box; animation: ticker 40s linear infinite; }
    .ticker-item { display: inline-block; padding: 0 20px; font-size: 0.9rem; color: white; }
    .ticker-item a { color: #8B5CF6; text-decoration: none; font-weight: 700; transition:0.3s; }
    .ticker-item a:hover { color: white; }
    @keyframes ticker { 0% { transform: translate3d(0, 0, 0); } 100% { transform: translate3d(-100%, 0, 0); } }

    @media (max-width: 992px) {
        .nav-menu { display: none; position: fixed; top: 70px; left: 0; width: 100%; background: rgba(2,6,23,0.98); flex-direction: column; padding: 20px; height: calc(100vh - 70px); align-items: flex-start; z-index: 1000; overflow-y: auto; }
        .nav-menu.active { display: flex; }
        .nav-menu a { width: 100%; padding: 12px 15px; font-size: 1.05rem; }
        .menu-toggle { display: block; }
        .ticker-wrap { top: 70px; }
        
        /* Global Grid Overrides for App Feel */
        .dashboard-sections, .info-cards, .filters-grid, .auth-grid, .about-grid, .values-grid { grid-template-columns: 1fr !important; }
        .container, .nav-container-inner, .main-content { padding-left: 15px !important; padding-right: 15px !important; }
    }

    @media (max-width: 576px) {
        /* App-Like Header Enhancements */
        .navbar { padding: 12px 0; }
        .navbar.scrolled { padding: 10px 0; }
        .nav-logo { font-size: 1.15rem; gap: 6px; }
        .nav-logo i { font-size: 1.3rem; }
        .nav-logo img { height: 26px !important; }
        
        .user-menu { gap: 8px; }
        .balance-badge { padding: 5px 8px; font-size: 0.8rem; border-radius: 8px; }
        .btn-outline-nav { padding: 5px 8px; font-size: 0.8rem; justify-content: center; }
        .btn-outline-nav span { display: none; } /* Hide text, keep icon */
        .notif-bell { font-size: 1.05rem; }
        .menu-toggle { font-size: 1.25rem; }
        .notif-dropdown { position: fixed; top: 60px; left: 15px; right: 15px; width: auto; max-width: none; }
        
        /* Typography and Padding logic */
        h1 { font-size: 1.5rem !important; }
        h2 { font-size: 1.3rem !important; }
        body { font-size: 0.95rem; }
        
        /* Cards and Elements */
        .card, .content-card, .login-box, .register-box, .service-card { padding: 15px !important; border-radius: 16px !important; }
        .list-item { padding: 12px !important; }
        
        /* Tables to Vertical Cards on small mobiles (Optional or Horizontal Scroll) */
        .table-glass-container { padding: 0 !important; background: transparent !important; border: none !important; box-shadow: none !important; }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 5px; }
        .modern-table th, .modern-table td { padding: 10px 12px !important; font-size: 0.85rem !important; white-space: nowrap; }
        
        /* Ticker text for mobile */
        .ticker-item { font-size: 0.8rem; padding: 0 10px; }
    }
    
    @media (max-width: 400px) {
        .balance-badge span { display: none; } /* Hide ₺ symbol if absolutely needed, or adjust */
        .nav-logo span { display: none; } /* Hide 'SMM Panel' text on very tiny screens to save space */
    }
</style>

<nav class="navbar" id="navbar">
    <div class="nav-container-inner">
        <a href="dashboard.php" class="nav-logo">
            <?php if(!empty(SITE_LOGO_IMAGE)): ?>
                <img src="<?php echo htmlspecialchars(SITE_LOGO_IMAGE); ?>" alt="Logo" style="height: 32px; vertical-align: middle;">
            <?php else: ?>
                <i class="fas fa-bolt"></i> <?php echo htmlspecialchars(SITE_LOGO_TEXT); ?>
            <?php endif; ?>
        </a>
        
        <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
        
        <div class="nav-menu" id="navMenu">
            <a href="dashboard.php" <?php echo ($current_page == 'dashboard.php') ? 'class="active"' : ''; ?>><i class="fas fa-home"></i> Dashboard</a>
            <a href="new_order.php" <?php echo ($current_page == 'new_order.php') ? 'class="active"' : ''; ?>><i class="fas fa-cart-plus"></i> Sipariş Ver</a>
            <a href="services.php" <?php echo ($current_page == 'services.php') ? 'class="active"' : ''; ?>><i class="fas fa-box"></i> Hizmetler</a>
            <a href="orders.php" <?php echo ($current_page == 'orders.php') ? 'class="active"' : ''; ?>><i class="fas fa-history"></i> Siparişler</a>
            <a href="balance.php" <?php echo ($current_page == 'balance.php') ? 'class="active"' : ''; ?>><i class="fas fa-wallet"></i> Bakiye</a>
            <a href="support.php" <?php echo ($current_page == 'support.php') ? 'class="active"' : ''; ?>><i class="fas fa-headset"></i> Destek</a>
            <a href="settings.php" <?php echo ($current_page == 'settings.php') ? 'class="active"' : ''; ?>><i class="fas fa-cog"></i> Ayarlar</a>
            <?php if (isset($user['user_role']) && in_array($user['user_role'], ['admin', 'super_admin'])): ?>
            <a href="admin_dashboard.php"><i class="fas fa-user-shield"></i> Admin Panel</a>
            <?php endif; ?>
        </div>
        
        <div class="user-menu">
            <div class="notif-wrapper" onclick="toggleNotifications()">
                <i class="fas fa-bell notif-bell"></i>
                <?php if($unread_notif_count > 0): ?>
                    <span class="notif-badge" id="notifBadge"><?php echo $unread_notif_count; ?></span>
                <?php endif; ?>
                
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span>Bildirimler</span>
                        <small style="cursor:pointer; color:#8B5CF6;" onclick="markAllRead(event)">Tümü Okundu</small>
                    </div>
                    <div class="notif-list">
                        <?php if(empty($notifications)): ?>
                            <div class="notif-empty">Henüz bildirim yok.</div>
                        <?php else: ?>
                            <?php foreach($notifications as $notif): ?>
                                <div class="notif-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" onclick="window.location.href='orders.php'" style="cursor:pointer;">
                                    <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                    <div class="notif-msg"><?php echo htmlspecialchars($notif['message']); ?></div>
                                    <div class="notif-time"><?php echo date('d.m H:i', strtotime($notif['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (isset($user['balance'])): ?>
            <div class="balance-badge"><i class="fas fa-coins"></i> ₺<?php echo number_format($user['balance'], 2); ?></div>
            <?php endif; ?>
            <a href="logout.php" class="btn-outline-nav"><i class="fas fa-sign-out-alt"></i> <span>Çıkış</span></a>
        </div>
    </div>
</nav>

<?php if (defined('ANNOUNCEMENT_ACTIVE') && ANNOUNCEMENT_ACTIVE == '1' && defined('ANNOUNCEMENT_CONTENT') && !empty(ANNOUNCEMENT_CONTENT)): 
    $tickers = json_decode(ANNOUNCEMENT_CONTENT, true);
    if (!is_array($tickers)) {
        // Migration support from raw HTML to json
        $tickers = [['icon' => 'fas fa-bullhorn', 'text' => strip_tags(ANNOUNCEMENT_CONTENT), 'link' => '', 'link_text' => '']];
    }
    if (count($tickers) > 0):
?>
<div class="ticker-wrap">
    <div class="ticker">
        <?php foreach ($tickers as $t): ?>
            <div class="ticker-item">
                <?php if (!empty($t['icon'])): ?><i class="<?php echo htmlspecialchars($t['icon']); ?>"></i><?php endif; ?>
                <?php echo htmlspecialchars($t['text']); ?>
                <?php if (!empty($t['link']) && !empty($t['link_text'])): ?>
                    <a href="<?php echo htmlspecialchars($t['link']); ?>" target="_blank"><?php echo htmlspecialchars($t['link_text']); ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; endif; ?>

<script>
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    if(menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => navMenu.classList.toggle('active'));
    }

    const navbar = document.getElementById('navbar');
    if(navbar) {
        window.addEventListener('scroll', () => {
            if(window.scrollY > 50) navbar.classList.add('scrolled');
            else navbar.classList.remove('scrolled');
        });
    }

    function toggleNotifications() {
        document.getElementById('notifDropdown').classList.toggle('active');
    }

    function markAllRead(e) {
        e.stopPropagation();
        fetch('ajax_read_notif', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=read_notifications'
        }).then(res => res.text()).then(data => {
            if(data === 'ok') {
                document.querySelectorAll('.notif-item').forEach(el => el.classList.remove('unread'));
                const badge = document.getElementById('notifBadge');
                if(badge) badge.style.display = 'none';
            }
        });
    }

    document.addEventListener('click', function(e) {
        let nDropdown = document.getElementById('notifDropdown');
        let nWrapper = document.querySelector('.notif-wrapper');
        if (nDropdown && nDropdown.classList.contains('active') && !nWrapper.contains(e.target)) {
            nDropdown.classList.remove('active');
        }
    });
</script>
