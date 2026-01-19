<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$categories = getCategories();

$stmt = $pdo->query("SELECT m.*, c.name as category_name FROM menu_items m LEFT JOIN categories c ON m.category_id = c.id ORDER BY c.sort_order, m.name");
$allMenus = $stmt->fetchAll();

$menuWithStock = array_filter($allMenus, fn($m) => $m['stock'] !== null);
$totalMenus = count($menuWithStock);
$totalLow = count(array_filter($menuWithStock, fn($m) => $m['stock'] <= 5 && $m['stock'] > 0));
$totalOut = count(array_filter($menuWithStock, fn($m) => $m['stock'] == 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($action === 'plus') {
        $pdo->prepare("UPDATE menu_items SET stock = COALESCE(stock,0) + 1 WHERE id = ?")->execute([$id]);
    }
    if ($action === 'minus') {
        $pdo->prepare("UPDATE menu_items SET stock = GREATEST(0, COALESCE(stock,0) - 1) WHERE id = ?")->execute([$id]);
    }
    if ($action === 'set') {
        $stock = $_POST['stock'] !== '' ? (int)$_POST['stock'] : null;
        $pdo->prepare("UPDATE menu_items SET stock = ? WHERE id = ?")->execute([$stock, $id]);
    }
    if ($action === 'bulk') {
        $catId = $_POST['cat'];
        $qty = (int)$_POST['qty'];
        if ($catId === 'all') {
            $pdo->prepare("UPDATE menu_items SET stock = stock + ? WHERE stock IS NOT NULL")->execute([$qty]);
        } else {
            $pdo->prepare("UPDATE menu_items SET stock = stock + ? WHERE category_id = ? AND stock IS NOT NULL")->execute([$qty, $catId]);
        }
    }
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]); exit;
    }
    header('Location: stok.php'); exit;
}

