<?php
session_start();
include '../config/koneksi.php';

// Proteksi login
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil data user
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = $id_user");
$user = mysqli_fetch_assoc($query_user);

if (!$user) {
    header("Location: dashboard.php");
    exit;
}

// Proses update profil
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Validasi email
        $check_email = mysqli_query($conn, "SELECT id_user FROM users WHERE email = '$email' AND id_user != $id_user");
        if (mysqli_num_rows($check_email) > 0) {
            $error_message = "Email sudah digunakan oleh user lain!";
        } else {
            $update = mysqli_query($conn, "UPDATE users SET nama = '$nama', email = '$email' WHERE id_user = $id_user");
            if ($update) {
                $_SESSION['nama'] = $nama;
                $success_message = "Profil berhasil diperbarui!";
                // Refresh data
                $query_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = $id_user");
                $user = mysqli_fetch_assoc($query_user);
            } else {
                $error_message = "Gagal memperbarui profil!";
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verifikasi password lama
        if ($user['password'] != $current_password) {
            $error_message = "Password saat ini salah!";
        } elseif (strlen($new_password) < 3) {
            $error_message = "Password baru minimal 3 karakter!";
        } elseif ($new_password != $confirm_password) {
            $error_message = "Konfirmasi password tidak cocok!";
        } else {
            $update = mysqli_query($conn, "UPDATE users SET password = '$new_password' WHERE id_user = $id_user");
            if ($update) {
                $success_message = "Password berhasil diubah!";
            } else {
                $error_message = "Gagal mengubah password!";
            }
        }
    }
}

// Fungsi aman
function safe($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | TiketMoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'navy': '#0a2540',
                        'accent-blue': '#0066cc',
                        'soft-blue': '#e6f0fa',
                    },
                    animation: {
                        'slide-in': 'slideIn 0.5s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .gradient-text {
            background: linear-gradient(135deg, #0a2540 0%, #0066cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .profile-card {
            transition: all 0.3s ease;
        }
        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -12px rgba(0,102,204,0.15);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-soft-blue via-white to-soft-blue min-h-screen">
    
    <!-- Navbar -->
    <nav class="bg-white/90 backdrop-blur-md shadow-lg sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-gradient-to-r from-accent-blue to-navy rounded-xl flex items-center justify-center shadow-lg animate-pulse-slow">
                    <i class="fas fa-ticket-alt text-white text-xl"></i>
                </div>
                <span class="font-extrabold text-2xl gradient-text">TiketMoo</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-gray-600 hover:text-accent-blue transition flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="../auth/logout.php" class="text-gray-600 hover:text-red-500 transition">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="mb-8 animate-[slideIn_0.3s_ease-out]">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-user-circle text-accent-blue"></i>
                Profil Saya
            </h1>
            <p class="text-gray-500 mt-2">Kelola informasi akun dan keamanan Anda</p>
        </div>

        <!-- Alert Messages -->
        <?php if ($success_message): ?>
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg animate-[slideIn_0.4s_ease-out]">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                <p class="text-green-700"><?= safe($success_message) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg animate-[slideIn_0.4s_ease-out]">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                <p class="text-red-700"><?= safe($error_message) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sidebar Profil -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-md overflow-hidden profile-card">
                    <div class="bg-gradient-to-r from-navy to-accent-blue p-6 text-center">
                        <div class="w-24 h-24 mx-auto bg-white/20 rounded-full flex items-center justify-center border-4 border-white/50">
                            <i class="fas fa-user text-white text-4xl"></i>
                        </div>
                        <h3 class="text-white font-bold text-xl mt-3"><?= safe($user['nama']) ?></h3>
                        <p class="text-blue-100 text-sm"><?= safe($user['email']) ?></p>
                        <div class="mt-2 inline-block px-3 py-1 bg-white/20 rounded-full text-xs text-white">
                            <i class="fas fa-shield-alt mr-1"></i> <?= ucfirst($user['role'] ?? 'User') ?>
                        </div>
                    </div>
                    <div class="p-4 border-t border-gray-100">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Member sejak</span>
                            <span class="font-medium text-gray-700"><?= date('d F Y', strtotime($user['id_user'] ?? 'now')) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Edit Profil -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Edit Profil -->
                <div class="bg-white rounded-2xl shadow-md overflow-hidden animate-[slideIn_0.5s_ease-out]">
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                        <h2 class="text-white font-semibold">
                            <i class="fas fa-edit mr-2"></i> Edit Profil
                        </h2>
                    </div>
                    <form method="POST" class="p-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <div class="relative">
                                    <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" name="nama" value="<?= safe($user['nama']) ?>" 
                                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent"
                                           required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <div class="relative">
                                    <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="email" name="email" value="<?= safe($user['email']) ?>" 
                                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent"
                                           required>
                                </div>
                            </div>
                            <button type="submit" name="update_profile" 
                                    class="w-full bg-accent-blue text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700 transition transform hover:scale-[1.02]">
                                <i class="fas fa-save mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Ganti Password -->
                <div class="bg-white rounded-2xl shadow-md overflow-hidden animate-[slideIn_0.6s_ease-out]">
                    <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-6 py-4">
                        <h2 class="text-white font-semibold">
                            <i class="fas fa-lock mr-2"></i> Ganti Password
                        </h2>
                    </div>
                    <form method="POST" class="p-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                                <div class="relative">
                                    <i class="fas fa-key absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="password" name="current_password" 
                                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent"
                                           required>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                                <div class="relative">
                                    <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="password" name="new_password" 
                                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent"
                                           required>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">Minimal 3 karakter</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                                <div class="relative">
                                    <i class="fas fa-check-circle absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="password" name="confirm_password" 
                                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent"
                                           required>
                                </div>
                            </div>
                            <button type="submit" name="change_password" 
                                    class="w-full bg-orange-500 text-white py-2.5 rounded-lg font-semibold hover:bg-orange-600 transition transform hover:scale-[1.02]">
                                <i class="fas fa-key mr-2"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>