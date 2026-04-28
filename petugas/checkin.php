<?php
session_start();
include '../config/koneksi.php';

// Proteksi role petugas
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['nama'])) $_SESSION['nama'] = 'Petugas';

// Fungsi helper
function safe($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

function getTicketData($conn, $kode) {
    $query = mysqli_query($conn, "
        SELECT a.*, od.nama_tiket, od.harga, o.no_order, e.nama_event, 
               e.tanggal as event_tanggal, v.nama_venue, u.nama as nama_pembeli
        FROM attendee a
        JOIN order_detail od ON a.id_detail = od.id_detail
        JOIN orders o ON od.id_order = o.id_order
        JOIN event e ON o.id_event = e.id_event
        JOIN venue v ON e.id_venue = v.id_venue
        JOIN users u ON o.id_user = u.id_user
        WHERE a.kode_tiket = '$kode'
    ");
    return mysqli_fetch_assoc($query);
}

// Proses Check-in Manual
$message = $messageType = '';
$lastScan = null;

if (isset($_POST['checkin_manual'])) {
    $kode = mysqli_real_escape_string($conn, trim($_POST['kode_manual']));
    if (!empty($kode)) {
        $data = getTicketData($conn, $kode);
        if ($data) {
            $lastScan = $data;
            if ($data['status_checkin'] == 'belum') {
                mysqli_query($conn, "UPDATE attendee SET status_checkin='sudah', waktu_checkin=NOW() WHERE kode_tiket='$kode'");
                $message = "✅ Check-in berhasil! Selamat datang di " . $data['nama_event'];
                $messageType = "success";
                $lastScan = getTicketData($conn, $kode);
            } else {
                $message = "⚠️ Tiket sudah digunakan! Check-in: " . date('d/m/Y H:i:s', strtotime($data['waktu_checkin']));
                $messageType = "error";
            }
        } else {
            $message = "❌ Kode tiket tidak ditemukan!";
            $messageType = "error";
        }
    } else {
        $message = "📝 Masukkan kode tiket terlebih dahulu!";
        $messageType = "error";
    }
}

// Check-in via QR Code (AJAX)
if (isset($_POST['checkin_qr'])) {
    $kode = mysqli_real_escape_string($conn, trim($_POST['kode_qr']));
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    if (!empty($kode)) {
        $data = getTicketData($conn, $kode);
        if ($data) {
            if ($data['status_checkin'] == 'belum') {
                mysqli_query($conn, "UPDATE attendee SET status_checkin='sudah', waktu_checkin=NOW() WHERE kode_tiket='$kode'");
                $response = ['success' => true, 'message' => "✅ Check-in berhasil!", 'data' => $data];
            } else {
                $response['message'] = "⚠️ Tiket sudah digunakan!";
                $response['data'] = $data;
            }
        } else {
            $response['message'] = "❌ Kode tiket tidak valid!";
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Approve Cancel
if (isset($_POST['approve_cancel'])) {
    $kode = mysqli_real_escape_string($conn, $_POST['kode_tiket']);
    $query = mysqli_query($conn, "SELECT a.id_detail, o.id_order FROM attendee a JOIN order_detail od ON a.id_detail = od.id_detail JOIN orders o ON od.id_order = o.id_order WHERE a.kode_tiket='$kode'");
    $data = mysqli_fetch_assoc($query);
    if ($data) {
        mysqli_query($conn, "UPDATE orders SET status='cancel' WHERE id_order={$data['id_order']}");
        mysqli_query($conn, "DELETE FROM attendee WHERE kode_tiket='$kode'");
        $_SESSION['success_message'] = "✅ Tiket berhasil dibatalkan!";
    } else {
        $_SESSION['error_message'] = "❌ Gagal membatalkan tiket!";
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Reject Cancel
if (isset($_POST['reject_cancel'])) {
    $kode = mysqli_real_escape_string($conn, $_POST['kode_tiket']);
    mysqli_query($conn, "UPDATE attendee SET cancel_request='rejected' WHERE kode_tiket='$kode'");
    $_SESSION['info_message'] = "ℹ️ Request pembatalan ditolak!";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Statistik dengan penanganan error (FIXED)
$today = date('Y-m-d');

// Query check-in hari ini
$result_today = mysqli_query($conn, "SELECT COUNT(*) as total_checkin FROM attendee WHERE status_checkin='sudah' AND DATE(waktu_checkin)='$today'");
if ($result_today) {
    $stats_today = mysqli_fetch_assoc($result_today);
    if (!$stats_today) $stats_today = ['total_checkin' => 0];
} else {
    $stats_today = ['total_checkin' => 0];
}

// Query total statistik
$result_total = mysqli_query($conn, "SELECT COUNT(*) as total_tiket, SUM(CASE WHEN status_checkin='sudah' THEN 1 ELSE 0 END) as sudah_checkin, SUM(CASE WHEN status_checkin='belum' THEN 1 ELSE 0 END) as belum_checkin FROM attendee");
if ($result_total) {
    $stats_total = mysqli_fetch_assoc($result_total);
    if (!$stats_total) $stats_total = ['total_tiket' => 0, 'sudah_checkin' => 0, 'belum_checkin' => 0];
} else {
    $stats_total = ['total_tiket' => 0, 'sudah_checkin' => 0, 'belum_checkin' => 0];
}

// PERBAIKAN: Fungsi Pagination yang lebih robust
function getPaginatedData($conn, $query, $limit, $offset, $search, $searchFields) {
    // Escape search parameter
    $search = mysqli_real_escape_string($conn, $search);
    
    // Buat kondisi search
    $searchCondition = "";
    if (!empty($search)) {
        $conditions = [];
        foreach ($searchFields as $field) {
            $conditions[] = "$field LIKE '%$search%'";
        }
        $searchCondition = "AND (" . implode(" OR ", $conditions) . ")";
    }
    
    // Buat query untuk menghitung total
    $countQuery = $query . $searchCondition;
    $totalResult = mysqli_query($conn, $countQuery);
    
    $total = 0;
    if ($totalResult) {
        $totalRow = mysqli_fetch_assoc($totalResult);
        $total = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
    }
    
    // Query data dengan limit
    $dataQuery = $query . $searchCondition . " LIMIT $offset, $limit";
    $data = mysqli_query($conn, $dataQuery);
    
    return [
        'data' => $data, 
        'total' => $total, 
        'pages' => $total > 0 ? ceil($total / $limit) : 1
    ];
}

$limit = 10;

// Query untuk belum check-in (FIXED - dengan SELECT COUNT)
$page_belum = $_GET['page_belum'] ?? 1;
$offset_belum = ($page_belum - 1) * $limit;
$search_belum = $_GET['search_belum'] ?? '';
$belumQuery = "
    SELECT COUNT(*) as total
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    JOIN users u ON o.id_user = u.id_user
    WHERE a.status_checkin='belum' AND (a.cancel_request IS NULL OR a.cancel_request != 'pending')
";
$belum = getPaginatedData($conn, $belumQuery, $limit, $offset_belum, $search_belum, ['a.kode_tiket', 'e.nama_event', 'u.nama', 'o.no_order']);

// Query untuk sudah check-in (FIXED)
$page_sudah = $_GET['page_sudah'] ?? 1;
$offset_sudah = ($page_sudah - 1) * $limit;
$search_sudah = $_GET['search_sudah'] ?? '';
$sudahQuery = "
    SELECT COUNT(*) as total
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    JOIN users u ON o.id_user = u.id_user
    WHERE a.status_checkin='sudah'
";
$sudah = getPaginatedData($conn, $sudahQuery, $limit, $offset_sudah, $search_sudah, ['a.kode_tiket', 'e.nama_event', 'u.nama', 'o.no_order']);

// Query untuk request cancel (FIXED)
$page_request = $_GET['page_request'] ?? 1;
$offset_request = ($page_request - 1) * $limit;
$search_request = $_GET['search_request'] ?? '';
$requestQuery = "
    SELECT COUNT(*) as total
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    JOIN users u ON o.id_user = u.id_user
    WHERE a.cancel_request='pending'
";
$requests = getPaginatedData($conn, $requestQuery, $limit, $offset_request, $search_request, ['a.kode_tiket', 'e.nama_event', 'u.nama', 'o.no_order']);

// Query untuk riwayat cancel (FIXED)
$page_cancel = $_GET['page_cancel'] ?? 1;
$offset_cancel = ($page_cancel - 1) * $limit;
$search_cancel = $_GET['search_cancel'] ?? '';
$cancelQuery = "
    SELECT COUNT(*) as total
    FROM orders o
    JOIN event e ON o.id_event = e.id_event
    JOIN venue v ON e.id_venue = v.id_venue
    JOIN users u ON o.id_user = u.id_user
    WHERE o.status='cancel'
";
$cancelData = getPaginatedData($conn, $cancelQuery, $limit, $offset_cancel, $search_cancel, ['o.no_order', 'e.nama_event', 'u.nama']);

// Ambil data detail untuk masing-masing tab (tetap menggunakan query asli untuk menampilkan data)
$belumData = mysqli_query($conn, "
    SELECT a.*, e.nama_event, u.nama as nama_pembeli, o.no_order, od.nama_tiket, e.tanggal as event_tanggal
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    JOIN users u ON o.id_user = u.id_user
    WHERE a.status_checkin='belum' AND (a.cancel_request IS NULL OR a.cancel_request != 'pending')
    LIMIT $offset_belum, $limit
");

$sudahData = mysqli_query($conn, "
    SELECT a.*, e.nama_event, u.nama as nama_pembeli, a.waktu_checkin, o.no_order
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    JOIN users u ON o.id_user = u.id_user
    WHERE a.status_checkin='sudah'
    LIMIT $offset_sudah, $limit
");

$requestData = mysqli_query($conn, "
    SELECT a.*, e.nama_event, u.nama as nama_pembeli, o.no_order, od.nama_tiket, e.tanggal as event_tanggal
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    JOIN users u ON o.id_user = u.id_user
    WHERE a.cancel_request='pending'
    LIMIT $offset_request, $limit
");

$cancelDataDetails = mysqli_query($conn, "
    SELECT o.*, e.nama_event, e.tanggal as event_tanggal, u.nama as nama_pembeli, v.nama_venue,
           (SELECT SUM(qty) FROM order_detail WHERE id_order = o.id_order) as total_tiket
    FROM orders o
    JOIN event e ON o.id_event = e.id_event
    JOIN venue v ON e.id_venue = v.id_venue
    JOIN users u ON o.id_user = u.id_user
    WHERE o.status='cancel'
    LIMIT $offset_cancel, $limit
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Tiket | Petugas | TiketMoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .card-hover { 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .card-hover:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15); 
        }
        .tab-active { 
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white; 
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3); 
        }
        .tab-inactive { 
            background: #f1f5f9; 
            color: #64748b; 
            transition: all 0.2s; 
        }
        .tab-inactive:hover { 
            background: #e2e8f0; 
            transform: translateY(-1px); 
        }
        .animate-fade-in { 
            animation: fadeIn 0.4s ease-out; 
        }
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(12px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 102, 204, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(0, 102, 204, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 102, 204, 0); }
        }
        #reader video { 
            border-radius: 1rem; 
            border: 2px solid #0066cc; 
            animation: pulse-ring 2s infinite;
        }
        .stat-card { 
            cursor: pointer; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .stat-card:hover { 
            transform: translateY(-3px) scale(1.01); 
        }
        .stat-card:active { 
            transform: scale(0.98); 
        }
        .btn-action {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-action:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }
        .table-row {
            transition: all 0.2s ease;
        }
        .table-row:hover {
            background-color: #f8fafc;
        }
        .gradient-text {
            background: linear-gradient(135deg, #0a2540 0%, #0066cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50/20 to-slate-50 min-h-screen">

    <!-- Navbar Modern -->
    <nav class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-5 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-ticket-alt text-white text-sm"></i>
                </div>
                <div>
                    <span class="font-bold text-xl gradient-text">TiketMoo</span>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full ml-2">Petugas</span>
                </div>
            </div>
            <div class="flex items-center gap-5">
                <div class="flex items-center gap-2 text-gray-600 bg-gray-50 px-3 py-1.5 rounded-full">
                    <div class="w-6 h-6 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-user-shield text-white text-[10px]"></i>
                    </div>
                    <span class="text-sm font-medium"><?= safe($_SESSION['nama']) ?></span>
                </div>
                <a href="../auth/logout.php" class="text-gray-500 hover:text-red-500 transition-all duration-200 text-sm flex items-center gap-1.5 group">
                    <i class="fas fa-sign-out-alt group-hover:translate-x-0.5 transition-transform"></i>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-5 py-8 max-w-7xl animate-fade-in">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-qrcode text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-800">Check-in Tiket</h1>
                    <p class="text-gray-500 text-sm mt-0.5">Scan QR Code atau masukkan kode tiket untuk check-in pengunjung</p>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if(isset($_SESSION['success_message'])): ?>
        <script>Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?= $_SESSION['success_message'] ?>', timer: 2500, showConfirmButton: false, backdrop: true });</script>
        <?php unset($_SESSION['success_message']); endif; ?>
        <?php if(isset($_SESSION['error_message'])): ?>
        <script>Swal.fire({ icon: 'error', title: 'Gagal!', text: '<?= $_SESSION['error_message'] ?>', timer: 2500, showConfirmButton: false });</script>
        <?php unset($_SESSION['error_message']); endif; ?>
        <?php if(isset($_SESSION['info_message'])): ?>
        <script>Swal.fire({ icon: 'info', title: 'Informasi', text: '<?= $_SESSION['info_message'] ?>', timer: 2000, showConfirmButton: false });</script>
        <?php unset($_SESSION['info_message']); endif; ?>

        <!-- Statistik Cards - FIXED dengan penanganan null -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <?php 
            // Ambil nilai dengan default 0 jika null
            $checkin_hari_ini = isset($stats_today['total_checkin']) ? $stats_today['total_checkin'] : 0;
            $sudah_checkin = isset($stats_total['sudah_checkin']) ? $stats_total['sudah_checkin'] : 0;
            $belum_checkin = isset($stats_total['belum_checkin']) ? $stats_total['belum_checkin'] : 0;
            $request_total = isset($requests['total']) ? $requests['total'] : 0;
            $total_tiket = isset($stats_total['total_tiket']) ? $stats_total['total_tiket'] : 0;
            
            $stats = [
                ['label' => 'Check-in Hari Ini', 'value' => number_format($checkin_hari_ini), 'icon' => 'fa-calendar-check', 'color' => 'emerald', 'bg' => 'from-emerald-50 to-green-50', 'onclick' => 'showTodayCheckin()'],
                ['label' => 'Sudah Check-in', 'value' => number_format($sudah_checkin), 'icon' => 'fa-check-circle', 'color' => 'blue', 'bg' => 'from-blue-50 to-indigo-50'],
                ['label' => 'Belum Check-in', 'value' => number_format($belum_checkin), 'icon' => 'fa-clock', 'color' => 'amber', 'bg' => 'from-amber-50 to-yellow-50', 'onclick' => "document.getElementById('tab-belum')?.click()"],
                ['label' => 'Request Cancel', 'value' => number_format($request_total), 'icon' => 'fa-hourglass-half', 'color' => 'orange', 'bg' => 'from-orange-50 to-red-50', 'onclick' => "document.getElementById('tab-request')?.click()"],
                ['label' => 'Total Tiket Aktif', 'value' => number_format($total_tiket), 'icon' => 'fa-ticket-alt', 'color' => 'purple', 'bg' => 'from-purple-50 to-pink-50']
            ];
            foreach($stats as $s): ?>
            <div class="stat-card bg-gradient-to-br <?= $s['bg'] ?> rounded-xl p-4 shadow-sm border border-<?= $s['color'] ?>-100" <?= isset($s['onclick']) ? "onclick='{$s['onclick']}'" : '' ?>>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-<?= $s['color'] ?>-600 text-xs font-semibold uppercase tracking-wide"><?= $s['label'] ?></p>
                        <p class="text-2xl font-bold text-slate-800 mt-1"><?= $s['value'] ?></p>
                    </div>
                    <div class="w-9 h-9 bg-white/60 rounded-lg flex items-center justify-center shadow-sm">
                        <i class="fas <?= $s['icon'] ?> text-<?= $s['color'] ?>-500 text-base"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- QR Scanner Section -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-8 overflow-hidden transition-all duration-300 hover:shadow-xl">
            <div class="bg-gradient-to-r from-slate-800 to-blue-800 px-6 py-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-camera text-white text-lg"></i>
                    <h2 class="text-white font-semibold">Scan QR Code Tiket</h2>
                </div>
                <p class="text-blue-200 text-sm mt-0.5">Arahkan kamera ke QR Code tiket</p>
            </div>
            <div class="p-6 text-center">
                <div id="reader" class="mx-auto max-w-md"></div>
                <div id="qr-message" class="mt-3 text-sm font-medium"></div>
                <div class="flex gap-3 justify-center mt-5">
                    <button id="start-scan" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-2.5 rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-md btn-action flex items-center gap-2">
                        <i class="fas fa-play text-xs"></i> Mulai Scan
                    </button>
                    <button id="stop-scan" class="bg-gray-500 text-white px-6 py-2.5 rounded-xl hover:bg-gray-600 transition-all duration-200 shadow-md hidden btn-action flex items-center gap-2">
                        <i class="fas fa-stop text-xs"></i> Hentikan
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="border-b border-gray-100 px-3 pt-3">
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <?php 
                    $tabs = [
                        ['id' => 'belum', 'icon' => 'fa-clock', 'label' => 'Belum Check-in', 'badge' => number_format($belum_checkin), 'badgeColor' => 'yellow'],
                        ['id' => 'request', 'icon' => 'fa-hourglass-half', 'label' => 'Request Cancel', 'badge' => number_format($request_total), 'badgeColor' => 'orange'],
                        ['id' => 'sudah', 'icon' => 'fa-check-circle', 'label' => 'Sudah Check-in', 'badge' => number_format($sudah_checkin), 'badgeColor' => 'green'],
                        ['id' => 'cancel', 'icon' => 'fa-times-circle', 'label' => 'Riwayat Cancel', 'badge' => number_format($cancelData['total'] ?? 0), 'badgeColor' => 'orange']
                    ];
                    $activeTab = $_GET['tab'] ?? 'belum';
                    foreach($tabs as $tab): ?>
                    <button id="tab-<?= $tab['id'] ?>" class="tab-button px-5 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 flex items-center gap-2 <?= $activeTab == $tab['id'] ? 'tab-active' : 'tab-inactive' ?>" data-tab="<?= $tab['id'] ?>">
                        <i class="fas <?= $tab['icon'] ?> text-sm"></i>
                        <span class="hidden sm:inline"><?= $tab['label'] ?></span>
                        <span class="px-2 py-0.5 text-xs rounded-full bg-white/20 text-white font-semibold"><?= $tab['badge'] ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tab Content: Belum Check-in -->
             <!-- Tab Content: Belum Check-in - Bagian Tabel yang Diperbaiki -->
<div id="content-belum" class="tab-content p-5 <?= $activeTab == 'belum' ? 'block animate-fade-in' : 'hidden' ?>">
    <div class="flex justify-between items-center mb-4 gap-3 flex-wrap">
        <div class="text-sm text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full">
            <i class="fas fa-database mr-1 text-gray-400"></i> Total: <strong class="text-gray-700"><?= number_format($belum['total'] ?? 0) ?></strong> tiket
        </div>
        <form method="GET" class="relative">
            <input type="hidden" name="tab" value="belum">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="search_belum" value="<?= safe($search_belum) ?>" placeholder="Cari kode, event, pembeli..." class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl w-64 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all duration-200">
        </form>
    </div>
    <?php if(isset($belumData) && mysqli_num_rows($belumData) > 0): ?>
    <div class="overflow-x-auto rounded-xl">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gradient-to-r from-yellow-50 to-amber-50 border-b-2 border-yellow-100">
                    <?php foreach(['No', 'Kode Tiket', 'No. Pesanan', 'Event', 'Tiket', 'Pembeli', 'Aksi'] as $h): ?>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 first:rounded-l-xl last:rounded-r-xl"><?= $h ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php $no = $offset_belum + 1; $today_date = date('Y-m-d'); while($row = mysqli_fetch_assoc($belumData)): $is_expired = strtotime($row['event_tanggal']) < strtotime($today_date); ?>
                <tr class="table-row border-b border-gray-100 hover:bg-gradient-to-r hover:from-yellow-50/50 hover:to-transparent transition-all duration-200 <?= $is_expired ? 'bg-red-50/30' : '' ?>">
                    <td class="px-4 py-3 font-medium text-gray-500"><?= $no++ ?></td>
                    <td class="px-4 py-3 font-mono text-xs font-bold text-blue-600"><?= safe($row['kode_tiket']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= safe($row['no_order']) ?></td>
                    <td class="px-4 py-3 font-medium"><?= safe($row['nama_event']) ?><?= $is_expired ? '<span class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full"><i class="fas fa-exclamation-circle"></i> Lewat</span>' : '' ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= safe($row['nama_tiket']) ?></td>
                    <td class="px-4 py-3 text-gray-600"><?= safe($row['nama_pembeli']) ?></td>
                    <td class="px-4 py-3 text-center"><?= !$is_expired ? '<button onclick="quickCheckin(\''.safe($row['kode_tiket']).'\')" class="btn-action bg-gradient-to-r from-green-500 to-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs hover:shadow-md transition-all duration-200 inline-flex items-center gap-1"><i class="fas fa-check text-xs"></i> Check-in</button>' : '<span class="text-gray-400 text-xs"><i class="fas fa-calendar-times"></i> Event Lewat</span>' ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php if(isset($belum['pages']) && $belum['pages'] > 1): ?>
    <div class="flex justify-between items-center mt-5 pt-2 border-t border-gray-100">
        <span class="text-xs text-gray-500">Menampilkan <?= $offset_belum+1 ?>-<?= min($offset_belum+$limit, $belum['total']) ?> dari <?= number_format($belum['total']) ?> data</span>
        <div class="flex gap-1">
            <?php if($page_belum > 1): ?>
            <a href="?tab=belum&page_belum=<?= $page_belum-1 ?>&search_belum=<?= urlencode($search_belum) ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-all duration-200"><i class="fas fa-chevron-left text-xs"></i></a>
            <?php endif; ?>
            <span class="px-3 py-1.5 bg-gradient-to-r from-yellow-500 to-amber-500 text-white rounded-lg text-sm font-medium shadow-sm"><?= $page_belum ?></span>
            <?php if($page_belum < $belum['pages']): ?>
            <a href="?tab=belum&page_belum=<?= $page_belum+1 ?>&search_belum=<?= urlencode($search_belum) ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-all duration-200"><i class="fas fa-chevron-right text-xs"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="text-center py-16 text-gray-400">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-ticket-alt text-3xl text-gray-300"></i>
        </div>
        <p class="font-medium">Tidak ada tiket yang belum check-in</p>
        <p class="text-xs mt-1">Semua tiket sudah diproses atau belum ada pemesanan</p>
    </div>
    <?php endif; ?>
</div>


            <!-- Tab Content: Request Cancel -->
            <div id="content-request" class="tab-content p-5 <?= $activeTab == 'request' ? 'block animate-fade-in' : 'hidden' ?>">
                <div class="flex justify-between items-center mb-4 gap-3 flex-wrap">
                    <div class="text-sm text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full">
                        <i class="fas fa-hourglass-half mr-1 text-gray-400"></i> Total: <strong class="text-gray-700"><?= number_format($requests['total'] ?? 0) ?></strong> request
                    </div>
                    <form method="GET" class="relative">
                        <input type="hidden" name="tab" value="request">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" name="search_request" value="<?= safe($search_request) ?>" placeholder="Cari..." class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl w-64 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    </form>
                </div>
                <?php if(isset($requestData) && mysqli_num_rows($requestData) > 0): ?>
                <div class="overflow-x-auto rounded-xl">
                    <table class="w-full text-sm">
                        <thead><tr class="bg-gradient-to-r from-orange-50 to-red-50 border-b-2 border-orange-100"><?php foreach(['No', 'Kode Tiket', 'Pesanan', 'Event', 'Tiket', 'Pembeli', 'Alasan', 'Tgl Request', 'Aksi'] as $h): ?><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600"><?= $h ?></th><?php endforeach; ?> </thead>
                        <tbody><?php $no = $offset_request + 1; while($row = mysqli_fetch_assoc($requestData)): ?><tr class="table-row border-b border-gray-100 hover:bg-orange-50/50"><td class="px-4 py-3"><?= $no++ ?></td><td class="px-4 py-3 font-mono text-xs font-bold text-blue-600"><?= safe($row['kode_tiket']) ?></td><td class="px-4 py-3"><?= safe($row['no_order']) ?></td><td class="px-4 py-3 font-medium"><?= safe($row['nama_event']) ?></td><td class="px-4 py-3"><?= safe($row['nama_tiket']) ?></td><td class="px-4 py-3"><?= safe($row['nama_pembeli']) ?></td><td class="px-4 py-3 text-xs text-gray-500 max-w-xs"><?= safe(substr($row['cancel_reason'] ?? '-', 0, 50)) ?>...</td><td class="px-4 py-3 text-xs"><?= date('d/m/Y H:i', strtotime($row['cancel_request_date'])) ?></td><td class="px-4 py-3"><div class="flex gap-2"><button onclick="approveCancel('<?= safe($row['kode_tiket']) ?>')" class="btn-action bg-gradient-to-r from-green-500 to-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs hover:shadow-md"><i class="fas fa-check"></i> Setuju</button><button onclick="rejectCancel('<?= safe($row['kode_tiket']) ?>')" class="btn-action bg-gradient-to-r from-red-500 to-rose-600 text-white px-3 py-1.5 rounded-lg text-xs hover:shadow-md"><i class="fas fa-times"></i> Tolak</button></div></td></table><?php endwhile; ?></tbody>
                    </table>
                </div>
                <?php if(isset($requests['pages']) && $requests['pages'] > 1): ?><div class="flex justify-between items-center mt-5 pt-2 border-t"><span class="text-xs text-gray-500">Menampilkan <?= $offset_request+1 ?>-<?= min($offset_request+$limit, $requests['total']) ?> dari <?= number_format($requests['total']) ?> data</span><div class="flex gap-1"><?php if($page_request > 1): ?><a href="?tab=request&page_request=<?= $page_request-1 ?>&search_request=<?= urlencode($search_request) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-left text-xs"></i></a><?php endif; ?><span class="px-3 py-1.5 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg text-sm"><?= $page_request ?></span><?php if($page_request < $requests['pages']): ?><a href="?tab=request&page_request=<?= $page_request+1 ?>&search_request=<?= urlencode($search_request) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-right text-xs"></i></a><?php endif; ?></div></div><?php endif; ?>
                <?php else: ?><div class="text-center py-16 text-gray-400"><div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-inbox text-3xl text-gray-300"></i></div><p class="font-medium">Tidak ada request pembatalan</p></div><?php endif; ?>
            </div>

            <!-- Tab Content: Sudah Check-in -->
            <div id="content-sudah" class="tab-content p-5 <?= $activeTab == 'sudah' ? 'block animate-fade-in' : 'hidden' ?>">
                <div class="flex justify-between items-center mb-4 gap-3 flex-wrap"><div class="text-sm text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full"><i class="fas fa-check-circle mr-1 text-green-500"></i> Total: <strong><?= number_format($sudah['total'] ?? 0) ?></strong> tiket</div><form method="GET" class="relative"><input type="hidden" name="tab" value="sudah"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><input type="text" name="search_sudah" value="<?= safe($search_sudah) ?>" placeholder="Cari..." class="pl-9 pr-4 py-2 text-sm border rounded-xl w-64 focus:ring-2 focus:ring-blue-500/50"></form></div>
                <?php if(isset($sudahData) && mysqli_num_rows($sudahData) > 0): ?>
                <div class="overflow-x-auto rounded-xl"><table class="w-full text-sm"><thead><tr class="bg-gradient-to-r from-green-50 to-emerald-50 border-b-2 border-green-100"><?php foreach(['No', 'Waktu', 'Kode Tiket', 'No. Pesanan', 'Event', 'Pembeli'] as $h): ?><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600"><?= $h ?></th><?php endforeach; ?> </thead><tbody><?php $no = $offset_sudah + 1; while($row = mysqli_fetch_assoc($sudahData)): ?><tr class="table-row border-b border-gray-100 hover:bg-green-50/50"><td class="px-4 py-3"><?= $no++ ?></td><td class="px-4 py-3 text-xs font-medium"><?= date('d/m/Y H:i:s', strtotime($row['waktu_checkin'])) ?></td><td class="px-4 py-3 font-mono text-xs font-bold text-blue-600"><?= safe($row['kode_tiket']) ?></td><td class="px-4 py-3"><?= safe($row['no_order']) ?></td><td class="px-4 py-3 font-medium"><?= safe($row['nama_event']) ?></td><td class="px-4 py-3"><?= safe($row['nama_pembeli']) ?></td></tr><?php endwhile; ?></tbody></table></div>
                <?php if(isset($sudah['pages']) && $sudah['pages'] > 1): ?><div class="flex justify-between items-center mt-5 pt-2 border-t"><span class="text-xs text-gray-500">Menampilkan <?= $offset_sudah+1 ?>-<?= min($offset_sudah+$limit, $sudah['total']) ?> dari <?= number_format($sudah['total']) ?> data</span><div class="flex gap-1"><?php if($page_sudah > 1): ?><a href="?tab=sudah&page_sudah=<?= $page_sudah-1 ?>&search_sudah=<?= urlencode($search_sudah) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-left text-xs"></i></a><?php endif; ?><span class="px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg text-sm"><?= $page_sudah ?></span><?php if($page_sudah < $sudah['pages']): ?><a href="?tab=sudah&page_sudah=<?= $page_sudah+1 ?>&search_sudah=<?= urlencode($search_sudah) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-right text-xs"></i></a><?php endif; ?></div></div><?php endif; ?>
                <?php else: ?><div class="text-center py-16 text-gray-400"><div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-check-circle text-3xl text-gray-300"></i></div><p class="font-medium">Belum ada data check-in</p></div><?php endif; ?>
            </div>

            <!-- Tab Content: Riwayat Cancel -->
            <div id="content-cancel" class="tab-content p-5 <?= $activeTab == 'cancel' ? 'block animate-fade-in' : 'hidden' ?>">
                <div class="flex justify-between items-center mb-4 gap-3 flex-wrap"><div class="text-sm text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full"><i class="fas fa-trash-alt mr-1 text-orange-500"></i> Total: <strong><?= number_format($cancelData['total'] ?? 0) ?></strong> pesanan</div><form method="GET" class="relative"><input type="hidden" name="tab" value="cancel"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><input type="text" name="search_cancel" value="<?= safe($search_cancel) ?>" placeholder="Cari..." class="pl-9 pr-4 py-2 text-sm border rounded-xl w-64 focus:ring-2 focus:ring-blue-500/50"></form></div>
                <?php if(isset($cancelDataDetails) && mysqli_num_rows($cancelDataDetails) > 0): ?>
                <div class="overflow-x-auto rounded-xl"><table class="w-full text-sm"><thead><tr class="bg-gradient-to-r from-orange-50 to-red-50 border-b-2 border-orange-100"><?php foreach(['No', 'No. Pesanan', 'Tgl Pesan', 'Event', 'Venue', 'Pembeli', 'Jml', 'Total', 'Tgl Event'] as $h): ?><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600"><?= $h ?></th><?php endforeach; ?> </thead><tbody><?php $no = $offset_cancel + 1; while($row = mysqli_fetch_assoc($cancelDataDetails)): ?><tr class="table-row border-b border-gray-100 hover:bg-orange-50/50"><td class="px-4 py-3"><?= $no++ ?></td><td class="px-4 py-3 font-mono text-xs font-bold"><?= safe($row['no_order']) ?></td><td class="px-4 py-3 text-xs"><?= date('d/m/Y H:i', strtotime($row['tanggal_order'])) ?></td><td class="px-4 py-3 font-medium"><?= safe($row['nama_event']) ?></td><td class="px-4 py-3 text-xs"><?= safe($row['nama_venue']) ?></td><td class="px-4 py-3"><?= safe($row['nama_pembeli']) ?></td><td class="px-4 py-3 text-center font-medium"><?= $row['total_tiket'] ?> tiket</td><td class="px-4 py-3 font-bold text-red-600">Rp <?= number_format($row['total'], 0, ',', '.') ?></td><td class="px-4 py-3 text-xs"><?= date('d/m/Y', strtotime($row['event_tanggal'])) ?></td></tr><?php endwhile; ?></tbody></table></div>
                <?php if(isset($cancelData['pages']) && $cancelData['pages'] > 1): ?><div class="flex justify-between items-center mt-5 pt-2 border-t"><span class="text-xs text-gray-500">Menampilkan <?= $offset_cancel+1 ?>-<?= min($offset_cancel+$limit, $cancelData['total']) ?> dari <?= number_format($cancelData['total']) ?> data</span><div class="flex gap-1"><?php if($page_cancel > 1): ?><a href="?tab=cancel&page_cancel=<?= $page_cancel-1 ?>&search_cancel=<?= urlencode($search_cancel) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-left text-xs"></i></a><?php endif; ?><span class="px-3 py-1.5 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg text-sm"><?= $page_cancel ?></span><?php if($page_cancel < $cancelData['pages']): ?><a href="?tab=cancel&page_cancel=<?= $page_cancel+1 ?>&search_cancel=<?= urlencode($search_cancel) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50"><i class="fas fa-chevron-right text-xs"></i></a><?php endif; ?></div></div><?php endif; ?>
                <?php else: ?><div class="text-center py-16 text-gray-400"><div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-trash-alt text-3xl text-gray-300"></i></div><p class="font-medium">Belum ada riwayat pembatalan</p></div><?php endif; ?>
            </div>
        </div>

        <!-- Forms -->
        <form id="approveForm" method="POST" class="hidden"><input type="hidden" name="approve_cancel" value="1"><input type="hidden" name="kode_tiket" id="approve_kode"></form>
        <form id="rejectForm" method="POST" class="hidden"><input type="hidden" name="reject_cancel" value="1"><input type="hidden" name="kode_tiket" id="reject_kode"></form>
    </div>

    <div id="notificationModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 backdrop-blur-sm"><div class="bg-white rounded-2xl max-w-md w-full mx-4 transform transition-all duration-300" id="modalContent"></div></div>

    <script>
        let html5QrCode = null, isScanning = false, lastScannedCode = null, scanTimeout = null;
        
        // Tab switching
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', function() {
                let tabId = this.dataset.tab;
                document.querySelectorAll('.tab-button').forEach(b => { b.classList.remove('tab-active'); b.classList.add('tab-inactive'); });
                this.classList.remove('tab-inactive'); this.classList.add('tab-active');
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                let newContent = document.getElementById(`content-${tabId}`);
                if(newContent) {
                    newContent.classList.remove('hidden');
                    newContent.classList.add('animate-fade-in');
                }
                let url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            });
        });
        
        function showModal(type, title, msg, data = null) {
            let modal = document.getElementById('notificationModal'), content = document.getElementById('modalContent');
            let icon = type === 'success' ? '<div class="w-16 h-16 mx-auto mb-3 bg-green-100 rounded-full flex items-center justify-center"><i class="fas fa-check-circle text-3xl text-green-500"></i></div>' : '<div class="w-16 h-16 mx-auto mb-3 bg-red-100 rounded-full flex items-center justify-center"><i class="fas fa-times-circle text-3xl text-red-500"></i></div>';
            let detail = data ? `<div class="mt-4 p-3 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl text-left text-sm"><p><strong class="text-blue-600"><i class="fas fa-ticket-alt mr-1"></i> Kode:</strong> <span class="font-mono font-bold">${data.kode_tiket}</span></p><p><strong class="text-blue-600"><i class="fas fa-calendar-alt mr-1"></i> Event:</strong> ${data.nama_event}</p><p><strong class="text-blue-600"><i class="fas fa-user mr-1"></i> Pembeli:</strong> ${data.nama_pembeli}</p></div>` : '';
            content.innerHTML = `<div class="rounded-2xl overflow-hidden shadow-2xl"><div class="bg-gradient-to-r ${type === 'success' ? 'from-green-500 to-emerald-600' : 'from-red-500 to-red-600'} px-5 py-3"><h3 class="text-white font-bold text-center text-lg">${title}</h3></div><div class="p-5 text-center">${icon}<p class="text-gray-700 mb-3">${msg}</p>${detail}<button onclick="closeModal()" class="${type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-red-600'} text-white px-6 py-2 rounded-lg font-semibold mt-3 transition-all duration-200 hover:shadow-md">OK</button></div></div>`;
            modal.classList.remove('hidden'); modal.classList.add('flex');
        }
        
        function closeModal() { document.getElementById('notificationModal').classList.add('hidden'); }
        
        function quickCheckin(kode) {
            Swal.fire({ 
                title: 'Konfirmasi Check-in', 
                html: `<div class="text-left"><p class="mb-2">Yakin ingin check-in tiket ini?</p><div class="bg-gray-50 p-3 rounded-lg"><code class="text-blue-600 font-bold">${kode}</code></div></div>`, 
                icon: 'question', 
                showCancelButton: true, 
                confirmButtonColor: '#10b981', 
                cancelButtonColor: '#ef4444', 
                confirmButtonText: '<i class="fas fa-check mr-1"></i> Ya, Check-in!',
                cancelButtonText: '<i class="fas fa-times mr-1"></i> Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses...', text: 'Sedang melakukan check-in', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'checkin_qr=1&kode_qr=' + encodeURIComponent(kode) })
                    .then(res => res.json()).then(data => { Swal.close(); showModal(data.success ? 'success' : 'error', data.success ? 'Berhasil!' : 'Gagal', data.message, data.data); if(data.success) setTimeout(() => location.reload(), 1500); })
                    .catch(err => { Swal.close(); showModal('error', 'Error', 'Terjadi kesalahan jaringan'); });
                }
            });
        }
        
        function approveCancel(kode) { Swal.fire({ title: 'Setujui Pembatalan?', html: `Yakin setujui pembatalan tiket <br><strong class="text-blue-600">${kode}</strong>?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#10b981', cancelButtonColor: '#ef4444', confirmButtonText: '<i class="fas fa-check mr-1"></i> Ya, Setujui!' }).then(r => { if(r.isConfirmed) { document.getElementById('approve_kode').value = kode; document.getElementById('approveForm').submit(); } }); }
        function rejectCancel(kode) { Swal.fire({ title: 'Tolak Pembatalan?', html: `Yakin tolak pembatalan tiket <br><strong class="text-blue-600">${kode}</strong>?`, icon: 'question', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#3085d6', confirmButtonText: '<i class="fas fa-times mr-1"></i> Ya, Tolak!' }).then(r => { if(r.isConfirmed) { document.getElementById('reject_kode').value = kode; document.getElementById('rejectForm').submit(); } }); }
        
        async function startScanner() {
            if(isScanning) return;
            lastScannedCode = null;
            const msg = document.getElementById('qr-message');
            msg.innerHTML = '<span class="text-blue-500"><i class="fas fa-spinner fa-spin mr-1"></i> Menginisialisasi kamera...</span>';
            try {
                if(!html5QrCode) html5QrCode = new Html5Qrcode("reader");
                await html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: { width: 250, height: 250 } }, onScanSuccess, onScanFailure);
                isScanning = true;
                document.getElementById('start-scan').classList.add('hidden');
                document.getElementById('stop-scan').classList.remove('hidden');
                msg.innerHTML = '<span class="text-green-600"><i class="fas fa-camera mr-1"></i> Kamera aktif, arahkan ke QR Code</span>';
            } catch(err) { msg.innerHTML = '<span class="text-red-500"><i class="fas fa-exclamation-triangle mr-1"></i> Gagal akses kamera, izinkan akses kamera</span>'; }
        }
        
        async function stopScanner() { if(html5QrCode && isScanning) { await html5QrCode.stop(); isScanning = false; document.getElementById('start-scan').classList.remove('hidden'); document.getElementById('stop-scan').classList.add('hidden'); document.getElementById('qr-message').innerHTML = '<span class="text-gray-500"><i class="fas fa-stop-circle mr-1"></i> Scanner dihentikan</span>'; } }
        
        function onScanSuccess(text) {
            if(lastScannedCode === text) return;
            lastScannedCode = text;
            if(scanTimeout) clearTimeout(scanTimeout);
            scanTimeout = setTimeout(() => { lastScannedCode = null; }, 3000);
            document.getElementById('qr-message').innerHTML = '<span class="text-yellow-600"><i class="fas fa-spinner fa-spin mr-1"></i> Memproses kode...</span>';
            stopScanner().then(() => {
                fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'checkin_qr=1&kode_qr=' + encodeURIComponent(text) })
                .then(res => res.json()).then(data => { showModal(data.success ? 'success' : 'error', data.success ? 'Berhasil!' : 'Gagal', data.message, data.data); if(data.success) { setTimeout(() => location.reload(), 1500); } else { document.getElementById('qr-message').innerHTML = '<span class="text-gray-500"><i class="fas fa-camera mr-1"></i> Klik "Mulai Scan" untuk scan ulang</span>'; } });
            });
        }
        
        function onScanFailure(error) {}
        
        function showTodayCheckin() { 
            let checkinCount = <?= $checkin_hari_ini ?>;
            Swal.fire({ title: 'Check-in Hari Ini', html: `<div class="text-center"><div class="text-5xl font-bold text-green-600 mb-2">${checkinCount}</div><p class="text-gray-600">pengunjung</p><div class="mt-3 text-sm text-gray-400"><i class="fas fa-calendar-alt mr-1"></i> <?= date('d F Y') ?></div></div>`, icon: 'success', confirmButtonColor: '#0066cc', confirmButtonText: '<i class="fas fa-check mr-1"></i> OK' }); 
        }
        
        document.getElementById('start-scan')?.addEventListener('click', startScanner);
        document.getElementById('stop-scan')?.addEventListener('click', stopScanner);
        document.getElementById('notificationModal')?.addEventListener('click', e => { if(e.target === e.currentTarget) closeModal(); });
        
        // Auto close modal on ESC
        document.addEventListener('keydown', function(e) { if(e.key === 'Escape') closeModal(); });
        
        <?php if($message && $messageType): ?>showModal('<?= $messageType ?>', '<?= $messageType == "success" ? "Berhasil!" : "Gagal" ?>', '<?= addslashes($message) ?>', <?= $lastScan ? json_encode($lastScan) : 'null' ?>);<?php endif; ?>
    </script>
</body>
</html>