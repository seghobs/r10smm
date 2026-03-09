<?php
ob_start();
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Balance and Role
$_SESSION['user_role'] = $user['user_role'];
$_SESSION['balance'] = $user['balance'];

// Fetch all active services
$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services WHERE status = 'active' ORDER BY category ASC, price ASC");
    $db_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($db_services as $svc) {
        $category_name = $svc['category'] ?: 'Diğer';
        $cat_lower = strtolower($category_name);
        $group = 'Diğer';
        
        if (strpos($cat_lower, 'instagram') !== false || strpos($cat_lower, 'ig') !== false) $group = 'Instagram';
        elseif (strpos($cat_lower, 'tiktok') !== false) $group = 'TikTok';
        elseif (strpos($cat_lower, 'youtube') !== false) $group = 'YouTube';
        elseif (strpos($cat_lower, 'twitter') !== false || strpos($cat_lower, 'x.com') !== false) $group = 'Twitter';
        elseif (strpos($cat_lower, 'facebook') !== false) $group = 'Facebook';
        elseif (strpos($cat_lower, 'spotify') !== false) $group = 'Spotify';
        elseif (strpos($cat_lower, 'telegram') !== false) $group = 'Telegram';
        elseif (strpos($cat_lower, 'twitch') !== false) $group = 'Twitch';
        elseif (strpos($cat_lower, 'linkedin') !== false) $group = 'LinkedIn';
        elseif (strpos($cat_lower, 'discord') !== false) $group = 'Discord';
        elseif (strpos($cat_lower, 'reddit') !== false) $group = 'Reddit';
        elseif (strpos($cat_lower, 'kick') !== false) $group = 'Kick';

        $services[] = [
            'id' => $svc['id'],
            'api_service_id' => $svc['api_service_id'],
            'name' => $svc['name'],
            'category' => $category_name,
            'group' => $group,
            'price' => round(floatval($svc['price']), 4),
            'min' => max(intval($svc['min_quantity']), 1),
            'max' => intval($svc['max_quantity']),
            'description' => $svc['description'] ?: 'Açıklama bulunmuyor.'
        ];
    }
} catch (Exception $e) {}

