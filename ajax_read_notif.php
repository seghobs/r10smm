<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo 'error';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'read_notifications') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    if ($stmt->execute([$_SESSION['user_id']])) {
        echo 'ok';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
?>
