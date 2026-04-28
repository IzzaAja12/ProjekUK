<?php
session_start();
include '../config/koneksi.php';

// proteksi login
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Set default session jika belum ada
if(!isset($_SESSION['nama'])) {
    $_SESSION['nama'] = 'Pengguna';
}

// ambil id event
$id = $_GET['id'] ?? 0;

if ($id == 0) {
    header("Location: dashboard.php");
    exit;
}

// ambil data event lengkap dengan venue
$query_event = mysqli_query($conn, "
    SELECT event.*, venue.nama_venue, venue.alamat, venue.kapasitas, venue.id_venue
    FROM event 
    JOIN venue ON event.id_venue = venue.id_venue 
    WHERE event.id_event = $id
");
$event = mysqli_fetch_assoc($query_event);

// Jika event tidak ditemukan
if (!$event) {
    header("Location: dashboard.php");
    exit;
}

// ambil tiket berdasarkan event
$tiket = mysqli_query($conn, "
    SELECT * FROM tiket WHERE id_event = $id ORDER BY harga ASC
");

// Cek apakah semua tiket habis
$semua_tiket_habis = true;
$ada_tiket = false;
$tiket_array = [];
while($t = mysqli_fetch_assoc($tiket)) {
    $ada_tiket = true;
    $tiket_array[] = $t;
    if($t['kuota'] > 0) {
        $semua_tiket_habis = false;
    }
}
mysqli_data_seek($tiket, 0);

// ambil voucher - HANYA jika tiket belum habis
$voucher = null;
if (!$semua_tiket_habis) {
    $voucher = mysqli_query($conn, "
        SELECT * FROM voucher 
        WHERE status = 'aktif' 
        AND kuota > 0 
        AND id_event = " . intval($id) . "
        ORDER BY potongan DESC
    ");
}

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
    <title>Detail Event | <?= safe($event['nama_event']) ?> | EventTicket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'navy': '#0a2540',
                        'accent-blue': '#0066cc',
                        'accent-hover': '#005bb5',
                        'soft-blue': '#e6f0fa',
                    },
                    animation: {
                        'slide-in': 'slideIn 0.5s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'shake': 'shake 0.5s ease-in-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .ticket-card {
            transition: all 0.3s ease;
        }
        .ticket-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0,102,204,0.15);
        }
        .ticket-habis {
            opacity: 0.6;
            background-color: #f9fafb;
        }
        .ticket-habis input {
            background-color: #f3f4f6;
            cursor: not-allowed;
        }
        .quantity-input {
            transition: all 0.2s ease;
        }
        .quantity-input:focus {
            outline: none;
            ring: 2px solid var(--accent-blue);
            border-color: var(--accent-blue);
        }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 0.5;
        }
        .error-border {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        .error-message {
            color: #ef4444;
            font-size: 0.7rem;
            margin-top: 0.25rem;
        }
        .voucher-badge {
            transition: all 0.2s ease;
        }
        .voucher-badge:hover {
            transform: scale(1.05);
            cursor: pointer;
        }
        .notif-habis {
            animation: shake 0.5s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-soft-blue to-white min-h-screen">
    
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-ticket-alt text-accent-blue text-2xl animate-pulse-slow"></i>
                <span class="font-bold text-xl bg-gradient-to-r from-navy to-accent-blue bg-clip-text text-transparent">EventTicket</span>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-user-circle text-accent-blue text-xl"></i>
                    <span class="hidden md:inline text-gray-600"><?= safe($_SESSION['nama']) ?></span>
                </div>
                <a href="dashboard.php" class="text-gray-600 hover:text-accent-blue transition">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <a href="../auth/logout.php" class="text-gray-600 hover:text-red-500 transition">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        
        <!-- Alert Semua Tiket Habis -->
        <?php if($semua_tiket_habis && $ada_tiket): ?>
        <div class="mb-6 bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 rounded-lg p-4 animate-[slideIn_0.3s_ease-out]">
            <div class="flex items-start">
                <i class="fas fa-exclamation-triangle text-red-500 mt-0.5 mr-3 text-xl"></i>
                <div>
                    <p class="text-sm font-semibold text-red-800">⚠️ Tiket Habis!</p>
                    <p class="text-xs text-red-600 mt-1">Maaf, semua tiket untuk event ini sudah habis terjual. Silakan cek event lainnya.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Event Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Event Image -->
                <div class="bg-white rounded-2xl shadow-md overflow-hidden animate-[slideIn_0.4s_ease-out]">
                    <?php 
                    $foto_event = !empty($event['foto']) ? "../uploads/event/" . $event['foto'] : "https://placehold.co/800x400?text=" . urlencode($event['nama_event']);
                    ?>
                    <img src="<?= $foto_event ?>" class="w-full h-64 md:h-80 object-cover" alt="<?= safe($event['nama_event']) ?>"
                         onerror="this.src='https://placehold.co/800x400?text=' + encodeURIComponent('<?= safe($event['nama_event']) ?>')">
                </div>

                <!-- Event Details -->
                <div class="bg-white rounded-2xl shadow-md p-6 animate-[slideIn_0.5s_ease-out]">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2"><?= safe($event['nama_event']) ?></h1>
                    
                    <div class="flex flex-wrap gap-4 mb-4 pb-4 border-b border-gray-100">
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-calendar-alt text-accent-blue mr-2"></i>
                            <span><?= date('l, d F Y', strtotime($event['tanggal'])) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-map-marker-alt text-accent-blue mr-2"></i>
                            <span><?= safe($event['nama_venue']) ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-users text-accent-blue mr-2"></i>
                            <span>Kapasitas: <?= number_format($event['kapasitas'], 0, ',', '.') ?> orang</span>
                        </div>
                    </div>

                    <?php if (!empty($event['deskripsi'])): ?>
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-700 mb-2">Deskripsi Event</h3>
                        <p class="text-gray-600 leading-relaxed"><?= nl2br(safe($event['deskripsi'])) ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="bg-gray-50 rounded-xl p-4">
                        <h3 class="font-semibold text-gray-700 mb-2">Lokasi Venue</h3>
                        <div class="flex items-start">
                            <i class="fas fa-location-dot text-accent-blue mt-1 mr-2"></i>
                            <p class="text-gray-600"><?= safe($event['alamat']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Ticket Selection -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-md sticky top-24 animate-[slideIn_0.6s_ease-out]">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-ticket-alt text-accent-blue mr-2"></i>
                            Pilih Tiket
                        </h2>
                        <p class="text-gray-500 text-sm mt-1">Pilih jenis tiket dan jumlah yang diinginkan</p>
                    </div>

                    <form action="order.php" method="POST" id="orderForm" onsubmit="return validateForm()">
                        <input type="hidden" name="id_event" value="<?= $event['id_event'] ?>">
                        <input type="hidden" name="id_venue" value="<?= $event['id_venue'] ?>">
                        
                        <div class="p-6 space-y-4">
                            <?php 
                            if (!$ada_tiket) { 
                            ?>
                            <div class="text-center py-8">
                                <i class="fas fa-ticket-alt text-5xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">Belum ada tiket tersedia untuk event ini.</p>
                                <p class="text-gray-400 text-sm mt-1">Silakan cek kembali nanti.</p>
                            </div>
                            <?php 
                            } else {
                                foreach($tiket_array as $t) { 
                                    $max_kuota = $t['kuota'];
                                    $tiket_id = $t['id_tiket'];
                                    $is_habis = ($max_kuota == 0);
                            ?>
                            <div class="ticket-card border border-gray-200 rounded-xl p-4 hover:border-accent-blue/30 transition <?= $is_habis ? 'ticket-habis' : '' ?>">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-semibold text-gray-800"><?= safe($t['nama_tiket']) ?></h3>
                                        <div class="flex items-center gap-3 mt-1">
                                            <span class="text-lg font-bold text-accent-blue">
                                                Rp <?= number_format($t['harga'], 0, ',', '.') ?>
                                            </span>
                                            <span class="text-xs <?= $is_habis ? 'text-red-500 font-semibold' : 'text-gray-400' ?>" id="sisa_<?= $tiket_id ?>">
                                                <i class="fas fa-users"></i> Sisa: <?= $max_kuota ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-600">Qty:</label>
                                        <input 
                                            type="number" 
                                            name="qty[<?= $tiket_id ?>]" 
                                            min="0" 
                                            max="<?= $max_kuota ?>" 
                                            value="0"
                                            <?= $is_habis ? 'disabled' : '' ?>
                                            class="quantity-input w-20 px-3 py-2 border border-gray-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent <?= $is_habis ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                            onchange="validateQuantity(this, <?= $tiket_id ?>, <?= $max_kuota ?>); updateTotal()"
                                            oninput="validateQuantity(this, <?= $tiket_id ?>, <?= $max_kuota ?>); updateTotal()"
                                            id="qty_<?= $tiket_id ?>"
                                        >
                                    </div>
                                </div>
                                <div class="text-xs <?= $is_habis ? 'text-red-500' : 'text-gray-400' ?>">
                                    <i class="fas fa-info-circle"></i> 
                                    <?= $is_habis ? '⚠️ Tiket sudah habis terjual!' : 'Maksimal pembelian ' . $max_kuota . ' tiket' ?>
                                </div>
                                <div id="error_<?= $tiket_id ?>" class="error-message hidden"></div>
                            </div>
                            <?php 
                                }
                            } 
                            ?>
                        </div>

                        <?php if ($ada_tiket && !$semua_tiket_habis): ?>
                        <!-- Voucher Section - HANYA ditampilkan jika tiket masih tersedia -->
                        <div class="p-6 border-t border-gray-100 bg-gray-50">
                            <h4 class="font-semibold text-gray-700 mb-3">
                                <i class="fas fa-gift text-accent-blue mr-2"></i>
                                Kode Voucher (opsional)
                            </h4>
                            
                            <div class="flex flex-col gap-3">
                                <div class="flex gap-2">
                                    <input 
                                        type="text" 
                                        name="kode_voucher" 
                                        id="kode_voucher"
                                        placeholder="Masukkan kode voucher" 
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent text-sm"
                                        onkeyup="checkVoucher()"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="applyVoucher()"
                                        class="bg-accent-blue text-white px-3 py-2 rounded-lg hover:bg-accent-hover transition text-sm whitespace-nowrap"
                                    >
                                        <i class="fas fa-check"></i> 
                                    </button>
                                </div>
                                <div id="voucherMessage" class="text-sm hidden"></div>
                            </div>
                            
                            <?php if ($voucher && mysqli_num_rows($voucher) > 0): ?>
                            <div class="mt-4">
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    mysqli_data_seek($voucher, 0);
                                    while($v = mysqli_fetch_assoc($voucher)):
                                    ?>
                                    <div onclick="applyVoucherCode('<?= safe($v['kode_voucher']) ?>')" 
                                         class="voucher-badge inline-flex items-center gap-1.5 text-xs bg-green-100 text-green-700 border border-green-200 px-2.5 py-1 rounded-full transition">
                                        <i class="fas fa-tag"></i>
                                        <span class="font-mono font-semibold"><?= safe($v['kode_voucher']) ?></span>
                                        <span class="text-xs">(<?= $v['potongan'] ?>% OFF)</span>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">
                                    <i class="fas fa-info-circle"></i> Klik voucher untuk menggunakannya
                                </p>
                            </div>
                            <?php else: ?>
                            <div class="mt-4 text-center py-2">
                                <p class="text-xs text-gray-400">
                                    <i class="fas fa-info-circle"></i> Belum ada voucher khusus untuk event ini.
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Order Summary -->
                        <div class="p-6 border-t border-gray-100">
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-gray-600">
                                    <span>Subtotal:</span>
                                    <span id="subtotal">Rp 0</span>
                                </div>
                                <div class="flex justify-between text-green-600" id="discountRow" style="display: none;">
                                    <span>Diskon (<span id="discountPercent">0</span>%):</span>
                                    <span id="discount">- Rp 0</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold text-gray-800 pt-2 border-t border-gray-200">
                                    <span>Total:</span>
                                    <span id="total" class="text-accent-blue">Rp 0</span>
                                </div>
                            </div>
                            
                            <?php if($semua_tiket_habis || !$ada_tiket): ?>
                            <button 
                                type="button" 
                                disabled
                                class="w-full bg-gray-400 text-white py-3 rounded-xl font-semibold cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <i class="fas fa-times-circle"></i>
                                Tiket Habis
                            </button>
                            <p class="text-center text-xs text-red-500 mt-3">
                                <i class="fas fa-exclamation-triangle"></i> Maaf, semua tiket untuk event ini sudah habis.
                            </p>
                            <?php else: ?>
                            <button 
                                type="submit" 
                                id="submitBtn"
                                class="w-full bg-accent-blue text-white py-3 rounded-xl font-semibold hover:bg-accent-hover transition transform hover:scale-[1.02] flex items-center justify-center gap-2"
                            >
                                <i class="fas fa-shopping-cart"></i>
                                Lanjutkan ke Pembayaran
                            </button>
                            <p class="text-center text-xs text-gray-400 mt-3">
                                <i class="fas fa-lock"></i> Pembayaran aman dan terjamin
                            </p>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data tiket dari PHP
        const tiketData = <?php 
            $data = [];
            foreach($tiket_array as $t) {
                $data[$t['id_tiket']] = [
                    'harga' => $t['harga'],
                    'nama' => $t['nama_tiket'],
                    'kuota' => $t['kuota']
                ];
            }
            echo json_encode($data);
        ?>;
        
        // Data voucher dari PHP - HANYA jika tiket tidak habis
        const voucherData = <?php 
            $vouchers = [];
            if (!$semua_tiket_habis && $voucher) {
                mysqli_data_seek($voucher, 0);
                while($v = mysqli_fetch_assoc($voucher)) {
                    $vouchers[$v['kode_voucher']] = [
                        'potongan' => $v['potongan'],
                        'id_voucher' => $v['id_voucher']
                    ];
                }
            }
            echo json_encode($vouchers);
        ?>;
        
        let appliedVoucher = null;
        let voucherPotongan = 0;
        let appliedVoucherId = null;

        // Fungsi untuk menampilkan notifikasi tiket habis
        function showHabisNotifikasi(namaTiket) {
            // Cek apakah sudah ada notifikasi
            if (!document.getElementById('notifikasiHabis')) {
                const notifDiv = document.createElement('div');
                notifDiv.id = 'notifikasiHabis';
                notifDiv.className = 'fixed top-20 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-3 notif-habis';
                notifDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <div>
                        <strong class="text-sm">Tiket Habis!</strong>
                        <p class="text-xs">${namaTiket} sudah tidak tersedia.</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="ml-4 hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                document.body.appendChild(notifDiv);
                
                // Auto hilang setelah 5 detik
                setTimeout(() => {
                    const notif = document.getElementById('notifikasiHabis');
                    if (notif) notif.remove();
                }, 5000);
            }
        }

        // Fungsi validasi quantity real-time
        function validateQuantity(input, tiketId, maxKuota) {
            let value = parseInt(input.value);
            const errorDiv = document.getElementById('error_' + tiketId);
            
            if (isNaN(value)) {
                value = 0;
                input.value = 0;
            }
            
            if (maxKuota === 0) {
                input.disabled = true;
                input.classList.add('bg-gray-100', 'cursor-not-allowed');
                errorDiv.classList.remove('hidden');
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Tiket sudah habis!';
                return 0;
            }
            
            if (value > maxKuota) {
                input.classList.add('error-border');
                errorDiv.classList.remove('hidden');
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Maksimal ' + maxKuota + ' tiket!';
                input.value = maxKuota;
                value = maxKuota;
            } 
            else if (value < 0) {
                input.classList.add('error-border');
                errorDiv.classList.remove('hidden');
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Jumlah tidak boleh negatif!';
                input.value = 0;
                value = 0;
            }
            else {
                input.classList.remove('error-border');
                errorDiv.classList.add('hidden');
                errorDiv.innerHTML = '';
            }
            
            const sisaElement = document.getElementById('sisa_' + tiketId);
            if (sisaElement) {
                const sisaBaru = maxKuota - value;
                sisaElement.innerHTML = '<i class="fas fa-users"></i> Sisa: ' + sisaBaru;
            }
            
            return value;
        }

        // Fungsi validasi sebelum submit form
        function validateForm() {
            let isValid = true;
            let totalPesanan = 0;
            
            for (let id in tiketData) {
                const input = document.getElementById('qty_' + id);
                if (input && !input.disabled) {
                    let qty = parseInt(input.value) || 0;
                    const maxKuota = tiketData[id].kuota;
                    
                    if (maxKuota === 0) {
                        isValid = false;
                        const errorDiv = document.getElementById('error_' + id);
                        errorDiv.classList.remove('hidden');
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Tiket sudah habis!';
                        input.classList.add('error-border');
                    }
                    
                    if (qty > maxKuota) {
                        isValid = false;
                        const errorDiv = document.getElementById('error_' + id);
                        errorDiv.classList.remove('hidden');
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Melebihi kuota tersedia!';
                        input.classList.add('error-border');
                    }
                    
                    totalPesanan += qty;
                }
            }
            
            if (totalPesanan === 0) {
                alert('Silakan pilih minimal 1 tiket untuk melanjutkan pemesanan.');
                isValid = false;
            }
            
            if (!isValid) {
                alert('Mohon periksa kembali jumlah tiket yang dipilih. Ada yang melebihi kuota atau tiket habis.');
            } else if (appliedVoucher) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'id_voucher';
                hiddenInput.value = appliedVoucherId;
                document.getElementById('orderForm').appendChild(hiddenInput);
            }
            
            return isValid;
        }

        function updateTotal() {
            let subtotal = 0;
            
            for (let id in tiketData) {
                const qtyInput = document.getElementById('qty_' + id);
                if (qtyInput && !qtyInput.disabled) {
                    let qty = parseInt(qtyInput.value) || 0;
                    const maxKuota = tiketData[id].kuota;
                    
                    if (qty > maxKuota) {
                        qty = maxKuota;
                        qtyInput.value = maxKuota;
                    }
                    if (qty < 0) {
                        qty = 0;
                        qtyInput.value = 0;
                    }
                    
                    const harga = tiketData[id].harga;
                    subtotal += qty * harga;
                }
            }
            
            document.getElementById('subtotal').innerText = 'Rp ' + formatNumber(subtotal);
            
            let diskon = 0;
            if (appliedVoucher && voucherPotongan > 0 && subtotal > 0) {
                diskon = Math.floor(subtotal * voucherPotongan / 100);
            }
            
            if (diskon > 0) {
                document.getElementById('discountRow').style.display = 'flex';
                document.getElementById('discount').innerText = '- Rp ' + formatNumber(diskon);
                document.getElementById('discountPercent').innerText = voucherPotongan;
            } else {
                document.getElementById('discountRow').style.display = 'none';
            }
            
            const total = subtotal - diskon;
            document.getElementById('total').innerText = 'Rp ' + formatNumber(total);
        }

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function checkVoucher() {
            const kode = document.getElementById('kode_voucher').value.trim().toUpperCase();
            const messageDiv = document.getElementById('voucherMessage');
            
            if (kode === '') {
                messageDiv.classList.add('hidden');
                return;
            }
            
            if (voucherData[kode]) {
                messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> Voucher ' + kode + ' siap digunakan (' + voucherData[kode].potongan + '% off)';
                messageDiv.classList.remove('hidden', 'text-red-600', 'bg-red-50');
                messageDiv.classList.add('text-green-600', 'bg-green-50', 'p-2', 'rounded-lg');
            } else {
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Kode voucher tidak valid untuk event ini';
                messageDiv.classList.remove('hidden', 'text-green-600', 'bg-green-50');
                messageDiv.classList.add('text-red-600', 'bg-red-50', 'p-2', 'rounded-lg');
            }
        }

        function applyVoucherCode(kode) {
            document.getElementById('kode_voucher').value = kode;
            applyVoucher();
        }

        function applyVoucher() {
            const kode = document.getElementById('kode_voucher').value.trim().toUpperCase();
            const messageDiv = document.getElementById('voucherMessage');
            
            if (kode === '') {
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Masukkan kode voucher terlebih dahulu';
                messageDiv.classList.remove('hidden', 'text-green-600', 'bg-green-50');
                messageDiv.classList.add('text-red-600', 'bg-red-50', 'p-2', 'rounded-lg');
                return;
            }
            
            if (voucherData[kode]) {
                appliedVoucher = kode;
                voucherPotongan = voucherData[kode].potongan;
                appliedVoucherId = voucherData[kode].id_voucher;
                updateTotal();
                
                messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> Voucher ' + kode + ' berhasil diterapkan! Potongan ' + voucherPotongan + '%';
                messageDiv.classList.remove('hidden', 'text-red-600', 'bg-red-50');
                messageDiv.classList.add('text-green-600', 'bg-green-50', 'p-2', 'rounded-lg');
            } else {
                appliedVoucher = null;
                voucherPotongan = 0;
                appliedVoucherId = null;
                updateTotal();
                
                messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Kode voucher tidak valid untuk event ini';
                messageDiv.classList.remove('hidden', 'text-green-600', 'bg-green-50');
                messageDiv.classList.add('text-red-600', 'bg-red-50', 'p-2', 'rounded-lg');
            }
        }

        // Event listener untuk semua input quantity
        document.addEventListener('DOMContentLoaded', function() {
            for (let id in tiketData) {
                const input = document.getElementById('qty_' + id);
                if (input) {
                    input.addEventListener('input', function() {
                        validateQuantity(this, id, tiketData[id].kuota);
                        updateTotal();
                    });
                    input.addEventListener('change', function() {
                        validateQuantity(this, id, tiketData[id].kuota);
                        updateTotal();
                    });
                }
            }
            updateTotal();
        });
    </script>
</body>
</html>