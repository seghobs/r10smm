<?php
ob_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: dashboard.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$success_msg = '';
$error_msg = '';

// ── ADD PROVIDER ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_provider'])) {
    $name    = trim($_POST['name']);
    $url     = rtrim(trim($_POST['url']), '/');
    $api_key = trim($_POST['api_key']);

    if (empty($name) || empty($url) || empty($api_key)) {
        $error_msg = 'Tüm alanları doldurun.';
    } else {
        // Test connection & fetch balance
        $balance = 0;
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => http_build_query(['key' => $api_key, 'action' => 'balance']),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $res = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($res, true);
            if (isset($data['balance'])) $balance = $data['balance'];
        } catch (Exception $e) {}

        $stmt = $pdo->prepare("INSERT INTO api_providers (name, url, api_key, balance) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $url, $api_key, $balance]);
        $success_msg = "\"$name\" sağlayıcısı eklendi! Bakiye: ₺$balance";
    }
}

// ── DELETE PROVIDER ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_provider'])) {
    $id = intval($_POST['provider_id']);
    $pdo->prepare("DELETE FROM api_providers WHERE id = ?")->execute([$id]);
    $success_msg = 'Sağlayıcı silindi.';
}

// ── TOGGLE STATUS ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id  = intval($_POST['provider_id']);
    $row = $pdo->prepare("SELECT status FROM api_providers WHERE id = ?");
    $row->execute([$id]);
    $cur = $row->fetchColumn();
    $new = $cur === 'active' ? 'inactive' : 'active';
    $pdo->prepare("UPDATE api_providers SET status = ? WHERE id = ?")->execute([$new, $id]);
    $success_msg = 'Durum güncellendi.';
}

// ── REFRESH BALANCE ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_balance'])) {
    $id  = intval($_POST['provider_id']);
    $row = $pdo->prepare("SELECT * FROM api_providers WHERE id = ?");
    $row->execute([$id]);
    $prov = $row->fetch();
    if ($prov) {
        $ch = curl_init($prov['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query(['key' => $prov['api_key'], 'action' => 'balance']),
            CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_TIMEOUT => 10, CURLOPT_FOLLOWLOCATION => true,
        ]);
        $res  = curl_exec($ch); curl_close($ch);
        $data = json_decode($res, true);
        $bal  = $data['balance'] ?? 0;
        $pdo->prepare("UPDATE api_providers SET balance = ? WHERE id = ?")->execute([$bal, $id]);
        $success_msg = "Bakiye güncellendi: $bal";
    }
}

// ── SYNC SERVICES ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_services'])) {
    $id   = intval($_POST['provider_id']);
    $markup = floatval($_POST['markup'] ?? 120);   // percentage mark-up
    $row  = $pdo->prepare("SELECT * FROM api_providers WHERE id = ?");
    $row->execute([$id]);
    $prov = $row->fetch();

    if ($prov) {
        $ch = curl_init($prov['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query(['key' => $prov['api_key'], 'action' => 'services']),
            CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => true,
        ]);
        $res  = curl_exec($ch); curl_close($ch);
        $services = json_decode($res, true);

        if (is_array($services)) {
            $added = 0; $skipped = 0;
            $check = $pdo->prepare("SELECT id FROM services WHERE api_service_id = ? AND provider_id = ?");
            $insert = $pdo->prepare("INSERT INTO services (name, description, category, price, price_per_1000, cost, min_quantity, max_quantity, api_service_id, api_provider, provider_id, status, created_at)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            foreach ($services as $s) {
                $api_id = $s['service'] ?? ($s['id'] ?? null);
                if (!$api_id) continue;
                $check->execute([$api_id, $id]);
                if ($check->fetchColumn()) { $skipped++; continue; }

                $cost          = floatval($s['rate'] ?? $s['price'] ?? 0);
                $sell_price    = round($cost * (1 + $markup / 100), 2);
                $name          = $s['name'] ?? 'Servis #' . $api_id;
                $category      = $s['category'] ?? 'Genel';
                $min_q         = intval($s['min'] ?? 10);
                $max_q         = intval($s['max'] ?? 100000);

                $insert->execute([$name, $name, $category, $sell_price, $sell_price, $cost, $min_q, $max_q, $api_id, $prov['name'], $id]);
                $added++;
            }
            $success_msg = "Senkronizasyon tamamlandı! ✓ $added yeni servis eklendi, $skipped zaten mevcut.";
        } else {
            $error_msg = 'API yanıtı beklenmedik format: ' . htmlspecialchars(substr($res, 0, 300));
        }
    }
}

