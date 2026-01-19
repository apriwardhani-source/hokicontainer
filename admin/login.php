<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (login($username, $password)) { header('Location: dashboard.php'); exit; }
    else { $error = "Kombinasi salah, Kak! Cek lagi ya."; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?php include '../includes/header_meta.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Hoki Container - Secure Login</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <style>
        body {
            background-color: #0c0c0d !important;
            color: var(--white);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-card-bespoke {
            width: 90%;
            max-width: 400px;
            padding: 40px 30px;
            z-index: 10;
            text-align: center;
            position: relative;
        }
        
        .bespoke-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
        }
        .bespoke-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 30px rgba(181, 141, 61, 0.4));
        }
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
            100% { transform: translateY(0px); }
        }
        
        .bespoke-title {
            font-family: 'Outfit', sans-serif;
            font-size: 2.2rem;
            font-weight: 950;
            margin-bottom: 5px;
            letter-spacing: -1px;
            color: var(--gold-luxury);
        }
        .bespoke-subtitle {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.3);
            margin-bottom: 45px;
            font-weight: 600;
        }
        
        .input-group-bespoke {
            margin-bottom: 30px;
            text-align: left;
        }
        .input-group-bespoke label {
            font-family: 'Outfit', sans-serif;
            font-size: 0.65rem;
            font-weight: 950;
            color: var(--gold-luxury);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
            display: block;
            padding-left: 15px;
        }
        .input-wrapper-bespoke {
            position: relative;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 100px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .input-wrapper-bespoke i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.2);
            font-size: 1.1rem;
        }
        .input-wrapper-bespoke input {
            width: 100%;
            background: transparent;
            border: none;
            padding: 18px 20px 18px 55px;
            color: #fff;
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .input-wrapper-bespoke input::placeholder { color: rgba(255, 255, 255, 0.2); }
        .input-wrapper-bespoke input:focus {
            outline: none;
        }
        .input-wrapper-bespoke:focus-within {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--gold-luxury);
            box-shadow: 0 0 20px rgba(181, 141, 61, 0.15);
        }
        
        .btn-bespoke {
            width: 100%;
            padding: 18px;
            border-radius: 100px;
            background: linear-gradient(to right, #f1d37a, #a67c00);
            color: #000;
            border: none;
            font-family: 'Outfit', sans-serif;
            font-weight: 950;
            font-size: 0.9rem;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 10px 30px rgba(166, 124, 0, 0.3);
            text-transform: uppercase;
            letter-spacing: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .btn-bespoke:active { transform: scale(0.96); }
        
        .footer-link-luxury {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 40px;
            color: rgba(255,255,255,0.2);
            text-decoration: none;
            font-size: 0.65rem;
            font-weight: 950;
            letter-spacing: 3px;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        .footer-link-luxury:hover { color: white; }
    </style>
</head>
<body class="admin-page">
    <div class="bespoke-bg">
        <div class="glow-blob" style="top: -10%; left: -10%;"></div>
        <div class="glow-blob" style="bottom: -10%; right: -10%; background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);"></div>
    </div>

    <div class="login-card-bespoke animate-up">
        <div class="bespoke-logo floating">
            <img src="../assets/img/logo.png" alt="Hoki Logo">
        </div>
        
        <h1 class="bespoke-title">Hoki Admin</h1>
        <p class="bespoke-subtitle">Protected Access System</p>

        <?php if (isset($error)): ?>
        <div class="error-bespoke">
            <i class="fas fa-shield-halved"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group-bespoke">
                <label>Username</label>
                <div class="input-wrapper-bespoke">
                    <i class="fas fa-fingerprint"></i>
                    <input type="text" name="username" placeholder="Username" required autocomplete="off">
                </div>
            </div>
            
            <div class="input-group-bespoke">
                <label>Password</label>
                <div class="input-wrapper-bespoke">
                    <i class="fas fa-key"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
            </div>
            
            <button type="submit" class="btn-bespoke">
                LOGIN <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
            </button>
        </form>

        <a href="../index.php" class="footer-link-luxury">
            <i class="fas fa-eye"></i> PUBLIC VIEW
        </a>
    </div>
</body>
</html>
