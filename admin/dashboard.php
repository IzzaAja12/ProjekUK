<?php
// Enable error reporting untuk debugging (hapus saat production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include '../config/koneksi.php';

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set default session jika belum ada
if(!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'Administrator';
    $_SESSION['nama'] = 'Admin';
}

// Function untuk cek query error
function checkQuery($result, $query_name, $conn) {
    if (!$result) {
        die("Error pada query '$query_name': " . mysqli_error($conn));
    }
    return $result;
}

// =======================
// AMBIL DATA STATISTIK REAL DARI DATABASE
// =======================

// Total Event
$query_event = checkQuery(mysqli_query($conn, "SELECT COUNT(*) as total FROM event"), "Total Event", $conn);
$total_event = mysqli_fetch_assoc($query_event)['total'] ?? 0;

// Total Tiket (jumlah semua tiket dari semua event)
$query_tiket = checkQuery(mysqli_query($conn, "SELECT COUNT(*) as total FROM tiket"), "Total Tiket", $conn);
$total_tiket = mysqli_fetch_assoc($query_tiket)['total'] ?? 0;

// Total Kuota Tiket (kapasitas total tiket yang tersedia)
$query_kuota_tiket = mysqli_query($conn, "SELECT SUM(kuota) as total_kuota FROM tiket");
if ($query_kuota_tiket) {
    $total_kuota_tiket = mysqli_fetch_assoc($query_kuota_tiket)['total_kuota'] ?? 0;
} else {
    $total_kuota_tiket = 0;
}

// Total Venue
$query_venue = checkQuery(mysqli_query($conn, "SELECT COUNT(*) as total FROM venue"), "Total Venue", $conn);
$total_venue = mysqli_fetch_assoc($query_venue)['total'] ?? 0;

// Total Voucher Aktif
$query_voucher_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM voucher WHERE status = 'aktif'");
if ($query_voucher_aktif) {
    $total_voucher_aktif = mysqli_fetch_assoc($query_voucher_aktif)['total'] ?? 0;
} else {
    $total_voucher_aktif = 0;
}

// Total Voucher (semua)
$query_voucher = mysqli_query($conn, "SELECT COUNT(*) as total FROM voucher");
if ($query_voucher) {
    $total_voucher = mysqli_fetch_assoc($query_voucher)['total'] ?? 0;
} else {
    $total_voucher = 0;
}

// Total Kapasitas Venue (akumulasi semua venue)
$query_kapasitas_venue = mysqli_query($conn, "SELECT SUM(kapasitas) as total_kapasitas FROM venue");
if ($query_kapasitas_venue) {
    $total_kapasitas_venue = mysqli_fetch_assoc($query_kapasitas_venue)['total_kapasitas'] ?? 0;
} else {
    $total_kapasitas_venue = 0;
}

// =======================
// DATA UNTUK GRAFIK
// =======================

// 1. Data Event per Bulan (6 bulan terakhir)
$bulan_labels = [];
$event_per_bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $bulan = date('Y-m', strtotime("-$i months"));
    $bulan_label = date('M Y', strtotime("-$i months"));
    $bulan_labels[] = $bulan_label;
    
    $query = mysqli_query($conn, "
        SELECT COUNT(*) as total 
        FROM event 
        WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'
    ");
    $data = mysqli_fetch_assoc($query);
    $event_per_bulan[] = $data['total'] ?? 0;
}

// 2. Data Status Tiket (Terjual vs Sisa)
$query_terjual = mysqli_query($conn, "SELECT COUNT(*) as total FROM attendee");
$total_terjual = mysqli_fetch_assoc($query_terjual)['total'] ?? 0;
$total_sisa = max(0, $total_kuota_tiket - $total_terjual);

// 3. Data Event per Venue (Top 5)
$top_venues_labels = [];
$top_venues_data = [];
$query_top_venues = mysqli_query($conn, "
    SELECT v.nama_venue, COUNT(e.id_event) as total_event
    FROM venue v
    LEFT JOIN event e ON v.id_venue = e.id_venue
    GROUP BY v.id_venue
    ORDER BY total_event DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($query_top_venues)) {
    $top_venues_labels[] = $row['nama_venue'];
    $top_venues_data[] = $row['total_event'];
}

