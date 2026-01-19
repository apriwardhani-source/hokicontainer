<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_settings') {
        updateSetting('store_name', $_POST['store_name']);
        updateSetting('store_address', $_POST['store_address']);
        updateSetting('store_hours', $_POST['store_hours']);
        updateSetting('owner_wa', $_POST['owner_wa']);
        alert('Pengaturan unit diperbarui!');
    }
    if ($action === 'update_profile') {
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        if ($stmt->execute([$_POST['name'], $_SESSION['user_id']])) {
            alert('Profil Anda diperbarui!');
        }
    }
    if ($action === 'add_user') {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
        $hashed = password_hash($_POST['new_user_password'], PASSWORD_DEFAULT);
        if ($stmt->execute([$_POST['new_username'], $hashed, $_POST['new_name']])) {
            alert('User baru berhasil ditambahkan!');
        } else {
            alert('Gagal menambahkan user!', 'danger');
        }
    }
    if ($action === 'change_password') {
        $user = getUser();
        if (password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([password_hash($_POST['new_password'], PASSWORD_DEFAULT), $user['id']]);
                alert('Kata sandi berhasil diganti!');
            } else {
                alert('Konfirmasi sandi tidak cocok!', 'danger');
            }
        } else {
            alert('Kata sandi saat ini salah!', 'danger');
        }
    }
    header('Location: settings.php'); exit;
}
$user = getUser();
$storeName = getSetting('store_name') ?: 'Hoki Container';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include '../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pengaturan Akun - Hoki Container</title>
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
        body { background-color: var(--dark-deep) !important; color: var(--white); }
        .app-container { background-color: var(--dark-deep) !important; min-height: 100vh; }
        .bespoke-bg { position: fixed; inset: 0; z-index: -1; overflow: hidden; background: var(--dark-deep); }


        .profile-card-bespoke {
            margin: 25px 20px;
            padding: 40px 25px;
            border-radius: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .profile-avatar-bespoke {
            width: 90px;
            height: 90px;
            background: rgba(255,255,255,0.05);
            border-radius: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            color: var(--gold-bright);
            position: relative;
            border: 1px solid rgba(197, 160, 40, 0.2);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
        .profile-avatar-bespoke::after {
            content: '\f521';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: -10px;
            right: -10px;
            background: var(--primary-gradient);
            width: 32px;
            height: 32px;
            border-radius: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 5px 15px rgba(197, 160, 40, 0.4);
        }
        .profile-card-bespoke h2 { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 950; letter-spacing: -1px; margin-bottom: 5px; }
        .profile-card-bespoke .badge-vip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 15px;
            background: rgba(181, 141, 61, 0.1);
            color: var(--gold-luxury);
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border: 1px solid rgba(181, 141, 61, 0.2);
        }

        .setting-section-bespoke {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 35px;
            margin: 0 20px 25px;
            padding: 25px;
        }
        .section-label-bespoke {
            font-family: 'Outfit', sans-serif;
            font-size: 0.75rem;
            font-weight: 950;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--gold-luxury);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0.8;
        }
        .section-label-bespoke::after { content: ''; flex: 1; height: 1px; background: linear-gradient(to right, rgba(181,141,61,0.2), transparent); }

        .form-group-bespoke { margin-bottom: 25px; }
        .form-group-bespoke label {
            font-family: 'Outfit', sans-serif;
            font-size: 0.7rem;
            font-weight: 900;
            color: var(--gold-luxury);
            display: block;
            margin-bottom: 12px;
            padding-left: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            opacity: 0.7;
        }

        .input-premium-bespoke {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 20px;
            padding: 18px 25px;
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            outline: none;
            box-sizing: border-box;
        }
        .input-premium-bespoke:focus {
            background: rgba(255, 255, 255, 0.06);
            border-color: var(--gold-luxury);
            box-shadow: 0 0 20px rgba(181, 141, 61, 0.1);
        }

        .setting-list-bespoke { padding-bottom: 120px; }
        .list-item-bespoke {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 18px 0;
            text-decoration: none;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.2s;
        }
        .list-item-bespoke:last-child { border-bottom: none; }
        .list-item-bespoke:active { opacity: 0.6; }
        .list-icon-bespoke {
            width: 44px;
            height: 44px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--gold-luxury);
            border: 1px solid rgba(255,255,255,0.05);
        }
        .list-content-bespoke { flex: 1; }
        .list-content-bespoke h4 { font-size: 0.9rem; font-weight: 800; margin-bottom: 3px; }
        .list-content-bespoke p { font-size: 0.7rem; color: rgba(255,255,255,0.3); font-weight: 600; }
        .list-chevron-bespoke { font-size: 0.8rem; color: rgba(255,255,255,0.1); }
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

    

    <?php showAlert(); ?>
    <div class="bespoke-bg">
        <div class="glow-blob" style="top: -15%; right: -15%;"></div>
        <div class="glow-blob" style="bottom: -15%; left: -15%; background: radial-gradient(circle, rgba(197, 160, 40, 0.08) 0%, transparent 70%);"></div>
    </div>

    <div class="app-container">
        <header class="header-aligned-luxury animate-up">
            <div class="header-brand-luxury">
                <img src="../assets/img/logo.png" class="header-logo-luxury">
            </div>

            <h1 class="font-heavy">Pengaturan</h1>

            <div class="header-action">
                <a href="dashboard.php">
                    <i class="fas fa-house"></i>
                </a>
            </div>
        </header>

        <section class="profile-card-bespoke luxury-glass animate-up" style="animation-delay: 0.05s;">
            <div class="profile-avatar-bespoke">
                <i class="fas fa-terminal"></i>
            </div>
            <h2 class="gold-gradient-text" style="font-family: 'Outfit', sans-serif; font-weight: 950;"><?= htmlspecialchars($user['name']) ?></h2>
            <div class="badge-vip"><i class="fas fa-crown"></i> Super Administrator</div>
        </section>

        <!-- Profil User -->
        <section class="setting-section-bespoke animate-up" style="animation-delay: 0.1s;">
            <div class="section-label-bespoke">Data Personel</div>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group-bespoke">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="input-premium-bespoke" placeholder="Nama Anda...">
                </div>
                <button type="submit" class="btn-premium btn-premium-primary" style="width: 100%; padding: 18px;">UPDATE PROFIL</button>
            </form>
        </section>

        <!-- Tambah User -->
        <section class="setting-section-bespoke animate-up" style="animation-delay: 0.12s;">
            <div class="section-label-bespoke">Tambah Administrator Baru</div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group-bespoke">
                    <label>Username Baru</label>
                    <input type="text" name="new_username" required class="input-premium-bespoke" placeholder="username...">
                </div>
                <div class="form-group-bespoke">
                    <label>Nama Lengkap</label>
                    <input type="text" name="new_name" required class="input-premium-bespoke" placeholder="Nama Lengkap...">
                </div>
                <div class="form-group-bespoke">
                    <label>Kata Sandi Baru</label>
                    <input type="password" name="new_user_password" required class="input-premium-bespoke" placeholder="••••••••">
                </div>
                <button type="submit" class="btn-premium" style="width: 100%; padding: 18px; background: var(--gold-luxury); color: #000; border: none;">DAFTARKAN USER</button>
            </form>
        </section>

        <!-- Profil Toko -->
        <section class="setting-section-bespoke animate-up" style="animation-delay: 0.15s;">
            <div class="section-label-bespoke">Konfigurasi Unit</div>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="form-group-bespoke">
                    <label>Identitas Brand</label>
                    <input type="text" name="store_name" value="<?= htmlspecialchars($storeName) ?>" class="input-premium-bespoke" placeholder="Nama Brand...">
                </div>
                
                <div class="form-group-bespoke">
                    <label>Titik Koordinat Cabang</label>
                    <input type="text" name="store_address" value="<?= htmlspecialchars(getSetting('store_address') ?: '') ?>" class="input-premium-bespoke" placeholder="Alamat Lengkap Unit...">
                </div>
                
                <div class="form-group-bespoke">
                    <label>WhatsApp Reporting (Owner)</label>
                    <input type="text" name="owner_wa" value="<?= htmlspecialchars(getSetting('owner_wa') ?: '') ?>" class="input-premium-bespoke" placeholder="Contoh: 628123456789">
                </div>
                
                <button type="submit" class="btn-premium" style="width: 100%; padding: 18px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02); color: white;">SIMPAN KONFIGURASI</button>
            </form>
        </section>

        <!-- Keamanan -->
        <section class="setting-section-bespoke animate-up" style="animation-delay: 0.15s;">
            <div class="section-label-bespoke">Protokol Keamanan</div>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group-bespoke">
                    <label>Kredensial Saat Ini</label>
                    <input type="password" name="current_password" required class="input-premium-bespoke" placeholder="••••••••">
                </div>
                <div class="form-group-bespoke">
                    <label>Kredensial Baru</label>
                    <input type="password" name="new_password" required class="input-premium-bespoke" placeholder="••••••••">
                </div>
                <div class="form-group-bespoke" style="margin-bottom: 25px;">
                    <label>Verifikasi Kredensial Baru</label>
                    <input type="password" name="confirm_password" required class="input-premium-bespoke" placeholder="••••••••">
                </div>
                <button type="submit" class="btn-premium" style="width: 100%; padding: 18px; background: rgba(255,255,255,0.05);">GANTI KREDENSIAL</button>
            </form>
        </section>

        <!-- Links -->
        <section class="setting-section-bespoke setting-list-bespoke animate-up" style="animation-delay: 0.2s;">
            <div class="section-label-bespoke">Akses Eksternal</div>
            <a href="../index.php" target="_blank" class="list-item-bespoke">
                <div class="list-icon-bespoke"><i class="fas fa-paper-plane"></i></div>
                <div class="list-content-bespoke">
                    <h4>Katalog Digital</h4>
                    <p>Buka tampilan menu untuk pelanggan</p>
                </div>
                <i class="fas fa-chevron-right list-chevron-bespoke"></i>
            </a>
            <a href="riwayat.php" class="list-item-bespoke">
                <div class="list-icon-bespoke"><i class="fas fa-fingerprint"></i></div>
                <div class="list-content-bespoke">
                    <h4>Audit Trail</h4>
                    <p>Lihat semua log transaksi unit</p>
                </div>
                <i class="fas fa-chevron-right list-chevron-bespoke"></i>
            </a>
            <a href="logout.php" class="list-item-bespoke" onclick="return confirm('Selesaikan sesi administrasi?')">
                <div class="list-icon-bespoke" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.1);"><i class="fas fa-power-off"></i></div>
                <div class="list-content-bespoke">
                    <h4 style="color: #ef4444;">Akhiri Sesi</h4>
                    <p>Logout dari sistem kontrol unit</p>
                </div>
                <i class="fas fa-chevron-right list-chevron-bespoke"></i>
            </a>
        </section>

        <div style="text-align: center; color: rgba(255,255,255,0.15); padding: 0 0 50px;">
            <p style="font-size: 0.65rem; font-weight: 900; letter-spacing: 3px; text-transform: uppercase;">POKS v3.0 // Bespoke Edition</p>
        </div>

        <nav class="luxury-bottom-dock">
            <a href="dashboard.php" class="dock-link"><i class="fas fa-compass"></i><span>Beranda</span></a>
            <a href="kasir.php" class="dock-link"><i class="fas fa-cash-register"></i><span>Kasir</span></a>
            <a href="menu.php" class="dock-link"><i class="fas fa-layer-group"></i><span>Menu</span></a>
            <a href="stok.php" class="dock-link"><i class="fas fa-warehouse"></i><span>Stok</span></a>
            <a href="laporan.php" class="dock-link"><i class="fas fa-chart-pie"></i><span>Laporan</span></a>
        </nav>
    </div>

    
    

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
