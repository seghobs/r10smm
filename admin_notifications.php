<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_notif'])) {
        $name = $_POST['name'];
        $service = $_POST['service_name'];
        $qty = $_POST['quantity'];
        
        $stmt = $pdo->prepare("INSERT INTO live_notifications (name, service_name, quantity) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $service, $qty])) {
            $success = "Bildirim başarıyla eklendi.";
        } else {
            $error = "Ekleme hatası.";
        }
    }
    
    if (isset($_POST['delete_notif'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM live_notifications WHERE id = ?");
        if ($stmt->execute([$id])) {
            $success = "Bildirim silindi.";
        }
    }
}

$notifications = $pdo->query("SELECT * FROM live_notifications ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirim Yönetimi - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8B5CF6;
            --bg-body: #020617;
            --bg-card: rgba(30, 41, 59, 0.6);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; }
        .container { max-width: 1000px; margin: 120px auto 50px; padding: 0 20px; }
        .card { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 20px; padding: 30px; margin-bottom: 30px; }
        .card-title { font-family: 'Outfit'; font-size: 1.5rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--text-muted); font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; outline: none; }
        .btn { padding: 12px 25px; border-radius: 12px; font-weight: 600; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-danger { background: #ef4444; color: white; padding: 8px 15px; font-size: 0.8rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { text-align: left; color: var(--text-muted); padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.2); }
    </style>
</head>
<body>
    <?php $current_page = 'admin_notifications.php'; include 'admin_navbar.php'; ?>

    <div class="container">
        <div class="card">
            <h2 class="card-title"><i class="fas fa-plus-circle"></i> Yeni Bildirim Ekle</h2>
            <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Kullanıcı Adı (Örn: Mehmet Y.)</label>
                        <input type="text" name="name" class="form-control" required placeholder="İsim Soyisim Baş Harfi">
                    </div>
                    <div class="form-group">
                        <label>Hizmet (Örn: TikTok Takipçi)</label>
                        <input type="text" name="service_name" class="form-control" required placeholder="Satın alınan hizmet">
                    </div>
                    <div class="form-group">
                        <label>Miktar (Örn: 4100)</label>
                        <input type="text" name="quantity" class="form-control" required placeholder="Adet">
                    </div>
                </div>
                <button type="submit" name="add_notif" class="btn btn-primary"><i class="fas fa-save"></i> Kaydet ve Yayına Al</button>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title"><i class="fas fa-list"></i> Mevcut Bildirimler</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Kullanıcı</th>
                            <th>Hizmet</th>
                            <th>Miktar</th>
                            <th>Tarih</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($notifications as $n): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($n['name']); ?></td>
                            <td><?php echo htmlspecialchars($n['service_name']); ?></td>
                            <td><?php echo htmlspecialchars($n['quantity']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($n['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
                                    <button type="submit" name="delete_notif" class="btn btn-danger" onclick="return confirm('Emin misiniz?')"><i class="fas fa-trash"></i> Sil</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