// =======================
// PAGINATION & SEARCH UNTUK EVENT
// =======================
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Query dengan search
$search_condition = "";
if (!empty($search)) {
    $search_condition = "WHERE (event.nama_event LIKE '%$search%' 
                           OR venue.nama_venue LIKE '%$search%')";
}

// Total data untuk pagination
$total_query = mysqli_query($conn, "
    SELECT COUNT(*) as total
    FROM event 
    LEFT JOIN venue ON event.id_venue = venue.id_venue 
    $search_condition
");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data event dengan pagination
$query_event_table = mysqli_query($conn, "
    SELECT event.*, venue.nama_venue 
    FROM event 
    LEFT JOIN venue ON event.id_venue = venue.id_venue 
    $search_condition
    ORDER BY event.tanggal DESC 
    LIMIT $offset, $limit
");

// =======================
// HITUNG PENDAPATAN POTENSIAL (jika semua tiket terjual)
// =======================
$query_pendapatan = mysqli_query($conn, "
    SELECT SUM(tiket.harga * tiket.kuota) as total_pendapatan 
    FROM tiket
");

if ($query_pendapatan) {
    $total_pendapatan = mysqli_fetch_assoc($query_pendapatan)['total_pendapatan'] ?? 0;
} else {
    $total_pendapatan = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Event Ticket</title>
    <link rel="icon" href="logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js untuk Grafik -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- SheetJS untuk Export Excel -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
    <!-- html2pdf untuk Export PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'navy': '#0a2540',
                        'accent-blue': '#0066cc',
                        'soft-blue': '#e6f0fa',
                        'admin-bg': '#f8fafc',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.4s ease-in',
                        'slide-up': 'slideUp 0.3s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0,102,204,0.1);
        }
        .menu-card {
            transition: all 0.2s ease;
        }
        .menu-card:hover {
            transform: scale(1.02);
            background: linear-gradient(135deg, #0066cc 0%, #0a2540 100%);
            color: white;
        }
        .menu-card:hover i, .menu-card:hover h3, .menu-card:hover p, .menu-card:hover div {
            color: white;
        }
        .chart-container {
            transition: all 0.3s ease;
        }
        .chart-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px -5px rgba(0,0,0,0.1);
        }
        .btn-export {
            transition: all 0.2s ease;
        }
        .btn-export:hover {
            transform: translateY(-2px);
        }
        canvas {
            max-height: 180px !important;
            width: 100% !important;
        }
        /* Style untuk PDF */
        .pdf-container {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
        }
        .pdf-table th, .pdf-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .pdf-table th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body class="bg-admin-bg">
    
    <!-- Top Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="font-bold text-xl bg-gradient-to-r from-navy to-accent-blue bg-clip-text text-transparent">Admin Panel</span>
                <span class="hidden md:inline text-xs bg-soft-blue text-accent-blue px-2 py-1 rounded-full">TiketMoo</span>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-user-circle text-accent-blue text-xl"></i>
                    <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></span>
                </div>
                <a href="../auth/logout.php" class="text-gray-600 hover:text-red-500 transition"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-6">
        <!-- Welcome Section -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8 animate-[fadeIn_0.5s_ease-in]">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Dashboard Administrator</h1>
                    <p class="text-gray-500 mt-1">Selamat datang kembali! Kelola event, tiket, venue, dan voucher dengan mudah</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="bg-soft-blue rounded-lg px-4 py-2">
                        <i class="fas fa-clock text-accent-blue mr-1"></i>
                        <span class="text-sm text-gray-600"><?php echo date('l, d F Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 4 Menu Utama: Event, Tiket, Venue, Voucher -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <a href="event.php" style="text-decoration:none;">
                <div class="bg-white rounded-xl shadow-md p-5 text-center cursor-pointer menu-card transition-all">
                    <i class="fas fa-calendar-alt text-3xl text-accent-blue mb-2"></i>
                    <h3 class="font-bold text-gray-800">Event</h3>
                    <div class="mt-2 text-accent-blue font-semibold">
                        <?= number_format($total_event, 0, ',', '.') ?> Event
                    </div>
                </div>
            </a>
            <a href="tiket.php" style="text-decoration:none;">
                <div class="bg-white rounded-xl shadow-md p-5 text-center cursor-pointer menu-card transition-all">
                    <i class="fas fa-ticket-alt text-3xl text-accent-blue mb-2"></i>
                    <h3 class="font-bold text-gray-800">Tiket</h3>
                    <div class="mt-2 text-accent-blue font-semibold">
                        <?= number_format($total_tiket, 0, ',', '.') ?> Jenis
                    </div>
                </div>
            </a>
            <a href="venue.php" style="text-decoration:none;">
                <div class="bg-white rounded-xl shadow-md p-5 text-center cursor-pointer menu-card transition-all">
                    <i class="fas fa-map-marker-alt text-3xl text-accent-blue mb-2"></i>
                    <h3 class="font-bold text-gray-800">Venue</h3>
                    <div class="mt-2 text-accent-blue font-semibold">
                        <?= number_format($total_venue, 0, ',', '.') ?> Venue
                    </div>
                </div>
            </a>
            <a href="voucher.php" style="text-decoration:none;">
                <div class="bg-white rounded-xl shadow-md p-5 text-center cursor-pointer menu-card transition-all">
                    <i class="fas fa-gift text-3xl text-accent-blue mb-2"></i>
                    <h3 class="font-bold text-gray-800">Voucher</h3>
                    <div class="mt-2 text-accent-blue font-semibold">
                        <?= number_format($total_voucher_aktif, 0, ',', '.') ?> Aktif
                    </div>
                </div>
            </a>
        </div>        
        
        <!-- GRAFIK SECTION - Ukuran Lebih Kecil -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
            <!-- Grafik Event per Bulan -->
            <div class="chart-container bg-white rounded-xl shadow-md p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 text-sm">
                        <i class="fas fa-chart-line text-accent-blue mr-1"></i> Event per Bulan
                    </h3>
                </div>
                <canvas id="eventChart" style="height: 160px !important; width: 100% !important;"></canvas>
            </div>

            <!-- Grafik Status Tiket -->
            <div class="chart-container bg-white rounded-xl shadow-md p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 text-sm">
                        <i class="fas fa-chart-pie text-accent-blue mr-1"></i> Status Tiket
                    </h3>
                </div>
                <canvas id="ticketStatusChart" style="height: 130px !important; width: 100% !important;"></canvas>
                <div class="mt-2 flex justify-center gap-4 text-xs">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span>Terjual: <?= number_format($total_terjual, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span>Sisa: <?= number_format($total_sisa, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Grafik Event per Venue -->
            <div class="chart-container bg-white rounded-xl shadow-md p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 text-sm">
                        <i class="fas fa-chart-bar text-accent-blue mr-1"></i> Event per Venue
                    </h3>
                </div>
                <canvas id="venueChart" style="height: 160px !important; width: 100% !important;"></canvas>
            </div>
        </div>

        <!-- Pendapatan Potensial Card -->
        <div class="bg-gradient-to-r from-navy to-accent-blue rounded-xl shadow-lg p-4 mb-8 text-white">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-white/80 text-xs">Pendapatan Potensial</p>
                    <p class="text-2xl font-bold">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></p>
                    <p class="text-white/60 text-xs mt-1">Jika semua tiket terjual habis</p>
                </div>
                <i class="fas fa-chart-line text-4xl text-white/30"></i>
            </div>
        </div>
        
        <!-- Tabel Data Event dengan Search, Pagination, dan Export -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200">
                <div class="flex flex-col md:flex-row justify-between items-center gap-3">
                    <h3 class="font-bold text-gray-800">
                        <i class="fas fa-calendar-alt mr-2 text-accent-blue"></i> Data Event
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <!-- Form Search -->
                        <form method="GET" action="" class="flex gap-2" id="searchForm">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input 
                                    type="text" 
                                    name="search" 
                                    id="searchInput"
                                    class="pl-9 pr-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-blue focus:border-transparent w-56"
                                    placeholder="Cari event atau venue..."
                                    value="<?= htmlspecialchars($search) ?>"
                                >
                            </div>
                            <button type="submit" class="bg-accent-blue text-white px-3 py-1.5 text-sm rounded-lg hover:bg-accent-hover transition">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if (!empty($search)): ?>
                            <a href="dashboard.php" class="bg-gray-500 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-gray-600 transition">
                                <i class="fas fa-times"></i> Reset
                            </a>
                            <?php endif; ?>
                        </form>
                        <!-- Tombol Export -->
                        <button onclick="exportToExcel()" class="btn-export bg-green-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                  
                        <button onclick="exportToPDF()" class="btn-export bg-red-600 text-white px-3 py-1.5 text-sm rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto" id="tableEventContainer">
                <table class="min-w-full divide-y divide-gray-200" id="eventTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php 
                        if($query_event_table && mysqli_num_rows($query_event_table) > 0) {
                            $no = $offset + 1;
                            while($event = mysqli_fetch_assoc($query_event_table)) {
                                $today = date('Y-m-d');
                                $event_date = $event['tanggal'] ?? '';
                                if($event_date > $today) {
                                    $status = '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs"><i class="fas fa-check-circle mr-1"></i>Akan Datang</span>';
                                } elseif($event_date == $today) {
                                    $status = '<span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-xs"><i class="fas fa-calendar-day mr-1"></i>Hari Ini</span>';
                                } else {
                                    $status = '<span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs"><i class="fas fa-check-double mr-1"></i>Selesai</span>';
                                }
                        ?>
                        <tr class="hover:bg-blue-50 transition">
                            <td class="px-5 py-3 text-sm text-gray-600"><?= $no++ ?></td>
                            <td class="px-5 py-3 font-medium text-gray-900 text-sm"><?= htmlspecialchars($event['nama_event'] ?? '-') ?></td>
                            <td class="px-5 py-3 text-gray-600 text-sm"><?= htmlspecialchars($event['nama_venue'] ?? '-') ?></td>
                            <td class="px-5 py-3 text-gray-600 text-sm"><?= isset($event['tanggal']) ? date('d M Y', strtotime($event['tanggal'])) : '-' ?></td>
                            <td class="px-5 py-3"><?= $status ?></td>
                        </tr>
                        <?php 
                            }
                        } else { 
                        ?>
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-gray-400">
                                <i class="fas fa-inbox text-3xl mb-2 block"></i>
                                Belum ada data event
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="px-5 py-3 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-3">
                <div class="text-xs text-gray-500">
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_data) ?> dari <?= number_format($total_data, 0, ',', '.') ?> data
                </div>
                <div class="flex gap-1 flex-wrap justify-center">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" 
                       class="px-2.5 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                       class="px-3 py-1 text-sm border border-gray-300 rounded-lg transition <?= $i == $page ? 'bg-accent-blue text-white' : 'hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" 
                       class="px-2.5 py-1 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer Admin -->
        <div class="text-center text-gray-400 text-xs mt-6 py-4">
            <i class="fas fa-shield-alt text-accent-blue"></i> Admin Panel Secure • TiketMoo v2.0
            <br>
            <span class="text-gray-300">© <?= date('Y') ?> TiketMoo System. All rights reserved.</span>
        </div>
    </div>

    <!-- Hidden div untuk export PDF -->
    <div id="pdfContent" style="display: none;">
        <div class="pdf-container">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #0a2540;">Laporan Data Event</h2>
                <p style="color: #666;">Event Ticket System</p>
                <p style="color: #999; font-size: 12px;">Tanggal: <?= date('d F Y H:i:s') ?></p>
                <hr style="margin: 10px 0;">
            </div>
            <table class="pdf-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Event</th>
                        <th>Venue</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Ambil semua data untuk PDF (tanpa pagination)
                    $query_pdf = mysqli_query($conn, "
                        SELECT event.*, venue.nama_venue 
                        FROM event 
                        LEFT JOIN venue ON event.id_venue = venue.id_venue 
                        $search_condition
                        ORDER BY event.tanggal DESC
                    ");
                    $no_pdf = 1;
                    while($event_pdf = mysqli_fetch_assoc($query_pdf)) {
                        $today = date('Y-m-d');
                        $event_date = $event_pdf['tanggal'] ?? '';
                        if($event_date > $today) {
                            $status_pdf = 'Akan Datang';
                        } elseif($event_date == $today) {
                            $status_pdf = 'Hari Ini';
                        } else {
                            $status_pdf = 'Selesai';
                        }
                        echo "<tr>";
                        echo "<td>{$no_pdf}</td>";
                        echo "<td>" . htmlspecialchars($event_pdf['nama_event'] ?? '-') . "</td>";
                        echo "<td>" . htmlspecialchars($event_pdf['nama_venue'] ?? '-') . "</td>";
                        echo "<td>" . (isset($event_pdf['tanggal']) ? date('d M Y', strtotime($event_pdf['tanggal'])) : '-') . "</td>";
                        echo "<td>{$status_pdf}</td>";
                        echo "</tr>";
                        $no_pdf++;
                    }
                    ?>
                </tbody>
            </table>
            <div style="margin-top: 20px; text-align: center; font-size: 10px; color: #999;">
                <hr>
                <p>© <?= date('Y') ?> Event Ticket System - Laporan generated on <?= date('d F Y H:i:s') ?></p>
            </div>
        </div>
    </div>

    <script>
        // Data dari PHP untuk grafik
        const bulanLabels = <?= json_encode($bulan_labels) ?>;
        const eventPerBulan = <?= json_encode($event_per_bulan) ?>;
        const totalTerjual = <?= $total_terjual ?>;
        const totalSisa = <?= $total_sisa ?>;
        const topVenuesLabels = <?= json_encode($top_venues_labels) ?>;
        const topVenuesData = <?= json_encode($top_venues_data) ?>;

        // Grafik Event per Bulan (Line Chart)
        const eventCtx = document.getElementById('eventChart').getContext('2d');
        new Chart(eventCtx, {
            type: 'line',
            data: {
                labels: bulanLabels,
                datasets: [{
                    label: 'Event',
                    data: eventPerBulan,
                    borderColor: '#0066cc',
                    backgroundColor: 'rgba(0, 102, 204, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#0066cc',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1.5,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0a2540', bodyFont: { size: 11 } }
                },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 9 }, rotation: 0 } }
                }
            }
        });

        // Grafik Status Tiket (Doughnut Chart)
        const ticketStatusCtx = document.getElementById('ticketStatusChart').getContext('2d');
        new Chart(ticketStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Terjual', 'Sisa Kuota'],
                datasets: [{
                    data: [totalTerjual, totalSisa],
                    backgroundColor: ['#10b981', '#3b82f6'],
                    borderColor: '#fff',
                    borderWidth: 1.5,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        backgroundColor: '#0a2540',
                        bodyFont: { size: 11 },
                        callbacks: {
                            label: function(context) {
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Grafik Event per Venue (Bar Chart)
        const venueCtx = document.getElementById('venueChart').getContext('2d');
        new Chart(venueCtx, {
            type: 'bar',
            data: {
                labels: topVenuesLabels,
                datasets: [{
                    label: 'Jumlah Event',
                    data: topVenuesData,
                    backgroundColor: 'rgba(0, 102, 204, 0.7)',
                    borderRadius: 4,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: '#0a2540', bodyFont: { size: 11 } }
                },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { font: { size: 10 } } },
                    x: { ticks: { font: { size: 9 }, rotation: 0, maxRotation: 30 } }
                }
            }
        });

        // Export ke Excel
        function exportToExcel() {
            const table = document.getElementById('eventTable');
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table, { raw: true });
            XLSX.utils.book_append_sheet(wb, ws, 'Data_Event');
            XLSX.writeFile(wb, `Data_Event_<?= date('Y-m-d') ?>.xlsx`);
        }

        // Export ke CSV
        function exportToCSV() {
            const table = document.getElementById('eventTable');
            const ws = XLSX.utils.table_to_sheet(table, { raw: true });
            const csv = XLSX.utils.sheet_to_csv(ws);
            const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.href = url;
            link.setAttribute('download', `Data_Event_<?= date('Y-m-d') ?>.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);     
        }

        // Export ke PDF
        function exportToPDF() {
            const element = document.getElementById('pdfContent');
            const opt = {
                margin: [0.5, 0.5, 0.5, 0.5],
                filename: `Data_Event_<?= date('Y-m-d') ?>.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, letterRendering: true, useCORS: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
            };
            html2pdf().set(opt).from(element).save();
        }

        // Search dengan enter
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('searchForm').submit();
                }
            });
        }
    </script>
</body>
</html>