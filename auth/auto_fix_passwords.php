<?php
include '../config/koneksi.php';

echo "<h2>Auto Fix Passwords - TiketMoo</h2>";

// Data user dengan password yang benar
$users = [
    ['email' => 'admin@gmail.com', 'password' => '123', 'role' => 'admin'],
    ['email' => 'petugas@gmail.com', 'password' => '123', 'role' => 'petugas'],
    ['email' => 'izza@gmail.com', 'password' => 'IZZATUN123', 'role' => 'user']
];

foreach ($users as $user) {
    // Buat hash baru dari password yang benar
    $new_hash = password_hash($user['password'], PASSWORD_DEFAULT);
    
    // Update database
    $query = mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE email = '{$user['email']}'");
    
    if ($query) {
        echo "✅ Password untuk <strong>{$user['email']}</strong> berhasil di-update<br>";
        echo "Hash baru: <code>$new_hash</code><br><br>";
    } else {
        echo "❌ Gagal update untuk {$user['email']}<br><br>";
    }
}

echo "<hr>";
echo "<strong style='color:green'>SELESAI! Sekarang coba login:</strong><br>";
echo "<ul>";
echo "<li><strong>Admin</strong>: admin@gmail.com / 123</li>";
echo "<li><strong>Petugas</strong>: petugas@gmail.com / 123</li>";
echo "<li><strong>User</strong>: izza@gmail.com / IZZATUN123</li>";
echo "</ul>";
echo "<br>";
echo "<a href='login.php' style='background:blue; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Klik disini untuk Login</a>";
?>