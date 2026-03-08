<?php
require_once 'config.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "MEVCUT TABLOLAR:\n";
echo "----------------\n";
foreach($tables as $table) {
    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    echo "- $table ($count kayıt)\n";
}

echo "\nKRİTİK ALAN KONTROLÜ:\n";
echo "---------------------\n";

// users tablosunda role/user_role kontrolü
$cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
echo "Users tablosu sütunları: " . implode(", ", $cols) . "\n";

if(in_array('user_role', $cols)) echo "[OK] users.user_role mevcut.\n";
else echo "[HATA] users.user_role EKSİK!\n";

// settings tablosu kontrolü
$settings = $pdo->query("SELECT setting_key FROM settings")->fetchAll(PDO::FETCH_COLUMN);
echo "\nAyarlar (Settings): " . implode(", ", $settings) . "\n";
