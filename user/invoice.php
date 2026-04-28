<?php
session_start();
include '../config/koneksi.php';

// Proteksi login
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$search_condition = "";
if (!empty($search)) {
    $search_condition = "AND (o.no_order LIKE '%$search%' 
                           OR e.nama_event LIKE '%$search%')";
}

// Total data
$total_query = mysqli_query($conn, "
    SELECT COUNT(DISTINCT o.id_order) as total
    FROM orders o
    JOIN event e ON o.id_event = e.id_event
    WHERE o.id_user = $id_user $search_condition
");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data pesanan
$query_orders = mysqli_query($conn, "
    SELECT o.*, e.nama_event, e.tanggal as event_tanggal, e.foto as event_foto,
           v.nama_venue, v.alamat as venue_alamat,
           vc.kode_voucher, vc.potongan
    FROM orders o
    JOIN event e ON o.id_event = e.id_event
    JOIN venue v ON e.id_venue = v.id_venue
    LEFT JOIN voucher vc ON o.id_voucher = vc.id_voucher
    WHERE o.id_user = $id_user $search_condition
    ORDER BY o.tanggal_order DESC
    LIMIT $offset, $limit
");

// Fungsi aman
function safe($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Fungsi format rupiah
function rupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan | TiketMoo</title>
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
        .order-card {
            transition: all 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -12px rgba(0,102,204,0.15);
        }
        .status-paid {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .status-pending {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        .status-cancel {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- Header -->
        <div class="mb-8 animate-[slideIn_0.3s_ease-out]">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-receipt text-accent-blue"></i>
                Riwayat Pesanan
            </h1>
            <p class="text-gray-500 mt-2">Lihat semua pesanan tiket yang telah Anda lakukan</p>
        </div>

        <!-- Search -->
        <div class="mb-6 flex justify-end">
            <form method="GET" action="" class="flex gap-2">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?= safe($search) ?>" 
                           placeholder="Cari no. pesanan atau event..."
                           class="pl-10 pr-4 py-2 w-64 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent">
                </div>
                <button type="submit" class="bg-accent-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search"></i> Cari
                </button>
                <?php if (!empty($search)): ?>
                <a href="invoice.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- List Orders -->
        <?php if (mysqli_num_rows($query_orders) > 0): ?>
            <div class="space-y-4">
                <?php while($order = mysqli_fetch_assoc($query_orders)): 
                    $status_class = '';
                    $status_text = '';
                    if ($order['status'] == 'paid') {
                        $status_class = 'status-paid';
                        $status_text = 'Lunas';
                    } elseif ($order['status'] == 'pending') {
                        $status_class = 'status-pending';
                        $status_text = 'Menunggu Pembayaran';
                    } else {
                        $status_class = 'status-cancel';
                        $status_text = 'Dibatalkan';
                    }
                ?>
                <div class="order-card bg-white rounded-2xl shadow-md overflow-hidden animate-[slideIn_0.5s_ease-out]">
                    <div class="flex flex-col md:flex-row">
                        <!-- Image -->
                        <div class="md:w-32 h-32 bg-gray-200 relative overflow-hidden">
                            <img src="<?= !empty($order['event_foto']) ? '../uploads/event/' . $order['event_foto'] : 'https://placehold.co/128x128?text=Event' ?>" 
                                 class="w-full h-full object-cover" alt="<?= safe($order['nama_event']) ?>">
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 p-5">
                            <div class="flex flex-wrap justify-between items-start gap-2">
                                <div>
                                    <h3 class="font-bold text-xl text-gray-800"><?= safe($order['nama_event']) ?></h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <i class="fas fa-calendar-alt text-accent-blue mr-1"></i>
                                        <?= date('l, d F Y', strtotime($order['event_tanggal'])) ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <i class="fas fa-map-marker-alt text-accent-blue mr-1"></i>
                                        <?= safe($order['nama_venue']) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white <?= $status_class ?>">
                                        <?= $status_text ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap justify-between items-center gap-3">
                                <div>
                                    <p class="text-xs text-gray-400">No. Pesanan</p>
                                    <p class="font-mono text-sm font-semibold text-gray-700"><?= safe($order['no_order']) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Tanggal Pesan</p>
                                    <p class="text-sm text-gray-700"><?= date('d M Y H:i', strtotime($order['tanggal_order'])) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400">Total Pembayaran</p>
                                    <p class="font-bold text-lg text-accent-blue"><?= rupiah($order['total']) ?></p>
                                </div>
                                <a href="invoice_detail.php?id=<?= $order['id_order'] ?>" 
                                   class="bg-accent-blue text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                                    <i class="fas fa-eye mr-1"></i> Detail
                                </a>
                            </div>
                            
                            <?php if ($order['kode_voucher']): ?>
                            <div class="mt-2">
                                <span class="inline-flex items-center gap-1 text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full">
                                    <i class="fas fa-tag"></i> Voucher: <?= safe($order['kode_voucher']) ?> (<?= $order['potongan'] ?>% OFF)
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-500">
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_data) ?> dari <?= number_format($total_data, 0, ',', '.') ?> pesanan
                </div>
                <div class="flex gap-2 flex-wrap justify-center">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition flex items-center gap-1">
                        <i class="fas fa-chevron-left text-sm"></i> Sebelumnya
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                       class="w-10 h-10 flex items-center justify-center rounded-xl transition <?= $i == $page ? 'bg-accent-blue text-white shadow-md' : 'border border-gray-300 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition flex items-center gap-1">
                        Selanjutnya <i class="fas fa-chevron-right text-sm"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="bg-white rounded-2xl shadow-md p-12 text-center animate-[slideIn_0.5s_ease-out]">
                <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">Belum Ada Pesanan</h3>
                <p class="text-gray-500 mb-6">Anda belum melakukan pemesanan tiket apapun.</p>
                <a href="dashboard.php" class="bg-accent-blue text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition inline-flex items-center gap-2">
                    <i class="fas fa-ticket-alt"></i> Pesan Tiket Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>