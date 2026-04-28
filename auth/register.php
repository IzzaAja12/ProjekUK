<?php
session_start();
include '../config/koneksi.php';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] == 'petugas') {
        header("Location: ../petugas/dashboard.php");
    } else {
        header("Location: ../user/dashboard.php");
    }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif (strlen($password) < 3) {
        $error = "Password minimal 3 karakter!";
    } elseif ($password != $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek apakah email sudah terdaftar
        $check = mysqli_query($conn, "SELECT id_user FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email sudah terdaftar! Silakan gunakan email lain.";
        } else {
            // 🔐 HASH PASSWORD menggunakan password_hash() sebelum disimpan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Simpan user baru dengan password yang sudah di-hash
            $query = mysqli_query($conn, "INSERT INTO users (nama, email, password, role) VALUES ('$nama', '$email', '$hashed_password', 'user')");
            
            if ($query) {
                $success = "Pendaftaran berhasil! Silakan login.";
                // Kosongkan form
                $nama = $email = '';
            } else {
                $error = "Pendaftaran gagal! Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Daftar | TiketMoo</title>
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
        .register-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .register-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 40px -12px rgba(0,102,204,0.2);
        }
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0,102,204,0.1);
            transform: translateY(-1px);
        }
        .btn-register {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,102,204,0.25);
        }
        .brand-icon {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }
        .input-icon {
            transition: all 0.2s ease;
        }
        .input-group:focus-within .input-icon {
            color: #0066cc;
        }
        .divider {
            background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-5xl animate-[fadeIn_0.6s_ease-out]">
        <div class="grid md:grid-cols-2 gap-0 bg-white rounded-2xl shadow-xl overflow-hidden register-card">
            
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
                        <!-- Tombol Kembali ke Beranda -->
                        <a href="../home.php" class="text-white/70 hover:text-white transition text-sm flex items-center gap-1">
                            <i class="fas fa-arrow-left text-xs"></i>
                            <span>Beranda</span>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <h2 class="text-white text-2xl md:text-3xl font-bold leading-tight">
                            Bergabunglah<br>Bersama Kami!
                        </h2>
                        <p class="text-blue-100 text-sm leading-relaxed">
                            Daftar sekarang dan nikmati kemudahan memesan tiket event favorit Anda.
                        </p>
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
            
            <!-- Right Side - Register Form -->
            <div class="p-8 md:p-10 bg-white animate-[slideInRight_0.5s_ease-out]">
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-gradient-to-r from-accent-blue to-navy rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-plus text-white text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Daftar Akun Baru</h3>
                    <p class="text-gray-500 text-sm mt-1">Isi data diri Anda untuk mendaftar</p>
                </div>
                
                <?php if ($error): ?>
                <div class="mb-5 p-3 bg-red-50 border-l-4 border-red-500 rounded-lg animate-[slideUp_0.3s_ease-out]">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-red-500 text-sm"></i>
                        <p class="text-red-600 text-sm"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="mb-5 p-3 bg-green-50 border-l-4 border-green-500 rounded-lg animate-[slideUp_0.3s_ease-out]">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500 text-sm"></i>
                        <p class="text-green-600 text-sm"><?= htmlspecialchars($success) ?> <a href="login.php" class="font-semibold underline">Masuk di sini</a></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <form action="" method="POST" class="space-y-4">
                    <!-- Nama Field -->
                    <div class="input-group">
                        <label class="block text-gray-700 text-sm font-medium mb-1.5">Nama Lengkap</label>
                        <div class="relative">
                            <i class="fas fa-user input-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" name="nama" required 
                                   value="<?= htmlspecialchars($nama ?? '') ?>"
                                   class="input-field w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue text-sm bg-gray-50 focus:bg-white transition-all"
                                   placeholder="Nama lengkap Anda">
                        </div>
                    </div>
                    
                    <!-- Email Field -->
                    <div class="input-group">
                        <label class="block text-gray-700 text-sm font-medium mb-1.5">Alamat Email</label>
                        <div class="relative">
                            <i class="fas fa-envelope input-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="email" name="email" required 
                                   value="<?= htmlspecialchars($email ?? '') ?>"
                                   class="input-field w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue text-sm bg-gray-50 focus:bg-white transition-all"
                                   placeholder="nama@email.com">
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="input-group">
                        <label class="block text-gray-700 text-sm font-medium mb-1.5">Password</label>
                        <div class="relative">
                            <i class="fas fa-lock input-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="password" name="password" required 
                                   class="input-field w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue text-sm bg-gray-50 focus:bg-white transition-all"
                                   placeholder="Minimal 3 karakter">
                            <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-accent-blue transition">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="input-group">
                        <label class="block text-gray-700 text-sm font-medium mb-1.5">Konfirmasi Password</label>
                        <div class="relative">
                            <i class="fas fa-check-circle input-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="password" name="confirm_password" required 
                                   class="input-field w-full pl-9 pr-10 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-accent-blue text-sm bg-gray-50 focus:bg-white transition-all"
                                   placeholder="Ulangi password Anda">
                            <button type="button" class="confirm-password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-accent-blue transition">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Terms & Conditions -->
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="terms" required class="w-3.5 h-3.5 text-accent-blue rounded border-gray-300 focus:ring-accent-blue focus:ring-1">
                        <label for="terms" class="text-xs text-gray-600">
                            Saya menyetujui <a href="#" class="text-accent-blue hover:underline">Syarat & Ketentuan</a> dan <a href="#" class="text-accent-blue hover:underline">Kebijakan Privasi</a>
                        </label>
                    </div>
                    
                    <!-- Register Button -->
                    <button type="submit" 
                            class="btn-register w-full bg-gradient-to-r from-accent-blue to-navy text-white font-semibold py-2.5 rounded-lg transition-all cursor-pointer">
                        <i class="fas fa-user-plus mr-2"></i>Daftar
                    </button>
                    
                    <!-- Login Link -->
                    <div class="text-center pt-3">
                        <p class="text-xs text-gray-500">
                            Sudah punya akun? 
                            <a href="login.php" class="text-accent-blue font-medium hover:underline">Masuk Sekarang</a>
                        </p>
                    </div>
                </form>
                
                <!-- Divider -->
                <div class="divider h-px w-full my-6"></div>
                
                <!-- Footer Info -->
                <div class="text-center">
                    <p class="text-xs text-gray-400">
                        © <?= date('Y') ?> TiketMoo. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password toggle visibility untuk password field
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye text-sm"></i>' : '<i class="fas fa-eye-slash text-sm"></i>';
            });
        });
        
        // Password toggle untuk confirm password field
        document.querySelectorAll('.confirm-password-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye text-sm"></i>' : '<i class="fas fa-eye-slash text-sm"></i>';
            });
        });
        
        // Ripple effect untuk button
        const buttons = document.querySelectorAll('.btn-register');
        buttons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                this.appendChild(ripple);
                
                const x = e.clientX - e.target.offsetLeft;
                const y = e.clientY - e.target.offsetTop;
                
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Ripple style
        const style = document.createElement('style');
        style.textContent = `
            .btn-register {
                position: relative;
                overflow: hidden;
            }
            .ripple {
                position: absolute;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.3);
                width: 100px;
                height: 100px;
                margin-top: -50px;
                margin-left: -50px;
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            @keyframes ripple-animation {
                from {
                    transform: scale(0);
                    opacity: 1;
                }
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>