$menusByCategory = [];
foreach ($allMenus as $m) {
    $cat = $m['category_name'] ?? 'Lainnya';
    $menusByCategory[$cat][] = $m;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include '../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Manajemen Stok - Hoki Container</title>
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
        body { background-color: var(--dark-deep) !important; color: white !important; }
        .bespoke-bg { position: fixed; inset: 0; z-index: -1; overflow: hidden; background: var(--dark-deep); }
        .app-container { background: transparent !important; }
        .app-container::after { background: linear-gradient(to top, var(--dark-deep) 0%, var(--dark-deep) 30%, transparent 100%) !important; }

        .stok-header-luxury {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px 25px;
            background: rgba(10, 10, 11, 0.95);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .stok-header-luxury h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem;
            font-weight: 950;
            letter-spacing: -1px;
            margin: 0;
            color: white;
            text-transform: uppercase;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            line-height: 1;
        }

        .stock-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px 25px;
        }
        .stock-sum-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 15px 10px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        .stock-sum-card .val { font-size: 1.4rem; font-weight: 950; display: block; margin-bottom: 2px; color: white !important; }
        .stock-sum-card .lbl { font-size: 0.55rem; color: rgba(255,255,255,0.35); font-weight: 900; text-transform: uppercase; letter-spacing: 1.5px; }
        .stock-sum-card.warning .val { color: var(--gold-bright) !important; }
        .stock-sum-card.danger { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.08); }
        .stock-sum-card.danger .val { color: #fe2c55 !important; }

        /* Active state for summary filters */
        .stock-sum-card.active {
            background: rgba(255,255,255,0.08);
            border-color: rgba(181, 141, 61, 0.4);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            transform: translateY(-3px);
        }
        .stock-sum-card.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 3px;
            background: var(--gold-luxury);
            border-radius: 10px;
        }

        .search-area-luxury {
            padding: 0 25px 20px;
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

        .cat-divider-luxury {
            padding: 10px 25px 8px;
            font-size: 0.6rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .cat-divider-luxury::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.05); }

        /* Category ribbon like kasir */
        .category-ribbon-stok {
            display: flex;
            gap: 12px;
            padding: 10px 25px 20px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .category-ribbon-stok::-webkit-scrollbar { display: none; }
        .cat-btn-stok {
            flex-shrink: 0;
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            font-size: 0.7rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            cursor: pointer;
        }
        .cat-btn-stok.active {
            background: var(--gold-luxury);
            color: #111;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(181, 141, 61, 0.2);
        }

        .stock-list-container { padding-bottom: 120px; }

        .stock-item-luxury {
            margin: 0 25px 12px;
            background: rgba(255,255,255,0.03) !important;
            border: 1px solid rgba(255,255,255,0.06) !important;
            border-radius: 20px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        .stock-item-luxury:active { transform: scale(0.98); background: rgba(255,255,255,0.05) !important; }
        .stock-item-luxury.out { background: rgba(239, 68, 68, 0.1) !important; border-color: rgba(239, 68, 68, 0.3) !important; }
        .stock-item-luxury.low { background: rgba(232, 196, 119, 0.1) !important; border-color: rgba(232, 196, 119, 0.3) !important; }

        .stock-details-luxury { flex: 1; }
        .stock-details-luxury h4 { font-size: 0.95rem; font-weight: 800; margin-bottom: 4px; color: white !important; letter-spacing: -0.2px; }
        .stock-details-luxury .badge-cat { 
            font-size: 0.55rem; 
            font-weight: 900; 
            color: var(--gold-luxury) !important; 
            background: rgba(181, 141, 61, 0.15) !important;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stock-control-luxury {
            display: flex;
            align-items: center;
            background: rgba(0,0,0,0.5) !important;
            padding: 5px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.08) !important;
        }
        .btn-ctrl-luxury {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            color: rgba(255,255,255,0.5) !important;
        }
        .btn-ctrl-luxury:hover { color: white !important; background: rgba(255,255,255,0.1) !important; }
        .btn-ctrl-luxury.plus { color: var(--gold-luxury) !important; font-weight: 800; }
        .btn-ctrl-luxury.plus:hover { background: rgba(181, 141, 61, 0.15) !important; }
        .btn-ctrl-luxury:active { transform: scale(0.9); }

        .stock-limit-luxury {
            width: 45px;
            text-align: center;
            font-size: 1.15rem;
            font-weight: 950;
            color: white !important;
        }
        .out .stock-limit-luxury { color: #fe2c55 !important; }
        .low .stock-limit-luxury { color: var(--gold-bright) !important; }

        .btn-edit-luxury {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold-luxury) !important;
            opacity: 0.6;
            font-size: 1rem;
            transition: all 0.3s;
            background: rgba(255,255,255,0.02) !important;
            border-radius: 12px;
            margin-left: 5px;
            border: 1px solid rgba(255,255,255,0.04) !important;
        }
        .btn-edit-luxury:hover { opacity: 1; color: var(--gold-bright) !important; background: rgba(255,255,255,0.08) !important; transform: translateY(-3px); }

        /* Custom Input Modal */
        .stock-input-field {
            background: rgba(181, 141, 61, 0.05) !important;
            border: 1px solid rgba(181, 141, 61, 0.2) !important;
            border-radius: 18px !important;
            width: 100%;
            padding: 20px !important;
            text-align: center !important;
            font-size: 2.5rem !important;
            font-weight: 950 !important;
            color: var(--gold-bright) !important;
            margin-bottom: 25px !important;
            outline: none !important;
            box-shadow: none !important;
        }
        .stock-input-field:focus { border-color: var(--gold-luxury) !important; }

        .stock-input-field:focus { border-color: var(--gold-luxury) !important; }

        /* Fix Modal Contrast for Ultra-Luxury Dark */
        .modal-premium {
            background: #111112 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 -20px 60px rgba(0,0,0,0.8) !important;
        }
        .modal-premium label {
            color: rgba(255,255,255,0.4) !important;
            font-weight: 900 !important;
            letter-spacing: 1.5px !important;
        }
        .stock-input-field {
            background: rgba(255,255,255,0.03) !important;
            border: 1px solid rgba(181, 141, 61, 0.2) !important;
            color: var(--gold-bright) !important;
            backdrop-filter: blur(10px);
        }
        .luxury-input-box {
            background: rgba(255,255,255,0.03) !important;
            border-color: rgba(255,255,255,0.1) !important;
        }
        .luxury-input-box select {
            color: white !important;
        }

        /* Bespoke Header Action Button */
        .btn-plus-luxury {
            background: var(--primary-gradient);
            color: #111 !important;
            padding: 8px 16px;
            border-radius: 14px;
            font-size: 0.65rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            box-shadow: 0 8px 20px rgba(181, 141, 61, 0.3);
            transition: all 0.3s;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-plus-luxury:active { transform: scale(0.92); box-shadow: 0 4px 10px rgba(181, 141, 61, 0.2); }
        .btn-plus-luxury i { font-size: 0.8rem; }
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

    

    <div class="bespoke-bg">
        <div class="glow-blob" style="top: -10%; right: -10%; background: radial-gradient(circle, rgba(197, 160, 40, 0.08) 0%, transparent 70%);"></div>
        <div class="glow-blob" style="bottom: -10%; left: -10%;"></div>
    </div>

    <div class="app-container">
        <header class="header-aligned-luxury animate-up">
            <div class="header-brand-luxury">
                <a href="dashboard.php" class="header-back"><i class="fas fa-chevron-left" style="font-size: 0.9rem;"></i></a>
                <img src="../assets/img/logo.png" class="header-logo-luxury">
            </div>

            <h1 class="font-heavy">Manage Stok</h1>

            <div class="header-action">
                <button class="btn-plus-luxury" onclick="openBulkModal()">
                    <i class="fas fa-plus"></i> RESTOK
                </button>
            </div>
        </header>

        <section class="stock-summary-grid animate-up" style="animation-delay: 0.05s;">
            <div class="stock-sum-card active" id="filter-all" onclick="filterStatus('all')">
                <span class="val"><?= (int)$totalMenus ?></span>
                <span class="lbl">Tersedia</span>
            </div>
            <div class="stock-sum-card <?= $totalLow ? 'warning' : '' ?>" id="filter-low" onclick="filterStatus('low')">
                <span class="val gold-gradient-text"><?= (int)$totalLow ?></span>
                <span class="lbl">Menipis</span>
            </div>
            <div class="stock-sum-card <?= $totalOut ? 'danger' : '' ?>" id="filter-out" onclick="filterStatus('out')">
                <span class="val"><?= (int)$totalOut ?></span>
                <span class="lbl">Kosong</span>
            </div>
        </section>

        <div class="search-area-luxury animate-up" style="animation-delay: 0.1s; margin-bottom: 5px;">
            <div class="luxury-input-box">
                <i class="fas fa-search"></i>
                <input type="text" id="stockSearch" placeholder="Cari menu..." onkeyup="filterStock()">
            </div>
        </div>

        <div class="category-ribbon-stok animate-up" style="animation-delay: 0.12s;">
            <div class="cat-btn-stok active" data-cat="all" onclick="filterCategory('all', this)">Semua</div>
            <?php foreach ($categories as $c): ?>
            <div class="cat-btn-stok" data-cat="<?= htmlspecialchars($c['name']) ?>" onclick="filterCategory('<?= htmlspecialchars($c['name']) ?>', this)"><?= htmlspecialchars($c['name']) ?></div>
            <?php endforeach; ?>
        </div>

        <div class="stock-list-container animate-up" style="animation-delay: 0.15s;">
            <?php foreach ($menusByCategory as $cat => $items): ?>
            <div class="cat-divider-luxury" data-cat-name="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></div>
            <?php foreach ($items as $m): 
                $has = $m['stock'] !== null;
                $out = $has && $m['stock'] == 0;
                $low = $has && $m['stock'] > 0 && $m['stock'] <= 5;
            ?>
            <div class="stock-item-luxury <?= $out ? 'out' : ($low ? 'low' : '') ?>" 
                 data-name="<?= strtolower($m['name']) ?>"
                 data-cat="<?= htmlspecialchars($cat) ?>"
                 data-status="<?= $out ? 'out' : ($low ? 'low' : 'normal') ?>">
                <div class="stock-details-luxury">
                    <h4><?= htmlspecialchars($m['name']) ?></h4>
                    <span class="badge-cat"><?= htmlspecialchars($cat) ?></span>
                </div>
                
                <div class="stock-action-luxury" style="display: flex; align-items: center;">
                    <?php if ($has): ?>
                        <div class="stock-control-luxury">
                            <button class="btn-ctrl-luxury minus" onclick="updateStock(<?= $m['id'] ?>, 'minus', this)"><i class="fas fa-minus"></i></button>
                            <div class="stock-limit-luxury" id="stock-<?= $m['id'] ?>"><?= (int)$m['stock'] ?></div>
                            <button class="btn-ctrl-luxury plus" onclick="updateStock(<?= $m['id'] ?>, 'plus', this)"><i class="fas fa-plus"></i></button>
                        </div>
                        <button class="btn-edit-luxury" onclick="openSetModal(<?= $m['id'] ?>,'<?= htmlspecialchars($m['name']) ?>', <?= (int)$m['stock'] ?>)">
                            <i class="fas fa-pen-nib"></i>
                        </button>
                    <?php else: ?>
                        <button class="btn-premium btn-premium-primary" style="padding: 10px 18px; font-size: 0.6rem; letter-spacing: 1px;" onclick="openSetModal(<?= $m['id'] ?>,'<?= htmlspecialchars($m['name']) ?>', 0)">SET STOK</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; endforeach; ?>
            <div id="noResults" style="display: none; text-align: center; padding: 60px 0; opacity: 0.2;">
                <i class="fas fa-search fa-3x" style="margin-bottom: 20px;"></i>
                <p style="font-weight: 900; letter-spacing: 5px; text-transform: uppercase; font-size: 0.8rem;">Item tidak ditemukan</p>
            </div>
        </div>

        <div class="modal-premium-overlay" id="setModal" onclick="if(event.target==this)closeSetModal()">
            <div class="modal-premium luxury-glass" style="padding: 35px 30px;">
                <div class="pull-bar"></div>
                <h3 class="font-heavy gold-gradient-text" style="text-align: center; margin-bottom: 5px; font-size: 1.4rem;">Update Persediaan</h3>
                <p id="setName" style="text-align: center; color: rgba(255,255,255,0.4); font-size: 0.8rem; margin-bottom: 25px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"></p>
                
                <form onsubmit="return handleSetStock(this)">
                    <input type="hidden" id="setId">
                    <input type="number" id="setStockVal" class="stock-input-field" autofocus>
                    
                    <div style="display: flex; gap: 15px;">
                        <button type="button" class="btn-premium" style="flex: 1; border-color: rgba(255,255,255,0.05); font-size: 0.7rem;" onclick="closeSetModal()">BATAL</button>
                        <button type="submit" class="btn-premium btn-premium-primary" style="flex: 1; font-size: 0.7rem;">SIMPAN</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-premium-overlay" id="bulkModal" onclick="if(event.target==this)closeBulkModal()">
            <div class="modal-premium luxury-glass" style="padding: 35px 30px;">
                <div class="pull-bar"></div>
                <h3 class="font-heavy gold-gradient-text" style="text-align: center; margin-bottom: 25px; font-size: 1.4rem;">Restok Massal</h3>
                
                <form onsubmit="return handleBulkRestock(this)">
                    <div style="margin-bottom: 25px;">
                        <label style="font-size: 0.6rem; font-weight: 950; color: rgba(255,255,255,0.25); display: block; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 2px;">Kategori Target</label>
                        <div class="luxury-input-box" style="background: rgba(181, 141, 61, 0.05) !important; border-color: rgba(181, 141, 61, 0.2);">
                            <i class="fas fa-layer-group"></i>
                            <select id="bulkCat" style="flex: 1; background: transparent; border: none; color: white; font-size: 0.8rem; font-weight: 700; outline: none; padding-right: 15px;">
                                <option value="all" style="background: #111;">SEMUA KATEGORI</option>
                                <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" style="background: #111;"><?= strtoupper(htmlspecialchars($c['name'])) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 35px;">
                        <label style="font-size: 0.6rem; font-weight: 950; color: rgba(255,255,255,0.25); display: block; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 2px;">Jumlah Tambahan</label>
                        <input type="number" id="bulkQty" value="10" class="stock-input-field" style="margin-bottom: 0;">
                    </div>
                    
                    <button type="submit" class="btn-premium btn-premium-primary" style="width: 100%; padding: 20px; font-size: 0.75rem; letter-spacing: 2px; font-weight: 900;">KONFIRMASI RESTOK</button>
                </form>
            </div>
        </div>

        <nav class="luxury-bottom-dock">
            <a href="dashboard.php" class="dock-link"><i class="fas fa-compass"></i><span>Beranda</span></a>
            <a href="kasir.php" class="dock-link"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
            <a href="menu.php" class="dock-link"><i class="fas fa-layer-group"></i><span>Menu</span></a>
            <a href="stok.php" class="dock-link active"><i class="fas fa-warehouse"></i><span>Stok</span></a>
            <a href="laporan.php" class="dock-link"><i class="fas fa-chart-pie"></i><span>Laporan</span></a>
        </nav>
    </div>

    <script>
    let currentStatusFilter = 'all';
    let currentCategoryFilter = 'all';

    function updateSummary() {
        let total = 0;
        let low = 0;
        let out = 0;
        
        document.querySelectorAll('.stock-item-luxury').forEach(item => {
            const status = item.dataset.status;
            total++;
            if (status === 'low') low++;
            if (status === 'out') out++;
        });
        
        document.querySelector('#filter-all .val').textContent = total;
        document.querySelector('#filter-low .val').textContent = low;
        document.querySelector('#filter-out .val').textContent = out;
        
        // Update visual warning/danger classes
        const lowCard = document.getElementById('filter-low');
        const outCard = document.getElementById('filter-out');
        
        if (low > 0) lowCard.classList.add('warning'); else lowCard.classList.remove('warning');
        if (out > 0) outCard.classList.add('danger'); else outCard.classList.remove('danger');
    }

    function filterStock() {
        const q = document.getElementById('stockSearch').value.toLowerCase();
        
        document.querySelectorAll('.stock-item-luxury').forEach(item => {
            const name = item.dataset.name;
            const cat = item.dataset.cat;
            const status = item.dataset.status;
            
            const matchQuery = name.includes(q);
            const matchCat = currentCategoryFilter === 'all' || cat === currentCategoryFilter;
            const matchStatus = currentStatusFilter === 'all' || status === currentStatusFilter;
            
            if (matchQuery && matchCat && matchStatus) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });

        // Hide/show category dividers
        document.querySelectorAll('.cat-divider-luxury').forEach(div => {
            const catName = div.dataset.catName;
            const hasVisibleItems = Array.from(document.querySelectorAll(`.stock-item-luxury[data-cat="${catName}"]`))
                                         .some(item => item.style.display !== 'none');
            div.style.display = hasVisibleItems ? 'flex' : 'none';
        });

        const anyVisible = Array.from(document.querySelectorAll('.stock-item-luxury'))
                                .some(item => item.style.display !== 'none');
        document.getElementById('noResults').style.display = anyVisible ? 'none' : 'block';
    }

    function filterStatus(status) {
        currentStatusFilter = status;
        document.querySelectorAll('.stock-sum-card').forEach(c => c.classList.remove('active'));
        document.getElementById('filter-' + status).classList.add('active');
        filterStock();
    }

    function filterCategory(cat, btn) {
        currentCategoryFilter = cat;
        document.querySelectorAll('.cat-btn-stok').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterStock();
    }

    function openSetModal(id, name, current) {
        document.getElementById('setId').value = id;
        document.getElementById('setName').textContent = name;
        document.getElementById('setStockVal').value = current;
        document.getElementById('setModal').classList.add('active');
        setTimeout(() => document.getElementById('setStockVal').focus(), 300);
    }
    function closeSetModal() { document.getElementById('setModal').classList.remove('active'); }

    function openBulkModal() { document.getElementById('bulkModal').classList.add('active'); }
    function closeBulkModal() { document.getElementById('bulkModal').classList.remove('active'); }

    function updateStock(id, action, btn) {
        btn.disabled = true;
        const numEl = document.getElementById('stock-' + id);
        let val = parseInt(numEl.textContent);
        
        if (action === 'plus') val++;
        else if (val > 0) val--;
        
        numEl.textContent = val;
        const row = btn.closest('.stock-item-luxury');
        row.classList.remove('out', 'low');
        if (val === 0) {
            row.classList.add('out');
            row.dataset.status = 'out';
        } else if (val <= 5) {
            row.classList.add('low');
            row.dataset.status = 'low';
        } else {
            row.dataset.status = 'normal';
        }
        
        // Update summary numbers (visual only for now, would be better to recalculate)
        // For simplicity, we can reload or keep it as is. AJAX is better.
        
        const params = new URLSearchParams();
        params.append('action', action);
        params.append('id', id);

        fetch('stok.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: params
        }).finally(() => {
            btn.disabled = false;
            updateSummary();
        });
    }

    function handleSetStock(form) {
        const id = document.getElementById('setId').value;
        const stock = document.getElementById('setStockVal').value;
        
        const params = new URLSearchParams();
        params.append('action', 'set');
        params.append('id', id);
        params.append('stock', stock);

        fetch('stok.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: params
        }).then(() => {
            const numEl = document.getElementById('stock-' + id);
            if (numEl) {
                numEl.textContent = stock;
                const row = numEl.closest('.stock-item-luxury');
                row.classList.remove('out', 'low');
                const s = parseInt(stock);
                if (s === 0) { row.classList.add('out'); row.dataset.status = 'out'; }
                else if (s <= 5) { row.classList.add('low'); row.dataset.status = 'low'; }
                else { row.dataset.status = 'normal'; }
                updateSummary();
            } else {
                location.reload(); // Fallback if item not found
            }
        });
        
        closeSetModal();
        return false;
    }

    function handleBulkRestock(form) {
        const cat = document.getElementById('bulkCat').value;
        const qty = document.getElementById('bulkQty').value;
        
        const params = new URLSearchParams();
        params.append('action', 'bulk');
        params.append('cat', cat);
        params.append('qty', qty);

        fetch('stok.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: params
        }).then(() => location.reload()); // Bulk usually affects many hidden items, reload is safer
        
        closeBulkModal();
        return false;
    }
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
