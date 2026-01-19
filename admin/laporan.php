<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$period = $_GET['period'] ?? 'today';
$startDate = date('Y-m-d');
$endDate = date('Y-m-d');
$periodLabel = 'Hari Ini';

if ($period === 'week') { 
    $startDate = date('Y-m-d', strtotime('-7 days')); 
    $periodLabel = '7 Hari Terakhir';
}
elseif ($period === 'month') { 
    $startDate = date('Y-m-d', strtotime('-30 days')); 
    $periodLabel = '30 Hari Terakhir';
}

// Handle Custom Date Range
if ($period === 'custom') {
    $startDate = $_GET['start'] ?? date('Y-m-d');
    $endDate = $_GET['end'] ?? date('Y-m-d');
    $periodLabel = date('d M', strtotime($startDate)) . ' - ' . date('d M', strtotime($endDate));
}


// Sales Summary
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count FROM transactions WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'");
$stmt->execute([$startDate, $endDate]);
$summary = $stmt->fetch();

$avgPerTrx = $summary['count'] > 0 ? $summary['total'] / $summary['count'] : 0;

$stmt = $pdo->prepare("SELECT COALESCE(SUM(ti.quantity), 0) as total_items FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status = 'completed'");
$stmt->execute([$startDate, $endDate]);
$totalItems = $stmt->fetch()['total_items'];

// Top Items
$stmt = $pdo->prepare("SELECT item_name, SUM(quantity) as qty, SUM(subtotal) as sales FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status = 'completed' GROUP BY item_name ORDER BY qty DESC LIMIT 5");
$stmt->execute([$startDate, $endDate]);
$topItems = $stmt->fetchAll();

