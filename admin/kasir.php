<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$categories = getCategories();
$menuItems = getMenuItems();

// Payment Logic
$showSuccessModal = false;
$totalPaid = 0;
$customerName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $cart = json_decode($_POST['cart'], true);
    $customerName = trim($_POST['customer_name'] ?? '');
    if (!empty($cart)) {
        $totalAmount = 0;
        foreach ($cart as $item) $totalAmount += $item['price'] * $item['qty'];
        
        $stmt = $pdo->prepare("INSERT INTO transactions (total_amount, customer_name, payment_method) VALUES (?, ?, 'cash')");
        $stmt->execute([$totalAmount, $customerName ?: null]);
        $transactionId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, menu_item_id, item_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($cart as $item) {
            $stmt->execute([$transactionId, $item['id'], $item['name'], $item['qty'], $item['price'], $item['price'] * $item['qty']]);
        }
        
        foreach ($cart as $item) {
            $menuId = $item['id'];
            $qtySold = $item['qty'];
            $stmtDeduct = $pdo->prepare("UPDATE menu_items SET stock = GREATEST(0, stock - ?) WHERE id = ? AND stock IS NOT NULL");
            $stmtDeduct->execute([$qtySold, $menuId]);
        }
        
        $showSuccessModal = true;
        $totalPaid = $totalAmount;
    }
}

