<?php
session_start();

// Koneksi database - SESUAIKAN DENGAN KONFIGURASI ANDA
$host = 'localhost';
$dbname = 'event_tiketizza';
$dbusername = 'root';
$dbpassword = '';

// Variabel untuk menyimpan error koneksi
$db_error = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $db_error = "Koneksi database gagal: " . $e->getMessage();
    error_log($db_error);
}

// Proses Reset Password Request (via AJAX)
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' && isset($_POST['action']) && $_POST['action'] == 'reset_request') {
    header('Content-Type: application/json');
    
    if ($db_error || !isset($pdo)) {
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal. Silakan coba lagi nanti.']);
        exit;
    }
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Cek apakah email terdaftar (gunakan id_user, bukan id)
    $stmt = $pdo->prepare("SELECT id_user, nama, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate token unik
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Hapus token lama untuk user ini
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$user['id_user']]);
        
        // Simpan token baru ke database
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id_user'], $token, $expires]);
        
        // Link reset password
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?reset_token=" . $token;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Link reset password telah dikirim ke email Anda.',
            'debug_link' => $reset_link
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'Jika email terdaftar, link reset password akan dikirimkan.'
        ]);
    }
    exit;
}

// Proses Reset Password (submit form reset) - SUDAH DIPERBAIKI DENGAN HASH
if (isset($_POST['action']) && $_POST['action'] == 'reset_password' && isset($_POST['token']) && isset($_POST['password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $error_reset = '';
    $success_reset = '';
    
    if (!isset($pdo)) {
        $error_reset = "Koneksi database gagal";
    } elseif ($new_password !== $confirm_password) {
        $error_reset = "Password dan konfirmasi password tidak cocok.";
    } elseif (strlen($new_password) < 6) {
        $error_reset = "Password minimal 6 karakter.";
    } else {
        // Verifikasi token
        $stmt = $pdo->prepare("SELECT pr.*, u.id_user, u.nama, u.email 
                               FROM password_resets pr 
                               JOIN users u ON pr.user_id = u.id_user 
                               WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
        $stmt->execute([$token]);
        $reset_data = $stmt->fetch();
        
        if ($reset_data) {
            // 🔐 HASH PASSWORD sebelum disimpan (PERBAIKAN)
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password dengan hash
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
            $stmt->execute([$hashed_password, $reset_data['id_user']]);
            
            // Tandai token sudah digunakan
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            $success_reset = "Password berhasil direset! Silakan login dengan password baru Anda.";
        } else {
            $error_reset = "Token tidak valid atau sudah kadaluarsa.";
        }
    }
}

// Cek apakah ada token reset password
$reset_token = isset($_GET['reset_token']) ? $_GET['reset_token'] : null;
$show_reset_form = false;
$token_valid = false;

if ($reset_token && isset($pdo)) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
    $stmt->execute([$reset_token]);
    if ($stmt->fetch()) {
        $show_reset_form = true;
        $token_valid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Login | TiketMoo</title>
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
                        'slate-800': '#1e293b',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'slide-in-left': 'slideInLeft 0.5s ease-out',
                        'slide-in-right': 'slideInRight 0.5s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        slideInLeft: {
                            '0%': { opacity: '0', transform: 'translateX(-30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                        slideInRight: {
                            '0%': { opacity: '0', transform: 'translateX(30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' },
                        },
                    }
                }
            }
        }
    </script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: linear-gradient(135deg, #e6f0fa 0%, #ffffff 100%); }
        .login-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .login-card:hover { transform: translateY(-4px); box-shadow: 0 25px 40px -12px rgba(0,102,204,0.2); }
        .input-field { transition: all 0.3s ease; }
        .input-field:focus { border-color: #0066cc; box-shadow: 0 0 0 3px rgba(0,102,204,0.1); transform: translateY(-1px); }
        .btn-login { transition: all 0.3s ease; position: relative; overflow: hidden; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,102,204,0.25); }
        .brand-icon { animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-5px); } }
        .input-group:focus-within .input-icon { color: #0066cc; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease-out;
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            animation: slideUp 0.3s ease-out;
        }
        .reset-container { max-width: 500px; margin: 0 auto; }
        
        /* Style untuk link reset yang rapi */
        .link-container {
            background-color: #f8fafc;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            margin-top: 0.75rem;
        }
        .link-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        .link-wrapper {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .link-input {
            flex: 1;
            font-size: 0.7rem;
            font-family: 'Courier New', monospace;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            color: #0066cc;
            overflow-x: auto;
            white-space: nowrap;
        }
        .link-input::-webkit-scrollbar {
            height: 4px;
        }
        .link-input::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }
        .link-input::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        .btn-copy {
            background-color: #0066cc;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-size: 0.7rem;
            font-weight: 500;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-copy:hover {
            background-color: #0a2540;
        }
        .dev-note {
            font-size: 0.65rem;
            color: #94a3b8;
            margin-top: 0.5rem;
            text-align: center;
        }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            z-index: 1001;
            animation: slideInRight 0.3s ease-out;
        }
        .border-red-500 { border-color: #ef4444 !important; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    
    <?php if ($show_reset_form && $token_valid): ?>
    <!-- Tampilan Form Reset Password dengan Toggle Password -->
    <div class="w-full max-w-md reset-container">
        <div class="bg-white rounded-2xl shadow-xl p-8 animate-[fadeIn_0.5s_ease-out]">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-accent-blue to-navy rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Reset Password</h2>
                <p class="text-gray-500 text-sm mt-2">Buat password baru untuk akun Anda</p>
            </div>
            
            <?php if (isset($error_reset) && $error_reset): ?>
                <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <p class="text-red-600 text-sm"><?= htmlspecialchars($error_reset) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_reset) && $success_reset): ?>
                <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <p class="text-green-600 text-sm"><?= htmlspecialchars($success_reset) ?></p>
                </div>
                <div class="text-center mt-6">
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="inline-block bg-gradient-to-r from-accent-blue to-navy text-white font-semibold py-2 px-6 rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login Sekarang
                    </a>
                </div>
            <?php else: ?>
                <form method="POST" action="" class="space-y-4">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($reset_token) ?>">
                    
                    <!-- Password Baru Field dengan toggle -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Password Baru</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="password" id="new_password" name="password" required 
                                   class="w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue focus:ring-2 focus:ring-accent-blue/20 transition"
                                   placeholder="Minimal 6 karakter">
                            <button type="button" onclick="togglePasswordReset('new_password')" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-accent-blue transition">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>Password minimal 6 karakter
                        </p>
                    </div>
                    
                    <!-- Konfirmasi Password Baru Field dengan toggle -->
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-2">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <i class="fas fa-check-circle absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue focus:ring-2 focus:ring-accent-blue/20 transition"
                                   placeholder="Ulangi password baru">
                            <button type="button" onclick="togglePasswordReset('confirm_password')" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-accent-blue transition">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-accent-blue to-navy text-white font-semibold py-2.5 rounded-lg hover:opacity-90 transition transform hover:-translate-y-0.5">
                        <i class="fas fa-save mr-2"></i>Reset Password
                    </button>
                </form>
                
                <div class="text-center mt-6">
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="text-sm text-accent-blue hover:underline">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali ke Login
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Tampilan Login Normal -->
    <div class="w-full max-w-5xl animate-[fadeIn_0.6s_ease-out]">
        <div class="grid md:grid-cols-2 gap-0 bg-white rounded-2xl shadow-xl overflow-hidden login-card">
            
            <!-- Left Side - Branding -->
            <div class="bg-gradient-to-br from-navy to-accent-blue p-8 md:p-10 flex flex-col justify-between">
                <div class="animate-[slideInLeft_0.5s_ease-out]">
                    <div class="flex items-center justify-between mb-10">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center brand-icon">
                                <i class="fas fa-ticket-alt text-white text-xl"></i>
                            </div>
                            <span class="text-white font-bold text-xl tracking-tight">TiketMoo</span>
                        </div>
                        <a href="../home.php" class="text-white/70 hover:text-white transition text-sm flex items-center gap-1">
                            <i class="fas fa-arrow-left text-xs"></i>
                            <span>Beranda</span>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <h2 class="text-white text-2xl md:text-3xl font-bold leading-tight">Selamat Datang Kembali!</h2>
                        <p class="text-blue-100 text-sm leading-relaxed">Akses dashboard Anda dan kelola tiket event dengan mudah.</p>
                    </div>
                </div>
                
                <div class="mt-10 space-y-2 animate-[slideInLeft_0.6s_ease-out]">
                    <div class="flex items-center gap-2 text-blue-100 text-xs">
                        <i class="fas fa-check-circle text-emerald-400 text-xs"></i>
                        <span>Pemesanan tiket instan</span>
                    </div>
                    <div class="flex items-center gap-2 text-blue-100 text-xs">
                        <i class="fas fa-check-circle text-emerald-400 text-xs"></i>
                        <span>Sistem 100% aman & terpercaya</span>
                    </div>
                    <div class="flex items-center gap-2 text-blue-100 text-xs">
                        <i class="fas fa-check-circle text-emerald-400 text-xs"></i>
                        <span>Dukungan pelanggan 24/7</span>
                    </div>
                </div>
                
                <div class="mt-8 animate-[slideInLeft_0.7s_ease-out]">
                    <div class="flex items-center gap-2 text-blue-200/70 text-xs">
                        <i class="fas fa-shield-alt text-xs"></i>
                        <span>Sistem Keamanan Terenkripsi</span>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Login Form -->
            <div class="p-8 md:p-10 bg-white animate-[slideInRight_0.5s_ease-out]">
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-gradient-to-r from-accent-blue to-navy rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lock text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Masuk ke Akun</h3>
                    <p class="text-gray-500 text-sm mt-1">Masukkan kredensial Anda untuk melanjutkan</p>
                </div>
                
                <?php if (isset($_GET['error'])): ?>
                <div class="mb-5 p-3 bg-red-50 border-l-4 border-red-500 rounded-lg">
                    <p class="text-red-600 text-sm"><?= htmlspecialchars($_GET['error']) ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['success'])): ?>
                <div class="mb-5 p-3 bg-green-50 border-l-4 border-green-500 rounded-lg">
                    <p class="text-green-600 text-sm"><?= htmlspecialchars($_GET['success']) ?></p>
                </div>
                <?php endif; ?>
                
                <form action="proses_login.php" method="POST" class="space-y-5">
                    <div class="input-group">
                        <label class="block text-gray-700 text-sm font-medium mb-1.5">Alamat Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope input-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="email" name="email" required 
                                   class="input-field w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue text-sm bg-gray-50 focus:bg-white transition-all"
                                   placeholder="nama@email.com">
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label class="block text-gray-700 text-sm font-medium mb-1.5">Password</label>
                        <div class="relative">
                            <i class="fas fa-lock input-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="password" name="password" required 
                                   class="input-field w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue text-sm bg-gray-50 focus:bg-white transition-all"
                                   placeholder="••••••••">
                            <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-accent-blue transition">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" class="w-3.5 h-3.5 text-accent-blue rounded border-gray-300">
                            <span class="text-xs text-gray-600">Ingat saya</span>
                        </label>
                        <a href="#" onclick="openResetModal(); return false;" class="text-xs text-accent-blue hover:underline">Lupa password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login w-full bg-gradient-to-r from-accent-blue to-navy text-white font-semibold py-2.5 rounded-lg transition-all cursor-pointer">
                        <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                    </button>
                    
                    <div class="text-center pt-3">
                        <p class="text-xs text-gray-500">
                            Belum punya akun? 
                            <a href="register.php" class="text-accent-blue font-medium hover:underline">Daftar Sekarang</a>
                        </p>
                    </div>
                </form>
                
                <div class="divider h-px w-full my-6 bg-gray-200"></div>
                
                <div class="text-center">
                    <p class="text-xs text-gray-400">© <?= date('Y') ?> TiketMoo. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reset Password - VERSI RAPI -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-800">Reset Password</h3>
                    <button onclick="closeResetModal()" class="text-gray-400 hover:text-gray-600 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form id="resetForm" onsubmit="submitResetRequest(event)">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-medium mb-2">Masukkan email Anda</label>
                        <input type="email" id="reset_email" required 
                               class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue"
                               placeholder="nama@email.com">
                    </div>
                    <div id="resetMessage" class="mb-3"></div>
                    <button type="submit" class="w-full bg-gradient-to-r from-accent-blue to-navy text-white font-semibold py-2 rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-paper-plane mr-2"></i>Kirim Link Reset
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Toggle password visibility untuk login form
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye text-sm"></i>' : '<i class="fas fa-eye-slash text-sm"></i>';
            });
        });
        
        // Toggle password visibility untuk reset form
        function togglePasswordReset(fieldId) {
            const input = document.getElementById(fieldId);
            const button = input.parentElement.querySelector('button');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            if (type === 'password') {
                button.innerHTML = '<i class="fas fa-eye text-sm"></i>';
            } else {
                button.innerHTML = '<i class="fas fa-eye-slash text-sm"></i>';
            }
        }
        
        // Validasi password match real-time
        function validatePasswordMatch() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (confirmPassword && newPassword && confirmPassword.value !== '') {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.classList.add('border-red-500');
                    confirmPassword.classList.remove('border-gray-200');
                } else {
                    confirmPassword.classList.remove('border-red-500');
                    confirmPassword.classList.add('border-gray-200');
                }
            }
        }
        
        // Modal functions
        function openResetModal() {
            document.getElementById('resetModal').style.display = 'block';
            document.getElementById('reset_email').value = '';
            document.getElementById('resetMessage').innerHTML = '';
        }
        
        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('resetModal');
            if (event.target == modal) modal.style.display = 'none';
        }
        
        // Fungsi untuk menampilkan toast notification
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }
        
        // Fungsi untuk copy link
        function copyResetLink(linkText) {
            navigator.clipboard.writeText(linkText).then(function() {
                showToast('Link berhasil disalin!');
            }, function() {
                const textarea = document.createElement('textarea');
                textarea.value = linkText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showToast('Link berhasil disalin!');
            });
        }
        
        // Submit reset request
        function submitResetRequest(event) {
            event.preventDefault();
            const email = document.getElementById('reset_email').value;
            const messageDiv = document.getElementById('resetMessage');
            
            if (!email) {
                messageDiv.innerHTML = '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle"></i> Email harus diisi!</div>';
                return;
            }
            
            messageDiv.innerHTML = '<div class="text-blue-600 text-sm"><i class="fas fa-spinner fa-spin"></i> Mengirim permintaan...</div>';
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=reset_request&email=' + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let messageHtml = '<div class="text-green-600 text-sm mb-2"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                    
                    if (data.debug_link) {
                        messageHtml += `
                            <div class="link-container">
                                <div class="link-label">
                                    <i class="fas fa-link mr-1"></i> LINK RESET PASSWORD
                                </div>
                                <div class="link-wrapper">
                                    <div class="link-input" id="resetLinkText">${data.debug_link}</div>
                                    <button type="button" class="btn-copy" onclick="copyResetLink('${data.debug_link}')">
                                        <i class="fas fa-copy mr-1"></i> Salin
                                    </button>
                                </div>
                                <div class="dev-note">
                                    <i class="fas fa-info-circle"></i> Mode Development: Klik Salin lalu buka di tab baru
                                </div>
                            </div>
                        `;
                    }
                    
                    messageDiv.innerHTML = messageHtml;
                    
                    setTimeout(() => {
                        if (document.getElementById('resetModal').style.display === 'block') {
                            closeResetModal();
                        }
                    }, 10000);
                } else {
                    messageDiv.innerHTML = '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.innerHTML = '<div class="text-red-600 text-sm"><i class="fas fa-exclamation-circle"></i> Terjadi kesalahan. Silakan coba lagi.</div>';
            });
        }
        
        // Event listener untuk validasi password match
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                newPassword.addEventListener('input', validatePasswordMatch);
                confirmPassword.addEventListener('input', validatePasswordMatch);
            }
        });
        
        // Ripple effect
        const buttons = document.querySelectorAll('.btn-login');
        buttons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                this.appendChild(ripple);
                const x = e.clientX - e.target.offsetLeft;
                const y = e.clientY - e.target.offsetTop;
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                setTimeout(() => ripple.remove(), 600);
            });
        });
        
        const style = document.createElement('style');
        style.textContent = `
            .btn-login { position: relative; overflow: hidden; }
            .ripple {
                position: absolute;
                border-radius: 50%;
                background-color: rgba(255,255,255,0.3);
                width: 100px;
                height: 100px;
                margin-top: -50px;
                margin-left: -50px;
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            @keyframes ripple-animation {
                from { transform: scale(0); opacity: 1; }
                to { transform: scale(4); opacity: 0; }
            }
            .toast-notification {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: #10b981;
                color: white;
                padding: 8px 16px;
                border-radius: 8px;
                font-size: 13px;
                z-index: 1001;
                animation: slideInRight 0.3s ease-out;
            }
        `;
        document.head.appendChild(style);
        
        <?php if (isset($success_reset) && $success_reset): ?>
        setTimeout(() => window.location.href = window.location.pathname, 3000);
        <?php endif; ?>
    </script>
</body>
</html>