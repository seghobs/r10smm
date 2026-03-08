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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'get_ticket_details') {
    while (ob_get_level()) { ob_end_clean(); }
    
    $ticket_string_id = trim($_POST['ticket_id']);

    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE ticket_id = ?");
    $stmt->execute([$ticket_string_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo '<div class="error-state"><i class="fas fa-exclamation-circle"></i> Talep bulunamadı.</div>';
        exit;
    }

    $conversation = [];
    $conversation[] = [
        'type' => 'user',
        'message' => $ticket['message'],
        'created_at' => $ticket['created_at'],
        'sender_name' => $ticket['username']
    ];

    $stmt = $pdo->prepare("SELECT message, created_at, is_admin FROM ticket_replies WHERE ticket_id = ? ORDER BY created_at ASC");
    $stmt->execute([$ticket['id']]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($replies as $reply) {
        $conversation[] = [
            'type' => $reply['is_admin'] ? 'admin' : 'user',
            'message' => $reply['message'],
            'created_at' => $reply['created_at'],
            'sender_name' => $reply['is_admin'] ? 'Destek Ekibi' : $ticket['username']
        ];
    }

    usort($conversation, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    ?>

    <div class="chat-header-info">
        <div class="ticket-info-group">
            <h3 class="modal-ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
            <div class="modal-ticket-meta">
                <span class="meta-id">#<?php echo $ticket['ticket_id']; ?></span>
                <span class="meta-user"><i class="fas fa-user"></i> <?php echo htmlspecialchars($ticket['username']); ?></span>
                <span class="status-badge status-<?php echo $ticket['status']; ?>">
                    <?php echo strtoupper($ticket['status']); ?>
                </span>
            </div>
        </div>
        <button type="button" class="btn-close-modal" onclick="closeModal()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="chat-area" id="chatAreaScroll">
        <?php foreach ($conversation as $msg): ?>
            <div class="message-row <?php echo $msg['type'] === 'admin' ? 'outgoing' : 'incoming'; ?>">
                <div class="message-bubble">
                    <div class="message-sender">
                        <?php if($msg['type'] === 'admin'): ?>
                            <i class="fas fa-headset"></i> Destek Ekibi
                        <?php else: ?>
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($msg['sender_name']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="message-text">
                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                    </div>
                    <div class="message-time">
                        <?php echo date('d.m H:i', strtotime($msg['created_at'])); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($ticket['status'] !== 'closed'): ?>
        <form id="replyForm" class="reply-box" onsubmit="submitReply(event, '<?php echo $ticket['ticket_id']; ?>')">
            <input type="hidden" name="action" value="quick_reply">
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
            
            <div class="quick-actions">
                <span onclick="insertTemplate('Merhaba,\n\nSorununuz çözülmüştür. İyi günler dileriz.')">✅ Çözüldü</span>
                <span onclick="insertTemplate('Merhaba,\n\nKonuyu ilgili birime ilettik, lütfen bekleyiniz.')">⏳ İnceleniyor</span>
                <span onclick="insertTemplate('Merhaba,\n\nLütfen sipariş numaranızı iletir misiniz?')">❓ Bilgi İste</span>
            </div>
            
            <div class="input-group">
                <textarea name="message" id="replyText" class="form-control" placeholder="Yanıtınızı buraya yazın..." rows="2" required></textarea>
                <button type="submit" class="btn-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            
            <div class="action-buttons-left">
                <button type="button" class="btn-close-ticket" onclick="closeTicket(<?php echo $ticket['id']; ?>)">
                    <i class="fas fa-lock"></i> Talebi Kapat
                </button>
            </div>
        </form>
    <?php else: ?>
        <div class="ticket-closed-wrapper">
            <div class="tc-info">
                <div class="tc-icon-box">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="tc-text">
                    <h4>Bu talep kapatılmıştır</h4>
                    <p>Konu çözüldüğü için yanıta kapatıldı. İşleme devam etmek için tekrar açabilirsiniz.</p>
                </div>
            </div>
            
            <button type="button" onclick="reopenTicket(<?php echo $ticket['id']; ?>)" class="btn-reopen-modern">
                <i class="fas fa-unlock-alt"></i> Tekrar Aç
            </button>
        </div>
    <?php endif; ?>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'quick_reply') {
        $id = intval($_POST['ticket_id']);
        $message = trim($_POST['message']);
        
        $t = $pdo->prepare("SELECT ticket_id, user_id FROM support_tickets WHERE id = ?");
        $t->execute([$id]);
        $ticket_data = $t->fetch();

        if ($ticket_data && !empty($message)) {
            $stmt = $pdo->prepare("INSERT INTO ticket_replies (ticket_id, user_id, message, is_admin, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->execute([$id, $_SESSION['user_id'], $message]);
            
            $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")->execute([$id]);
            
            try {
                $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'info', NOW())")
                    ->execute([$ticket_data['user_id'], "Destek Yanıtı", "Ticket #{$ticket_data['ticket_id']} yanıtlandı."]);
            } catch(Exception $e){}
            echo "OK"; exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'close_ticket') {
        $id = intval($_POST['ticket_id']);
        $stmt = $pdo->prepare("SELECT user_id, ticket_id FROM support_tickets WHERE id = ?");
        $stmt->execute([$id]);
        $ticket_data = $stmt->fetch();
        if ($ticket_data) {
            $pdo->prepare("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ?")->execute([$id]);
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'warning', NOW())")
                ->execute([$ticket_data['user_id'], 'Talep Kapatıldı', "Ticket #{$ticket_data['ticket_id']} kapatıldı."]);
        }
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    if (isset($_POST['action']) && $_POST['action'] == 'reopen_ticket') {
        $id = intval($_POST['ticket_id']);
        $pdo->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?")->execute([$id]);
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    if (isset($_POST['action']) && $_POST['action'] == 'delete_ticket') {
        $id = intval($_POST['ticket_id']);
        $t = $pdo->prepare("SELECT ticket_id FROM support_tickets WHERE id = ?");
        $t->execute([$id]);
        $d = $t->fetch();
        if ($d) {
            $pdo->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM support_tickets WHERE id = ?")->execute([$id]);
        }
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT * FROM support_tickets WHERE 1=1";
$params = [];
if ($status_filter !== 'all') { $sql .= " AND status = ?"; $params[] = $status_filter; }
if (!empty($search_query)) { $sql .= " AND (subject LIKE ? OR ticket_id LIKE ? OR username LIKE ?)"; $term = "%$search_query%"; $params = array_merge($params, [$term, $term, $term]); }
$sql .= " ORDER BY CASE WHEN status = 'open' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END, updated_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$stats = [
    'open' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open'")->fetchColumn(),
    'answered' => $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'answered'")->fetchColumn(),
    'total' => $pdo->query("SELECT COUNT(*) FROM support_tickets")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destek Yönetimi - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root { --primary: #8B5CF6; --primary-dark: #7C3AED; --secondary: #10B981; --accent: #F59E0B; --danger: #EF4444; --bg-body: #020617; --bg-card: rgba(30, 41, 59, 0.6); --text-main: #F8FAFC; --text-muted: #94A3B8; --gradient-main: linear-gradient(135deg, #8B5CF6 0%, #4F46E5 100%); --glass-border: 1px solid rgba(255, 255, 255, 0.08); }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-body); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }
        .background-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; }
        .glow-blob { position: absolute; filter: blur(90px); opacity: 0.3; border-radius: 50%; }
        .blob-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: var(--primary); }
        .blob-2 { bottom: 10%; right: -10%; width: 500px; height: 500px; background: #059669; }
.container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
.main-content { padding: 100px 0 40px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--bg-card); border: var(--glass-border); padding: 20px; border-radius: 20px; text-align: center; backdrop-filter: blur(10px); }
        .stat-number { font-family: 'Outfit'; font-size: 2rem; font-weight: 700; color: white; }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; }
        .text-warning { color: var(--accent); } .text-success { color: var(--secondary); } .text-danger { color: var(--danger); }
        
        .filter-bar { display: flex; gap: 15px; margin-bottom: 30px; background: var(--bg-card); padding: 20px; border-radius: 16px; border: var(--glass-border); align-items: center; }
        .search-input { flex: 1; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); padding: 12px 15px; border-radius: 12px; color: white; font-size: 0.95rem; }
        .filter-select { background: #0f172a; border: 1px solid rgba(255,255,255,0.1); padding: 12px 15px; border-radius: 12px; color: white; font-size: 0.95rem; min-width: 150px; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 12px 25px; border-radius: 12px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        
        .table-container { 
            width: 100%; 
            background: var(--bg-card); 
            border-radius: 24px; 
            border: var(--glass-border); 
            overflow: hidden;
        }
        
        .custom-table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
        }
        
        .custom-table th, .custom-table td { 
            padding: 18px 20px; 
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            color: var(--text-main); 
            font-size: 0.95rem; 
            vertical-align: middle; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        
        .custom-table th { 
            background: rgba(0,0,0,0.2); 
            color: var(--text-muted); 
            font-weight: 600; 
            font-size: 0.85rem; 
            text-transform: uppercase; 
        }
        
        .custom-table tr:hover { background: rgba(255,255,255,0.02); }
        
        .w-id { width: 100px; }
        .w-user { width: 15%; }
        .w-subject { width: auto; }
        .w-category { width: 12%; }
        .w-status { width: 12%; }
        .w-date { width: 150px; }
        .w-action { width: 160px; text-align: right; }
        
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .status-open { background: rgba(245, 158, 11, 0.15); color: #F59E0B; }
        .status-answered { background: rgba(16, 185, 129, 0.15); color: #10B981; }
        .status-closed { background: rgba(148, 163, 184, 0.1); color: var(--text-muted); }
        
        .clickable-id { color: var(--primary); font-family: monospace; font-weight: 600; cursor: pointer; text-decoration: underline; }
        .clickable-id:hover { color: white; }

        .btn-sm { padding: 8px 14px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; color: white; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 5px; transition: 0.3s; }
        .btn-view { background: rgba(139, 92, 246, 0.2); color: #C4B5FD; border: 1px solid rgba(139, 92, 246, 0.3); }
        .btn-view:hover { background: var(--primary); color: white; }
        .btn-delete { background: rgba(239, 68, 68, 0.2); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-delete:hover { background: var(--danger); color: white; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(5px); z-index: 2000; align-items: center; justify-content: center; }
        .modal-content { background: #1e293b; width: 95%; max-width: 900px; height: 90vh; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 50px 100px rgba(0,0,0,0.7); }
        .chat-header-info { padding: 20px; background: rgba(15, 23, 42, 0.9); border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .modal-ticket-subject { font-size: 1.3rem; font-weight: 700; color: white; margin: 0; }
        .modal-ticket-meta { font-size: 0.85rem; color: var(--text-muted); display: flex; gap: 15px; align-items: center; margin-top: 5px; }
        .btn-close-modal { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; }
        .chat-area { flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; background: rgba(2,6,23,0.3); }
        .message-row { display: flex; width: 100%; }
        .incoming { justify-content: flex-start; }
        .outgoing { justify-content: flex-end; }
        .message-bubble { max-width: 80%; padding: 15px 20px; border-radius: 16px; font-size: 0.95rem; line-height: 1.6; }
        .incoming .message-bubble { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.05); }
        .outgoing .message-bubble { background: var(--gradient-main); color: white; }
        .message-sender { font-size: 0.75rem; font-weight: 700; margin-bottom: 5px; opacity: 0.8; }
        .reply-box { padding: 20px; background: #1e293b; border-top: 1px solid rgba(255,255,255,0.05); flex-shrink: 0; }
        .quick-actions { display: flex; gap: 10px; margin-bottom: 15px; }
        .quick-actions span { background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); color: var(--text-muted); }
        .quick-actions span:hover { border-color: var(--primary); color: white; }
        .input-group { display: flex; gap: 10px; }
        .form-control { flex: 1; background: #0f172a; border: 1px solid rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; color: white; resize: none; font-size: 0.95rem; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        .btn-send { background: var(--primary); color: white; border: none; padding: 0 25px; border-radius: 12px; cursor: pointer; font-size: 1.2rem; }
        .action-buttons-left { margin-top: 15px; }
        .btn-close-ticket { background: rgba(239, 68, 68, 0.1); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.3); padding: 8px 15px; border-radius: 8px; cursor: pointer; font-size: 0.85rem; }
        .error-state { color: #EF4444; text-align: center; padding: 50px; font-size: 1.2rem; }
        
        .ticket-closed-wrapper { margin-top: auto; padding: 20px; background: linear-gradient(145deg, rgba(30, 41, 59, 0.4), rgba(15, 23, 42, 0.6)); border-top: 1px solid rgba(255, 255, 255, 0.05); display: flex; align-items: center; justify-content: space-between; gap: 20px; backdrop-filter: blur(10px); }
        .tc-info { display: flex; align-items: center; gap: 15px; }
        .tc-icon-box { width: 45px; height: 45px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #EF4444; font-size: 1.2rem; box-shadow: 0 0 15px rgba(239, 68, 68, 0.1); }
        .tc-text h4 { color: white; font-size: 0.95rem; font-weight: 700; margin-bottom: 2px; font-family: 'Outfit', sans-serif; }
        .tc-text p { color: var(--text-muted); font-size: 0.8rem; margin: 0; }
        .btn-reopen-modern { background: rgba(255, 255, 255, 0.05); color: var(--text-main); border: 1px solid rgba(255, 255, 255, 0.1); padding: 10px 20px; border-radius: 12px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
        .btn-reopen-modern:hover { background: var(--primary); border-color: var(--primary); color: white; box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3); transform: translateY(-2px); }
        .btn-reopen-modern i { font-size: 0.9rem; }
        
        @media (max-width: 992px) {
.stats-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 768px) { .filters-grid { grid-template-columns: 1fr; } .filter-bar { flex-direction: column; } }
        @media (max-width: 576px) { .ticket-closed-wrapper { flex-direction: column; text-align: center; } .tc-info { flex-direction: column; } .btn-reopen-modern { width: 100%; justify-content: center; } }
    </style>
    <link rel="stylesheet" href="admin_shared.css">
</head>
<body>

    <div class="background-glow"><div class="glow-blob blob-1"></div><div class="glow-blob blob-2"></div></div>

    <?php $current_page = 'admin_tickets.php'; include 'admin_navbar.php'; ?>

    <div class="main-content container">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total']; ?></div><div class="stat-label">Toplam Talep</div></div>
            <div class="stat-card"><div class="stat-number text-warning"><?php echo $stats['open']; ?></div><div class="stat-label">Bekleyen</div></div>
            <div class="stat-card"><div class="stat-number text-success"><?php echo $stats['answered']; ?></div><div class="stat-label">Yanıtlanan</div></div>
        </div>

        <form class="filter-bar" method="GET">
            <input type="text" name="search" class="search-input" placeholder="Ticket ID, Konu veya Kullanıcı ara..." value="<?php echo htmlspecialchars($search_query); ?>">
            <select name="status" class="filter-select">
                <option value="all">Tüm Durumlar</option>
                <option value="open" <?php echo $status_filter == 'open' ? 'selected' : ''; ?>>Bekleyen</option>
                <option value="answered" <?php echo $status_filter == 'answered' ? 'selected' : ''; ?>>Yanıtlanan</option>
                <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>Kapalı</option>
            </select>
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filtrele</button>
        </form>

        <div class="table-container">
            <table class="custom-table">
                <colgroup>
                    <col class="w-id">
                    <col class="w-user">
                    <col class="w-subject">
                    <col class="w-category">
                    <col class="w-status">
                    <col class="w-date">
                    <col class="w-action">
                </colgroup>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kullanıcı</th>
                        <th>Konu</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th>Son Güncelleme</th>
                        <th style="text-align:right;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tickets) > 0): ?>
                        <?php foreach ($tickets as $t): ?>
                        <tr>
                            <td><span class="clickable-id" onclick="openTicketModal('<?php echo htmlspecialchars($t['ticket_id']); ?>')">#<?php echo htmlspecialchars($t['ticket_id']); ?></span></td>
                            <td><?php echo htmlspecialchars($t['username']); ?></td>
                            <td title="<?php echo htmlspecialchars($t['subject']); ?>"><?php echo htmlspecialchars($t['subject']); ?></td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($t['category'] ?? '-'); ?></td>
                            <td><span class="status-badge status-<?php echo $t['status']; ?>"><?php echo strtoupper($t['status']); ?></span></td>
                            <td style="color: var(--text-muted);"><?php echo date('d.m H:i', strtotime($t['updated_at'])); ?></td>
                            <td>
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <button type="button" class="btn-sm btn-view" onclick="openTicketModal('<?php echo htmlspecialchars($t['ticket_id']); ?>')">
                                        <i class="fas fa-eye"></i> Detay
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Silmek istediğine emin misin?');" style="margin:0;">
                                        <input type="hidden" name="action" value="delete_ticket">
                                        <input type="hidden" name="ticket_id" value="<?php echo $t['id']; ?>">
                                        <button type="submit" class="btn-sm btn-delete" style="width:35px;"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px; color: var(--text-muted);">Kayıt bulunamadı.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="ticketModal" class="modal">
        <div class="modal-content">
            <div id="modalBody" style="display:flex; flex-direction:column; height:100%;">
                <div style="flex:1; display:flex; align-items:center; justify-content:center; color:white;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openTicketModal(ticketId) {
            const modal = document.getElementById('ticketModal');
            const body = document.getElementById('modalBody');
            modal.style.display = 'flex';
            body.innerHTML = '<div style="flex:1; display:flex; align-items:center; justify-content:center; color:white;"><i class="fas fa-spinner fa-spin fa-3x"></i></div>';
            
            const formData = new FormData();
            formData.append('action', 'get_ticket_details');
            formData.append('ticket_id', ticketId);

            fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.text())
            .then(html => {
                body.innerHTML = html;
                const chatArea = document.getElementById('chatAreaScroll');
                if(chatArea) chatArea.scrollTop = chatArea.scrollHeight;
            })
            .catch(err => {
                body.innerHTML = '<div class="error-state">Bağlantı hatası oluştu.</div>';
            });
        }

        function closeModal() { document.getElementById('ticketModal').style.display = 'none'; }
        window.onclick = function(event) { if (event.target == document.getElementById('ticketModal')) closeModal(); }

        function insertTemplate(text) { document.getElementById('replyText').value = text; }

        function submitReply(e, ticketId) {
            e.preventDefault();
            const form = e.target;
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(window.location.href, { method: 'POST', body: new FormData(form) })
            .then(r => r.text())
            .then(res => {
                if(res.trim() === 'OK') openTicketModal(ticketId);
                else alert('Hata: ' + res);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            });
        }

        function closeTicket(id) {
            if(confirm('Talebi kapatmak istiyor musunuz?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="close_ticket"><input type="hidden" name="ticket_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        function reopenTicket(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="reopen_ticket"><input type="hidden" name="ticket_id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>