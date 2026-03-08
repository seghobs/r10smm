<?php
$files = ['dashboard.php', 'settings.php', 'orders.php', 'services.php', 'support.php', 'balance.php'];
foreach ($files as $f) {
    if(file_exists($f)) {
        $c = file_get_contents($f);
        
        $c = preg_replace('/function markAllRead\([^)]*\)\s*\{(?:[^{}]*|(?R))*\}/s', '', $c);
        $c = preg_replace('/function markAllRead\([^\)]*\)\s*\{[^\}]+\}/s', '', $c);
        
        $c = preg_replace('/document\.addEventListener\(\'click\',\s*function\(e\)\s*\{.*?\n        \}\);/s', '', $c);
        $c = preg_replace('/document\.addEventListener\(\'click\',\s*function\(e\)\s*\{(?:[^{}]*|(?R))*\}\);/s', '', $c);

        $c = preg_replace('/function toggleNotifications\(\)\s*\{(?:[^{}]*|(?R))*\}/s', '', $c);

        file_put_contents($f, $c);
        echo "Cleaned JS in $f\n";
    }
}
