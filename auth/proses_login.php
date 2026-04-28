<?php
session_start();
include '../config/koneksi.php';

// Cek apakah form disubmit via POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: login.php?error=Metode tidak diizinkan");
    exit;
}

// Ambil dan sanitasi input
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input tidak kosong
if (empty($email) || empty($password)) {
    header("Location: login.php?error=Email dan password harus diisi!");
    exit;
}

// Validasi format email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: login.php?error=Format email tidak valid!");
    exit;
}

// Gunakan prepared statement untuk mencegah SQL Injection
$stmt = mysqli_prepare($conn, "SELECT id_user, nama, email, password, role FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt); // Mengambil hasil query
$user = mysqli_fetch_assoc($result); // Mengambil data sebagai array asosiatif

// Tutup statement
mysqli_stmt_close($stmt);

// Verifikasi user dan password
if ($user) {
    // 🔐 VERIFIKASI PASSWORD dengan password_verify() untuk hash yang aman
    if (password_verify($password, $user['password'])) {
        // Password cocok - buat session
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Regenerasi session ID untuk keamanan (mencegah session fixation)
        session_regenerate_id(true);
        
        // Redirect berdasarkan role
        if ($user['role'] == 'admin') {
            header("Location: ../admin/dashboard.php");
        } elseif ($user['role'] == 'user') {
            header("Location: ../user/dashboard.php");
        } elseif ($user['role'] == 'petugas') {
            header("Location: ../petugas/checkin.php");
        } else {
            // Role tidak dikenal
            header("Location: login.php?error=Role tidak dikenal!");
        }
        exit;
    } else {
        // Password salah
        header("Location: login.php?error=Email atau password salah!");
        exit;
    }
} else {
    // Email tidak ditemukan
    header("Location: login.php?error=Email atau password salah!");
    exit;
}
?>