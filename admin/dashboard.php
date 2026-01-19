<?php
// Debugging top-most level
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<!-- DEBUG: Database and Functions loaded -->";

requireLogin();

echo "<!-- DEBUG: Login verified -->";

try {
    $user = getUser();
    $todaySales = getTodaySales();
    $todayTransactions = getTodayTransactionCount();
    $lowStockItems = getLowStockItems();
    $topItems = getTopSellingItems(5, 7);
    $avgPerTrx = $todayTransactions > 0 ? $todaySales / $todayTransactions : 0;
    $ownerWa = getSetting('owner_wa') ?: OWNER_WA;
    $waMessage = generateWhatsAppReport();
} catch (Exception $e) {
    die("ERROR DATABASE: " . $e->getMessage());
} catch (Error $e) {
    die("PHP ERROR: " . $e->getMessage() . " on line " . $e->getLine());
}

echo "<!-- DEBUG: All data fetched -->";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include __DIR__ . '/../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Hoki Admin - Ultra Luxury Dashboard</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@700;900&display=swap" rel="stylesheet">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css" as="style">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=1.2.5">
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

    <div class="app-container">
        <header class="header-aligned-luxury animate-up">
            <div class="header-brand-luxury">
                <img src="../assets/img/logo.png" class="header-logo-luxury">
            </div>

            <h1 class="font-heavy dashboard-greeting"><?= getGreeting() ?>, <?= explode(' ', $user['name'])[0] ?></h1>

            <div class="header-action">
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                </a>
            </div>
        </header>

        <section class="hero-sales-panel animate-up" style="animation-delay: 0.1s;">
            <span class="label">Omzet Penjualan Hari Ini</span>
            <div class="amount" data-target="<?= (int)$todaySales ?>">Rp 0</div>
            
            <div class="summary-stats-grid">
                <div class="stat-capsule">
                    <span class="val animate-num" data-target="<?= (int)$todayTransactions ?>">0</span>
                    <span class="lbl">Pesanan</span>
                </div>
                <div class="stat-capsule">
                    <span class="val animate-num" data-type="currency" data-target="<?= (int)$avgPerTrx ?>">Rp 0</span>
                    <span class="lbl">Rata"/pelanggan</span>
                </div>
            </div>
        </section>

        <?php if (count($lowStockItems) > 0): ?>
        <a href="stok.php" class="notification-strip animate-up" style="animation-delay: 0.2s;">
            <i class="fas fa-bell-concierge"></i>
            <span class="msg">PENTING: <?= count($lowStockItems) ?> Item perlu segera direstok</span>
            <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
        </a>
        <?php endif; ?>

        <div class="feature-dock animate-up" style="animation-delay: 0.25s;">
            <a href="kasir.php" class="dock-item">
                <div class="dock-icon"><i class="fas fa-id-card-clip"></i></div>
                <span>Kasir Digital</span>
            </a>
            <a href="menu.php" class="dock-item">
                <div class="dock-icon"><i class="fas fa-leaf"></i></div>
                <span>Katalog Menu</span>
            </a>
            <a href="stok.php" class="dock-item">
                <div class="dock-icon"><i class="fas fa-cubes-stacked"></i></div>
                <span>Manajemen Stok</span>
            </a>
            <a href="https://wa.me/<?= $ownerWa ?>?text=<?= $waMessage ?>" target="_blank" class="dock-item">
                <div class="dock-icon" style="color: #4ade80;"><i class="fas fa-paper-plane"></i></div>
                <span>Kirim Laporan</span>
            </a>
        </div>

        <section class="section-luxury animate-up" style="animation-delay: 0.3s;">
            <h3>Menu Terpopuler</h3>

            <?php if (!empty($topItems)): ?>
                <?php $rank = 1; foreach ($topItems as $item): 
                    $isGold = $rank == 1;
                ?>
                <div class="top-seller-row">
                    <div class="rank-indicator <?= $isGold ? 'gold' : '' ?>"><?= $rank ?></div>
                    <div class="seller-info">
                        <h4><?= htmlspecialchars($item['item_name']) ?></h4>
                        <p><?= (int)$item['total_qty'] ?> porsi terjual</p>
                    </div>
                    <div class="seller-value">
                        <strong><?= formatRupiahShort($item['total_sales']) ?></strong>
                    </div>
                </div>
                <?php $rank++; endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 0; opacity: 0.1;">
                    <i class="fas fa-compass fa-3x" style="margin-bottom: 20px;"></i>
                    <p style="font-weight: 900; letter-spacing: 5px;">MENUNGGU DATA</p>
                </div>
            <?php endif; ?>
        </section>

        <nav class="luxury-bottom-dock">
            <a href="dashboard.php" class="dock-link active"><i class="fas fa-compass"></i><span>Beranda</span></a>
            <a href="kasir.php" class="dock-link"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
            <a href="menu.php" class="dock-link"><i class="fas fa-layer-group"></i><span>Menu</span></a>
            <a href="stok.php" class="dock-link"><i class="fas fa-warehouse"></i><span>Stok</span></a>
            <a href="laporan.php" class="dock-link"><i class="fas fa-chart-pie"></i><span>Laporan</span></a>
        </nav>
    </div>
    <script>
        function animateValue(obj, start, end, duration, isCurrency = false) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const val = Math.floor(progress * (end - start) + start);
                
                if (isCurrency) {
                    obj.innerHTML = new Intl.NumberFormat('id-ID', { 
                        style: 'currency', 
                        currency: 'IDR', 
                        maximumFractionDigits: 0 
                    }).format(val);
                } else {
                    obj.innerHTML = val.toLocaleString('id-ID');
                }

                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const mainAmount = document.querySelector('.hero-sales-panel .amount');
            if (mainAmount) {
                animateValue(mainAmount, 0, parseInt(mainAmount.dataset.target), 1500, true);
            }

            document.querySelectorAll('.animate-num').forEach(el => {
                const target = parseInt(el.dataset.target);
                const isCurrency = el.dataset.type === 'currency';
                animateValue(el, 0, target, 1500, isCurrency);
            });
        });
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
