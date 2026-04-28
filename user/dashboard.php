<?php
session_start();
include '../config/koneksi.php';

// Set default session jika belum ada atau tidak lengkap
if(!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'User';
}

if(!isset($_SESSION['nama'])) {
    $_SESSION['nama'] = 'Pengguna';
}

if(!isset($_SESSION['id_user'])) {
    $_SESSION['id_user'] = 1;
}

$id_user = $_SESSION['id_user'];

// =======================
// AMBIL DATA USER UNTUK PROFIL
// =======================
$query_user = mysqli_query($conn, "SELECT * FROM users WHERE id_user = $id_user");
$user_data = mysqli_fetch_assoc($query_user);

// =======================
// AMBIL DATA TIKET YANG SUDAH DIBELI USER
// =======================
$query_my_tickets = mysqli_query($conn, "
    SELECT COUNT(DISTINCT o.id_order) as total_order,
           COUNT(a.id_attendee) as total_ticket,
           SUM(od.subtotal) as total_spent
    FROM orders o
    JOIN order_detail od ON o.id_order = od.id_order
    JOIN attendee a ON od.id_detail = a.id_detail
    WHERE o.id_user = $id_user
");

$my_tickets_data = mysqli_fetch_assoc($query_my_tickets);
$total_order = $my_tickets_data['total_order'] ?? 0;
$total_ticket_bought = $my_tickets_data['total_ticket'] ?? 0;
$total_spent = $my_tickets_data['total_spent'] ?? 0;

// Ambil 3 tiket terbaru untuk ditampilkan di widget
$query_recent_tickets = mysqli_query($conn, "
    SELECT 
        a.kode_tiket,
        a.status_checkin,
        e.nama_event,
        e.tanggal as event_tanggal,
        e.foto as event_foto,
        o.tanggal_order
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    WHERE o.id_user = $id_user
    ORDER BY o.tanggal_order DESC, a.id_attendee DESC
    LIMIT 3
");

// =======================
// PAGINATION & SEARCH UNTUK EVENT
// =======================
$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$search_condition = "";
if (!empty($search)) {
    $search_condition = "AND (event.nama_event LIKE '%$search%' 
                           OR venue.nama_venue LIKE '%$search%'
                           OR event.deskripsi LIKE '%$search%')";
}

// Total data event
$total_query = mysqli_query($conn, "
    SELECT COUNT(*) as total
    FROM event 
    JOIN venue ON event.id_venue = venue.id_venue 
    WHERE event.tanggal >= CURDATE() $search_condition
");
$total_data = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_data / $limit);

// Ambil data event
$query_event = mysqli_query($conn, "
    SELECT event.*, venue.nama_venue, venue.alamat, venue.kapasitas,
           (SELECT COUNT(*) FROM tiket WHERE tiket.id_event = event.id_event) as total_tiket
    FROM event 
    JOIN venue ON event.id_venue = venue.id_venue 
    WHERE event.tanggal >= CURDATE() $search_condition
    ORDER BY event.tanggal ASC 
    LIMIT $offset, $limit
");

// Ambil semua event untuk statistik
$query_all_event = mysqli_query($conn, "SELECT COUNT(*) as total FROM event WHERE tanggal >= CURDATE()");
$total_event_available = mysqli_fetch_assoc($query_all_event)['total'] ?? 0;

// =======================
// AMBIL DATA VENUE
// =======================
$query_venue = mysqli_query($conn, "SELECT COUNT(*) as total FROM venue");
$total_venue = mysqli_fetch_assoc($query_venue)['total'] ?? 0;

// =======================
// AMBIL DATA TIKET
$query_total_tiket = mysqli_query($conn, "SELECT SUM(kuota) as total FROM tiket");
$total_tiket = mysqli_fetch_assoc($query_total_tiket)['total'] ?? 0;

// =======================
// FUNGSI UNTUK AMAN
function safe($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | TiketMoo</title>
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
                        'primary': '#0066cc',
                        'secondary': '#0a2540',
                    },
                    animation: {
                        'slide-in': 'slideIn 0.5s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 3s ease-in-out infinite',
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
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .event-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 35px -12px rgba(0,102,204,0.25);
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px -10px rgba(0,102,204,0.15);
        }
        .ticket-item {
            transition: all 0.3s ease;
        }
        .ticket-item:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #e6f0fa 100%);
            transform: translateX(8px);
        }
        .gradient-text {
            background: linear-gradient(135deg, #0a2540 0%, #0066cc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        body {
            font-family: 'Inter', sans-serif;
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(0,102,204,0.1);
            border-color: #0066cc;
        }
        .pagination-active {
            background: linear-gradient(135deg, #0066cc 0%, #0a2540 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(0,102,204,0.3);
        }
        .badge-upcoming {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .badge-today {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .profile-dropdown {
            transition: all 0.3s ease;
        }
        .profile-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .dropdown-menu {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-soft-blue via-white to-soft-blue min-h-screen">
    
    <!-- Navbar dengan efek glassmorphism -->
    <nav class="bg-white/90 backdrop-blur-md shadow-lg sticky top-0 z-50 border-b border-gray-100">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-gradient-to-r from-accent-blue to-navy rounded-xl flex items-center justify-center shadow-lg animate-pulse-slow">
                    <i class="fas fa-ticket-alt text-white text-xl"></i>
                </div>
                <span class="font-extrabold text-2xl gradient-text">TiketMoo</span>
            </div>
            <div class="flex items-center space-x-5">
                <!-- Tiket Saya -->
                <a href="my_tickets.php" class="relative group">
                    <div class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-soft-blue transition-all duration-300">
                        <i class="fas fa-ticket-alt text-accent-blue text-lg group-hover:scale-110 transition"></i>
                        <span class="font-medium text-gray-700 hidden md:inline">Tiket Saya</span>
                        <?php if($total_ticket_bought > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 min-w-[18px] text-center shadow-md animate-pulse">
                            <?= $total_ticket_bought ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
                
                <!-- Profile Dropdown -->
                <div class="relative profile-dropdown">
                   <button class="flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-soft-blue transition-all duration-300 group">
    <div class="w-6 h-6 bg-gradient-to-r from-accent-blue to-navy rounded-full flex items-center justify-center shadow-sm">
        <i class="fas fa-user text-white text-xs"></i>
    </div>
    <span class="font-medium text-gray-700 text-sm hidden md:inline">
        <?php echo safe($_SESSION['nama']); ?>
    </span>
    <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-300 group-hover:rotate-180"></i>
</button>
                    
                    <!-- Dropdown Menu -->
                    <div class="dropdown-menu absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50">
                        <div class="bg-gradient-to-r from-navy to-accent-blue px-4 py-3">
                            <p class="text-white font-semibold text-sm"><?php echo safe($_SESSION['nama']); ?></p>
                            <p class="text-blue-200 text-xs"><?php echo safe($user_data['email'] ?? 'user@example.com'); ?></p>
                        </div>
                        <div class="py-2">
                            <a href="profile.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-soft-blue transition group">
                                <i class="fas fa-user-circle text-accent-blue w-5 group-hover:scale-110 transition"></i>
                                <span class="text-sm">Profil Saya</span>
                            </a>
                            <a href="my_tickets.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-soft-blue transition group">
                                <i class="fas fa-ticket-alt text-accent-blue w-5 group-hover:scale-110 transition"></i>
                                <span class="text-sm">Tiket Saya</span>
                                <?php if($total_ticket_bought > 0): ?>
                                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-0.5"><?= $total_ticket_bought ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="invoice.php" class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-soft-blue transition group">
                                <i class="fas fa-receipt text-accent-blue w-5 group-hover:scale-110 transition"></i>
                                <span class="text-sm">Riwayat Pesanan</span>
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-2.5 text-red-600 hover:bg-red-50 transition group">
                                <i class="fas fa-sign-out-alt w-5 group-hover:scale-110 transition"></i>
                                <span class="text-sm">Keluar</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Welcome Banner dengan efek modern -->
        <div class="relative overflow-hidden bg-gradient-to-r from-navy via-accent-blue to-navy rounded-3xl p-8 text-white mb-10 animate-[slideIn_0.5s_ease-out] shadow-2xl">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-center">
                <div>
                    <h1 class="text-3xl md:text-4xl font-extrabold mb-2 flex items-center gap-2">
                        Selamat datang, <?php echo safe($_SESSION['nama']); ?>! 
                        <span class="text-3xl animate-wave">👋</span>
                    </h1>
                    <p class="text-blue-100 text-lg">Temukan dan pesan tiket event favorit Anda dengan mudah</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <div class="bg-white/20 backdrop-blur-sm rounded-2xl px-5 py-3 flex items-center gap-3 border border-white/30">
                        <i class="fas fa-calendar-alt text-xl animate-pulse"></i>
                        <span class="font-medium"><?php echo date('l, d F Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistik Pribadi User dengan desain modern -->
        <?php if($total_ticket_bought > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-10">
            <div class="group relative overflow-hidden bg-gradient-to-br from-green-50 via-emerald-50 to-green-50 rounded-2xl p-5 stat-card cursor-pointer shadow-md" onclick="window.location.href='my_tickets.php'">
                <div class="absolute top-0 right-0 w-20 h-20 bg-green-500/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-green-700 text-sm font-semibold uppercase tracking-wide">Total Tiket</p>
                        <p class="font-bold text-3xl text-green-800 mt-1"><?= number_format($total_ticket_bought, 0, ',', '.') ?></p>
                        <p class="text-green-600 text-xs mt-2 flex items-center gap-1">
                            <i class="fas fa-arrow-right"></i> Klik untuk lihat detail
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-green-500/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition">
                        <i class="fas fa-ticket-alt text-3xl text-green-600"></i>
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden bg-gradient-to-br from-blue-50 via-indigo-50 to-blue-50 rounded-2xl p-5 stat-card shadow-md">
                <div class="absolute top-0 right-0 w-20 h-20 bg-blue-500/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-blue-700 text-sm font-semibold uppercase tracking-wide">Total Pesanan</p>
                        <p class="font-bold text-3xl text-blue-800 mt-1"><?= number_format($total_order, 0, ',', '.') ?></p>
                        <p class="text-blue-600 text-xs mt-2">Pesanan selesai</p>
                    </div>
                    <div class="w-14 h-14 bg-blue-500/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition">
                        <i class="fas fa-shopping-bag text-3xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            <div class="group relative overflow-hidden bg-gradient-to-br from-purple-50 via-pink-50 to-purple-50 rounded-2xl p-5 stat-card shadow-md">
                <div class="absolute top-0 right-0 w-20 h-20 bg-purple-500/10 rounded-full -mr-10 -mt-10 group-hover:scale-150 transition"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-purple-700 text-sm font-semibold uppercase tracking-wide">Total Belanja</p>
                        <p class="font-bold text-2xl text-purple-800 mt-1">Rp <?= number_format($total_spent, 0, ',', '.') ?></p>
                        <p class="text-purple-600 text-xs mt-2">Total pengeluaran</p>
                    </div>
                    <div class="w-14 h-14 bg-purple-500/20 rounded-2xl flex items-center justify-center group-hover:scale-110 transition">
                        <i class="fas fa-money-bill-wave text-3xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        

        
        <!-- Grid Event dengan Search dan Pagination -->
        <div class="mb-10">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-10 bg-gradient-to-b from-accent-blue to-navy rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-fire-flame text-orange-500 mr-2"></i>Event Terdekat
                    </h2>
                </div>
                
                <!-- Form Search -->
                <form method="GET" action="" class="flex gap-2">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input 
                            type="text" 
                            name="search" 
                            id="searchInput"
                            class="search-input pl-10 pr-4 py-2.5 w-64 md:w-80 border border-gray-300 rounded-xl focus:outline-none focus:border-accent-blue transition-all"
                            placeholder="Cari event, venue, atau deskripsi..."
                            value="<?= htmlspecialchars($search) ?>"
                        >
                    </div>
                    <button type="submit" class="bg-accent-blue text-white px-5 py-2.5 rounded-xl hover:bg-blue-700 transition-all duration-300 flex items-center gap-2">
                        <i class="fas fa-search"></i>
                        <span class="hidden sm:inline">Cari</span>
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="dashboard.php" class="bg-gray-500 text-white px-5 py-2.5 rounded-xl hover:bg-gray-600 transition-all duration-300 flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        <span class="hidden sm:inline">Reset</span>
                    </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Hasil Pencarian -->
            <?php if (!empty($search)): ?>
            <div class="mb-4 p-3 bg-blue-50 rounded-xl flex items-center gap-2">
                <i class="fas fa-info-circle text-accent-blue"></i>
                <p class="text-sm text-gray-600">Menampilkan hasil pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong> - Ditemukan <?= $total_data ?> event</p>
            </div>
            <?php endif; ?>
    
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-7">
                <?php 
                if($query_event && mysqli_num_rows($query_event) > 0) {
                    while($event = mysqli_fetch_assoc($query_event)) {
                        $query_harga_termurah = mysqli_query($conn, "
                            SELECT MIN(harga) as harga_termurah FROM tiket WHERE id_event = " . intval($event['id_event'])
                        );
                        $harga_data = mysqli_fetch_assoc($query_harga_termurah);
                        $harga_termurah = $harga_data['harga_termurah'] ?? 0;
                        
                        $gradients = [
                            'from-blue-500 to-blue-700',
                            'from-green-500 to-teal-600',
                            'from-purple-500 to-pink-600',
                            'from-orange-500 to-red-600',
                            'from-indigo-500 to-purple-700',
                            'from-teal-500 to-cyan-600'
                        ];
                        $gradient_index = ($event['id_event'] ?? 1) % count($gradients);
                        $gradient = $gradients[$gradient_index];
                        
                        $harga_text = ($harga_termurah > 0) ? 'Rp ' . number_format($harga_termurah, 0, ',', '.') : 'Gratis';
                        $foto_event = (!empty($event['foto'])) ? "../uploads/event/" . $event['foto'] : "https://placehold.co/400x250?text=" . urlencode($event['nama_event'] ?? 'Event');
                        $deskripsi = $event['deskripsi'] ?? '';
                        $deskripsi_short = strlen($deskripsi) > 100 ? substr($deskripsi, 0, 100) . '...' : $deskripsi;
                        if(empty($deskripsi_short)) {
                            $deskripsi_short = 'Event menarik untuk Anda!';
                        }
                        
                        $today = date('Y-m-d');
                        $event_date = $event['tanggal'];
                        if($event_date == $today) {
                            $badge_status = '<span class="absolute top-3 left-3 z-20 badge-today text-white text-xs px-2 py-1 rounded-full shadow-md"><i class="fas fa-calendar-day mr-1"></i>Hari Ini</span>';
                        } else {
                            $badge_status = '<span class="absolute top-3 left-3 z-20 badge-upcoming text-white text-xs px-2 py-1 rounded-full shadow-md"><i class="fas fa-calendar-alt mr-1"></i>Akan Datang</span>';
                        }
                ?>
                <div class="group relative bg-white rounded-2xl overflow-hidden shadow-lg event-card">
                    <div class="relative h-48 overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent z-10"></div>
                        <img src="<?= $foto_event ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700" 
                             alt="<?php echo safe($event['nama_event'] ?? 'Event'); ?>"
                             onerror="this.src='https://placehold.co/400x250?text=' + encodeURIComponent('<?php echo safe($event['nama_event'] ?? 'Event'); ?>')">
                        <?= $badge_status ?>
                        <div class="absolute top-3 right-3 z-20 bg-white/95 backdrop-blur-sm rounded-full px-3 py-1 text-xs font-bold text-accent-blue shadow-lg">
                            <i class="fas fa-tag mr-1"></i> <?= $harga_text ?>
                        </div>
                        <div class="absolute bottom-3 left-3 z-20 bg-black/60 backdrop-blur-sm rounded-full px-2.5 py-1 text-xs text-white">
                            <i class="fas fa-ticket-alt mr-1"></i> <?= $event['total_tiket'] ?? 0 ?> tiket
                        </div>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-xl text-gray-800 mb-2 line-clamp-1"><?php echo safe($event['nama_event'] ?? 'Event'); ?></h3>
                        <div class="flex items-center gap-3 text-gray-500 text-sm mb-3">
                            <div class="flex items-center gap-1">
                                <i class="fas fa-calendar text-accent-blue text-xs"></i>
                                <span><?= date('d M Y', strtotime($event['tanggal'] ?? 'now')) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <i class="fas fa-map-marker-alt text-accent-blue text-xs"></i>
                                <span class="truncate"><?php echo safe($event['nama_venue'] ?? 'Venue'); ?></span>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm leading-relaxed line-clamp-2 mb-4">
                            <?php echo safe($deskripsi_short); ?>
                        </p>
                        <a href="detail.php?id=<?= $event['id_event'] ?>" 
                           class="group relative w-full bg-gradient-to-r from-accent-blue to-navy text-white py-2.5 rounded-xl font-semibold hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 overflow-hidden">
                            <span class="relative z-10">Pesan Tiket</span>
                            <i class="fas fa-ticket-alt relative z-10 group-hover:translate-x-1 transition"></i>
                            <div class="absolute inset-0 bg-gradient-to-r from-navy to-accent-blue opacity-0 group-hover:opacity-100 transition duration-300"></div>
                        </a>
                    </div>
                </div>
                <?php 
                    }
                } else { 
                ?>
                <div class="col-span-3 text-center py-16 bg-white rounded-2xl shadow-md">
                    <i class="fas fa-calendar-times text-7xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">Tidak ada event yang ditemukan.</p>
                    <p class="text-gray-400 text-sm mt-2"><?= !empty($search) ? 'Coba dengan kata kunci lain' : 'Silakan cek kembali nanti untuk event terbaru' ?></p>
                    <?php if (!empty($search)): ?>
                    <a href="dashboard.php" class="inline-block mt-4 text-accent-blue hover:underline">Lihat semua event</a>
                    <?php endif; ?>
                </div>
                <?php } ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-8 pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-500">
                    Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_data) ?> dari <?= number_format($total_data, 0, ',', '.') ?> event
                </div>
                <div class="flex gap-2 flex-wrap justify-center">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-all duration-300 flex items-center gap-1">
                        <i class="fas fa-chevron-left text-sm"></i>
                        <span>Sebelumnya</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" 
                       class="w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300 <?= $i == $page ? 'pagination-active shadow-md' : 'border border-gray-300 hover:bg-gray-50' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-xl hover:bg-gray-50 transition-all duration-300 flex items-center gap-1">
                        <span>Selanjutnya</span>
                        <i class="fas fa-chevron-right text-sm"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistik Platform -->
        <div class="bg-white rounded-2xl shadow-md p-6 hover:shadow-xl transition-all duration-300 mt-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-line text-accent-blue"></i>
                Statistik Platform
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-3 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 cursor-pointer hover:scale-105 transition" onclick="window.location.href='events.php'">
                    <i class="fas fa-calendar-check text-2xl text-accent-blue mb-2"></i>
                    <p class="text-xs text-gray-500">Event Tersedia</p>
                    <p class="font-bold text-xl text-gray-800"><?= number_format($total_event_available, 0, ',', '.') ?></p>
                </div>
                <div class="text-center p-3 rounded-xl bg-gradient-to-br from-green-50 to-green-100 cursor-pointer hover:scale-105 transition" onclick="window.location.href='venues.php'">
                    <i class="fas fa-map-marker-alt text-2xl text-accent-blue mb-2"></i>
                    <p class="text-xs text-gray-500">Total Venue</p>
                    <p class="font-bold text-xl text-gray-800"><?= number_format($total_venue, 0, ',', '.') ?></p>
                </div>
                <div class="text-center p-3 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100">
                    <i class="fas fa-ticket-alt text-2xl text-accent-blue mb-2"></i>
                    <p class="text-xs text-gray-500">Total Tiket</p>
                    <p class="font-bold text-xl text-gray-800"><?= number_format($total_tiket, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>
        
        <!-- Footer dengan desain elegan -->
        <footer class="mt-12 pt-8 border-t border-gray-200">
            <div class="text-center">
                <div class="flex justify-center items-center gap-2 mb-3">
                    <div class="w-8 h-8 bg-gradient-to-r from-accent-blue to-navy rounded-lg flex items-center justify-center">
                        <i class="fas fa-ticket-alt text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-gray-700">TiketMoo</span>
                </div>
                <p class="text-gray-400 text-sm">Temukan event terbaik untuk Anda</p>
                <div class="flex justify-center gap-4 mt-4">
                    <a href="#" class="text-gray-400 hover:text-accent-blue transition"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-accent-blue transition"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-accent-blue transition"><i class="fab fa-facebook"></i></a>
                </div>
                <p class="text-gray-300 text-xs mt-6">© <?= date('Y') ?> TiketMoo. All rights reserved.</p>
            </div>
        </footer>
    </div>
    
    <style>
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(20deg); }
            75% { transform: rotate(-10deg); }
        }
        .animate-wave {
            animation: wave 1s ease-in-out infinite;
            display: inline-block;
        }
    </style>

    <script>
        // Search dengan enter key
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });
        }
    </script>
</body>
</html>