$providers = $pdo->query("SELECT * FROM api_providers ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Sağlayıcıları - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary: #8B5CF6; --primary-dark: #7C3AED; --secondary: #10B981;
            --accent: #F59E0B; --danger: #EF4444;
            --bg-body: #020617; --bg-card: rgba(30,41,59,.65);
            --text-main: #F8FAFC; --text-muted: #94A3B8;
            --glass-border: 1px solid rgba(255,255,255,.08);
            --gradient: linear-gradient(135deg,#8B5CF6 0%,#4F46E5 100%);
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--bg-body); color:var(--text-main); min-height:100vh; }
        .bg-glow { position:fixed; top:0; left:0; width:100%; height:100%; z-index:-1; pointer-events:none; }
        .blob { position:absolute; border-radius:50%; filter:blur(90px); opacity:.25; }
        .blob-1 { top:-10%; left:-10%; width:600px; height:600px; background:#8B5CF6; }
        .blob-2 { bottom:0; right:-10%; width:500px; height:500px; background:#059669; }

        /* NAV */
.container { max-width:1400px; margin:0 auto; padding:0 20px; }
/* MAIN */
        .main { padding:100px 0 60px; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; flex-wrap:wrap; gap:15px; }
        .page-title { font-family:'Outfit'; font-size:2rem; font-weight:700; }
        .page-title span { background:var(--gradient); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .btn { display:inline-flex; align-items:center; gap:8px; padding:12px 22px; border-radius:14px; font-weight:600; border:none; cursor:pointer; transition:.3s; font-size:.9rem; }
        .btn-primary { background:var(--gradient); color:white; box-shadow:0 4px 20px rgba(139,92,246,.3); }
        .btn-primary:hover { transform:translateY(-2px); box-shadow:0 8px 25px rgba(139,92,246,.5); }
        .btn-sm { padding:8px 14px; border-radius:10px; font-size:.85rem; }
        .btn-success { background:rgba(16,185,129,.15); color:#10B981; border:1px solid rgba(16,185,129,.3); }
        .btn-success:hover { background:#10B981; color:white; }
        .btn-warning { background:rgba(245,158,11,.15); color:#F59E0B; border:1px solid rgba(245,158,11,.3); }
        .btn-warning:hover { background:#F59E0B; color:white; }
        .btn-danger { background:rgba(239,68,68,.15); color:#EF4444; border:1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background:#EF4444; color:white; }
        .btn-purple { background:rgba(139,92,246,.15); color:#C4B5FD; border:1px solid rgba(139,92,246,.3); }
        .btn-purple:hover { background:var(--primary); color:white; }

        /* ADD CARD */
        .add-card { background:var(--bg-card); border:var(--glass-border); border-radius:24px; padding:30px; margin-bottom:35px; backdrop-filter:blur(15px); position:relative; overflow:hidden; }
        .add-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--gradient); }
        .card-title { font-family:'Outfit'; font-size:1.2rem; font-weight:700; margin-bottom:22px; display:flex; align-items:center; gap:10px; color:white; }
        .card-title i { color:var(--primary); }
        .form-grid { display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:15px; align-items:end; }
        .form-group label { display:block; margin-bottom:8px; font-size:.85rem; color:var(--text-muted); font-weight:500; }
        .form-control { width:100%; padding:13px 15px; background:rgba(2,6,23,.6); border:1px solid rgba(255,255,255,.1); border-radius:12px; color:white; font-size:.9rem; transition:.3s; font-family:'Plus Jakarta Sans'; }
        .form-control:focus { outline:none; border-color:var(--primary); background:rgba(139,92,246,.06); }
        .form-control::placeholder { color:var(--text-muted); }

        /* PROVIDERS GRID */
        .providers-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(400px, 1fr)); gap:20px; }
        .provider-card { background:var(--bg-card); border:var(--glass-border); border-radius:22px; padding:25px; backdrop-filter:blur(15px); transition:.3s; position:relative; overflow:hidden; }
        .provider-card:hover { transform:translateY(-4px); border-color:rgba(139,92,246,.4); box-shadow:0 15px 40px rgba(0,0,0,.4); }
        .provider-card.inactive { opacity:.6; }
        .provider-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:18px; }
        .provider-name { font-family:'Outfit'; font-size:1.2rem; font-weight:700; color:white; }
        .provider-url { color:var(--text-muted); font-size:.8rem; margin-top:4px; word-break:break-all; }
        .status-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:6px; }
        .status-dot.active { background:#10B981; box-shadow:0 0 8px #10B981; }
        .status-dot.inactive { background:#94A3B8; }

        .provider-stats { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:20px; }
        .stat-box { background:rgba(0,0,0,.25); border-radius:12px; padding:12px 15px; }
        .stat-label { font-size:.75rem; color:var(--text-muted); margin-bottom:4px; }
        .stat-value { font-family:'Outfit'; font-size:1.1rem; font-weight:700; color:white; }
        .stat-value.green { color:#10B981; }
        .stat-value.purple { color:#C4B5FD; }

        .provider-key { background:rgba(0,0,0,.3); border-radius:10px; padding:10px 14px; font-family:monospace; font-size:.8rem; color:var(--text-muted); margin-bottom:18px; word-break:break-all; cursor:pointer; transition:.3s; }
        .provider-key:hover { color:white; }

        .provider-actions { display:flex; gap:8px; flex-wrap:wrap; }

        /* SYNC MODAL */
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.85); backdrop-filter:blur(8px); z-index:2000; align-items:center; justify-content:center; }
        .modal.open { display:flex; }
        .modal-box { background:#0f172a; border:var(--glass-border); border-radius:24px; padding:35px; width:95%; max-width:500px; animation:zoomIn .3s ease; }
        @keyframes zoomIn { from{transform:scale(.9);opacity:0;} to{transform:scale(1);opacity:1;} }
        .modal-title { font-family:'Outfit'; font-size:1.4rem; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:12px; }
        .modal-footer { display:flex; justify-content:flex-end; gap:10px; margin-top:25px; }
        .range-wrap { display:flex; gap:15px; align-items:center; }
        .range-input { flex:1; }
        .range-val { min-width:55px; text-align:center; font-weight:700; color:var(--primary); font-size:1.1rem; }

        /* ALERT */
        .alert { padding:14px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; }
        .alert-success { background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.3); color:#6EE7B7; }
        .alert-error   { background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.3); color:#FCA5A5; }

        /* EMPTY STATE */
        .empty-state { text-align:center; padding:60px; color:var(--text-muted); }
        .empty-state i { font-size:4rem; opacity:.3; margin-bottom:20px; display:block; }
        .empty-state p { font-size:1.1rem; }

        .loading-spin { animation:spin 1s linear infinite; }
        @keyframes spin { to{transform:rotate(360deg);} }

        @media(max-width:768px) { .form-grid{grid-template-columns:1fr;} .providers-grid{grid-template-columns:1fr;}
}
    </style>
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>
<div class="bg-glow"><div class="blob blob-1"></div><div class="blob blob-2"></div></div>

<?php $current_page = 'admin_providers.php'; include 'admin_navbar.php'; ?>

<div class="main container">

    <div class="page-header">
        <div>
            <h1 class="page-title">API <span>Sağlayıcıları</span></h1>
            <p style="color:var(--text-muted);margin-top:5px;">TurkPaneli, Reklambayi, NajSMM vb. tüm SMM panellerini buradan yönetin</p>
        </div>
        <a href="admin_services.php" class="btn btn-primary"><i class="fas fa-boxes"></i> Servisler</a>
    </div>

    <?php if ($success_msg): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- ADD FORM -->
    <div class="add-card">
        <div class="card-title"><i class="fas fa-plus-circle"></i> Yeni API Sağlayıcı Ekle</div>
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Panel Adı</label>
                    <input type="text" name="name" class="form-control" placeholder="TurkPaneli, Reklambayi..." required>
                </div>
                <div class="form-group">
                    <label>API URL <small style="color:var(--text-muted)">(Base URL)</small></label>
                    <input type="url" name="url" class="form-control" placeholder="https://turkpaneli.com/api/v2" required>
                </div>
                <div class="form-group">
                    <label>API Key / Token</label>
                    <input type="text" name="api_key" class="form-control" placeholder="Panelden aldığınız API key" required>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" name="add_provider" class="btn btn-primary" style="width:100%;justify-content:center;">
                        <i class="fas fa-plug"></i> Bağla
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- PROVIDERS GRID -->
    <?php if (empty($providers)): ?>
    <div class="empty-state">
        <i class="fas fa-satellite-dish"></i>
        <p>Henüz API sağlayıcısı eklenmemiş.</p>
        <p style="margin-top:8px;font-size:.9rem;">Türkpaneli, Reklambayi, NajSMM gibi herhangi bir SMM panelini yukarıdan ekleyebilirsiniz.</p>
    </div>
    <?php else: ?>
    <div class="providers-grid">
        <?php foreach ($providers as $p): ?>
        <?php
            // Count services from this provider
            $svc_count = $pdo->prepare("SELECT COUNT(*) FROM services WHERE provider_id = ?");
            $svc_count->execute([$p['id']]);
            $svc_count = $svc_count->fetchColumn();
        ?>
        <div class="provider-card <?php echo $p['status']; ?>">
            <div class="provider-header">
                <div>
                    <div class="provider-name"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div class="provider-url"><?php echo htmlspecialchars($p['url']); ?></div>
                </div>
                <div class="status-dot <?php echo $p['status']; ?>"></div>
            </div>

            <div class="provider-stats">
                <div class="stat-box">
                    <div class="stat-label">Uzak Bakiye</div>
                    <div class="stat-value green"><?php echo number_format($p['balance'], 2); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Çekilen Servis</div>
                    <div class="stat-value purple"><?php echo number_format($svc_count); ?></div>
                </div>
            </div>

            <div class="provider-key" onclick="copyKey('<?php echo htmlspecialchars(addslashes($p['api_key'])); ?>', this)" title="Kopyalamak için tıklayın">
                <i class="fas fa-key" style="margin-right:6px;"></i><?php echo substr($p['api_key'], 0, 20) . '...'; ?>
            </div>

            <div class="provider-actions">
                <!-- Sync Services -->
                <button class="btn btn-sm btn-primary" onclick="openSyncModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>')">
                    <i class="fas fa-sync"></i> Servisleri Çek
                </button>

                <!-- Refresh Balance -->
                <form method="POST" style="margin:0;" id="bal-form-<?php echo $p['id']; ?>">
                    <input type="hidden" name="provider_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" name="refresh_balance" class="btn btn-sm btn-success" onclick="spinBtn(this)">
                        <i class="fas fa-wallet"></i> Bakiye
                    </button>
                </form>

                <!-- Toggle Status -->
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="provider_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" name="toggle_status" class="btn btn-sm btn-warning">
                        <?php if ($p['status'] === 'active'): ?>
                            <i class="fas fa-pause"></i> Pasif
                        <?php else: ?>
                            <i class="fas fa-play"></i> Aktif
                        <?php endif; ?>
                    </button>
                </form>

                <!-- Delete -->
                <form method="POST" style="margin:0;" onsubmit="return confirm('Bu sağlayıcıyı silmek istiyor musunuz?')">
                    <input type="hidden" name="provider_id" value="<?php echo $p['id']; ?>">
                    <button type="submit" name="delete_provider" class="btn btn-sm btn-danger">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>

<!-- SYNC MODAL -->
<div class="modal" id="syncModal">
    <div class="modal-box">
        <div class="modal-title"><i class="fas fa-cloud-download-alt" style="color:var(--primary);"></i> Servisleri Senkronize Et</div>
        <p style="color:var(--text-muted);margin-bottom:22px;" id="sync-provider-label"></p>

        <form method="POST" id="syncForm">
            <input type="hidden" name="provider_id" id="sync-provider-id">

            <div class="form-group" style="margin-bottom:22px;">
                <label style="display:block;margin-bottom:10px;font-size:.9rem;font-weight:600;">Kâr Marjı (%)</label>
                <div class="range-wrap">
                    <input type="range" name="markup" id="markupRange" min="0" max="500" value="120" class="form-control range-input" style="padding:0;border:none;background:transparent;cursor:pointer;" oninput="document.getElementById('markupVal').textContent = this.value + '%'">
                    <div class="range-val" id="markupVal">120%</div>
                </div>
                <p style="color:var(--text-muted);font-size:.8rem;margin-top:8px;">
                    <i class="fas fa-info-circle"></i> Sağlayıcıdaki maliyet fiyatının üstüne eklenen kâr yüzdesi. 120% = Maliyetin 2.2 katı
                </p>
            </div>

            <div style="background:rgba(139,92,246,.08);border:1px solid rgba(139,92,246,.2);border-radius:14px;padding:16px;margin-bottom:5px;">
                <p style="font-size:.85rem;color:#C4B5FD;"><i class="fas fa-bolt"></i> <strong>Nasıl Çalışır?</strong></p>
                <ul style="font-size:.82rem;color:var(--text-muted);margin-top:8px;padding-left:18px;line-height:1.8;">
                    <li>Sağlayıcının API'sine bağlanılır ve tüm servisler çekilir</li>
                    <li>Zaten mevcut olanlar (aynı provider_id + service_id) atlanır</li>
                    <li>Yeni servisler belirlediğiniz kâr marjıyla veritabanına eklenir</li>
                </ul>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-danger" onclick="closeSyncModal()"><i class="fas fa-times"></i> İptal</button>
                <button type="submit" name="sync_services" class="btn btn-sm btn-primary" id="syncBtn">
                    <i class="fas fa-cloud-download-alt"></i> Servisleri Çek
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function openSyncModal(id, name) {
    document.getElementById('sync-provider-id').value = id;
    document.getElementById('sync-provider-label').textContent = '📡 ' + name + ' sağlayıcısından servisler çekilecek.';
    document.getElementById('syncModal').classList.add('open');
}
function closeSyncModal() {
    document.getElementById('syncModal').classList.remove('open');
}
window.addEventListener('click', e => {
    if (e.target === document.getElementById('syncModal')) closeSyncModal();
});

document.getElementById('syncForm').addEventListener('submit', function() {
    const btn = document.getElementById('syncBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner loading-spin"></i> Çekiliyor...';
});

function copyKey(key, el) {
    navigator.clipboard.writeText(key).then(() => {
        const orig = el.innerHTML;
        el.innerHTML = '<i class="fas fa-check" style="color:#10B981;margin-right:6px;"></i>Kopyalandı!';
        setTimeout(() => el.innerHTML = orig, 1500);
    });
}

function spinBtn(btn) {
    btn.innerHTML = '<i class="fas fa-spinner loading-spin"></i> ...';
    btn.disabled = true;
}
</script>
</body>
</html>
