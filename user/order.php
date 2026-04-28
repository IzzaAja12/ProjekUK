<?php
session_start();
include '../config/koneksi.php';

// proteksi akses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: event.php");
    exit;
}

// Proteksi login
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Set default session jika belum ada
if(!isset($_SESSION['nama'])) {
    $_SESSION['nama'] = 'Pengguna';
}

$id_user = $_SESSION['id_user'] ?? 1;
$qty = $_POST['qty'] ?? [];
$kode_voucher = $_POST['kode_voucher'] ?? '';
$id_event = $_POST['id_event'] ?? 0;

// Fungsi untuk redirect dengan pesan
function redirectWithMessage($url, $message, $type = 'error') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit;
}

// =======================
// VALIDASI QTY
// =======================
if (empty($qty)) {
    redirectWithMessage("detail.php?id=$id_event", "Tidak ada tiket yang dipilih!", "error");
}

$ada = false;
$selected_tickets = [];
foreach ($qty as $id_tiket => $jumlah) {
    $jumlah = intval($jumlah);
    if ($jumlah > 0) {
        $ada = true;
        $selected_tickets[$id_tiket] = $jumlah;
    }
}

if (!$ada) {
    redirectWithMessage("detail.php?id=$id_event", "Silakan pilih minimal 1 tiket!", "error");
}

// =======================
// VALIDASI KETERSEDIAAN TIKET & HITUNG TOTAL
// =======================
$total = 0;
$tiket_details = [];

foreach ($selected_tickets as $id_tiket => $jumlah) {
    // Ambil data tiket
    $query_tiket = mysqli_query($conn, "
        SELECT t.*, e.nama_event, e.tanggal, e.id_event 
        FROM tiket t
        JOIN event e ON t.id_event = e.id_event
        WHERE t.id_tiket = $id_tiket
    ");
    
    if (!$query_tiket || mysqli_num_rows($query_tiket) == 0) {
        redirectWithMessage("detail.php?id=$id_event", "Tiket tidak ditemukan!", "error");
    }
    
    $tiket = mysqli_fetch_assoc($query_tiket);
    
    // Validasi kuota
    if ($tiket['kuota'] < $jumlah) {
        redirectWithMessage("detail.php?id={$tiket['id_event']}", 
            "Maaf, kuota tiket {$tiket['nama_tiket']} tidak mencukupi! Sisa: {$tiket['kuota']}", "error");
    }
    
    $subtotal = $tiket['harga'] * $jumlah;
    $total += $subtotal;
    
    $tiket_details[$id_tiket] = [
        'nama_tiket' => $tiket['nama_tiket'],
        'harga' => $tiket['harga'],
        'jumlah' => $jumlah,
        'subtotal' => $subtotal,
        'nama_event' => $tiket['nama_event'],
        'tanggal_event' => $tiket['tanggal'],
        'id_event' => $tiket['id_event']
    ];
}

// =======================
// CEK VOUCHER
// =======================
$potongan = 0;
$id_voucher = NULL;
$voucher_data = null;

if (!empty($kode_voucher)) {
    // Gunakan prepared statement untuk keamanan
    $stmt = mysqli_prepare($conn, "SELECT * FROM voucher WHERE kode_voucher = ? AND status = 'aktif' AND kuota > 0");
    mysqli_stmt_bind_param($stmt, "s", $kode_voucher);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $voucher = mysqli_fetch_assoc($result);
    
    if ($voucher) {
        $potongan_persen = $voucher['potongan'];
        $potongan = floor($total * $potongan_persen / 100);
        $id_voucher = $voucher['id_voucher'];
        $voucher_data = [
            'kode' => $voucher['kode_voucher'],
            'potongan_persen' => $potongan_persen,
            'potongan_nominal' => $potongan
        ];
    } else {
        redirectWithMessage("detail.php?id=$id_event", "Kode voucher tidak valid atau sudah habis masa berlakunya!", "error");
    }
}

// =======================
// TOTAL AKHIR
// =======================
$total_akhir = max(0, $total - $potongan);

// =======================
// SIMPAN KE ORDERS
// =======================
$status_order = 'pending';
$tanggal_order = date('Y-m-d H:i:s');
$no_order = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());

$query_order = "INSERT INTO orders (no_order, id_user, id_event, tanggal_order, subtotal, potongan, total, status, id_voucher) 
                VALUES ('$no_order', '$id_user', '$id_event', '$tanggal_order', '$total', '$potongan', '$total_akhir', '$status_order', " . ($id_voucher ? $id_voucher : "NULL") . ")";

if (!mysqli_query($conn, $query_order)) {
    redirectWithMessage("detail.php?id=$id_event", "Gagal memproses pesanan: " . mysqli_error($conn), "error");
}

$id_order = mysqli_insert_id($conn);

// =======================
// SIMPAN KE ORDER DETAIL & KURANGI KUOTA TIKET
// =======================
foreach ($selected_tickets as $id_tiket => $jumlah) {
    $detail = $tiket_details[$id_tiket];
    $subtotal = $detail['subtotal'];
    
    // Insert order detail
    $query_detail = "INSERT INTO order_detail (id_order, id_tiket, nama_tiket, harga, qty, subtotal) 
                     VALUES ('$id_order', '$id_tiket', '{$detail['nama_tiket']}', '{$detail['harga']}', '$jumlah', '$subtotal')";
    mysqli_query($conn, $query_detail);
    $id_detail = mysqli_insert_id($conn);
    
    // Kurangi kuota tiket
    $query_update_kuota = "UPDATE tiket SET kuota = kuota - $jumlah WHERE id_tiket = $id_tiket";
    mysqli_query($conn, $query_update_kuota);
    
    // =======================
    // GENERATE TIKET (ATTENDEE)
    // =======================
    for ($i = 0; $i < $jumlah; $i++) {
        $kode_tiket = "TKT-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -6)) . "-" . str_pad($i+1, 2, '0', STR_PAD_LEFT);
        
        $query_attendee = "INSERT INTO attendee (id_detail, kode_tiket, status_checkin, created_at) 
                           VALUES ('$id_detail', '$kode_tiket', 'belum', NOW())";
        mysqli_query($conn, $query_attendee);
    }
}

// Kurangi kuota voucher jika digunakan
if ($id_voucher) {
    mysqli_query($conn, "UPDATE voucher SET kuota = kuota - 1 WHERE id_voucher = $id_voucher");
}

// Set session success message
$_SESSION['order_success'] = true;
$_SESSION['order_data'] = [
    'no_order' => $no_order,
    'total' => $total,
    'potongan' => $potongan,
    'total_akhir' => $total_akhir,
    'tiket_details' => $tiket_details,
    'voucher' => $voucher_data,
    'tanggal_order' => $tanggal_order
];

// Redirect ke halaman sukses
header("Location: order_success.php");
exit;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Order | EventTicket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .loader {
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #0066cc;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-soft-blue to-white min-h-screen flex items-center justify-center">
    <div class="text-center">
        <div class="loader mx-auto mb-4"></div>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Memproses Pesanan Anda...</h2>
        <p class="text-gray-500">Mohon tunggu sebentar</p>
        
        <!-- Hidden form untuk auto redirect jika JavaScript tidak jalan -->
        <form id="redirectForm" action="order_success.php" method="POST" style="display: none;">
            <input type="hidden" name="order_id" value="<?= $id_order ?? '' ?>">
        </form>
    </div>
    
    <script>
        // Auto redirect setelah 2 detik
        setTimeout(function() {
            window.location.href = 'order_success.php';
        }, 2000);
    </script>
</body>
</html>