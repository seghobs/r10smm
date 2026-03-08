<?php
require 'config.php';
try {
    $r = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    print_r($r);
} catch (Exception $e) {
    echo $e->getMessage();
}
