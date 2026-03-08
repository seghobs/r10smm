<?php
require_once 'config.php';
require_once 'iyzipay/IyzipayBootstrap.php';
IyzipayBootstrap::init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: balance");
    exit;
}

if (!isset($_POST['token'])) {
    header("Location: balance");
    exit;
}

$token = $_POST['token'];

// İyzico Yapılandırması
$options = new \Iyzipay\Options();
$options->setApiKey(IYZICO_API_KEY);
$options->setSecretKey(IYZICO_SECRET_KEY);
$options->setBaseUrl(IYZICO_BASE_URL);

$request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
$request->setLocale(\Iyzipay\Model\Locale::TR);
$request->setToken($token);

$checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($request, $options);

if ($checkoutForm->getStatus() == 'success' && $checkoutForm->getPaymentStatus() == 'SUCCESS') {
    
    $merchant_oid = $checkoutForm->getConversationId();
    // Eger guvenlik amaciyla odeme sonrasi tam tutar kullanilmak istenirse:
    $amount_tl = floatval($checkoutForm->getPaidPrice() ?? $checkoutForm->getPrice());

    $stmt = $pdo->prepare("SELECT * FROM payments WHERE payment_id = ? AND status = 'pending'");
    $stmt->execute([$merchant_oid]);
    $payment = $stmt->fetch();

    if ($payment) {
        $user_id = $payment['user_id'];
        
        $pdo->beginTransaction();
        try {
            // Ödemeyi onayla
            $stmt = $pdo->prepare("UPDATE payments SET status='completed', updated_at=NOW() WHERE payment_id=?");
            $stmt->execute([$merchant_oid]);

            // Bakiyeyi ekle
            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            // Kullanicinin odedigi miktar payment'taki miktar
            $stmt->execute([$payment['amount'], $user_id]);

            $pdo->commit();
            header("Location: paytr_success"); // Paytr logoyu reuse edebilir veya genel bir success sayfası kullanılabilir.
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            header("Location: balance");
            exit;
        }
    } else {
        header("Location: balance");
        exit;
    }
} else {
    // İşlem başarısız
    $merchant_oid = $checkoutForm->getConversationId();
    
    if ($merchant_oid) {
        $stmt = $pdo->prepare("UPDATE payments SET status='failed', reject_reason=?, updated_at=NOW() WHERE payment_id=?");
        $stmt->execute([substr($checkoutForm->getErrorMessage(), 0, 250), $merchant_oid]);
    }
    
    header("Location: paytr_fail");
    exit;
}
