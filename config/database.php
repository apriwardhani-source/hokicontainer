<?php
// Session start - HARUS di paling atas sebelum output apapun
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
// Database Configuration
$isLocal = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || 
           ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1' || 
           php_sapi_name() === 'cli'; // Tambahan buat deteksi Command Line

// Setup Environment for Vercel & TiDB
if (getenv('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_NAME', getenv('DB_NAME'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_PORT', getenv('DB_PORT') ?: 3306);
    define('DB_SSL', getenv('DB_SSL') === 'true'); 
    
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    define('APP_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? ''));
} 
elseif ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'hoki_container');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('APP_URL', 'http://localhost/hoki-container');
    define('DB_SSL', false);
} else {
    // Fallback Legacy
    define('DB_HOST', 'sql101.infinityfree.com'); 
    define('DB_NAME', 'if0_40933466_hoki_container'); 
    define('DB_USER', 'if0_40933466'); 
    define('DB_PASS', 'ISI_PASSWORD_VPANEL_BOS_DI_SINI');
    define('APP_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? ''));
    define('DB_SSL', false);
}

// Create connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    if (defined('DB_PORT')) $dsn .= ";port=" . DB_PORT;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+08:00'"
    ];

    if (defined('DB_SSL') && DB_SSL) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', $options);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// App Configuration
define('APP_NAME', 'Hoki Container');
define('OWNER_WA', '6285654631899');

// Set timezone to WITA (Makassar)
date_default_timezone_set('Asia/Makassar');
