<?php
require_once 'config.php';

$pages = [
    'index.php',
    'about.html' => 'about.php',
    'contact.php',
    'faq.html' => 'faq.php',
    'tos.html' => 'tos.php',
    'privacy.html' => 'privacy.php',
    'refund.html' => 'refund.php',
    'register.php',
    'login.php',
    'services.php'
];

foreach ($pages as $old => $new) {
    if (is_int($old)) $old = $new;
    
    if (!file_exists($old)) {
        echo "File not found: $old\n";
        continue;
    }

    $content = file_get_contents($old);
    
    // 1. Inject PHP header if needed
    if (strpos($content, '<?php') === false || strpos($content, '<?php') > 50) {
        $content = "<?php\nrequire_once 'config.php';\nsession_start();\n?>\n" . $content;
    } else {
        // Ensure config is there
        if (strpos($content, "require_once 'config.php'") === false && strpos($content, "include 'config.php'") === false) {
            $content = preg_replace('/<\?php\s*/', "<?php\nrequire_once 'config.php';\n", $content, 1);
        }
    }

    // 2. Standardize Title (Optional, keeping page-specific titles)

    // 3. Replace Style block with home_styles.php
    // We look for <style> or specific vars
    $content = preg_replace('/<style>.*?<\/style>/is', "<?php include 'home_styles.php'; ?>", $content);
    
    // 4. Replace Background Glow
    $content = preg_replace('/<div class="background-glow">.*?<\/div>\s*<\/div>/is', "", $content); // Try to remove old one if it has 3 inner divs
    $content = preg_replace('/<div class="background-glow">.*?<\/div>/is', "", $content);

    // 5. Replace Navbar
    $content = preg_replace('/<nav class="navbar".*?<\/nav>/is', "<?php include 'home_navbar.php'; ?>", $content);

    // 6. Replace Footer
    $content = preg_replace('/<footer class="footer".*?<\/footer>/is', "<?php include 'home_footer.php'; ?>", $content);

    // 7. Remove redundant JS for navbar
    $content = preg_replace('/const menuToggle = document\.getElementById\(\'menuToggle\'\);.*?window\.addEventListener\(\'scroll\', \(\) => \{[^}]*\}\);/is', '', $content);
    $content = preg_replace('/<script>\s*const menuToggle = document\.getElementById\(\'menuToggle\'\);.*?<\/script>/is', '', $content);

    // 8. Fix .html links to .php links in content
    $content = str_replace(['about.html', 'faq.html', 'tos.html', 'privacy.html', 'refund.html'], ['about.php', 'faq.php', 'tos.php', 'privacy.php', 'refund.php'], $content);

    // Save
    file_put_contents($new, $content);
    if ($old !== $new) {
        // unlink($old); // Keep old for now or delete/rename? Renaming is better.
        rename($old, $old . '.bak');
    }
    echo "Processed: $new\n";
}
echo "Standardization complete!\n";
?>
