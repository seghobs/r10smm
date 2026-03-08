<?php
require_once 'config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS live_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        name VARCHAR(255) NOT NULL, 
        service_name VARCHAR(255) NOT NULL, 
        quantity VARCHAR(50) NOT NULL, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Add some default ones if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM live_notifications");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO live_notifications (name, service_name, quantity) VALUES 
        ('Mehmet Y.', 'TikTok Takipçi', '4100'),
        ('Ahmet K.', 'Instagram Beğeni', '2500'),
        ('Ayşe S.', 'YouTube Abone', '1000'),
        ('Fatma D.', 'Instagram Takipçi', '5000'),
        ('Caner B.', 'Twitter ReTweet', '500')");
    }
    echo "Success: Table created and seeded.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