$categoryIcons = [
    'Es' => 'fa-snowflake', 'Boba' => 'fa-mug-hot', 'Nutrisari' => 'fa-glass-water',
    'Marjan Squash' => 'fa-glass-citrus', 'Mojito' => 'fa-martini-glass-citrus',
    'Teh' => 'fa-leaf', 'Kopi' => 'fa-mug-saucer', 'Soda Gembira' => 'fa-champagne-glasses',
    'Cemilan' => 'fa-burger', 'Dimsum' => 'fa-bowl-food'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include '../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Hoki Admin - Terminal Kasir Digital</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@700;900&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" as="style">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2.5">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@700;900&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" as="style">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2.5">
    <style>
        body { background-color: var(--dark-deep); color: var(--white); overflow-x: hidden; }
        .app-container { background: var(--dark-deep) !important; } /* Force dark background */
        
        .ultra-bg {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: radial-gradient(circle at 100% 0%, rgba(181, 141, 61, 0.03) 0%, transparent 50%),
                        radial-gradient(circle at 0% 100%, rgba(181, 141, 61, 0.02) 0%, transparent 50%);
        }


        .search-area-luxury {
            padding: 10px 20px 15px;
        }
        .luxury-input-box {
            display: flex;
            align-items: center;
            height: 38px;
            background: rgba(18, 18, 20, 0.8) !important;
            border: 1px solid rgba(181, 141, 61, 0.25);
            border-radius: 100px;
            padding: 0 15px;
            transition: all 0.3s ease;
        }
        .luxury-input-box:focus-within { 
            background: rgba(22, 22, 24, 1) !important;
            border-color: var(--gold-luxury);
            box-shadow: 0 0 20px rgba(181, 141, 61, 0.1);
        }
        .luxury-input-box i { 
            color: var(--gold-luxury); 
            font-size: 0.8rem;
            opacity: 0.6;
            margin-right: 12px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            height: 100%;
        }
        .luxury-input-box input { 
            flex: 1;
            background: transparent !important; 
            border: none !important; 
            color: white !important; 
            font-family: inherit; 
            font-size: 0.8rem; 
            font-weight: 600; 
            letter-spacing: 0.3px;
            outline: none !important;
            padding: 0;
            margin: 0;
            height: 38px;
            line-height: 38px;
            display: block;
            box-shadow: none !important;
        }
        .luxury-input-box input::placeholder { color: rgba(255,255,255,0.25); font-weight: 400; }
        .tab-scroller-luxury {
            padding: 0 25px 20px;
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .tab-scroller-luxury::-webkit-scrollbar { display: none; }
        
        .tab-capsule {
            flex-shrink: 0;
            padding: 10px 18px;
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tab-capsule.active { background: var(--gold-luxury); border-color: var(--gold-bright); color: var(--dark-deep); }
        .tab-capsule span { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .tab-capsule.active span { color: var(--dark-deep); }
        .tab-capsule .item-count { font-size: 0.55rem; background: rgba(0,0,0,0.1); padding: 2px 5px; border-radius: 5px; font-weight: 900; }
        .tab-capsule:not(.active) .item-count { color: var(--gold-luxury); background: rgba(181, 141, 61, 0.1); }
        
        .btn-remove-tab {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.55rem;
            background: rgba(255,255,255,0.05);
            color: var(--white-mute);
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: 2px;
        }
        .btn-remove-tab:hover { background: #ef4444; color: white; }
        .tab-capsule.active .btn-remove-tab { background: rgba(0,0,0,0.1); color: var(--dark-deep); }
        .tab-capsule.active .btn-remove-tab:hover { background: #dc2626; color: white; }

        .category-ribbon {
            padding: 0 25px 25px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .category-ribbon::-webkit-scrollbar { display: none; }
        .cat-luxury-btn {
            flex-shrink: 0;
            padding: 6px 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: 800;
            color: rgba(255,255,255,0.5); /* Stronger contrast */
            text-transform: uppercase;
            letter-spacing: 1.2px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .cat-luxury-btn.active { color: var(--gold-bright); border-color: var(--gold-luxury); background: rgba(181, 141, 61, 0.05); }

        .menu-luxury-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            padding: 0 20px 160px;
        }
        .product-card-luxury {
            background: var(--dark-surface);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
            position: relative;
        }
        .product-card-luxury:active { transform: scale(0.94); border-color: var(--gold-luxury); }
        .product-card-luxury.disabled { opacity: 0.3; filter: grayscale(1); pointer-events: none; }
        
        .product-media {
            height: 80px;
            background-size: cover;
            background-position: center;
            background-color: var(--dark-elevated);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .product-media i { font-size: 1.5rem; color: var(--gold-luxury); opacity: 0.2; }
        
        .stock-pill {
            position: absolute;
            top: 6px;
            right: 6px;
            padding: 3px 8px;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 7px;
            font-size: 0.5rem;
            font-weight: 950;
            color: var(--white);
            text-transform: uppercase;
        }
        .stock-pill.danger { color: #f87171; border-color: rgba(239, 68, 68, 0.3); }

        .product-content { padding: 10px; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .product-content h4 { font-size: 0.65rem; font-weight: 800; color: white; margin-bottom: 3px; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .product-content .price-tag { font-size: 0.75rem; font-weight: 900; color: var(--gold-bright); letter-spacing: -0.2px; }

        /* Floating Total Bar Bespoke */
        .dock-summary-luxury {
            position: fixed;
            bottom: 110px;
            left: 20px;
            right: 20px;
            height: 64px;
            background: rgba(22, 22, 24, 0.98);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(181, 141, 61, 0.4);
            border-radius: 20px;
            display: flex;
            align-items: center;
            padding: 0 15px;
            gap: 12px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.5);
            z-index: 900;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .dock-icon-box {
            width: 40px;
            height: 40px;
            background: var(--gold-luxury);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-deep);
            font-size: 1rem;
        }

        /* Bottom Navigation (From Dashboard) */
        .dock-text { flex: 1; }
        .dock-text .lbl { font-size: 0.45rem; font-weight: 900; color: var(--white-mute); text-transform: uppercase; letter-spacing: 1px; opacity: 0.6; }
        .dock-text .tot { font-size: 0.95rem; font-weight: 950; color: white; display: block; line-height: 1; }
        .btn-view-cart {
            background: var(--dark-elevated);
            border: 1px solid rgba(181, 141, 61, 0.5);
            color: var(--gold-bright);
            padding: 10px 15px;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Modal & Sheet System */
        .modal-premium-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 2000;
            display: flex;
            align-items: flex-end;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modal-premium-overlay.active { opacity: 1; visibility: visible; }
        
        .modal-premium {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            transform: translateY(100%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 90vh;
            overflow-y: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }
        .modal-premium-overlay.active .modal-premium { transform: translateY(0); }
        .modal-premium::-webkit-scrollbar { display: none; }

        .luxury-sheet {
            background: var(--dark-deep);
            border-radius: 40px 40px 0 0;
            padding: 30px 25px 80px;
            border-top: 1px solid var(--glass-border);
            box-shadow: 0 -20px 40px rgba(0,0,0,0.5);
        }
        .order-item-luxury {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid var(--glass-border);
        }
        .order-item-meta { flex: 1; }
        .order-item-meta h5 { font-size: 1rem; font-weight: 800; margin-bottom: 4px; color: white; }
        .order-item-meta span { font-size: 0.85rem; color: var(--gold-luxury); font-weight: 750; }
        
        .luxury-qty {
            display: flex;
            align-items: center;
            gap: 18px;
            background: var(--dark-surface);
            padding: 10px 15px;
            border-radius: 18px;
            border: 1px solid var(--glass-border);
        }
        .luxury-qty button { background: transparent; border: none; color: var(--gold-bright); font-size: 1.1rem; cursor: pointer; }
        .luxury-qty .num { font-weight: 950; font-size: 1.1rem; min-width: 25px; text-align: center; }

        .bill-panel-luxury {
            background: linear-gradient(135deg, rgba(181, 141, 61, 0.08) 0%, rgba(181, 141, 61, 0.02) 100%);
            border: 1px solid rgba(181, 141, 61, 0.2);
            border-radius: 30px;
            padding: 30px 25px;
            margin: 30px 0;
            position: relative;
            overflow: hidden;
        }
        .bill-panel-luxury::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(181, 141, 61, 0.3), transparent);
        }
        .bill-panel-luxury .lbl { 
            font-size: 0.65rem; 
            font-weight: 900; 
            color: var(--white-mute); 
            text-transform: uppercase; 
            letter-spacing: 2px;
            margin-bottom: 10px;
            display: block;
            text-align: center;
        }
        .bill-total-wrap {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 8px;
        }
        .bill-panel-luxury .unit { 
            font-size: 1.2rem; 
            font-weight: 950; 
            color: white; 
            opacity: 0.9;
        }
        .bill-panel-luxury .val { 
            font-size: 2.8rem; 
            font-weight: 950; 
            color: #fff; 
            letter-spacing: -2px;
            line-height: 1;
        }

        /* Payment Precision */
        .quick-denoms { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 25px; }
        .denom-btn {
            background: var(--dark-surface);
            border: 1px solid var(--glass-border);
            color: var(--white);
            padding: 12px;
            border-radius: 15px;
            font-weight: 850;
            font-size: 0.75rem;
            transition: all 0.3s;
        }
        .denom-btn:active { background: var(--gold-luxury); color: var(--dark-deep); }
        .denom-btn.featured { border-color: var(--gold-luxury); color: var(--gold-bright); }
        
        .cash-input-wrap {
            border-bottom: 2px solid var(--glass-border);
            padding: 15px 0;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .cash-input-wrap i { font-size: 1.5rem; color: var(--gold-luxury); opacity: 0.6; }
        .cash-input-wrap input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--gold-bright);
            font-size: 1.4rem;
            font-weight: 950;
            text-align: right;
            outline: none;
        }

        .change-display-luxury {
            background: var(--dark-surface);
            padding: 25px;
            border-radius: 25px;
            text-align: center;
            margin-bottom: 35px;
            border: 1.5px solid var(--glass-border);
        }

        /* Order Sheet Input */
        .luxury-input-group {
            margin-bottom: 25px;
            padding: 0 5px;
        }
        .luxury-input-group label {
            color: rgba(255,255,255,0.25);
            font-weight: 900;
            font-size: 0.55rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            display: block;
            padding-left: 15px;
        }
        .luxury-input-field {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(181, 141, 61, 0.15);
            border-radius: 100px;
            display: flex;
            align-items: center;
            height: 44px;
            padding: 0 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .luxury-input-field:focus-within {
            background: rgba(181, 141, 61, 0.03);
            border-color: var(--gold-luxury);
            box-shadow: 0 0 20px rgba(181, 141, 61, 0.1);
        }
        .luxury-input-field i {
            color: var(--gold-luxury);
            font-size: 0.8rem;
            opacity: 0.4;
            margin-right: 12px;
        }
        .luxury-input-field input {
            flex: 1;
            background: transparent !important;
            border: none !important;
            color: white !important;
            font-size: 0.85rem;
            font-weight: 700;
            outline: none !important;
            padding: 0;
            height: 100%;
        }
        .change-display-luxury .l { font-size: 0.7rem; font-weight: 950; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; }
        .change-display-luxury .v { font-size: 2.2rem; font-weight: 950; }

        /* Micro-Animations */
        @keyframes itemSlideIn {
            from { opacity: 0; transform: translateX(15px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .order-item-luxury {
            animation: itemSlideIn 0.3s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        @keyframes totalPop {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); color: var(--gold-bright); }
            100% { transform: scale(1); }
        }
        .bill-panel-luxury.pop .val {
            animation: totalPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes btnPulse {
            0% { box-shadow: 0 0 0 0 rgba(197, 160, 40, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(197, 160, 40, 0); }
            100% { box-shadow: 0 0 0 0 rgba(197, 160, 40, 0); }
        }
        .btn-premium-primary:not(:disabled) {
            animation: btnPulse 2s infinite;
        }
    </style>
</head>
<body class="admin-page">
    <div class="luxury-loader-wrap" id="pageLoader">
        <div class="loader-brand-container">
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <img src="../assets/img/logo.png" class="loader-logo" alt="Logo">
        </div>
        <div class="loader-text">Hoki Container</div>
    </div>

    

    <div class="ultra-bg"></div>

    <div class="app-container">
        <header class="header-aligned-luxury animate-up">
            <div class="header-brand-luxury">
                <a href="dashboard.php" class="header-back"><i class="fas fa-chevron-left" style="font-size: 0.9rem;"></i></a>
                <img src="../assets/img/logo.png" class="header-logo-luxury">
            </div>

            <h1 class="font-heavy">Kasir</h1>

            <div class="header-action">
                <!-- Refresh icon removed -->
            </div>
        </header>

        <div class="search-area-luxury animate-up" style="animation-delay: 0.05s;">
            <div class="luxury-input-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari menu produk..." oninput="searchMenu(this.value)">
            </div>
        </div>

        <div class="tab-scroller-luxury animate-up" style="animation-delay: 0.1s;">
            <div class="tab-scroller-luxury" id="customerTabs" style="padding: 0;">
                <div class="tab-capsule active" data-tab="1" onclick="switchTab(1)">
                    <span>Pelanggan #1</span>
                    <span class="item-count" id="tabCount1">0</span>
                </div>
            </div>
            <div class="avatar-ring" onclick="addNewTab()" style="width: 40px; height: 40px; flex-shrink: 0; cursor: pointer; border-color: var(--glass-border); color: var(--gold-luxury);">
                <i class="fas fa-plus"></i>
            </div>
        </div>

        <div class="category-ribbon animate-up" style="animation-delay: 0.15s;">
            <div class="cat-luxury-btn active" data-category="all">Semua</div>
            <?php foreach ($categories as $cat): ?>
            <div class="cat-luxury-btn" data-category="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="menu-luxury-grid animate-up" id="menuGrid" style="animation-delay: 0.2s;">
            <?php foreach ($menuItems as $item): 
                $catName = $item['category_name'] ?? '';
                $icon = $categoryIcons[$catName] ?? 'fa-bowl-food';
                $hasImage = !empty($item['image']);
                $stock = $item['stock'] ?? null;
                $isOutOfStock = ($stock === null || $stock <= 0);
                $isUnavailable = !$item['is_available'] || $isOutOfStock;
            ?>
            <div class="product-card-luxury <?= $isUnavailable ? 'disabled' : '' ?>" 
                 data-category="<?= $item['category_id'] ?>"
                 data-id="<?= $item['id'] ?>"
                 data-name="<?= htmlspecialchars($item['name']) ?>"
                 data-price="<?= $item['price'] ?>"
                 data-stock="<?= $stock === null ? 'null' : (int)$stock ?>"
                 id="product-<?= $item['id'] ?>"
                 onclick="addToCart(this)">
                
                <div class="product-media" style="<?= $hasImage ? "background-image: url('../uploads/".htmlspecialchars($item['image'])."');" : "" ?>">
                    <?php if (!$hasImage): ?>
                        <i class="fas <?= $icon ?>"></i>
                    <?php endif; ?>
                    
                    <?php if (!$item['is_available']): ?>
                        <div class="stock-pill danger">NON-AKTIF</div>
                    <?php elseif ($stock === null): ?>
                        <div class="stock-pill danger">STOK BELUM DI SET</div>
                    <?php elseif ($stock <= 0): ?>
                        <div class="stock-pill danger">HABIS</div>
                    <?php else: ?>
                        <div class="stock-pill <?= $stock <= 5 ? 'danger' : '' ?>"><?= (int)$stock ?> PORSI</div>
                    <?php endif; ?>
                </div>
                
                <div class="product-content">
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <div class="price-tag"><?= formatRupiahShort($item['price']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Floating Summary Dock -->
        <div class="dock-summary-luxury animate-up" id="cartFab" style="display: none; opacity: 0; transform: translateY(30px);">
            <div class="dock-icon-box"><i class="fas fa-shopping-bag"></i></div>
            <div class="dock-text">
                <span class="lbl">Pesanan</span>
                <span class="tot" id="fabTotal">Rp 0</span>
            </div>
            <button class="btn-view-cart" onclick="toggleCart()">LIHAT PESANAN <i class="fas fa-arrow-right" style="margin-left: 8px;"></i></button>
        </div>

        <nav class="luxury-bottom-dock">
            <a href="dashboard.php" class="dock-link"><i class="fas fa-compass"></i><span>Beranda</span></a>
            <a href="kasir.php" class="dock-link active"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
            <a href="menu.php" class="dock-link"><i class="fas fa-layer-group"></i><span>Menu</span></a>
            <a href="stok.php" class="dock-link"><i class="fas fa-warehouse"></i><span>Stok</span></a>
            <a href="laporan.php" class="dock-link"><i class="fas fa-chart-pie"></i><span>Laporan</span></a>
        </nav>

        <!-- Order Sheet -->
        <div class="modal-premium-overlay" id="cartOverlay" onclick="if(event.target==this) toggleCart(false)">
            <div class="modal-premium luxury-sheet">
                <div class="pull-bar" style="background: var(--glass-border); width: 60px;"></div>
                <div class="flex-between" style="margin-bottom: 30px;">
                    <h3 class="font-heavy" style="font-size: 1.5rem; letter-spacing: -1px;">Daftar Pesanan</h3>
                    <button onclick="clearCart()" class="btn-ghost" style="color: #f87171; font-weight: 900; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Kosongkan</button>
                </div>
                
                <div id="cartItems" style="max-height: 45vh; overflow-y: auto; margin-bottom: 25px; padding-right: 5px; -webkit-overflow-scrolling: touch;">
                    <!-- Items Grid -->
                </div>
                
                <div class="bill-panel-luxury">
                    <span class="lbl">Total Pembayaran</span>
                    <div class="bill-total-wrap">
                        <span class="unit">Rp</span>
                        <span class="val" id="cartTotal">0</span>
                    </div>
                </div>

                <div class="luxury-input-group">
                    <label>Atas Nama Pelanggan</label>
                    <div class="luxury-input-field">
                        <i class="fas fa-tag"></i>
                        <input type="text" id="customerNameInput" placeholder="Contoh: Dhani..." autocomplete="off">
                    </div>
                </div>
                
                <button type="button" class="btn-premium btn-premium-primary" id="checkoutBtn" disabled onclick="showPaymentModal()" style="padding: 22px; width: 100%; font-size: 0.9rem; letter-spacing: 2px;">
                    PROSES PEMBAYARAN <i class="fas fa-chevron-right" style="margin-left: 10px;"></i>
                </button>
            </div>
        </div>

        <!-- Payment Precision Modal -->
        <div class="modal-premium-overlay" id="paymentOverlay" onclick="if(event.target==this) closePaymentModal()">
            <div class="modal-premium luxury-sheet">
                <div class="pull-bar" style="background: var(--glass-border); width: 60px;"></div>
                <h3 class="font-heavy" style="font-size: 1.4rem; margin-bottom: 30px; text-align: center; letter-spacing: -0.5px;">Konfirmasi Kasir</h3>
                
                <div id="paymentSummary" style="margin-bottom: 25px; padding: 22px; background: rgba(255,255,255,0.02); border-radius: 25px; border: 1px solid var(--glass-border);">
                    <!-- Summary Rows -->
                </div>
                
                <div class="flex-between" style="padding: 25px 0; border-top: 1px solid var(--glass-border); margin-bottom: 30px;">
                    <span style="font-weight: 900; color: var(--white-mute); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 2px;">Total Bill</span>
                    <span class="gold-gradient-text" id="paymentTotal" style="font-size: 2.2rem; font-weight: 950; letter-spacing: -1.5px;">Rp 0</span>
                </div>

                <div style="margin-bottom: 35px;">
                    <div class="quick-denoms">
                        <button type="button" class="denom-btn" onclick="setCash(10000)">10.000</button>
                        <button type="button" class="denom-btn" onclick="setCash(20000)">20.000</button>
                        <button type="button" class="denom-btn" onclick="setCash(50000)">50.000</button>
                        <button type="button" class="denom-btn" onclick="setCash(100000)">100.000</button>
                        <button type="button" class="denom-btn featured" onclick="setExactCash()">Uang Pas</button>
                    </div>
                    
                    <div class="cash-input-wrap" style="margin-bottom: 25px; padding: 10px 0;">
                        <i class="fas fa-money-bill-transfer" style="font-size: 1.2rem;"></i>
                        <input type="number" id="cashInput" placeholder="Jumlah diterima..." oninput="calculateChange()" autofocus style="font-size: 1.2rem;">
                    </div>
                </div>
                
                <div id="changeDisplay" style="display: none;" class="change-display-luxury">
                    <div class="l" id="changeLabel">KEMBALIAN</div>
                    <div class="v" id="changeAmount">Rp 0</div>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <button type="button" class="btn-premium" style="background: var(--dark-elevated); color: var(--white-mute); border-color: var(--glass-border); flex: 1;" onclick="closePaymentModal()">BATAL</button>
                    <button type="button" class="btn-premium btn-premium-primary" style="flex: 2; padding: 20px;" id="confirmPayBtn" onclick="confirmPayment()">SELESAIKAN TRANSAKSI</button>
                </div>
            </div>
        </div>

        <!-- Success Signature Modal -->
        <?php if ($showSuccessModal): ?>
        <div class="modal-premium-overlay active" style="z-index: 5000;">
            <div class="modal-premium luxury-sheet" style="text-align: center; padding: 60px 40px; border-radius: 40px;">
                <div style="width: 110px; height: 110px; background: rgba(181, 141, 61, 0.1); color: var(--gold-bright); border-radius: 40px; display: flex; align-items: center; justify-content: center; margin: 0 auto 35px; border: 2px solid var(--gold-luxury); transform: rotate(10deg);">
                    <i class="fas fa-check" style="font-size: 3.5rem; transform: rotate(-10deg);"></i>
                </div>
                <h3 class="gold-gradient-text" style="font-size: 2.2rem; margin-bottom: 20px; font-weight: 950; letter-spacing: -1px;">Transaksi Sukses</h3>
                <p style="margin-bottom: 45px; color: var(--white-mute); font-weight: 700; line-height: 1.6;">Laporan penjualan sebesar <strong><?= formatRupiah($totalPaid) ?></strong> telah disimpan dalam arsip eksklusif.</p>
                <a href="kasir.php" class="btn-premium btn-premium-primary" style="width: 100%; padding: 22px;">SELESAI & KEMBALI</a>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" id="checkoutForm" style="display:none;">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="cart" id="cartData">
            <input type="hidden" name="customer_name" id="customerNameData">
        </form>
    </div>

    <script>
    let currentTab = 1;
    let carts = { 1: [] };
    let tabCounter = 1;

    function loadState() {
        const saved = localStorage.getItem('hokiCarts');
        if (saved) {
            const state = JSON.parse(saved);
            carts = state.carts || { 1: [] };
            tabCounter = state.tabCounter || 1;
            currentTab = state.currentTab || 1;
            
            const container = document.getElementById('customerTabs');
            container.innerHTML = '';
            for (let tabId in carts) { addTabElement(parseInt(tabId)); }
        }
        updateUI();
    }

    function saveState() {
        localStorage.setItem('hokiCarts', JSON.stringify({ carts, tabCounter, currentTab }));
    }

    function lockScroll() { document.body.style.overflow = 'hidden'; }
    function unlockScroll() { document.body.style.overflow = ''; }

    function addTabElement(tabId) {
        const container = document.getElementById('customerTabs');
        const tab = document.createElement('div');
        tab.className = 'tab-capsule' + (tabId === currentTab ? ' active' : '');
        tab.id = `tabContainer${tabId}`;
        tab.onclick = () => switchTab(tabId);
        tab.innerHTML = `
            <span>Pelanggan #${tabId}</span>
            <span class="item-count" id="tabCount${tabId}">0</span>
            <button class="btn-remove-tab" onclick="removeTab(${tabId}, event)"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(tab);
    }

    function removeTab(tabId, event) {
        if (event) event.stopPropagation();
        
        const tabKeys = Object.keys(carts).sort((a,b) => a-b);
        if (tabKeys.length <= 1) {
            if (confirm('Bersihkan seluruh pesanan pelanggan ini?')) {
                carts[tabId] = [];
                updateUI(); saveState();
            }
            return;
        }
        
        if (confirm(`Hapus antrean Pelanggan #${tabId}?`)) {
            resequenceCarts(tabId);
        }
    }

    function resequenceCarts(targetId) {
        const tabKeys = Object.keys(carts).sort((a,b) => a-b);
        const allCarts = tabKeys
            .filter(id => parseInt(id) !== parseInt(targetId))
            .map(id => carts[id]);
        
        const deletedIdx = tabKeys.indexOf(targetId.toString()) + 1;
        
        const newCarts = {};
        allCarts.forEach((cart, index) => {
            newCarts[index + 1] = cart;
        });
        
        carts = newCarts;
        tabCounter = allCarts.length;
        
        if (currentTab > deletedIdx) {
            currentTab--;
        } else if (currentTab === deletedIdx) {
            if (currentTab > tabCounter) currentTab = tabCounter;
        }
        
        const container = document.getElementById('customerTabs');
        container.innerHTML = '';
        for (let id in carts) { addTabElement(parseInt(id)); }
        updateUI(); saveState();
    }

    function switchTab(tabId) {
        currentTab = tabId;
        document.querySelectorAll('.tab-capsule').forEach(el => {
            el.classList.toggle('active', parseInt(el.textContent.replace('Pelanggan #', '')) === tabId);
        });
        updateUI(); saveState();
    }

    function addNewTab() {
        tabCounter++;
        carts[tabCounter] = [];
        addTabElement(tabCounter);
        switchTab(tabCounter);
        saveState();
    }

    function addToCart(el) {
        if(el.classList.contains('disabled')) return;
        const id = el.dataset.id, name = el.dataset.name, price = parseInt(el.dataset.price);
        const stockRaw = el.dataset.stock;
        
        if (stockRaw === 'null') {
            alert('Stok menu ini belum diatur.');
            return;
        }
        
        const stockLimit = parseInt(stockRaw);
        const currentTotalUsage = calculateGlobalUsage(id);
        
        if (currentTotalUsage >= stockLimit) {
            alert(`Stok ${name} terbatas! Sisa tinggal ${stockLimit} porsi.`);
            return;
        }

        const cart = carts[currentTab];
        const item = cart.find(i => i.id === id);
        if(item) item.qty++; else cart.push({id, name, price, qty: 1});
        
        updateUI(); saveState();
    }

    function calculateGlobalUsage(id) {
        let total = 0;
        for (let tabId in carts) {
            const item = (carts[tabId] || []).find(i => i.id === id);
            if (item) total += item.qty;
        }
        return total;
    }

    function updateQty(id, delta) {
        const cart = carts[currentTab];
        const item = cart.find(i => i.id === id);
        if(item) {
            if (delta > 0) {
                const productEl = document.getElementById(`product-${id}`);
                const stockLimit = productEl ? (productEl.dataset.stock === 'null' ? Infinity : parseInt(productEl.dataset.stock)) : Infinity;
                const currentTotalUsage = calculateGlobalUsage(id);
                
                if (currentTotalUsage >= stockLimit) {
                    alert('Tidak bisa menambah lebih banyak, stok sudah maksimum.');
                    return;
                }
            }
            
            item.qty += delta;
            if(item.qty <= 0) carts[currentTab] = cart.filter(i => i.id !== id);
        }
        updateUI(); saveState();
    }

    function clearCart() {
        if(!confirm('Hapus seluruh daftar pesanan ini?')) return;
        carts[currentTab] = [];
        updateUI(); saveState(); toggleCart(false);
    }

    function updateUI() {
        const cart = carts[currentTab] || [];
        const itemsList = document.getElementById('cartItems'),
              totalEl = document.getElementById('cartTotal'),
              fabLabel = document.getElementById('fabTotal'),
              fab = document.getElementById('cartFab'),
              checkoutBtn = document.getElementById('checkoutBtn');
        
        let total = 0, count = 0, html = '';
        cart.forEach(item => {
            total += item.price * item.qty;
            count += item.qty;
            html += `
                <div class="order-item-luxury">
                    <div class="order-item-meta">
                        <h5>${item.name}</h5>
                        <span>${formatRupiah(item.price * item.qty)}</span>
                    </div>
                    <div class="luxury-qty">
                        <button onclick="updateQty('${item.id}', -1)"><i class="fas fa-minus"></i></button>
                        <span class="num">${item.qty}</span>
                        <button onclick="updateQty('${item.id}', 1)"><i class="fas fa-plus"></i></button>
                    </div>
                </div>`;
        });
        
        itemsList.innerHTML = html || '<div style="text-align:center; padding: 60px 0; opacity: 0.2;"><i class="fas fa-box-open fa-3x" style="margin-bottom: 15px;"></i><p style="font-weight: 850; letter-spacing: 2px;">DAFTAR KOSONG</p></div>';
        const currentTotalText = totalEl.textContent;
        const newTotalText = total.toLocaleString('id-ID');
        
        if (currentTotalText !== newTotalText) {
            const billPanel = document.querySelector('.bill-panel-luxury');
            billPanel.classList.remove('pop');
            void billPanel.offsetWidth; // Trigger reflow
            billPanel.classList.add('pop');
        }

        totalEl.textContent = newTotalText;
        fabLabel.textContent = formatRupiah(total);
        
        fab.style.display = count > 0 ? 'flex' : 'none';
        if (count > 0) {
            setTimeout(() => {
                fab.style.opacity = '1';
                fab.style.transform = 'translateY(0)';
            }, 10);
        } else {
            fab.style.opacity = '0';
            fab.style.transform = 'translateY(30px)';
        }
        checkoutBtn.disabled = count === 0;
        
        for (let tabId in carts) {
            const b = document.getElementById(`tabCount${tabId}`);
            if (b) b.textContent = carts[tabId].reduce((s, i) => s + i.qty, 0);
        }
    }

    function toggleCart(show) {
        const o = document.getElementById('cartOverlay');
        const isActive = show === undefined ? o.classList.toggle('active') : (show ? o.classList.add('active') : o.classList.remove('active'));
        
        if (o.classList.contains('active')) lockScroll();
        else unlockScroll();
    }

    let paymentTotal = 0;
    function showPaymentModal() {
        paymentTotal = carts[currentTab].reduce((s, i) => s + (i.price * i.qty), 0);
        document.getElementById('paymentTotal').textContent = formatRupiah(paymentTotal);
        
        let html = '';
        carts[currentTab].forEach(i => {
            html += `<div class="flex-between" style="font-size: 0.9rem; margin-bottom: 12px; color: var(--white-mute); font-weight: 700;">
                <span>${i.name} (${i.qty})</span>
                <span style="color: white;">${formatRupiah(i.price * i.qty)}</span>
            </div>`;
        });
        document.getElementById('paymentSummary').innerHTML = html;
        document.getElementById('cashInput').value = '';
        document.getElementById('changeDisplay').style.display = 'none';
        document.getElementById('paymentOverlay').classList.add('active');
        lockScroll();
        toggleCart(false);
    }

    function closePaymentModal() { 
        document.getElementById('paymentOverlay').classList.remove('active'); 
        unlockScroll();
    }
    function setCash(val) { document.getElementById('cashInput').value = val; calculateChange(); }
    function setExactCash() { document.getElementById('cashInput').value = paymentTotal; calculateChange(); }

    function calculateChange() {
        const cash = parseInt(document.getElementById('cashInput').value) || 0;
        const change = cash - paymentTotal;
        const d = document.getElementById('changeDisplay');
        const a = document.getElementById('changeAmount');
        const l = document.getElementById('changeLabel');
        
        if (cash > 0) {
            d.style.display = 'block';
            if (change >= 0) {
                d.style.borderColor = 'rgba(181, 141, 61, 0.3)';
                a.style.color = 'var(--gold-bright)';
                l.textContent = 'KEMBALIAN';
                a.textContent = formatRupiah(change);
            } else {
                d.style.borderColor = 'rgba(239, 68, 68, 0.3)';
                a.style.color = '#ef4444';
                l.textContent = 'KEKURANGAN';
                a.textContent = formatRupiah(Math.abs(change));
            }
        } else { d.style.display = 'none'; }
    }

    function confirmPayment() {
        const cash = parseInt(document.getElementById('cashInput').value) || 0;
        if (cash < paymentTotal) { alert('Jumlah pembayaran belum mencukupi.'); return; }
        
        const cartToSubmit = carts[currentTab];
        
        if (Object.keys(carts).length <= 1) {
            carts = { 1: [] }; tabCounter = 1; currentTab = 1;
            const container = document.getElementById('customerTabs');
            container.innerHTML = '';
            addTabElement(1);
        } else {
            resequenceCarts(currentTab);
        }
        
        saveState();

        document.getElementById('cartData').value = JSON.stringify(cartToSubmit);
        document.getElementById('customerNameData').value = document.getElementById('customerNameInput').value;
        document.getElementById('checkoutForm').submit();
    }

    function formatRupiah(n) { return 'Rp ' + n.toLocaleString('id-ID'); }
    function formatRupiahShort(n) { return n >= 1000 ? 'Rp ' + (n/1000) + 'k' : 'Rp ' + n; }

    function searchMenu(q) {
        q = q.toLowerCase();
        document.querySelectorAll('.product-card-luxury').forEach(card => {
            card.style.display = card.dataset.name.toLowerCase().includes(q) ? 'flex' : 'none';
        });
    }

    document.querySelectorAll('.cat-luxury-btn').forEach(tab => {
        tab.onclick = () => {
            document.querySelectorAll('.cat-luxury-btn').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const cat = tab.dataset.category;
            document.querySelectorAll('.product-card-luxury').forEach(card => {
                card.style.display = (cat === 'all' || card.dataset.category === cat) ? 'flex' : 'none';
            });
        };
    });

    window.onload = loadState;
    </script>

    
    

    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('pageLoader');
            if (loader) {
                loader.classList.add('loaded');
                setTimeout(() => loader.remove(), 600);
            }
        });
    </script>
</body>
</html>
