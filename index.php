<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

try {
    $storeName = getSetting('store_name') ?: "HOKI CONTAINER";
    $categories = getCategories();
    $menuItems = getMenuItems();
    $waNumber = getSetting('owner_wa') ?: '6285654631899';
} catch (Exception $e) {
    // Fallback kalau database lelet/error
    $storeName = "HOKI CONTAINER";
    $categories = [];
    $menuItems = [];
    $waNumber = '6285654631899';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- PWA -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Hoki Container">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0a0a0b">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/img/favicon.png">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?= $storeName ?> - Signature Menu</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        body { 
            background: #050505 !important; 
            color: white; 
            overflow-x: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
        }
        
        /* Background Aurora */
        .bespoke-bg { 
            position: fixed; 
            inset: 0; 
            z-index: -1; 
            background: #050505;
            overflow: hidden; 
        }
        .aurora-blob {
            position: absolute;
            width: 80vw;
            height: 80vw;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.12;
            z-index: -1;
            animation: aurora-float 20s infinite alternate ease-in-out;
        }
        @keyframes aurora-float {
            0% { transform: translate(-20%, -20%) rotate(0deg) scale(1.0); }
            100% { transform: translate(20%, 20%) rotate(180deg) scale(1.2); }
        }

        /* Hero */
        .public-hero-bespoke {
            padding: 80px 30px 40px;
            text-align: left;
            position: relative;
        }
        .public-logo-bespoke {
            width: 70px;
            height: 70px;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(181, 141, 61, 0.2);
            border-radius: 20px;
            padding: 15px;
            backdrop-filter: blur(10px);
        }
        .public-hero-bespoke h1 { 
            font-family: 'Outfit', sans-serif;
            font-size: 3.2rem; 
            font-weight: 950; 
            line-height: 0.9;
            letter-spacing: -3px; 
            margin-bottom: 15px; 
            text-transform: uppercase;
        }
        .public-hero-bespoke .brand-tagline { 
            font-size: 0.7rem; 
            color: var(--gold-luxury); 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 5px; 
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .public-hero-bespoke .brand-tagline::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, var(--gold-luxury), transparent); opacity: 0.3; }

        /* Sticky Nav */
        .public-nav-sticky {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(5, 5, 5, 0.85);
            backdrop-filter: blur(20px);
            padding: 20px 0;
            margin-top: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }
        .category-scroller-bespoke {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 0 30px;
            scrollbar-width: none;
        }
        .category-scroller-bespoke::-webkit-scrollbar { display: none; }
        .cat-pill-bespoke {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.4);
            white-space: nowrap;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
        }
        .cat-pill-bespoke.active {
            background: var(--gold-luxury);
            color: #000;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(181, 141, 61, 0.4);
        }

        /* Menu Grid */
        .menu-grid-public {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            padding: 30px 25px 120px;
        }

        .menu-item-public {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            padding: 12px;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
            animation: animateUp 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), background 0.3s;
        }
        @keyframes animateUp { to { opacity: 1; transform: translateY(0); } }
        
        .menu-item-public.out { opacity: 0.4; filter: grayscale(1); }
        .menu-item-public:active { transform: scale(0.95); background: rgba(255,255,255,0.05); }
        
        .item-image-container {
            width: 100%;
            aspect-ratio: 1/1;
            border-radius: 22px;
            background: rgba(255,255,255,0.02);
            overflow: hidden;
            margin-bottom: 15px;
            position: relative;
        }
        .item-image-container img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s; }
        
        .item-meta-info { padding: 0 5px 8px; }
        .item-meta-info h3 { font-size: 0.9rem; font-weight: 800; margin-bottom: 6px; color: #fff; line-height: 1.2; letter-spacing: -0.2px; }
        .item-meta-info .price-tag { font-family: 'Outfit', sans-serif; font-size: 0.8rem; font-weight: 900; color: var(--gold-luxury); }

        .add-btn-bespoke {
            position: absolute;
            bottom: 15px;
            right: 15px;
            width: 38px;
            height: 38px;
            background: var(--gold-luxury);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-size: 1rem;
            box-shadow: 0 8px 15px rgba(181, 141, 61, 0.2);
            z-index: 10;
            text-decoration: none !important;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .add-btn-bespoke:active { transform: scale(0.9); }

        .sold-out-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 950;
            text-transform: uppercase;
            font-size: 0.6rem;
            letter-spacing: 2px;
            color: white;
            backdrop-filter: blur(3px);
            z-index: 5;
        }

        /* Float Order Dock */
        .order-dock-bespoke {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(120px);
            background: var(--gold-luxury);
            padding: 16px 25px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            z-index: 1000;
            box-shadow: 0 20px 40px rgba(181, 141, 61, 0.3);
            transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
            color: #000;
            width: calc(100% - 40px);
            max-width: 400px;
        }
        .order-dock-bespoke.active { transform: translateX(-50%) translateY(0); }
        .order-dock-bespoke .badge { background: #000; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 900; }
        .order-dock-bespoke .text { flex: 1; font-weight: 900; font-size: 0.85rem; }
        .order-dock-bespoke .price { font-weight: 950; font-family: 'Outfit', sans-serif; font-size: 1rem; }

        /* Modal Order */
        #orderModal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.95);
            backdrop-filter: blur(20px);
            z-index: 2000;
            display: none;
            padding: 40px 25px;
            overflow-y: auto;
        }
        .cart-item { display: flex; align-items: center; gap: 15px; padding: 18px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .cart-qty-ctrl { display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.05); padding: 6px 15px; border-radius: 12px; }
        .checkout-btn { width: 100%; padding: 20px; background: var(--gold-luxury); border: none; border-radius: 20px; font-weight: 950; letter-spacing: 1px; color: #000; margin-top: 30px; }

        .public-footer { padding: 60px 30px; text-align: center; }
        .admin-trigger-button { display: inline-block; padding: 12px 25px; background: rgba(255, 255, 255, 0.03); border-radius: 15px; font-size: 0.6rem; font-weight: 900; color: rgba(255,255,255,0.2); letter-spacing: 3px; text-decoration: none; }
    </style>
</head>
<body class="public-page">

    <div class="bespoke-bg">
        <div class="aurora-blob" style="top: -10%; left: -20%; background: radial-gradient(circle, rgba(181, 141, 61, 0.15) 0%, transparent 70%);"></div>
        <div class="aurora-blob" style="bottom: -10%; right: -20%; background: radial-gradient(circle, rgba(181, 141, 61, 0.1) 0%, transparent 70%); animation-duration: 25s; animation-delay: -5s;"></div>
    </div>

    <div class="app-container">
        <header class="public-hero-bespoke animate-up">
            <a href="admin/login.php" style="display: block; width: fit-content; text-decoration: none;">
                <div class="public-logo-bespoke">
                    <img src="assets/img/logo.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            </a>
            <h1 class="gold-gradient-text"><?= htmlspecialchars($storeName) ?></h1>
            <div class="brand-tagline">Original Signature</div>
        </header>

        <div class="public-nav-sticky animate-up" style="animation-delay: 0.1s;">
            <div class="category-scroller-bespoke">
                <a href="javascript:void(0)" class="cat-pill-bespoke active" onclick="filterPublic('all', this)">SEMUA</a>
                <?php foreach ($categories as $cat): ?>
                <a href="javascript:void(0)" class="cat-pill-bespoke" onclick="filterPublic(<?= $cat['id'] ?>, this)"><?= strtoupper(htmlspecialchars($cat['name'])) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="menu-grid-public" id="menuGrid">
            <?php 
            $delay = 0;
            foreach ($menuItems as $item): 
                $out = !$item['is_available'];
                $delay += 0.06;
            ?>
            <div class="menu-item-public <?= $out ? 'out' : '' ?>" data-cat="<?= $item['category_id'] ?>" style="animation-delay: <?= $delay ?>s;">
                <div class="item-image-container">
                    <?php if ($item['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy" style="opacity: 0; transition: opacity 0.3s;" onload="this.style.opacity=1">
                    <?php else: ?>
                        <div style="height: 100%; display: flex; align-items: center; justify-content: center; opacity: 0.1;"><i class="fas fa-mug-hot fa-3x"></i></div>
                    <?php endif; ?>
                    <?php if ($out): ?><div class="sold-out-overlay">Sold Out</div><?php endif; ?>
                </div>
                <div class="item-meta-info">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <div class="price-tag"><?= formatRupiah($item['price']) ?></div>
                </div>
                <?php if (!$out): ?>
                <a href="javascript:void(0)" class="add-btn-bespoke" onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>', <?= $item['price'] ?>)">
                    <i class="fas fa-plus"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <footer class="public-footer">
            <div style="opacity: 0.1; margin-bottom: 20px;"><img src="assets/img/logo.png" height="35"></div>
            <a href="admin/login.php" class="admin-trigger-button">ADMIN PORTAL</a>
        </footer>
    </div>

    <!-- Order Drawer Content -->
    <div id="orderModal">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 class="gold-gradient-text" style="font-family:'Outfit'; font-weight:950; font-size:1.8rem;">REVIEW PESANAN</h2>
            <a href="javascript:void(0)" onclick="toggleModal(false)" style="color:var(--gold-luxury); font-size:1.5rem;"><i class="fas fa-times"></i></a>
        </div>
        <div id="cartList"></div>
        <button class="checkout-btn" onclick="sendWA()">PESAN VIA WHATSAPP <i class="fas fa-paper-plane" style="margin-left:10px;"></i></button>
    </div>

    <!-- Floating Order Dock -->
    <div class="order-dock-bespoke" id="orderDock" onclick="toggleModal(true)">
        <div class="badge" id="dockBadge">0</div>
        <div class="text">Review Pesanan</div>
        <div class="price" id="dockPrice">Rp 0</div>
    </div>

    <script>
    let cart = [];
    const waPhone = '<?= $waNumber ?>';

    function addToCart(id, name, price) {
        const item = cart.find(i => i.id === id);
        if(item) item.qty++;
        else cart.push({id, name, price, qty:1});
        updateDock();
        if(navigator.vibrate) navigator.vibrate(50);
    }

    function updateDock() {
        const dock = document.getElementById('orderDock');
        const count = cart.reduce((s, i) => s + i.qty, 0);
        const total = cart.reduce((s, i) => s + (i.price * i.qty), 0);
        
        if(count > 0) {
            dock.classList.add('active');
            document.getElementById('dockBadge').innerText = count;
            document.getElementById('dockPrice').innerText = formatIDR(total);
        } else {
            dock.classList.remove('active');
        }
        renderCart();
    }

    function renderCart() {
        const list = document.getElementById('cartList');
        if(cart.length === 0) { list.innerHTML = '<p style="text-align:center; opacity:0.3; padding:50px 0;">Belum ada pesanan.</p>'; return; }
        list.innerHTML = cart.map((item, idx) => `
            <div class="cart-item">
                <div style="flex:1;">
                    <h4 style="margin-bottom:5px;">${item.name}</h4>
                    <span style="color:var(--gold-luxury); font-weight:800; font-family:'Outfit';">${formatIDR(item.price * item.qty)}</span>
                </div>
                <div class="cart-qty-ctrl">
                    <a href="javascript:void(0)" onclick="updateQty(${idx}, -1)" style="color:white; opacity:0.5;"><i class="fas fa-minus"></i></a>
                    <span style="font-weight:900; min-width:20px; text-align:center;">${item.qty}</span>
                    <a href="javascript:void(0)" onclick="updateQty(${idx}, 1)" style="color:white; opacity:0.5;"><i class="fas fa-plus"></i></a>
                </div>
            </div>
        `).join('');
    }

    function updateQty(idx, d) {
        cart[idx].qty += d;
        if(cart[idx].qty <= 0) cart.splice(idx, 1);
        updateDock();
        if(cart.length === 0) toggleModal(false);
    }

    function toggleModal(s) {
        document.getElementById('orderModal').style.display = s ? 'block' : 'none';
        document.body.style.overflow = s ? 'hidden' : 'auto';
    }

    function sendWA() {
        let msg = `*HALO ${'<?= $storeName ?>'.toUpperCase()}*\nSaya ingin memesan:\n\n`;
        cart.forEach((i, idx) => { msg += `${idx+1}. *${i.name}* (${i.qty}x)\n`; });
        const total = cart.reduce((s, i) => s + (i.price * i.qty), 0);
        msg += `\n*TOTAL: ${formatIDR(total)}*\n\nTerima kasih!`;
        window.open(`https://wa.me/${waPhone}?text=${encodeURIComponent(msg)}`, '_blank');
    }

    function formatIDR(n) { return new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', maximumFractionDigits:0}).format(n).replace('Rp', 'Rp '); }

    function filterPublic(catId, btn) {
        document.querySelectorAll('.cat-pill-bespoke').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const q = catId.toString();
        let count = 0;
        document.querySelectorAll('.menu-item-public').forEach(card => {
            if (catId === 'all' || card.dataset.cat === q) {
                card.style.display = 'block';
                card.style.animation = 'none';
                card.offsetHeight;
                card.style.animation = `animateUp 0.6s cubic-bezier(0.23, 1, 0.32, 1) ${count * 0.05}s forwards`;
                count++;
            } else {
                card.style.display = 'none';
                card.style.opacity = '0';
            }
        });
    }
    </script>
</body>
</html>
