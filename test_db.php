<?php
require_once __DIR__ . '/config/database.php';

echo "<h1>Debug Koneksi TiDB</h1>";

try {
    $stmt = $pdo->query("SELECT username, name FROM users");
    $users = $stmt->fetchAll();
    
    echo "✅ Koneksi Berhasil!<br>";
    echo "Daftar User di Database:<br>";
    echo "<pre>";
    print_r($users);
    echo "</pre>";

    echo "Session ID: " . session_id() . "<br>";
    echo "Isi Session: ";
    print_r($_SESSION);

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