$json_services = json_encode($services, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

$groups = ['Hepsi', 'Instagram', 'TikTok', 'YouTube', 'Twitter', 'Facebook', 'Spotify', 'Telegram', 'Twitch', 'LinkedIn', 'Discord', 'Reddit', 'Kick', 'Diğer'];

// Handle potential errors/success from create_order.php return
$error_msg = '';
$success_msg = '';
if (isset($_SESSION['error'])) {
    $error_msg = $_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    $success_msg = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Sipariş - <?php echo SITE_LOGO_TEXT; ?> SMM Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8B5CF6;
            --primary-dark: #7C3AED;
            --secondary: #10B981;
            --accent: #F59E0B;
            --danger: #EF4444;
            --bg-body: #020617;
            --bg-card: rgba(30, 41, 59, 0.6);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --gradient-main: linear-gradient(135deg, #8B5CF6 0%, #4F46E5 100%);
            --gradient-card: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(15, 23, 42, 0.4) 100%);
            --glass-border: 1px solid rgba(255, 255, 255, 0.08);
            --glow: 0 0 30px rgba(139, 92, 246, 0.3);
            --radius: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); background: var(--bg-body); line-height: 1.6; overflow-x: hidden; }

        .background-glow { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden; pointer-events: none; }
        .glow-blob { position: absolute; filter: blur(90px); opacity: 0.3; border-radius: 50%; animation: float 15s infinite alternate ease-in-out; }
        .blob-1 { top: -10%; left: -10%; width: 600px; height: 600px; background: var(--primary); }
        .blob-2 { bottom: 10%; right: -10%; width: 500px; height: 500px; background: #059669; animation-delay: -5s; }
        @keyframes float { 0% { transform: translate(0, 0) scale(1); } 100% { transform: translate(40px, 40px) scale(1.05); } }

        .main-content { padding: 120px 0 60px; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; padding: 0 20px; }

        .header-title { font-family: 'Outfit', sans-serif; font-size: 2.2rem; font-weight: 700; margin-bottom: 20px; text-align: center; }
        .header-title span { background: var(--gradient-main); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .alert-box { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem; border: var(--glass-border); animation: slideDown 0.3s ease; }
        .alert-error { background: rgba(239, 68, 68, 0.1); color: #EF4444; border-left: 4px solid #EF4444; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #10B981; border-left: 4px solid #10B981; }

        /* The Order Container */
        .order-container { background: var(--bg-card); backdrop-filter: blur(15px); border: var(--glass-border); border-radius: 24px; padding: 30px; }
        
        /* Platform Tabs */
        .platform-tabs { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 25px; }
        .platform-tab {
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); color: var(--text-muted);
            padding: 10px 18px; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 0.9rem;
            transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px;
        }
        .platform-tab:hover { background: rgba(255,255,255,0.1); color: white; transform: translateY(-2px); }
        .platform-tab.active { background: rgba(139, 92, 246, 0.15); border-color: var(--primary); color: white; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2); }
        
        .platform-tab i.fa-instagram { color: #E1306C; }
        .platform-tab i.fa-tiktok { color: #FFFFFF; }
        .platform-tab i.fa-youtube { color: #FF0000; }
        .platform-tab i.fa-twitter { color: #1DA1F2; }
        .platform-tab i.fa-facebook { color: #1877F2; }
        .platform-tab i.fa-spotify { color: #1DB954; }
        .platform-tab i.fa-telegram { color: #0088cc; }
        .platform-tab i.fa-twitch { color: #6441a5; }

        /* Form Controls */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem; }
        
        .search-wrap { position: relative; margin-bottom: 25px; }
        .search-wrap i { position: absolute; left: 15px; top: 15px; color: var(--text-muted); }
        .search-input { width: 100%; padding: 12px 15px 12px 40px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; font-size: 1rem; transition: 0.3s; }
        .search-input:focus { border-color: var(--primary); outline: none; background: rgba(255,255,255,0.05); }

        select.form-control, input.form-control {
            width: 100%; padding: 14px 15px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px; color: white; font-size: 0.95rem; font-family: 'Plus Jakarta Sans', sans-serif;
            appearance: none; transition: 0.3s;
        }
        select.form-control:focus, input.form-control:focus { border-color: var(--primary); outline: none; background: rgba(255,255,255,0.05); }
        select.form-control option { background: #0f172a; color: white; padding: 10px; }
        
        .select-wrapper { position: relative; }
        .select-wrapper::after { content: '\f107'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 15px; top: 14px; color: var(--text-muted); pointer-events: none; }

        /* Description Box */
        .desc-box { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; margin-bottom: 20px; font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; }
        .desc-title { font-weight: 700; color: white; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .desc-title i { color: var(--accent); }

        /* Price Calculation */
        .price-box { display: flex; justify-content: space-between; align-items: center; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; }
        .price-label { color: var(--text-muted); font-weight: 600; }
        .price-amount { font-family: 'Outfit', sans-serif; font-size: 1.8rem; font-weight: 800; color: #10B981; }

        .btn-order { background: var(--gradient-main); color: white; border: none; width: 100%; padding: 16px; border-radius: 14px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s; box-shadow: var(--glow); display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-order:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(139, 92, 246, 0.5); }
        .btn-order:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

        .service-meta { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        .meta-badge { background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); padding: 5px 10px; border-radius: 8px; font-size: 0.75rem; color: #cbd5e1; display: flex; align-items: center; gap: 5px; }

        @media (max-width: 768px) {
            .order-container { padding: 20px; }
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="background-glow">
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>
    </div>

    <?php include 'user_navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <h1 class="header-title">Yeni <span>Sipariş</span></h1>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-box alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_msg)): ?>
                <div class="alert-box alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <div class="order-container">
                <!-- Platform Tabs -->
                <div class="platform-tabs" id="platformTabs">
                    <?php foreach($groups as $grp): 
                        $icon = 'fas fa-layer-group';
                        $grpLower = strtolower($grp);
                        if($grpLower == 'instagram') $icon = 'fab fa-instagram';
                        elseif($grpLower == 'tiktok') $icon = 'fab fa-tiktok';
                        elseif($grpLower == 'youtube') $icon = 'fab fa-youtube';
                        elseif($grpLower == 'twitter') $icon = 'fab fa-twitter';
                        elseif($grpLower == 'facebook') $icon = 'fab fa-facebook';
                        elseif($grpLower == 'spotify') $icon = 'fab fa-spotify';
                        elseif($grpLower == 'telegram') $icon = 'fab fa-telegram';
                        elseif($grpLower == 'twitch') $icon = 'fab fa-twitch';
                        elseif($grpLower == 'linkedin') $icon = 'fab fa-linkedin';
                        elseif($grpLower == 'discord') $icon = 'fab fa-discord';
                        elseif($grpLower == 'reddit') $icon = 'fab fa-reddit';
                    ?>
                        <div class="platform-tab <?php echo $grp == 'Hepsi' ? 'active' : ''; ?>" data-platform="<?php echo htmlspecialchars($grp); ?>">
                            <i class="<?php echo $icon; ?>"></i> <?php echo htmlspecialchars($grp); ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Search -->
                <div class="search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Servis veya kategori ara...">
                </div>

                <form id="orderForm" action="create_order" method="POST">
                    
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <div class="select-wrapper">
                            <select id="categorySelect" class="form-control">
                                <option value="">Kategori Seçin</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Servis</label>
                        <div class="select-wrapper">
                            <select id="serviceSelect" class="form-control" name="service" required>
                                <option value="">Servis Seçin</option>
                            </select>
                        </div>
                        
                        <!-- Hidden inputs for backend -->
                        <input type="hidden" id="serviceDbId" name="service_db_id">
                        <input type="hidden" id="serviceNameInput" name="service_name">
                        <input type="hidden" id="categoryNameInput" name="service_category">
                        <input type="hidden" id="pricePer1000Input" name="price_per_1000">
                    </div>

                    <!-- Service Description Box -->
                    <div class="desc-box" id="descBox" style="display:none;">
                        <div class="desc-title"><i class="fas fa-info-circle"></i> Servis Açıklaması</div>
                        <div id="descContent"></div>
                        <div class="service-meta" id="serviceMeta">
                            <!-- populated by js -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Link</label>
                        <input type="text" name="link" id="linkInput" class="form-control" placeholder="https://... veya @username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Miktar</label>
                        <input type="number" name="quantity" id="quantityInput" class="form-control" placeholder="0" required>
                    </div>

                    <div class="price-box">
                        <div class="price-label">Toplam Tutar:</div>
                        <div class="price-amount" id="totalPriceDisplay">₺0.00</div>
                    </div>

                    <button type="submit" class="btn-order" id="submitBtn" disabled>
                        <i class="fas fa-rocket"></i> Sipariş Ver
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
        const servicesData = <?php echo $json_services; ?>;
        
        let currentPlatform = 'Hepsi';
        let filteredServices = [];

        const platformTabs = document.querySelectorAll('.platform-tab');
        const searchInput = document.getElementById('searchInput');
        const categorySelect = document.getElementById('categorySelect');
        const serviceSelect = document.getElementById('serviceSelect');
        const quantityInput = document.getElementById('quantityInput');
        
        const descBox = document.getElementById('descBox');
        const descContent = document.getElementById('descContent');
        const serviceMeta = document.getElementById('serviceMeta');
        const totalPriceDisplay = document.getElementById('totalPriceDisplay');
        const submitBtn = document.getElementById('submitBtn');

        // Initial Load
        updateFilters();

        // Platform Tab Clicks
        platformTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                platformTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentPlatform = tab.getAttribute('data-platform');
                updateFilters();
            });
        });

        // Search Input
        searchInput.addEventListener('input', () => {
            updateFilters();
        });

        // Category Change
        categorySelect.addEventListener('change', () => {
            populateServices();
        });

        // Service Change
        serviceSelect.addEventListener('change', () => {
            onServiceSelected();
            calculatePrice();
        });

        // Quantity Change
        quantityInput.addEventListener('input', () => {
            calculatePrice();
        });

        function updateFilters() {
            const query = searchInput.value.toLowerCase();
            
            // Filter by Platform
            let platformFiltered = servicesData;
            if (currentPlatform !== 'Hepsi') {
                platformFiltered = servicesData.filter(s => s.group === currentPlatform);
            }

            // Filter by Search Query
            if (query) {
                filteredServices = platformFiltered.filter(s => 
                    s.name.toLowerCase().includes(query) || 
                    s.category.toLowerCase().includes(query)
                );
            } else {
                filteredServices = platformFiltered;
            }

            populateCategories();
        }

        function populateCategories() {
            // Get unique categories from filteredServices
            const uniqueCats = [...new Set(filteredServices.map(s => s.category))];
            
            // Save selected category if valid
            const selectedCat = categorySelect.value;
            
            categorySelect.innerHTML = '<option value="">Kategori Seçin</option>';
            uniqueCats.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat;
                opt.textContent = cat;
                categorySelect.appendChild(opt);
            });

            if (selectedCat && uniqueCats.includes(selectedCat)) {
                categorySelect.value = selectedCat;
            } else if (uniqueCats.length > 0) {
                // Auto select first if searching
                categorySelect.selectedIndex = 1;
            }
            
            populateServices();
        }

        function populateServices() {
            const selectedCat = categorySelect.value;
            
            // If a category is selected, filter by it. Otherwise, show all filteredServices.
            let srvs = filteredServices;
            if (selectedCat) {
                srvs = filteredServices.filter(s => s.category === selectedCat);
            }
            
            serviceSelect.innerHTML = '<option value="">Servis Seçin</option>';
            srvs.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.api_service_id || s.id; // external api id or db id fallback
                opt.setAttribute('data-db-id', s.id); // save local id here
                opt.textContent = `${s.id} - ${s.name} - ₺${s.price.toFixed(2)}`;
                serviceSelect.appendChild(opt);
            });

            if (srvs.length > 0) {
                serviceSelect.selectedIndex = 1;
            }
            
            onServiceSelected();
        }

        function onServiceSelected() {
            const sid = serviceSelect.options[serviceSelect.selectedIndex]?.dataset.dbId;
            if (!sid) {
                descBox.style.display = 'none';
                resetHiddenInputs();
                submitBtn.disabled = true;
                return;
            }

            const activeService = servicesData.find(s => String(s.id) === String(sid));
            if (activeService) {
                // Populate hidden inputs for form submission
                document.getElementById('serviceDbId').value = activeService.id;
                document.getElementById('serviceNameInput').value = activeService.name;
                document.getElementById('categoryNameInput').value = activeService.category;
                document.getElementById('pricePer1000Input').value = activeService.price;
                
                // Since this uses API, the actual service value in option or hidden might be the external API ID.
                // In create_order.php it checks $_POST['service'] which is serviceSelect.value (set to db_id here), 
                // and it resolves api_service_id in backend. 
                // Therefore, setting DB ID as the `value` of `serviceSelect` is optimal since create_order.php accepts `service_db_id` as well.
                
                // To be exact to create_order.php logic: 'service' field traditionally held api_id. 
                // Wait, create_order.php uses: $service_api_id = $_POST['service']; $service_db_id = $_POST['service_db_id'];
                
                // Show desc box
                descBox.style.display = 'block';
                
                // Format description (preserve line breaks)
                descContent.innerHTML = (activeService.description || '').replace(/\n/g, '<br>');

                // Show metadata badges
                serviceMeta.innerHTML = `
                    <div class="meta-badge"><i class="fas fa-coins"></i> ₺${activeService.price.toFixed(2)} / 1000</div>
                    <div class="meta-badge"><i class="fas fa-arrow-down"></i> Min: ${activeService.min}</div>
                    <div class="meta-badge"><i class="fas fa-arrow-up"></i> Max: ${activeService.max}</div>
                `;

                // Update min/max input rules
                quantityInput.min = activeService.min;
                quantityInput.max = activeService.max;
                
                if (!quantityInput.value || quantityInput.value < activeService.min) {
                    quantityInput.value = activeService.min;
                }

                submitBtn.disabled = false;
                calculatePrice();
            }
        }

        function resetHiddenInputs() {
            document.getElementById('serviceDbId').value = '';
            document.getElementById('serviceNameInput').value = '';
            document.getElementById('categoryNameInput').value = '';
            document.getElementById('pricePer1000Input').value = '';
            totalPriceDisplay.textContent = '₺0.00';
            quantityInput.value = '';
        }

        function calculatePrice() {
            const selId = serviceSelect.options[serviceSelect.selectedIndex]?.dataset.dbId;
            if(!selId) {
                totalPriceDisplay.textContent = '₺0.00';
                return;
            }
            
            const s = servicesData.find(x => String(x.id) === String(selId));
            const q = parseInt(quantityInput.value) || 0;
            
            if(s && q > 0) {
                const total = (q / 1000) * s.price;
                totalPriceDisplay.textContent = '₺' + total.toFixed(4);
            } else {
                totalPriceDisplay.textContent = '₺0.00';
            }
        }
    </script>
</body>
</html>
