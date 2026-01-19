<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

$categories = getCategories();
$menuItems = getMenuItems();

$currentFilter = $_GET['cat'] ?? $_POST['current_category'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectCat = $_POST['current_category'] ?? 'all';
    
    if ($action === 'add' || $action === 'edit') {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $categoryId = $_POST['category_id'] ?: null;
        $stock = $_POST['stock'] !== '' ? $_POST['stock'] : null;
        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
        }
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, name, price, stock, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$categoryId, $name, $price, $stock, $image]);
        } else {
            $id = $_POST['id'];
            if ($image) {
                $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, name = ?, price = ?, stock = ?, image = ? WHERE id = ?");
                $stmt->execute([$categoryId, $name, $price, $stock, $image, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE menu_items SET category_id = ?, name = ?, price = ?, stock = ? WHERE id = ?");
                $stmt->execute([$categoryId, $name, $price, $stock, $id]);
            }
        }
    }
    if ($action === 'toggle') {
        $stmt = $pdo->prepare("UPDATE menu_items SET is_available = NOT is_available WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]); exit;
        }
    }
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]); exit;
        }
    }
    header("Location: menu.php?cat=$redirectCat"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include '../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Katalog Menu - Hoki Container</title>
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

        .menu-header-luxury {
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
        .menu-header-luxury h1 {
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

        /* Category ribbon style from stok/kasir */
        .category-ribbon-menu {
            display: flex;
            gap: 12px;
            padding: 0 0 20px;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .category-ribbon-menu::-webkit-scrollbar { display: none; }
        .cat-btn-menu {
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
        .cat-btn-menu.active {
            background: var(--gold-luxury);
            color: #111;
            border-color: transparent;
            box-shadow: 0 8px 20px rgba(181, 141, 61, 0.2);
        }

        .menu-grid-bespoke {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 0 15px 120px;
        }
        @media (max-width: 480px) {
            .menu-grid-bespoke { grid-template-columns: repeat(2, 1fr); }
        }
        /* Overriding for user request: 3 columns priority */
        .menu-grid-bespoke { grid-template-columns: repeat(3, 1fr) !important; }

        .menu-card-bespoke {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 30px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .menu-card-bespoke:active { transform: scale(0.95); }
        .menu-card-bespoke.unavailable { opacity: 0.5; filter: grayscale(0.8); }

        .image-wrapper-bespoke {
            height: 120px;
            background: rgba(255,255,255,0.03);
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
            position: relative;
            transition: transform 0.6s ease;
        }
        .menu-card-bespoke:hover .image-wrapper-bespoke { transform: scale(1.08); }
        .image-wrapper-bespoke::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, transparent 40%, rgba(10, 10, 11, 0.9));
        }
        .image-wrapper-bespoke i { font-size: 1.5rem; color: rgba(255,255,255,0.05); }

        .badge-status-bespoke {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.5rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        .badge-status-bespoke.active { background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge-status-bespoke.inactive { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .menu-info-bespoke { padding: 12px 12px 5px; flex: 1; }
        .menu-info-bespoke .category { font-size: 0.5rem; font-weight: 800; color: var(--gold-luxury) !important; background: rgba(181, 141, 61, 0.15); padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 7px; display: inline-block; }
        .menu-info-bespoke h4 { font-size: 0.85rem; font-weight: 800; color: #ffffff !important; margin-bottom: 6px; line-height: 1.4; letter-spacing: -0.1px; }
        .menu-info-bespoke .price { font-size: 1rem; font-weight: 950; color: var(--gold-bright) !important; margin-bottom: 12px; font-family: 'Plus Jakarta Sans', sans-serif; }

        .menu-actions-bespoke {
            display: flex;
            gap: 5px;
            padding: 5px 10px 10px;
        }
        .btn-action-bespoke {
            flex: 1;
            height: 32px;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.03);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-action-bespoke i { opacity: 0.7; }
        .btn-action-bespoke.edit:active { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .btn-action-bespoke.toggle:active { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .btn-action-bespoke.ingredients:active { background: rgba(197, 160, 40, 0.2); color: var(--gold-bright); }
        .btn-action-bespoke.delete:active { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        /* Media Upload Preview */
        /* Media Upload Preview */
        .preview-upload-bespoke {
            width: 100%;
            height: 180px;
            border-radius: 30px;
            border: 2px dashed rgba(181, 141, 61, 0.2);
            background: rgba(181, 141, 61, 0.02);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        .preview-upload-bespoke:hover {
            border-color: var(--gold-luxury);
            background: rgba(181, 141, 61, 0.05);
        }
        .preview-upload-bespoke img { width: 100%; height: 100%; object-fit: cover; }
        .preview-upload-bespoke .placeholder { text-align: center; color: rgba(255,255,255,0.3); }
        .preview-upload-bespoke .placeholder i { font-size: 2.2rem; margin-bottom: 15px; color: var(--gold-luxury); opacity: 0.8; }
        .preview-upload-bespoke input { position: absolute; inset: 0; opacity: 0; cursor: pointer; z-index: 2; }

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
        <div class="glow-blob" style="top: -10%; left: -10%;"></div>
        <div class="glow-blob" style="bottom: 10%; right: -10%; background: radial-gradient(circle, rgba(197, 160, 40, 0.08) 0%, transparent 70%);"></div>
    </div>

    

    <div class="app-container">
        <header class="header-aligned-luxury animate-up">
            <div class="header-brand-luxury">
                <a href="dashboard.php" class="header-back"><i class="fas fa-chevron-left" style="font-size: 0.9rem;"></i></a>
                <img src="../assets/img/logo.png" class="header-logo-luxury">
            </div>

            <h1 class="font-heavy">Menu</h1>

            <div class="header-action">
                <button class="btn-plus-luxury" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> TAMBAH
                </button>
            </div>
        </header>

        <div class="search-area-luxury animate-up" style="animation-delay: 0.05s;">
            <div class="luxury-input-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari nama menu..." onkeyup="filterMenu()">
            </div>
            
            <div class="category-ribbon-menu" style="margin-top: 15px;">
                <div class="cat-btn-menu active" onclick="filterByCategory('all', this)">Semua</div>
                <?php foreach ($categories as $cat): ?>
                <div class="cat-btn-menu" onclick="filterByCategory(<?= $cat['id'] ?>, this)"><?= htmlspecialchars($cat['name']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="menu-grid-bespoke animate-up" id="menuGrid" style="animation-delay: 0.1s;">
            <?php foreach ($menuItems as $item): ?>
            <div class="menu-card-bespoke <?= !$item['is_available'] ? 'unavailable' : '' ?>" data-category="<?= $item['category_id'] ?>" data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>">
                <div class="badge-status-bespoke <?= $item['is_available'] ? 'active' : 'inactive' ?>">
                    <?= $item['is_available'] ? 'Aktif' : 'Habis' ?>
                </div>
                <div class="image-wrapper-bespoke" style="<?= $item['image'] ? "background-image: url('../uploads/".htmlspecialchars($item['image'])."');" : "" ?>">
                    <?php if (!$item['image']): ?><i class="fas fa-bowl-food"></i><?php endif; ?>
                </div>
                <div class="menu-info-bespoke">
                    <span class="category"><?= htmlspecialchars($item['category_name'] ?? 'Lainnya') ?></span>
                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                    <div class="price gold-gradient-text"><?= formatRupiah($item['price']) ?></div>
                </div>
                <div class="menu-actions-bespoke">
                    <button class="btn-action-bespoke toggle" onclick="toggleItem(<?= $item['id'] ?>, this)" title="Tersedia/Habis">
                        <i class="fas <?= $item['is_available'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                    </button>
                    <button class="btn-action-bespoke edit" onclick='openModal("edit", <?= json_encode($item) ?>)' title="Edit Menu">
                        <i class="fas fa-pen-to-square"></i>
                    </button>
                    <button class="btn-action-bespoke delete" onclick="deleteItem(<?= $item['id'] ?>, this)" title="Hapus Menu">
                        <i class="fas fa-trash-can"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <div id="noResults" style="display: none; grid-column: 1 / -1; text-align: center; padding: 60px 0; opacity: 0.2;">
                <i class="fas fa-search fa-3x" style="margin-bottom: 20px;"></i>
                <p style="font-weight: 900; letter-spacing: 5px; text-transform: uppercase; font-size: 0.8rem;">Menu tidak ditemukan</p>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal-premium-overlay" id="menuModal" onclick="if(event.target==this) closeModal()">
            <div class="modal-premium luxury-glass" style="padding: 35px 30px;">
                <div class="pull-bar"></div>
                <h3 id="modalTitle" class="font-heavy gold-gradient-text" style="text-align: center; margin-bottom: 25px; font-size: 1.4rem;">Tambah Menu</h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="formId">
                    <input type="hidden" name="current_category" id="currentCategory" value="all">
                    
                    <div class="preview-upload-bespoke">
                        <div class="placeholder" id="imagePlaceholder">
                            <i class="fas fa-camera-retro"></i>
                            <p style="font-size: 0.7rem; font-weight: 850; letter-spacing: 1px;">UNGGAH FOTO PRODUK</p>
                        </div>
                        <img id="previewImg" style="display: none;">
                        <input type="file" name="image" accept="image/*" onchange="previewFile(this)">
                    </div>

                    <div style="margin-bottom: 22px;">
                        <label class="label-premium">Nama Produk</label>
                        <input type="text" name="name" id="formName" class="input-premium" required placeholder="Contoh: Es Cokelat Premium">
                    </div>

                    <div style="display: flex; gap: 15px; margin-bottom: 22px;">
                        <div style="flex: 1;">
                            <label class="label-premium">Harga (Rp)</label>
                            <input type="number" name="price" id="formPrice" class="input-premium" required placeholder="15000">
                        </div>
                        <div style="flex: 1;">
                            <label class="label-premium">Stok Awal</label>
                            <input type="number" name="stock" id="formStock" class="input-premium" placeholder="50">
                        </div>
                    </div>

                    <div style="margin-bottom: 35px;">
                        <label class="label-premium">Kategori</label>
                        <select name="category_id" id="formCategory" class="input-premium">
                            <option value="">Pilih Kategori</option>
                            <?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 15px;">
                        <button type="button" class="btn-premium" style="flex: 1;" onclick="closeModal()">Batal</button>
                        <button type="submit" class="btn-premium btn-premium-primary" style="flex: 1.5; padding: 20px;">SIMPAN PRODUK</button>
                    </div>
                </form>
            </div>
        </div>

        <nav class="luxury-bottom-dock">
            <a href="dashboard.php" class="dock-link"><i class="fas fa-compass"></i><span>Beranda</span></a>
            <a href="kasir.php" class="dock-link"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
            <a href="menu.php" class="dock-link active"><i class="fas fa-layer-group"></i><span>Menu</span></a>
            <a href="stok.php" class="dock-link"><i class="fas fa-warehouse"></i><span>Stok</span></a>
            <a href="laporan.php" class="dock-link"><i class="fas fa-chart-pie"></i><span>Laporan</span></a>
        </nav>
    </div>

    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('pageLoader');
            loader.classList.add('loaded');
            setTimeout(() => loader.remove(), 600);
        });

        let currentFilter = 'all';

        function openModal(mode, data = null) {
            document.getElementById('menuModal').classList.add('active');
            document.getElementById('modalTitle').textContent = mode === 'add' ? 'Tambah Menu Baru' : 'Perbarui Menu';
            document.getElementById('formAction').value = mode;
            document.getElementById('formId').value = data ? data.id : '';
            document.getElementById('formName').value = data ? data.name : '';
            document.getElementById('formPrice').value = data ? data.price : '';
            document.getElementById('formCategory').value = data ? (data.category_id || '') : '';
            document.getElementById('formStock').value = data ? (data.stock || '') : '';
            document.getElementById('currentCategory').value = currentFilter;
            
            const preview = document.getElementById('previewImg');
            const placeholder = document.getElementById('imagePlaceholder');
            if (data && data.image) {
                preview.src = '../uploads/' + data.image;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
            }
        }

        function closeModal() { document.getElementById('menuModal').classList.remove('active'); }

        function previewFile(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('previewImg').style.display = 'block';
                    document.getElementById('imagePlaceholder').style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function filterByCategory(id, btn) {
            currentFilter = id;
            document.querySelectorAll('.cat-btn-menu').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            filterMenu();
        }

        function filterMenu() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            let visibleCount = 0;
            document.querySelectorAll('.menu-card-bespoke').forEach(card => {
                const name = card.dataset.name;
                const cat = card.dataset.category;
                const matchName = name.includes(q);
                const matchCat = currentFilter === 'all' || cat == currentFilter;
                
                if (matchName && matchCat) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';
        }

        function toggleItem(id, btn) {
            const params = new URLSearchParams();
            params.append('action', 'toggle');
            params.append('id', id);
            fetch('menu.php', { 
                method: 'POST', 
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: params 
            })
            .then(() => {
                const card = btn.closest('.menu-card-bespoke');
                card.classList.toggle('unavailable');
                const badge = card.querySelector('.badge-status-bespoke');
                badge.classList.toggle('active');
                badge.classList.toggle('inactive');
                badge.textContent = badge.classList.contains('active') ? 'Aktif' : 'Habis';
                
                // Update icon
                const icon = btn.querySelector('i');
                if (badge.classList.contains('active')) {
                    icon.className = 'fas fa-eye';
                } else {
                    icon.className = 'fas fa-eye-slash';
                }
            });
        }

        function deleteItem(id, btn) {
            if (!confirm('Hapus produk ini secara permanen?')) return;
            const params = new URLSearchParams();
            params.append('action', 'delete');
            params.append('id', id);
            fetch('menu.php', { 
                method: 'POST', 
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: params 
            })
            .then(() => btn.closest('.menu-card-bespoke').remove());
        }
    </script>
    
</body>
</html>
