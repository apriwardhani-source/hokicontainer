<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM transaction_items WHERE transaction_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        echo json_encode(['success' => true]); exit;
    }
    header("Location: riwayat.php?date=" . ($_POST['date'] ?? date('Y-m-d'))); 
    exit;
}

$filterDate = $_GET['date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count FROM transactions WHERE DATE(created_at) = ? AND status = 'completed'");
$stmt->execute([$filterDate]);
$daySummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(ti.quantity), 0) as total_items FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE DATE(t.created_at) = ? AND t.status = 'completed'");
$stmt->execute([$filterDate]);
$dayItems = $stmt->fetch()['total_items'];

$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT GROUP_CONCAT(CONCAT(item_name, ' x', quantity) SEPARATOR ', ') 
            FROM transaction_items WHERE transaction_id = t.id) as items,
           (SELECT SUM(quantity) FROM transaction_items WHERE transaction_id = t.id) as item_count
    FROM transactions t
    WHERE DATE(t.created_at) = ? AND t.status = 'completed'
    ORDER BY t.created_at DESC
");
$stmt->execute([$filterDate]);
$transactions = $stmt->fetchAll();

$avgPerTrx = $daySummary['count'] > 0 ? $daySummary['total'] / $daySummary['count'] : 0;
$isToday = $filterDate === date('Y-m-d');
$dateLabel = $isToday ? 'Hari Ini' : date('d M Y', strtotime($filterDate));

