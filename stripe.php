<?php
// Basit Stripe Checkout için
function createStripeCheckout($amount, $user_id, $username, $email) {
    $stripe_secret_key = 'YOUR_STRIPE_SECRET_KEY';
    
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.stripe.com/v1/checkout/sessions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $stripe_secret_key,
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_POSTFIELDS => http_build_query([
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => 'try',
            'line_items[0][price_data][product_data][name]' => SITE_LOGO_TEXT . ' SMM Panel Bakiye Yükleme',
            'line_items[0][price_data][product_data][description]' => $username . ' için bakiye yükleme',
            'line_items[0][price_data][unit_amount]' => $amount * 100,
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/balance.php?success=true&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/balance.php?canceled=true',
            'customer_email' => $email,
            'metadata[user_id]' => $user_id,
            'metadata[username]' => $username
        ])
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}
?>