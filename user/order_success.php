<?php
session_start();
include '../config/koneksi.php';

// Proteksi login
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Set default session jika belum ada
if(!isset($_SESSION['nama'])) {
    $_SESSION['nama'] = 'Pengguna';
}

// Ambil data dari session atau database
$order_data = $_SESSION['order_data'] ?? null;
$id_user = $_SESSION['id_user'] ?? 1;

// Jika tidak ada data di session, ambil dari database (order terbaru user)
if (!$order_data) {
    $query_order = mysqli_query($conn, "
        SELECT o.*, 
               COUNT(od.id_detail) as total_tiket
        FROM orders o
        LEFT JOIN order_detail od ON o.id_order = od.id_order
        WHERE o.id_user = $id_user
        ORDER BY o.id_order DESC
        LIMIT 1
    ");
    $order = mysqli_fetch_assoc($query_order);
    
    if ($order) {
        // Ambil detail tiket
        $query_detail = mysqli_query($conn, "
            SELECT od.*, t.nama_tiket, t.harga
            FROM order_detail od
            JOIN tiket t ON od.id_tiket = t.id_tiket
            WHERE od.id_order = {$order['id_order']}
        ");
        
        $tiket_details = [];
        while($row = mysqli_fetch_assoc($query_detail)) {
            $tiket_details[] = $row;
        }
        
        $order_data = [
            'no_order' => $order['no_order'],
            'total' => $order['subtotal'],
            'potongan' => $order['potongan'],
            'total_akhir' => $order['total'],
            'tiket_details' => $tiket_details,
            'tanggal_order' => $order['tanggal_order']
        ];
    }
}

// Hapus session data setelah diambil
unset($_SESSION['order_data']);
unset($_SESSION['order_success']);

// Fungsi untuk aman
function safe($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Berhasil | EventTicket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto;
            animation: bounce 0.5s ease-in-out;
        }
        @keyframes bounce {
            0%, 100% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            80% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
        .ticket-card {
            transition: all 0.3s ease;
        }
        .ticket-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0,102,204,0.15);
        }
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
            body {
                background: white;
                padding: 20px;
            }
            .ticket-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
        .print-only {
            display: none;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-soft-blue to-white min-h-screen">
    
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50 no-print">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-ticket-alt text-accent-blue text-2xl"></i>
                <span class="font-bold text-xl bg-gradient-to-r from-navy to-accent-blue bg-clip-text text-transparent">EventTicket</span>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-user-circle text-accent-blue text-xl"></i>
                    <span class="hidden md:inline text-gray-600"><?= safe($_SESSION['nama']) ?></span>
                </div>
                <a href="dashboard.php" class="text-gray-600 hover:text-accent-blue transition">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="my_tickets.php" class="text-gray-600 hover:text-accent-blue transition">
                    <i class="fas fa-ticket-alt"></i> Tiket Saya
                </a>
                <a href="../auth/logout.php" class="text-gray-600 hover:text-red-500 transition">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Success Animation -->
        <div class="text-center mb-6">
            <div class="success-checkmark">
                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" stroke="#10b981" stroke-width="4"/>
                    <path class="checkmark__check" fill="none" stroke="#10b981" stroke-width="4" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mt-4">Pesanan Berhasil! 🎉</h1>
            <p class="text-gray-500 mt-2">Terima kasih telah memesan tiket di EventTicket</p>
        </div>
        
        <!-- Order Summary Card -->
        <div class="bg-white rounded-2xl shadow-md overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-navy to-accent-blue px-6 py-4">
                <h2 class="text-white font-semibold text-lg">
                    <i class="fas fa-receipt mr-2"></i> Ringkasan Pesanan
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-gray-500 text-sm">Nomor Pesanan</p>
                        <p class="font-semibold text-gray-800"><?= safe($order_data['no_order'] ?? 'ORD-' . date('Ymd') . '-001') ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Tanggal Pemesanan</p>
                        <p class="font-semibold text-gray-800"><?= date('d F Y H:i', strtotime($order_data['tanggal_order'] ?? 'now')) ?></p>
                    </div>
                </div>
                
                <div class="border-t border-gray-100 pt-4 mb-4">
                    <h3 class="font-semibold text-gray-700 mb-3">Detail Tiket</h3>
                    <?php 
                    $tiket_details = $order_data['tiket_details'] ?? [];
                    foreach($tiket_details as $detail): 
                    ?>
                    <div class="flex justify-between items-center py-2 border-b border-gray-50">
                        <div>
                            <p class="font-medium text-gray-800"><?= safe($detail['nama_tiket']) ?></p>
                            <p class="text-xs text-gray-400"><?= $detail['qty'] ?> tiket x Rp <?= number_format($detail['harga'], 0, ',', '.') ?></p>
                        </div>
                        <p class="font-semibold text-gray-800">Rp <?= number_format($detail['subtotal'], 0, ',', '.') ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="border-t border-gray-200 pt-4 space-y-2">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span>Rp <?= number_format($order_data['total'] ?? 0, 0, ',', '.') ?></span>
                    </div>
                    <?php if(($order_data['potongan'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-green-600">
                        <span>Diskon</span>
                        <span>- Rp <?= number_format($order_data['potongan'] ?? 0, 0, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between text-lg font-bold text-gray-800 pt-2 border-t border-gray-200">
                        <span>Total Dibayar</span>
                        <span class="text-accent-blue">Rp <?= number_format($order_data['total_akhir'] ?? 0, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Info -->
        <div class="bg-blue-50 rounded-xl p-4 mb-6 border border-blue-200 no-print">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-accent-blue text-xl mt-0.5"></i>
                <div>
                    <p class="font-semibold text-gray-800">Informasi Pembayaran</p>
                    <p class="text-sm text-gray-600">Pembayaran dapat dilakukan melalui transfer bank ke rekening:</p>
                    <p class="text-sm font-mono bg-white px-3 py-1 rounded inline-block mt-2">BCA: 1234567890 a.n EventTicket</p>
                    <p class="text-sm font-mono bg-white px-3 py-1 rounded inline-block ml-2 mt-2">Mandiri: 0987654321 a.n EventTicket</p>
                    <p class="text-xs text-gray-500 mt-2">*Konfirmasi pembayaran akan kami proses dalam 1x24 jam</p>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center no-print">
            <button onclick="window.print()" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-300 transition flex items-center justify-center gap-2">
                <i class="fas fa-print"></i> Cetak Invoice
            </button>
            <a href="my_tickets.php" class="bg-accent-blue text-white px-6 py-3 rounded-xl font-semibold hover:bg-accent-hover transition flex items-center justify-center gap-2">
                <i class="fas fa-ticket-alt"></i> Lihat Tiket Saya
            </a>
            <a href="dashboard.php" class="border border-accent-blue text-accent-blue px-6 py-3 rounded-xl font-semibold hover:bg-soft-blue transition flex items-center justify-center gap-2">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
        
        <!-- Print Version -->
        <div class="print-only">
            <div class="text-center mb-4">
                <h2 class="text-xl font-bold">EventTicket - Invoice Pesanan</h2>
                <p class="text-sm">Terima kasih telah memesan tiket di EventTicket</p>
            </div>
        </div>
    </div>
    
    <style>
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 4;
            stroke-miterlimit: 10;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        @keyframes stroke {
            100% { stroke-dashoffset: 0; }
        }
    </style>
</body>
</html>