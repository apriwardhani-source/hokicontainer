<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch();

if (!$trx) {
    echo "Transaksi tidak ditemukan.";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM transaction_items WHERE transaction_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>

<div style="margin-bottom: 25px; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 15px;">
    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
        <span style="opacity: 0.5; font-size: 0.7rem; text-transform: uppercase;">Pelanggan</span>
        <span style="font-weight: 800;"><?= $trx['customer_name'] ?: 'Pelanggan Umum' ?></span>
    </div>
    <div style="display: flex; justify-content: space-between;">
        <span style="opacity: 0.5; font-size: 0.7rem; text-transform: uppercase;">Waktu</span>
        <span style="font-weight: 800;"><?= date('d M Y, H:i', strtotime($trx['created_at'])) ?></span>
    </div>
</div>

<div class="items-list-luxury" style="display: flex; flex-direction: column; gap: 15px;">
    <?php foreach ($items as $item): ?>
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="flex: 1;">
            <h6 style="margin: 0; font-size: 0.85rem; font-weight: 700;"><?= htmlspecialchars($item['item_name']) ?></h6>
            <span style="font-size: 0.7rem; color: var(--gold-luxury); font-weight: 700;"><?= $item['quantity'] ?>x <?= formatRupiahShort($item['price']) ?></span>
        </div>
        <div style="text-align: right;">
            <span style="font-weight: 850; font-size: 0.85rem;"><?= formatRupiahShort($item['subtotal']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="bill-panel-luxury" style="margin-top: 30px; padding: 20px; background: rgba(181, 141, 61, 0.05); border-radius: 20px; border: 1px solid rgba(181, 141, 61, 0.1);">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <span style="font-size: 0.75rem; font-weight: 900; text-transform: uppercase; color: var(--gold-luxury);">Total Bayar</span>
        <span style="font-size: 1.3rem; font-weight: 950; color: white;"><?= formatRupiah($trx['total_amount']) ?></span>
    </div>
</div>
