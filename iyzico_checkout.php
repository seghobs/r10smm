<?php
require_once 'config.php';
require_once 'iyzipay/IyzipayBootstrap.php';
IyzipayBootstrap::init();
check_session();

if (!isset($_REQUEST['amount']) || floatval($_REQUEST['amount']) < 75) {
    header('Location: balance');
    exit;
}

$user_id = $_SESSION['user_id'];
$amount_tl = floatval($_REQUEST['amount']);
$payment_amount_str = number_format($amount_tl, 2, '.', '');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

if (empty(IYZICO_API_KEY) || empty(IYZICO_SECRET_KEY)) {
    die("Iyzico API ayarları yapılandırılmamış. Lütfen Admin panelinden ayarları güncelleyin.");
}

$merchant_oid = 'IYZ' . $user_id . time() . rand(100,999);
$email = !empty($user['email']) ? $user['email'] : 'test@example.com';
$user_name = !empty($user['username']) ? $user['username'] : 'Müşteri';
$user_phone = !empty($user['phone']) ? $user['phone'] : '+905555555555';

// İyzico Yapılandırması
$options = new \Iyzipay\Options();
$options->setApiKey(IYZICO_API_KEY);
$options->setSecretKey(IYZICO_SECRET_KEY);
$options->setBaseUrl(IYZICO_BASE_URL);

$request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
$request->setLocale(\Iyzipay\Model\Locale::TR);
$request->setConversationId($merchant_oid);
$request->setPrice($payment_amount_str);
$request->setPaidPrice($payment_amount_str);
$request->setCurrency(\Iyzipay\Model\Currency::TL);
$request->setBasketId("BASKET_" . $merchant_oid);
$request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
$request->setCallbackUrl(SITE_URL . "/iyzico_callback");
$request->setEnabledInstallments(array(2, 3, 6, 9, 12));

// Alıcı Bilgileri
$buyer = new \Iyzipay\Model\Buyer();
$buyer->setId((string) $user_id);
$buyer->setName($user_name);
$buyer->setSurname($user_name);
$buyer->setGsmNumber($user_phone);
$buyer->setEmail($email);
$buyer->setIdentityNumber("11111111111"); // Iyzico zorunlu tutabiliyor
$buyer->setRegistrationAddress("Belirtilmedi");
$buyer->setIp(get_client_ip());
$buyer->setCity("Istanbul");
$buyer->setCountry("Turkey");
$buyer->setZipCode("34000");
$request->setBuyer($buyer);

// Fatura Adresi
$billingAddress = new \Iyzipay\Model\Address();
$billingAddress->setContactName($user_name);
$billingAddress->setCity("Istanbul");
$billingAddress->setCountry("Turkey");
$billingAddress->setAddress("Belirtilmedi");
$billingAddress->setZipCode("34000");
$request->setBillingAddress($billingAddress);

// Teslimat Adresi
$shippingAddress = new \Iyzipay\Model\Address();
$shippingAddress->setContactName($user_name);
$shippingAddress->setCity("Istanbul");
$shippingAddress->setCountry("Turkey");
$shippingAddress->setAddress("Belirtilmedi");
$shippingAddress->setZipCode("34000");
$request->setShippingAddress($shippingAddress);

// Sepet İçeriği
$basketItems = array();
$firstBasketItem = new \Iyzipay\Model\BasketItem();
$firstBasketItem->setId("ITEM_1");
$firstBasketItem->setName("Bakiye Yukleme - " . $payment_amount_str . " TL");
$firstBasketItem->setCategory1("Bakiye");
$firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::VIRTUAL);
$firstBasketItem->setPrice($payment_amount_str);
$basketItems[0] = $firstBasketItem;

$request->setBasketItems($basketItems);

// İsteği gönder
$checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, $options);

if ($checkoutFormInitialize->getStatus() == 'success') {
    // Bekleyen ödemeyi veritabanına ekle
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, payment_id, amount, payment_method, status, created_at) VALUES (?, ?, ?, 'iyzico', 'pending', NOW())");
    $stmt->execute([$user_id, $merchant_oid, $amount_tl]);
    
    $paymentPageUrl = $checkoutFormInitialize->getPaymentPageUrl();
} else {
    die("IYZICO HATA DÖNDÜRDÜ: " . $checkoutFormInitialize->getErrorMessage() . " (Lütfen API bilgilerinizi kontrol edin)");
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İyzico Güvenli Ödeme</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #020617; color: white; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; margin: 0; padding-top: 40px; }
        .payment-box { width: 100%; max-width: 800px; background: rgba(30, 41, 59, 0.6); padding: 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .back-btn { display: inline-flex; align-items: center; gap: 8px; color: #94A3B8; text-decoration: none; margin-bottom: 20px; transition: 0.3s; }
        .back-btn:hover { color: white; }
    </style>
</head>
<body>

    <div class="payment-box">
        <a href="balance" class="back-btn"><i class="fas fa-arrow-left"></i> Önceki Sayfaya Dön</a>
        <h3 style="text-align: center; margin-bottom: 20px;">Iyzico Güvenli Kredi Kartı Ödemesi</h3>
        
        <!-- Iyzico Responsive Page -->
        <iframe src="<?php echo $paymentPageUrl; ?>" id="iyzicoiframe" frameborder="0" scrolling="yes" style="width: 100%; height: 600px; border-radius: 12px; background: white;"></iframe>
    </div>

</body>
</html>