if (isset($_GET['ajax'])) {
    ob_start();
}
?>
<?php if (!isset($_GET['ajax'])): ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include '../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Arsip Transaksi - Hoki Container</title>
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
        html, body { background-color: var(--dark-deep) !important; can-override: true; }
        .app-container { background: transparent !important; box-shadow: none !important; }
        .bespoke-bg { position: fixed; inset: 0; z-index: -1; overflow: hidden; }


        .date-navigator-bespoke {
            padding: 20px;
            background: linear-gradient(to bottom, rgba(10, 10, 11, 0.9), transparent);
        }
        .luxury-date-input {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
        }
        .luxury-date-input i { color: var(--gold-luxury); font-size: 1.1rem; cursor: pointer; }
        .luxury-date-input .date-label {
            flex: 1;
            color: white;
            font-size: 0.95rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .luxury-date-input input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
        }
        select::-webkit-calendar-picker-indicator {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .summary-ribbon-bespoke {
            display: flex;
            gap: 12px;
            padding: 0 20px 20px;
        }
        .ribbon-box-bespoke {
            flex: 1;
            background: rgba(30, 30, 35, 0.6) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 18px 10px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .ribbon-box-bespoke .val { font-size: 1.1rem; font-weight: 950; display: block; margin-bottom: 4px; color: white !important; }
        .ribbon-box-bespoke .lbl { font-size: 0.65rem; color: rgba(255,255,255,0.5); font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; }

        .trx-list-bespoke { padding: 0 20px 120px; }
        .trx-card-bespoke {
            background: rgba(30, 30, 35, 0.6) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 24px;
            padding: 22px;
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .trx-header-bespoke {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            border-bottom: 1px dashed rgba(255,255,255,0.1);
            padding-bottom: 12px;
        }
        .trx-amount-bespoke { font-size: 1.2rem; font-weight: 950; color: var(--gold-bright); letter-spacing: -0.5px; }
        .trx-time-bespoke { font-size: 0.75rem; color: rgba(255,255,255,0.4); font-weight: 700; display: flex; align-items: center; gap: 5px; }

        .trx-items-bespoke {
            background: rgba(0,0,0,0.2);
            border-radius: 14px;
            padding: 12px 15px;
            margin-bottom: 18px;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.8);
            line-height: 1.5;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .trx-footer-bespoke {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .trx-cust-bespoke {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gold-luxury);
            font-size: 0.85rem;
            font-weight: 800;
        }
        .trx-cust-bespoke i {
            width: 28px;
            height: 28px;
            background: rgba(197, 160, 40, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .btn-trash-bespoke {
            width: 38px;
            height: 38px;
            background: rgba(239, 68, 68, 0.05);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-trash-bespoke:active { background: rgba(239, 68, 68, 0.2); transform: scale(0.9); }
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
        <div class="glow-blob" style="bottom: 10%; right: -10%; background: radial-gradient(circle, rgba(197, 160, 40, 0.08) 0%, transparent 70%);"></div>
    </div>

    <div class="app-container">
        <header class="header-aligned-luxury animate-up">
            <div class="header-brand-luxury">
                <a href="laporan.php" class="header-back"><i class="fas fa-chevron-left" style="font-size: 0.9rem;"></i></a>
                <img src="../assets/img/logo.png" class="header-logo-luxury">
            </div>
            <h1 class="font-heavy">Riwayat Transaksi</h1>
            <div style="width: 40px;"></div>
        </header>

        <div id="riwayatContent">
<?php endif; // End Header Check ?>

        <div class="date-navigator-bespoke <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="animation-delay: 0.05s;">
            <div class="luxury-date-input" onclick="document.getElementById('historyDate').showPicker()">
                <i class="fas fa-calendar-day"></i>
                <div class="date-label"><?= date('d F Y', strtotime($filterDate)) ?></div>
                <input type="date" id="historyDate" value="<?= $filterDate ?>" onchange="loadHistory(this.value)">
                <i class="fas fa-chevron-down" style="font-size: 0.8rem; opacity: 0.3;"></i>
            </div>
            
            <div class="category-scroller-bespoke">
                <button type="button" onclick="loadHistory('<?= date('Y-m-d') ?>')" class="cat-pill-bespoke <?= $filterDate === date('Y-m-d') ? 'active' : '' ?>">HARI INI</button>
                <button type="button" onclick="loadHistory('<?= date('Y-m-d', strtotime('-1 day')) ?>')" class="cat-pill-bespoke <?= $filterDate === date('Y-m-d', strtotime('-1 day')) ? 'active' : '' ?>">KEMARIN</button>
                <?php for($i=2; $i<=4; $i++): 
                    $d = date('Y-m-d', strtotime("-$i days"));
                ?>
                <button type="button" onclick="loadHistory('<?= $d ?>')" class="cat-pill-bespoke <?= $filterDate === $d ? 'active' : '' ?>"><?= date('d M', strtotime($d)) ?></button>
                <?php endfor; ?>
            </div>
        </div>

        <div class="summary-ribbon-bespoke <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="animation-delay: 0.1s;">
            <div class="ribbon-box-bespoke luxury-glass">
                <span class="val gold-gradient-text"><?= formatRupiahShort($daySummary['total']) ?></span>
                <span class="lbl">Omzet</span>
            </div>
            <div class="ribbon-box-bespoke luxury-glass">
                <span class="val"><?= (int)$daySummary['count'] ?></span>
                <span class="lbl">Transaksi</span>
            </div>
            <div class="ribbon-box-bespoke luxury-glass">
                <span class="val"><?= (int)$dayItems ?></span>
                <span class="lbl">Produk</span>
            </div>
        </div>

        <div class="trx-list-bespoke <?= isset($_GET['ajax']) ? '' : 'animate-up' ?>" style="animation-delay: 0.15s;">
            <?php if (!empty($transactions)): ?>
                <?php foreach ($transactions as $trx): ?>
                <div class="trx-card-bespoke luxury-glass">
                    <div class="trx-header-bespoke">
                        <span class="trx-amount-bespoke"><?= formatRupiah($trx['total_amount']) ?></span>
                        <span class="trx-time-bespoke"><i class="far fa-clock"></i> <?= date('H:i', strtotime($trx['created_at'])) ?></span>
                    </div>
                    
                    <div class="trx-items-bespoke">
                        <i class="fas fa-receipt" style="font-size: 0.7rem; opacity: 0.3; margin-right: 8px;"></i>
                        <?= htmlspecialchars($trx['items']) ?>
                    </div>
                    
                    <div class="trx-footer-bespoke">
                        <div class="trx-cust-bespoke">
                            <i><i class="fas fa-user-tag"></i></i>
                            <?= !empty($trx['customer_name']) ? htmlspecialchars($trx['customer_name']) : 'Pelanggan Tanpa Nama' ?>
                        </div>
                        <button class="btn-trash-bespoke" onclick="deleteTrx(<?= $trx['id'] ?>, this)"><i class="fas fa-trash-can"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 80px 0; opacity: 0.2;">
                    <i class="fas fa-ghost" style="font-size: 4rem; margin-bottom: 20px;"></i>
                    <p style="font-weight: 900; letter-spacing: 2px;">TIDAK ADA RIWAYAT TRANSAKSI</p>
                    <p style="font-size: 0.75rem;">Tidak ada rekaman data pada tanggal ini</p>
                </div>
            <?php endif; ?>
        </div>
        
<?php if (isset($_GET['ajax'])) { echo ob_get_clean(); exit; } ?>
<?php if (!isset($_GET['ajax'])): ?>
        </div> <!-- End #riwayatContent -->

        <nav class="luxury-bottom-dock">
            <a href="dashboard.php" class="dock-link"><i class="fas fa-compass"></i><span>Beranda</span></a>
            <a href="kasir.php" class="dock-link"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
            <a href="menu.php" class="dock-link"><i class="fas fa-layer-group"></i><span>Menu</span></a>
            <a href="stok.php" class="dock-link"><i class="fas fa-warehouse"></i><span>Stok</span></a>
            <a href="laporan.php" class="dock-link active"><i class="fas fa-chart-pie"></i><span>Laporan</span></a>
        </nav>
    </div>

    <script>
        function loadHistory(date) {
        // UI Feedback: Opacity check
        const container = document.getElementById('riwayatContent');
        container.style.opacity = '0.5';
        container.style.pointerEvents = 'none';

        // Update URL
        const url = new URL(window.location);
        url.searchParams.set('date', date);
        window.history.pushState({}, '', url);

        fetch(`riwayat.php?ajax=1&date=${date}`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
                container.style.pointerEvents = 'all';
                
                // Re-bind value to input if needed (though HTML replacement handles it)
                // document.getElementById('historyDate').value = date;
            });
    }

    async function deleteTrx(id, btn) {
        if (!confirm('Hapus transaksi ini?')) return;
        const card = btn.closest('.trx-card-bespoke');
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        formData.append('date', document.getElementById('historyDate').value);

        const res = await fetch('riwayat.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();
        if (json.success) {
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            card.style.transition = 'all 0.3s ease';
            setTimeout(() => card.remove(), 300);
        }
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
