<?php
/**
 * Shared Admin Navbar
 * Include this file in all admin pages: <?php $current_page = 'admin_dashboard.php'; include 'admin_navbar.php'; ?>
 * Set $current_page before including to highlight the active menu item.
 * Requires $admin variable (current admin user row) to be set.
 */
if (!isset($current_page)) $current_page = basename($_SERVER['PHP_SELF']);
if (!isset($admin)) $admin = ['username' => 'Admin'];
?>
<style>
    /* ═══ ADMIN NAVBAR (SHARED) ═══ */
    .admin-navbar {
        padding: 16px 0;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
        background: rgba(2, 6, 23, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        transition: all 0.3s ease;
    }
    .admin-navbar .nav-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .admin-navbar .nav-logo {
        font-family: 'Outfit', sans-serif;
        font-size: 1.4rem;
        font-weight: 800;
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: -0.5px;
        flex-shrink: 0;
    }
    .admin-navbar .nav-logo i {
        color: #8B5CF6;
        font-size: 1.3rem;
        filter: drop-shadow(0 0 8px rgba(139, 92, 246, 0.4));
    }
    .admin-navbar .nav-logo .badge-admin {
        background: linear-gradient(135deg, #F59E0B, #D97706);
        color: #000;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }
    .admin-navbar .nav-links {
        display: flex;
        gap: 4px;
        align-items: center;
    }
    .admin-navbar .nav-links a {
        color: #94A3B8;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.88rem;
        padding: 8px 14px;
        border-radius: 10px;
        transition: all 0.25s ease;
        white-space: nowrap;
    }
    .admin-navbar .nav-links a:hover {
        color: #F8FAFC;
        background: rgba(255, 255, 255, 0.05);
    }
    .admin-navbar .nav-links a.active {
        color: #C4B5FD;
        background: rgba(139, 92, 246, 0.12);
        font-weight: 600;
    }
    .admin-navbar .nav-links a.nav-site-link {
        color: #F59E0B;
    }
    .admin-navbar .nav-links a.nav-site-link:hover {
        color: #FBBF24;
        background: rgba(245, 158, 11, 0.08);
    }
    .admin-navbar .nav-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
    }
    .admin-navbar .nav-user {
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
    }
    .admin-navbar .nav-logout {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #FCA5A5;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .admin-navbar .nav-logout:hover {
        background: #EF4444;
        color: white;
        border-color: #EF4444;
    }
    .admin-navbar .nav-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 1.4rem;
        cursor: pointer;
        padding: 5px;
    }
    @media (max-width: 1200px) {
        .admin-navbar .nav-links { gap: 2px; }
        .admin-navbar .nav-links a { font-size: 0.82rem; padding: 7px 10px; }
    }
    @media (max-width: 992px) {
        .admin-navbar .nav-toggle { display: block; }
        .admin-navbar .nav-links {
            display: none;
            position: fixed;
            top: 58px;
            left: 0;
            width: 100%;
            background: rgba(2, 6, 23, 0.98);
            backdrop-filter: blur(20px);
            flex-direction: column;
            padding: 15px 20px;
            gap: 4px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            max-height: calc(100vh - 58px);
            overflow-y: auto;
        }
        .admin-navbar .nav-links.open { display: flex; }
        .admin-navbar .nav-links a { width: 100%; padding: 12px 15px; font-size: 0.95rem; }
    }
</style>

<nav class="admin-navbar" id="adminNavbar">
    <div class="nav-container">
        <a href="admin_dashboard.php" class="nav-logo">
            <?php if(!empty(SITE_LOGO_IMAGE)): ?><img src="<?php echo htmlspecialchars(SITE_LOGO_IMAGE); ?>" alt="Logo" style="height: 32px; vertical-align: middle;"><?php else: ?><i class="fas fa-bolt"></i> <?php echo htmlspecialchars(SITE_LOGO_TEXT); ?><?php endif; ?> <span class="badge-admin">YÖNETİM</span>
        </a>

        <button class="nav-toggle" id="adminNavToggle" onclick="document.getElementById('adminNavLinks').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>

        <div class="nav-links" id="adminNavLinks">
            <?php
            $menu_items = [
                'admin_dashboard.php'  => 'Dashboard',
                'admin_orders.php'     => 'Siparişler',
                'admin_payments.php'   => 'Ödemeler',
                'admin_users.php'      => 'Kullanıcılar',
                'admin_services.php'   => 'Servisler',
                'admin_providers.php'  => 'API Sağlayıcıları',
                'admin_tickets.php'    => 'Destek',
                'admin_notifications.php' => 'Bildirimler',
                'admin_settings.php'   => 'Ayarlar',
            ];
            foreach ($menu_items as $href => $label):
                $is_active = ($current_page === $href) ? ' class="active"' : '';
            ?>
            <a href="<?php echo $href; ?>"<?php echo $is_active; ?>><?php echo $label; ?></a>
            <?php endforeach; ?>
            <a href="dashboard.php" class="nav-site-link">Siteye Dön <i class="fas fa-external-link-alt"></i></a>
        </div>

        <div class="nav-right">
            <span class="nav-user"><?php echo htmlspecialchars($admin['username']); ?></span>
            <a href="logout.php" class="nav-logout"><i class="fas fa-sign-out-alt"></i> Çıkış</a>
        </div>
    </div>
</nav>
