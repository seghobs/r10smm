<?php
require_once 'config.php';
check_session();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ödeme Başarısız - <?php echo SITE_LOGO_TEXT; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #020617; color: white; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; text-align: center; }
        .box { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); padding: 40px; border-radius: 20px; max-width: 400px; box-shadow: 0 0 30px rgba(239, 68, 68, 0.2); }
        i { font-size: 4rem; color: #EF4444; margin-bottom: 20px; display: block; }
        h2 { margin: 0 0 10px; font-weight: 700; color: #EF4444; }
        p { color: #94A3B8; font-size: 0.95rem; margin-bottom: 30px; }
        a { text-decoration: none; background: rgba(255,255,255,0.1); color: white; padding: 12px 25px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2); font-weight: 600; transition: 0.3s; }
        a:hover { background: rgba(255,255,255,0.2); }
    </style>
    <script>
        setTimeout(function() { window.location.href = "balance.php"; }, 5000);
    </script>
</head>
<body>
    <div class="box">
        <i class="fas fa-times-circle"></i>
        <h2>Ödeme Başarısız</h2>
        <p>İşlem sırasında bir hata oluştu ve ödeme reddedildi. Lütfen kart limitinizi/bilgilerinizi kontrol edip tekrar deneyin. 5 saniye içinde bakiye sayfasına yönlendirileceksiniz.</p>
        <a href="balance.php">Bakiye Sayfasına Dön</a>
    </div>
</body>
</html>
