<?php
require_once __DIR__ . '/config/database.php';

echo "<h1>Reset Password Admin</h1>";

try {
    $newPassword = 'admin123';
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Password admin berhasil direset ke: <b>admin123</b><br>";
    } else {
        echo "ℹ️ Tidak ada perubahan (mungkin password sudah sama atau user admin tidak ditemukan).<br>";
        
        // Cek apakah user admin ada
        $check = $pdo->query("SELECT id FROM users WHERE username = 'admin'")->fetch();
        if (!$check) {
            echo "⚠️ User 'admin' tidak ditemukan! Membuat user admin baru...<br>";
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, role) VALUES ('admin', ?, 'Administrator', 'owner')");
            $stmt->execute([$hash]);
            echo "✅ User admin baru berhasil dibuat dengan password: <b>admin123</b><br>";
        }
    }
    
    echo "<br><a href='admin/login.php'>Ke Halaman Login</a>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
