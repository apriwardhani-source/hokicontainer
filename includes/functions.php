<?php
// Helper functions

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function formatPrice($amount) {
    if ($amount >= 1000) {
        return number_format($amount / 1000, 0) . 'K';
    }
    return number_format($amount, 0);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/admin/login.php');
        exit;
    }
}

function login($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        return true;
    }
    return false;
}

function getUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getSetting($key) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : null;
}

function updateSetting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$key, $value, $value]);
}

function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order");
    return $stmt->fetchAll();
}

function getMenuItems($categoryId = null) {
    global $pdo;
    if ($categoryId) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? ORDER BY name");
        $stmt->execute([$categoryId]);
    } else {
        $stmt = $pdo->query("SELECT m.*, c.name as category_name FROM menu_items m LEFT JOIN categories c ON m.category_id = c.id ORDER BY c.sort_order, m.name");
    }
    return $stmt->fetchAll();
}

function getMenuItemsPaginated($categoryId = 'all', $search = '', $page = 1, $limit = 12) {
    global $pdo;
    $offset = ($page - 1) * $limit;
    $params = [];
    $sql = "SELECT m.*, c.name as category_name FROM menu_items m LEFT JOIN categories c ON m.category_id = c.id WHERE 1=1";

    if ($categoryId !== 'all' && $categoryId !== '') {
        $sql .= " AND m.category_id = ?";
        $params[] = $categoryId;
    }

    if ($search !== '') {
        $sql .= " AND m.name LIKE ?";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY c.sort_order, m.name LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getMenuItemsCount($categoryId = 'all', $search = '') {
    global $pdo;
    $params = [];
    $sql = "SELECT COUNT(*) as total FROM menu_items m WHERE 1=1";

    if ($categoryId !== 'all' && $categoryId !== '') {
        $sql .= " AND m.category_id = ?";
        $params[] = $categoryId;
    }

    if ($search !== '') {
        $sql .= " AND m.name LIKE ?";
        $params[] = "%$search%";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch()['total'];
}

function getMenuVariants($menuItemId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM menu_variants WHERE menu_item_id = ? ORDER BY price");
    $stmt->execute([$menuItemId]);
    return $stmt->fetchAll();
}

function getStockItems() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM stock_items ORDER BY name");
    return $stmt->fetchAll();
}

function getLowStockItems() {
    global $pdo;
    // Updated to query menu_items based on stok.php logic (stock <= 5)
    $stmt = $pdo->query("SELECT * FROM menu_items WHERE stock IS NOT NULL AND stock <= 5 ORDER BY stock ASC");
    return $stmt->fetchAll();
}

function getTodayTransactions() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM transactions WHERE DATE(created_at) = CURDATE() AND status = 'completed' ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function getTodaySales() {
    global $pdo;
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM transactions WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
    $result = $stmt->fetch();
    return $result['total'];
}

function getTodayTransactionCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE DATE(created_at) = CURDATE() AND status = 'completed'");
    $result = $stmt->fetch();
    return $result['count'];
}

function getTopSellingItems($limit = 5, $days = 7) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT ti.item_name, SUM(ti.quantity) as total_qty, SUM(ti.subtotal) as total_sales
        FROM transaction_items ti
        JOIN transactions t ON ti.transaction_id = t.id
        WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        AND t.status = 'completed'
        GROUP BY ti.item_name
        ORDER BY total_qty DESC
        LIMIT ?
    ");
    $stmt->execute([$days, $limit]);
    return $stmt->fetchAll();
}

function generateWhatsAppReport() {
    global $pdo;
    
    $storeName = getSetting('store_name') ?: APP_NAME;
    $today = date('d F Y');
    $todaySales = getTodaySales();
    $transactionCount = getTodayTransactionCount();
    $avgPerTrx = $transactionCount > 0 ? $todaySales / $transactionCount : 0;
    
    // Total items sold today
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(ti.quantity), 0) as total FROM transaction_items ti JOIN transactions t ON ti.transaction_id = t.id WHERE DATE(t.created_at) = CURDATE() AND t.status = 'completed'");
    $stmt->execute();
    $totalItems = $stmt->fetch()['total'];
    
    // Get all transactions today
    $stmt = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE DATE(created_at) = CURDATE() AND status = 'completed'
        ORDER BY created_at ASC
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll();
    
    // Fetch items for each transaction separately to avoid complex subquery errors
    foreach ($transactions as &$trx) {
        $stmtItems = $pdo->prepare("
            SELECT item_name, SUM(quantity) as total_qty 
            FROM transaction_items 
            WHERE transaction_id = ? 
            GROUP BY item_name
        ");
        $stmtItems->execute([$trx['id']]);
        $itemsInfo = $stmtItems->fetchAll();
        
        $itemParts = [];
        foreach ($itemsInfo as $ii) {
            $itemParts[] = $ii['item_name'] . " x" . $ii['total_qty'];
        }
        $trx['items'] = implode(', ', $itemParts);
    }
    
    // Build message
    $message = "═══════════════════\n";
    $message .= "📊 *LAPORAN HARIAN*\n";
    $message .= "*{$storeName}*\n";
    $message .= "═══════════════════\n\n";
    
    $message .= "📅 {$today}\n\n";
    
    $message .= "💵 *TOTAL: " . formatRupiah($todaySales) . "*\n";
    $message .= "🧾 {$transactionCount} transaksi · {$totalItems} item\n\n";
    
    if (!empty($transactions)) {
        $message .= "📋 *DETAIL TRANSAKSI*\n";
        $message .= "━━━━━━━━━━━━━━━━━━━\n";
        $no = 1;
        foreach ($transactions as $trx) {
            $time = date('H:i', strtotime($trx['created_at']));
            $amount = formatRupiah($trx['total_amount']);
            $items = $trx['items'];
            $message .= "\n*{$no}. [{$time}]* - {$amount}\n";
            $message .= "   {$items}\n";
            $no++;
        }
        $message .= "\n";
    }
    
    $message .= "═══════════════════\n";
    $message .= "🕐 Dikirim: " . date('H:i') . " WITA";
    
    return urlencode($message);
}

function alert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        $isSuccess = $alert['type'] === 'success';
        $icon = $isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';
        $bgColor = $isSuccess ? '#10b981' : '#ef4444';
        echo "
        <div id='toastAlert' style='
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: {$bgColor};
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            animation: slideDown 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
        '>
            <i class='fas {$icon}'></i>
            {$alert['message']}
        </div>
        <style>
            @keyframes slideDown {
                from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
                to { opacity: 1; transform: translateX(-50%) translateY(0); }
            }
        </style>
        <script>setTimeout(() => document.getElementById('toastAlert')?.remove(), 3000);</script>
        ";
    }
}

function formatRupiahShort($amount) {
    if ($amount >= 1000000) {
        return 'Rp ' . number_format($amount / 1000000, 1, ',', '.') . 'jt';
    } else if ($amount >= 1000) {
        return 'Rp ' . number_format($amount / 1000, 0, ',', '.') . 'rb';
    }
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function getGreeting() {
    $hour = (int)date('H');
    if ($hour >= 5 && $hour < 11) return "Selamat Pagi";
    if ($hour >= 11 && $hour < 15) return "Selamat Siang";
    if ($hour >= 15 && $hour < 18) return "Selamat Sore";
    return "Selamat Malam";
}