// Category breakdown
$stmt = $pdo->prepare("
    SELECT c.name as category, COALESCE(SUM(ti.quantity), 0) as qty, COALESCE(SUM(ti.subtotal), 0) as sales
    FROM transaction_items ti 
    JOIN transactions t ON ti.transaction_id = t.id 
    LEFT JOIN menu_items m ON ti.menu_item_id = m.id
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status = 'completed'
    GROUP BY c.name
    ORDER BY sales DESC
    LIMIT 4
");
$stmt->execute([$startDate, $endDate]);
$categoryBreakdown = $stmt->fetchAll();

// Last 10 transactions
$stmt = $pdo->prepare("SELECT t.*, (SELECT GROUP_CONCAT(item_name SEPARATOR ', ') FROM transaction_items WHERE transaction_id = t.id) as items, (SELECT SUM(quantity) FROM transaction_items WHERE transaction_id = t.id) as item_count FROM transactions t WHERE DATE(t.created_at) BETWEEN ? AND ? AND t.status = 'completed' ORDER BY t.created_at DESC LIMIT 10");
$stmt->execute([$startDate, $endDate]);
$lastTrx = $stmt->fetchAll();

$ownerWa = getSetting('owner_wa') ?: OWNER_WA;
$waMessage = generateWhatsAppReport();

if (isset($_GET['ajax'])) {
    // Return Only Content for AJAX
    ob_start();
}
?>
<?php if (!isset($_GET['ajax'])): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include __DIR__ . '/../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laporan Premium - Hoki Container</title>
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

        .laporan-header-luxury {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 15px 25px;
            background: rgba(10, 10, 11, 0.95);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .laporan-header-luxury h1 {
            text-align: center;
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 950;
            letter-spacing: -1.5px;
            margin: 0;
            color: white;
            text-transform: uppercase;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .hero-laporan-bespoke {
            margin: 15px 20px 20px;
            padding: 30px 20px;
            border-radius: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(181, 141, 61, 0.2);
            background: linear-gradient(135deg, rgba(18, 18, 20, 0.95) 0%, rgba(10, 10, 11, 0.98) 100%) !important;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .hero-laporan-bespoke .label {
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--gold-luxury);
            text-transform: uppercase;
            letter-spacing: 2.5px;
            margin-bottom: 12px;
            opacity: 0.7;
        }
        .hero-laporan-bespoke .main-val {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: -1px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--gold-bright) !important;
        }

        .stat-grid-bespoke {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 0 20px;
            margin-top: -30px;
            margin-bottom: 25px;
            position: relative;
            z-index: 10;
        }
        .stat-box-bespoke {
            background: rgba(18, 18, 20, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 15px 10px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .stat-box-bespoke i { font-size: 1rem; color: var(--gold-bright); margin-bottom: 10px; opacity: 0.8; }
        .stat-box-bespoke .val { font-size: 1.05rem; font-weight: 950; display: block; margin-bottom: 4px; color: white; }
        .stat-box-bespoke .lbl { font-size: 0.55rem; color: rgba(255,255,255,0.3); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }

        .report-card-bespoke {
            background: rgba(18, 18, 20, 0.4);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.03);
            border-radius: 30px;
            padding: 22px;
            margin: 0 20px 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        .card-title-bespoke {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card-title-bespoke i { color: var(--gold-luxury); font-size: 0.9rem; opacity: 0.8; }

        .cat-row-bespoke { margin-bottom: 20px; }
        .cat-info-bespoke { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.8rem; font-weight: 700; letter-spacing: -0.2px; }
        .cat-info-bespoke .sale { color: var(--gold-bright); font-weight: 800; }
        .cat-bar-bespoke { height: 4px; background: rgba(255,255,255,0.03); border-radius: 100px; overflow: hidden; }
        .cat-fill-bespoke { height: 100%; background: linear-gradient(90deg, var(--gold-luxury), #ffd700); border-radius: 100px; box-shadow: 0 0 10px rgba(181, 141, 61, 0.4); }

        .trx-list-bespoke { padding-bottom: 120px; }
        .trx-card-bespoke {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(181, 141, 61, 0.05);
        }
        .trx-card-bespoke:last-child { border-bottom: none; }
        .trx-icon-box-bespoke {
            width: 40px;
            height: 40px;
            background: rgba(181, 141, 61, 0.08);
            border: 1px solid rgba(181, 141, 61, 0.15);
            color: var(--gold-luxury);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }
        .trx-info-bespoke { flex: 1; }
        .trx-info-bespoke h5 { font-size: 0.9rem; font-weight: 800; margin-bottom: 4px; color: white; letter-spacing: -0.2px; }
        .trx-info-bespoke p { font-size: 0.65rem; color: rgba(255,255,255,0.3); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        /* Ribbon Style Local */
        .category-ribbon-laporan {
            display: flex;
            gap: 12px;
            padding: 0 0 20px;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .category-ribbon-laporan::-webkit-scrollbar { display: none; }
        .cat-btn-laporan {
            flex-shrink: 0;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            font-size: 0.75rem;
            font-weight: 800;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .cat-btn-laporan.active {
            background: var(--gold-luxury);
            color: #111;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(181, 141, 61, 0.2);
        }

        .btn-send-wa-luxury {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            border-radius: 100px;
            color: #22c55e;
            text-decoration: none;
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            justify-self: end;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            position: relative;
        }
        .btn-send-wa-luxury:active { transform: scale(0.9); background: rgba(34, 197, 94, 0.2); }
        .btn-send-wa-luxury i { font-size: 1.1rem; }
        
        .pulse-wa {
            position: absolute;
            inset: 0;
            border-radius: 100px;
            border: 2px solid rgba(34, 197, 94, 0.5);
            animation: pulse-wa-anim 2s infinite;
            pointer-events: none;
        }
        @keyframes pulse-wa-anim {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.3, 1.6); opacity: 0; }
        }

        .trx-meta-bespoke .time { font-size: 0.75rem; color: var(--gold-luxury); font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; opacity: 0.8; }

        .rank-circle-bespoke {
            width: 28px;
            height: 28px;
            border: 1.5px solid rgba(181, 141, 61, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 900;
            margin-right: 12px;
            flex-shrink: 0;
            background: rgba(181, 141, 61, 0.05);
        }

        .custom-date-box {
            display: none;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            padding: 15px 20px;
            background: rgba(255,255,255,0.02);
            border-radius: 20px;
            margin: 0 20px 15px;
            border: 1px solid var(--glass-border);
        }
        .custom-date-box input {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--glass-border);
            color: white;
            padding: 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            outline: none;
        }
        .btn-apply-date {
            background: var(--gold-luxury);
            color: black;
            border: none;
            padding: 8px 15px;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.7rem;
        }


        .btn-send-wa-luxury {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 100px;
            text-decoration: none; font-size: 0.75rem; font-weight: 800; letter-spacing: 1px;
            color: #4ade80; 
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.5);
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.4);
            animation: whatsappPulse 2s infinite;
            transition: all 0.3s;
        }
        .btn-send-wa-luxury i { font-size: 1.1rem; }
        .btn-send-wa-luxury:active { transform: scale(0.95); }
        
        @keyframes whatsappPulse {
            0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.4); border-color: rgba(34, 197, 94, 0.5); }
            70% { box-shadow: 0 0 0 10px rgba(74, 222, 128, 0); border-color: rgba(34, 197, 94, 0.8); }
            100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); border-color: rgba(34, 197, 94, 0.5); }
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

    

    <div class="bespoke-bg">
        <div class="glow-blob" style="top: -10%; left: -10%;"></div>
        <div class="glow-blob" style="bottom: 20%; right: -10%; background: radial-gradient(circle, rgba(197, 160, 40, 0.08) 0%, transparent 70%);"></div>
    </div>

    <div class="app-container">
        <header class="header-aligned-luxury animate-up">
            <div class="header-brand-luxury">
                <a href="dashboard.php" class="header-back"><i class="fas fa-chevron-left" style="font-size: 0.9rem;"></i></a>
                <img src="../assets/img/logo.png" class="header-logo-luxury">
            </div>
            <h1 class="font-heavy">Laporan</h1>
            <div class="header-action">
                <a href="https://wa.me/<?= $ownerWa ?>?text=<?= $waMessage ?>" target="_blank" class="btn-send-wa-luxury">
                    <i class="fab fa-whatsapp"></i> <span>LAPOR BOS</span>
                </a>
            </div>
        </header>

        <div class="search-area-luxury <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="padding: 10px 20px 0; animation-delay: 0.05s;">
            <div class="category-ribbon-laporan">
                <button type="button" onclick="loadReport('today', this)" class="cat-btn-laporan <?= $period === 'today' ? 'active' : '' ?>">Hari Ini</button>
                <button type="button" onclick="loadReport('week', this)" class="cat-btn-laporan <?= $period === 'week' ? 'active' : '' ?>">7 Hari</button>
                <button type="button" onclick="loadReport('month', this)" class="cat-btn-laporan <?= $period === 'month' ? 'active' : '' ?>">30 Hari</button>
                <button type="button" onclick="toggleCustomDate()" class="cat-btn-laporan <?= $period === 'custom' ? 'active' : '' ?>">Lainnya</button>
            </div>
        </div>

        <form class="custom-date-box <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" onsubmit="handleCustomDate(event)">
            <input type="date" id="calStart" name="start" value="<?= $startDate ?>">
            <input type="date" id="calEnd" name="end" value="<?= $endDate ?>">
            <button type="submit" class="btn-apply-date">Pilih</button>
        </form>

        <div id="reportContent">
<?php endif; // End Header Check ?>

        <section class="hero-laporan-bespoke <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="animation-delay: 0.1s;">
            <div class="label">Total Omzet <?= $periodLabel ?></div>
            <div class="main-val gold-gradient-text" data-target="<?= $summary['total'] ?>">Rp 0</div>
        </section>

        <div class="stat-grid-bespoke <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="animation-delay: 0.15s;">
            <div class="stat-box-bespoke">
                <i class="fas fa-receipt"></i>
                <span class="val"><?= (int)$summary['count'] ?></span>
                <span class="lbl">Transaksi</span>
            </div>
            <div class="stat-box-bespoke">
                <i class="fas fa-bag-shopping"></i>
                <span class="val"><?= (int)$totalItems ?></span>
                <span class="lbl">Item</span>
            </div>
            <div class="stat-box-bespoke">
                <i class="fas fa-chart-line"></i>
                <span class="val"><?= formatRupiahShort($avgPerTrx) ?></span>
                <span class="lbl">Rata"/Pelanggan</span>
            </div>
        </div>

        <!-- Breakdown Kategori -->
        <?php if (!empty($categoryBreakdown)): ?>
        <div class="report-card-bespoke <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="animation-delay: 0.2s;">
            <div class="card-title-bespoke"><i class="fas fa-layer-group"></i> Performa Kategori</div>
            <?php 
            $maxSales = max(array_column($categoryBreakdown, 'sales')) ?: 1;
            foreach ($categoryBreakdown as $cat): 
                $percent = ($cat['sales'] / $maxSales) * 100;
            ?>
            <div class="cat-row-bespoke">
                <div class="cat-info-bespoke">
                    <span><?= htmlspecialchars($cat['category'] ?? 'Lainnya') ?></span>
                    <span class="sale"><?= formatRupiahShort($cat['sales']) ?></span>
                </div>
                <div class="cat-bar-bespoke">
                    <div class="cat-fill-bespoke" style="width: <?= $percent ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Menu Terlaris -->
        <?php if (!empty($topItems)): ?>
        <div class="report-card-bespoke animate-up" style="animation-delay: 0.25s;">
            <div class="card-title-bespoke"><i class="fas fa-fire" style="color: #f97316;"></i> Produk Terlaris</div>
            <?php $rank = 1; foreach ($topItems as $item): 
                $rankColor = $rank == 1 ? "#ffd700" : ($rank == 2 ? "#c0c0c0" : ($rank == 3 ? "#cd7f32" : "rgba(255,255,255,0.2)"));
            ?>
            <div class="trx-card-bespoke">
                <div class="rank-circle-bespoke" style="width: 30px; height: 30px; border-color: <?= $rankColor ?>; color: <?= $rankColor ?>; font-size: 0.7rem; margin-right: 5px;"><?= $rank ?></div>
                <div class="trx-info-bespoke">
                    <h5><?= htmlspecialchars($item['item_name']) ?></h5>
                    <p><?= (int)$item['qty'] ?> terjual</p>
                </div>
                <div class="trx-meta-bespoke">
                    <strong style="color: var(--gold-bright); font-size: 0.95rem;"><?= formatRupiahShort($item['sales']) ?></strong>
                </div>
            </div>
            <?php $rank++; endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Transaksi Terakhir -->
        <div class="report-card-bespoke trx-list-bespoke <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="animation-delay: 0.3s;">
            <div class="card-title-bespoke"><i class="fas fa-clock-rotate-left"></i> Transaksi Terakhir</div>
            <?php if (!empty($lastTrx)): ?>
                <?php foreach ($lastTrx as $trx): ?>
                <div class="trx-card-bespoke" onclick="showTrxDetail(<?= $trx['id'] ?>)" style="cursor: pointer;">
                    <div class="trx-icon-box-bespoke"><i class="fas fa-check-double"></i></div>
                    <div class="trx-info-bespoke">
                        <h5><?= formatRupiah($trx['total_amount']) ?></h5>
                        <p><?= $trx['customer_name'] ?: 'Pelanggan Umum' ?> · <?= $trx['item_count'] ?> item</p>
                    </div>
                    <div class="trx-meta-bespoke">
                        <span class="time"><?= date('H:i', strtotime($trx['created_at'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 0; opacity: 0.2;">
                    <i class="fas fa-receipt fa-3x" style="margin-bottom: 20px;"></i>
                    <p style="font-weight: 900; letter-spacing: 5px; text-transform: uppercase; font-size: 0.8rem;">Belum ada transaksi</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="animate-up" style="text-align: center; margin: -10px 20px 30px; animation-delay: 0.35s;">
            <a href="riwayat.php" class="cat-btn-laporan" style="display: inline-block; width: 100%; text-align: center; background: rgba(181, 141, 61, 0.1); color: var(--gold-luxury); border: 1px solid var(--gold-luxury);">
                <i class="fas fa-history" style="margin-right: 8px;"></i> LIHAT RIWAYAT LENGKAP
            </a>
        </div>

<?php if (isset($_GET['ajax'])) { echo ob_get_clean(); exit; } ?>
<?php if (!isset($_GET['ajax'])): ?>
        </div> <!-- End #reportContent -->

        <nav class="luxury-bottom-dock">
            <a href="dashboard.php" class="dock-link"><i class="fas fa-compass"></i><span>Beranda</span></a>
            <a href="kasir.php" class="dock-link"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
            <a href="menu.php" class="dock-link"><i class="fas fa-layer-group"></i><span>Menu</span></a>
            <a href="stok.php" class="dock-link"><i class="fas fa-warehouse"></i><span>Stok</span></a>
            <a href="laporan.php" class="dock-link active"><i class="fas fa-chart-pie"></i><span>Laporan</span></a>
        </nav>
    </div>

    <!-- Modal Detail Transaksi -->
    <div class="modal-premium-overlay" id="trxDetailOverlay">
        <div class="modal-premium">
            <div class="luxury-sheet">
                <div class="card-title-bespoke"><i class="fas fa-receipt"></i> Detail Transaksi</div>
                <div id="trxDetailContent">
                    <!-- Loaded via AJAX -->
                    <div style="text-align: center; padding: 40px; opacity: 0.5;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
                <button onclick="closeTrxDetail()" class="cat-btn-laporan" style="width: 100%; margin-top: 20px; background: rgba(255,255,255,0.05);">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        // 1. Running Numbers Animation
        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const val = Math.floor(progress * (end - start) + start);
                obj.innerHTML = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        const omzetEl = document.querySelector('.main-val');
        if(omzetEl) {
            const target = parseInt(omzetEl.getAttribute('data-target'));
            animateValue(omzetEl, 0, target, 1500);
        }

        // 2. Transaction Detail Logic
        function showTrxDetail(id) {
            const overlay = document.getElementById('trxDetailOverlay');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';

            fetch(`api_trx_detail.php?id=${id}`)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('trxDetailContent').innerHTML = html;
                });
        }

        function closeTrxDetail() {
            document.getElementById('trxDetailOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        // AJAX Report Logic (Modern & Fast)
        function loadReport(period, btn = null) {
            // UI Feedback
            if(btn) {
                document.querySelectorAll('.cat-btn-laporan').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            }
            document.querySelector('.custom-date-box').style.display = 'none';

            // Loading State (Opacity)
            const container = document.getElementById('reportContent');
            container.style.opacity = '0.5';
            container.style.pointerEvents = 'none';

            fetch(`laporan.php?ajax=1&period=${period}`)
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'all';
                    
                    // Re-run animations
                    const omzetEl = container.querySelector('.main-val');
                    if(omzetEl) animateValue(omzetEl, 0, parseInt(omzetEl.getAttribute('data-target')), 1000);
                    
                    // Update URL without reload
                    const url = new URL(window.location);
                    url.searchParams.set('period', period);
                    url.searchParams.delete('start');
                    url.searchParams.delete('end');
                    window.history.pushState({}, '', url);
                });
        }

        function toggleCustomDate() {
            const box = document.querySelector('.custom-date-box');
            box.style.display = box.style.display === 'grid' ? 'none' : 'grid';
            document.querySelectorAll('.cat-btn-laporan').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
        }

        function handleCustomDate(e) {
            e.preventDefault();
            const start = document.getElementById('calStart').value;
            const end = document.getElementById('calEnd').value;
            
            const container = document.getElementById('reportContent');
            container.style.opacity = '0.5';

            fetch(`laporan.php?ajax=1&period=custom&start=${start}&end=${end}`)
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                    container.style.opacity = '1';
                    
                    const omzetEl = container.querySelector('.main-val');
                    if(omzetEl) animateValue(omzetEl, 0, parseInt(omzetEl.getAttribute('data-target')), 1000);

                     // Update URL
                    const url = new URL(window.location);
                    url.searchParams.set('period', 'custom');
                    url.searchParams.set('start', start);
                    url.searchParams.set('end', end);
                    window.history.pushState({}, '', url);
                });
        }

        // Auto-show custom date if period is custom
        if ("<?= $period ?>" === "custom") {
            document.querySelector('.custom-date-box').style.display = 'grid';
        }
    </script>
<?php endif; ?>

    
    

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
