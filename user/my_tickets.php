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

$id_user = $_SESSION['id_user'] ?? 1;

// Proses Request Cancel Tiket (Kirim request ke petugas)
if(isset($_POST['request_cancel']) && isset($_POST['kode_tiket'])) {
    $kode_tiket = mysqli_real_escape_string($conn, $_POST['kode_tiket']);
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan_cancel']);
    
    // Cek status tiket sebelum request cancel
    $check_query = mysqli_query($conn, "SELECT status_checkin FROM attendee WHERE kode_tiket = '$kode_tiket'");
    $check_data = mysqli_fetch_assoc($check_query);
    
    if($check_data && $check_data['status_checkin'] == 'belum') {
        // Update status cancel request menjadi pending
        $update_query = mysqli_query($conn, "
            UPDATE attendee 
            SET cancel_request = 'pending', 
                cancel_reason = '$alasan',
                cancel_request_date = NOW()
            WHERE kode_tiket = '$kode_tiket'
        ");
        
        if($update_query) {
            $_SESSION['success_message'] = "Permintaan pembatalan tiket telah dikirim ke petugas. Silakan tunggu konfirmasi.";
        } else {
            $_SESSION['error_message'] = "Gagal mengirim permintaan. Silakan coba lagi.";
        }
    } else {
        $_SESSION['error_message'] = "Tiket tidak dapat dibatalkan karena sudah di-check-in!";
    }
    
    header("Location: my_tickets.php");
    exit;
}

// Ambil semua tiket yang sudah dibeli user (status order masih 'pending' atau 'paid' dan belum di-cancel)
$query_tickets = mysqli_query($conn, "
    SELECT 
        a.id_attendee,
        a.kode_tiket,
        a.status_checkin,
        a.cancel_request,
        a.cancel_reason,
        a.cancel_request_date,
        a.created_at as tiket_created_at,
        od.qty,
        od.subtotal,
        od.nama_tiket,
        od.harga,
        o.no_order,
        o.tanggal_order,
        o.total as total_order,
        o.status as order_status,
        e.nama_event,
        e.tanggal as event_tanggal,
        e.foto as event_foto,
        v.nama_venue,
        v.alamat as venue_alamat
    FROM attendee a
    JOIN order_detail od ON a.id_detail = od.id_detail
    JOIN orders o ON od.id_order = o.id_order
    JOIN event e ON o.id_event = e.id_event
    JOIN venue v ON e.id_venue = v.id_venue
    WHERE o.id_user = $id_user AND (o.status != 'cancel' OR o.status IS NULL)
    ORDER BY o.tanggal_order DESC, a.id_attendee ASC
");

// Kelompokkan berdasarkan order
$orders = [];
while($row = mysqli_fetch_assoc($query_tickets)) {
    $order_no = $row['no_order'];
    if(!isset($orders[$order_no])) {
        $orders[$order_no] = [
            'no_order' => $row['no_order'],
            'tanggal_order' => $row['tanggal_order'],
            'total_order' => $row['total_order'],
            'nama_event' => $row['nama_event'],
            'event_tanggal' => $row['event_tanggal'],
            'event_foto' => $row['event_foto'],
            'nama_venue' => $row['nama_venue'],
            'venue_alamat' => $row['venue_alamat'],
            'tickets' => []
        ];
    }
    $orders[$order_no]['tickets'][] = $row;
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
    <title>Tiket Saya | TiketMoo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Library QR Code Generator (JavaScript) -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .ticket-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -12px rgba(0,102,204,0.2);
        }
        @media print {
            .no-print {
                display: none;
            }
            .ticket-card {
                page-break-inside: avoid;
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .qr-code-container {
                display: block !important;
            }
        }
        .qr-code-container canvas,
        .qr-code-container img {
            width: 80px !important;
            height: 80px !important;
        }
        .modal-qr canvas,
        .modal-qr img {
            width: 160px !important;
            height: 160px !important;
        }
        .btn-cancel {
            transition: all 0.3s ease;
        }
        .btn-cancel:hover {
            transform: scale(1.05);
        }
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        .status-approved {
            background: #d1fae5;
            color: #059669;
        }
        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-white min-h-screen">
    
    <!-- Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-50 no-print">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-ticket-alt text-blue-600 text-2xl"></i>
                <span class="font-bold text-xl text-gray-800">TiketMoo</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 transition">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="../auth/logout.php" class="text-gray-600 hover:text-red-500 transition">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fas fa-ticket-alt text-blue-600"></i>
                Tiket Saya
            </h1>
            <p class="text-gray-500 mt-2">Semua tiket yang sudah Anda pesan</p>
        </div>
        
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['success_message'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?= $_SESSION['success_message'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['success_message']); endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: '<?= $_SESSION['error_message'] ?>',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['error_message']); endif; ?>
        
        <?php if(empty($orders)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-2xl shadow-md p-12 text-center">
            <i class="fas fa-ticket-alt text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Belum Ada Tiket</h3>
            <p class="text-gray-500 mb-6">Anda belum memiliki tiket. Yuk, pesan tiket event favorit Anda!</p>
            <a href="dashboard.php" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700 transition inline-flex items-center gap-2">
                <i class="fas fa-search"></i> Cari Event
            </a>
        </div>
        <?php else: ?>
        
        <!-- Tickets List -->
        <div class="space-y-8">
            <?php foreach($orders as $order): ?>
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <!-- Order Header -->
                <div class="bg-gradient-to-r from-gray-800 to-blue-700 px-6 py-4">
                    <div class="flex flex-wrap justify-between items-center">
                        <div>
                            <p class="text-blue-100 text-sm">Nomor Pesanan</p>
                            <p class="text-white font-semibold"><?= safe($order['no_order']) ?></p>
                        </div>
                        <div>
                            <p class="text-blue-100 text-sm">Tanggal Pesan</p>
                            <p class="text-white"><?= date('d F Y', strtotime($order['tanggal_order'])) ?></p>
                        </div>
                        <div>
                            <p class="text-blue-100 text-sm">Total</p>
                            <p class="text-white font-semibold">Rp <?= number_format($order['total_order'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Event Info -->
                <div class="p-6 border-b border-gray-100">
                    <div class="flex flex-wrap gap-4">
                        <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                            <?php 
                            $foto = !empty($order['event_foto']) ? "../uploads/event/" . $order['event_foto'] : "https://placehold.co/400x400?text=Event";
                            ?>
                            <img src="<?= $foto ?>" class="w-full h-full object-cover" alt="<?= safe($order['nama_event']) ?>">
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-xl text-gray-800"><?= safe($order['nama_event']) ?></h3>
                            <div class="flex flex-wrap gap-4 mt-1 text-sm text-gray-500">
                                <span><i class="fas fa-calendar-alt text-blue-600 mr-1"></i> <?= date('l, d F Y', strtotime($order['event_tanggal'])) ?></span>
                                <span><i class="fas fa-map-marker-alt text-blue-600 mr-1"></i> <?= safe($order['nama_venue']) ?></span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1"><?= safe($order['venue_alamat']) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Tickets -->
                <div class="p-6">
                    <h4 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-qrcode text-blue-600"></i>
                        Daftar Tiket
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach($order['tickets'] as $index => $ticket): 
                            $qr_id = 'qr_' . $ticket['kode_tiket'];
                            $event_date = strtotime($order['event_tanggal']);
                            $current_date = time();
                            $is_event_passed = $event_date < $current_date;
                            
                            // Cek status cancel request
                            $cancel_status = $ticket['cancel_request'] ?? null;
                            $is_cancel_pending = ($cancel_status == 'pending');
                            $is_cancel_approved = ($cancel_status == 'approved');
                            $is_cancel_rejected = ($cancel_status == 'rejected');
                        ?>
                        <div class="ticket-card border border-gray-200 rounded-xl p-4 hover:shadow-lg transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold">
                                            <?= safe($ticket['nama_tiket']) ?>
                                        </span>
                                        <?php if($ticket['status_checkin'] == 'sudah'): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-semibold">
                                            <i class="fas fa-check-circle"></i> Sudah Check-in
                                        </span>
                                        <?php else: ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-lg text-xs font-semibold">
                                            <i class="fas fa-clock"></i> Belum Check-in
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php if($is_cancel_pending): ?>
                                        <span class="px-2 py-1 status-pending rounded-lg text-xs font-semibold">
                                            <i class="fas fa-hourglass-half"></i> Menunggu Konfirmasi Cancel
                                        </span>
                                        <?php elseif($is_cancel_approved): ?>
                                        <span class="px-2 py-1 status-approved rounded-lg text-xs font-semibold">
                                            <i class="fas fa-check-circle"></i> Cancel Disetujui
                                        </span>
                                        <?php elseif($is_cancel_rejected): ?>
                                        <span class="px-2 py-1 status-rejected rounded-lg text-xs font-semibold">
                                            <i class="fas fa-times-circle"></i> Cancel Ditolak
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php if($is_event_passed && $ticket['status_checkin'] == 'belum' && !$is_cancel_pending && !$is_cancel_approved): ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-semibold">
                                            <i class="fas fa-exclamation-circle"></i> Event Telah Lewat
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="font-mono text-sm text-gray-600 mt-2">
                                        <i class="fas fa-barcode text-blue-600 mr-1"></i>
                                        <?= safe($ticket['kode_tiket']) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400">Harga Tiket</p>
                                    <p class="font-semibold text-gray-800">Rp <?= number_format($ticket['harga'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                            
                            <!-- QR Code - LANGSUNG KODE TIKET tanpa prefix -->
                            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div id="<?= $qr_id ?>" class="qr-code-container"></div>
                                    <span class="text-xs text-gray-500">Scan QR Code untuk check-in</span>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="showTicketDetail('<?= $ticket['kode_tiket'] ?>', '<?= safe($ticket['nama_tiket']) ?>', '<?= safe($order['nama_event']) ?>', '<?= $order['event_tanggal'] ?>', '<?= safe($order['nama_venue']) ?>', '<?= $qr_id ?>')" 
                                            class="text-blue-600 text-sm hover:underline flex items-center gap-1 no-print">
                                        <i class="fas fa-eye"></i> Detail
                                    </button>
                                    
                                    <!-- Tombol Cancel (hanya untuk tiket yang belum check-in, event belum lewat, dan belum request cancel) -->
                                    <?php if($ticket['status_checkin'] == 'belum' && !$is_event_passed && !$is_cancel_pending && !$is_cancel_approved): ?>
                                    <button onclick="requestCancel('<?= $ticket['kode_tiket'] ?>', '<?= safe($ticket['nama_tiket']) ?>', '<?= safe($order['nama_event']) ?>')" 
                                            class="text-red-600 text-sm hover:underline flex items-center gap-1 btn-cancel no-print">
                                        <i class="fas fa-times-circle"></i> Request Cancel
                                    </button>
                                    <?php elseif($is_cancel_pending): ?>
                                    <span class="text-orange-600 text-xs flex items-center gap-1">
                                        <i class="fas fa-hourglass-half"></i> Menunggu Konfirmasi
                                    </span>
                                    <?php elseif($is_cancel_approved): ?>
                                    <span class="text-green-600 text-xs flex items-center gap-1">
                                        <i class="fas fa-check-circle"></i> Tiket Dibatalkan
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <script>
                            // Generate QR Code untuk setiap tiket - LANGSUNG KODE TIKET (tanpa prefix)
                            new QRCode(document.getElementById("<?= $qr_id ?>"), {
                                text: "<?= $ticket['kode_tiket'] ?>",
                                width: 80,
                                height: 80,
                                colorDark: "#000000",
                                colorLight: "#ffffff",
                                correctLevel: QRCode.CorrectLevel.H
                            });
                        </script>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="px-6 py-4 bg-gray-50 flex flex-wrap gap-3 no-print">
                    <button onclick="printTickets()" class="text-gray-600 hover:text-blue-600 transition text-sm flex items-center gap-1">
                        <i class="fas fa-print"></i> Cetak Semua Tiket
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal Detail Tiket -->
    <div id="ticketModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 no-print" onclick="closeModal()">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 p-6" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Detail Tiket</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="modalContent">
                <!-- Content will be filled by JavaScript -->
            </div>
            <button onclick="printSingleTicket()" class="mt-4 w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition flex items-center justify-center gap-2">
                <i class="fas fa-print"></i> Cetak Tiket
            </button>
        </div>
    </div>
    
    <!-- Form Request Cancel Tiket (Hidden) -->
    <form id="cancelForm" method="POST" action="" class="hidden">
        <input type="hidden" name="request_cancel" value="1">
        <input type="hidden" name="kode_tiket" id="cancel_kode_tiket">
        <input type="hidden" name="alasan_cancel" id="cancel_alasan">
    </form>
    
    <script>
        let currentTicketData = null;
        
        function requestCancel(kodeTiket, namaTiket, namaEvent) {
            Swal.fire({
                title: 'Request Pembatalan Tiket',
                html: `
                    <div class="text-left">
                        <p class="mb-2">Apakah Anda yakin ingin membatalkan tiket:</p>
                        <div class="bg-gray-50 p-3 rounded-lg mb-3">
                            <p><strong>🎫 Tiket:</strong> ${namaTiket}</p>
                            <p><strong>📅 Event:</strong> ${namaEvent}</p>
                            <p><strong>🔑 Kode:</strong> ${kodeTiket}</p>
                        </div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Pembatalan:</label>
                        <textarea id="alasanCancel" class="w-full border border-gray-300 rounded-lg p-2 text-sm" rows="3" placeholder="Masukkan alasan pembatalan..."></textarea>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Kirim Request',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const alasan = document.getElementById('alasanCancel').value;
                    if (!alasan.trim()) {
                        Swal.showValidationMessage('Alasan pembatalan harus diisi!');
                        return false;
                    }
                    return { alasan: alasan };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cancel_kode_tiket').value = kodeTiket;
                    document.getElementById('cancel_alasan').value = result.value.alasan;
                    document.getElementById('cancelForm').submit();
                }
            });
        }
        
        function showTicketDetail(kode, namaTiket, namaEvent, tanggal, venue, qrId) {
            // Ambil elemen QR Code yang sudah ada
            const qrElement = document.getElementById(qrId);
            let qrHtml = '';
            
            if (qrElement) {
                // Clone QR Code yang sudah ada
                const qrClone = qrElement.cloneNode(true);
                qrHtml = qrClone.outerHTML;
            } else {
                qrHtml = `<div id="modal-qr-${kode}" class="modal-qr"></div>`;
            }
            
            currentTicketData = { kode, namaTiket, namaEvent, tanggal, venue, qrId };
            
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = `
                <div class="space-y-3">
                    <div class="border-b pb-2">
                        <p class="text-gray-500 text-sm">Kode Tiket</p>
                        <p class="font-mono font-semibold text-gray-800">${kode}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-gray-500 text-sm">Jenis Tiket</p>
                        <p class="font-semibold text-gray-800">${namaTiket}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-gray-500 text-sm">Event</p>
                        <p class="font-semibold text-gray-800">${namaEvent}</p>
                    </div>
                    <div class="border-b pb-2">
                        <p class="text-gray-500 text-sm">Tanggal Event</p>
                        <p class="font-semibold text-gray-800">${new Date(tanggal).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Lokasi Venue</p>
                        <p class="font-semibold text-gray-800">${venue}</p>
                    </div>
                </div>
                <div class="mt-4 p-4 bg-gray-100 rounded-lg text-center">
                    <div class="flex justify-center mb-2" id="modal-qr-container">
                        ${qrHtml}
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Scan QR Code untuk check-in</p>
                </div>
            `;
            
            // Jika QR Code belum ada di modal, buat baru (LANGSUNG KODE TIKET)
            if (!qrElement && document.getElementById(`modal-qr-${kode}`)) {
                new QRCode(document.getElementById(`modal-qr-${kode}`), {
                    text: kode,
                    width: 160,
                    height: 160,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            } else if (qrElement && !document.querySelector('#modal-qr-container canvas')) {
                // Clone dan resize QR Code untuk modal
                const modalContainer = document.getElementById('modal-qr-container');
                if (modalContainer) {
                    const newQRDiv = document.createElement('div');
                    newQRDiv.id = `modal-qr-new-${kode}`;
                    modalContainer.innerHTML = '';
                    modalContainer.appendChild(newQRDiv);
                    new QRCode(newQRDiv, {
                        text: kode,
                        width: 160,
                        height: 160,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            }
            
            document.getElementById('ticketModal').classList.remove('hidden');
            document.getElementById('ticketModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('ticketModal').classList.add('hidden');
            document.getElementById('ticketModal').classList.remove('flex');
            currentTicketData = null;
        }
        
        function printSingleTicket() {
            if (currentTicketData) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Cetak Tiket - ${currentTicketData.kode}</title>
                        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
                        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"><\/script>
                    </head>
                    <body class="p-8">
                        <div class="max-w-md mx-auto border rounded-lg p-6">
                            <div class="text-center mb-4">
                                <h2 class="text-xl font-bold">TiketMoo</h2>
                                <p class="text-sm text-gray-600">Tiket Event</p>
                            </div>
                            <div class="border-t pt-4">
                                <p><strong>Kode Tiket:</strong> ${currentTicketData.kode}</p>
                                <p><strong>Jenis Tiket:</strong> ${currentTicketData.namaTiket}</p>
                                <p><strong>Event:</strong> ${currentTicketData.namaEvent}</p>
                                <p><strong>Tanggal:</strong> ${currentTicketData.tanggal}</p>
                                <p><strong>Venue:</strong> ${currentTicketData.venue}</p>
                            </div>
                            <div class="text-center mt-6">
                                <div id="print-qr" class="flex justify-center"></div>
                            </div>
                            <p class="text-center text-xs text-gray-500 mt-4">Scan QR Code untuk check-in</p>
                        </div>
                        <script>
                            new QRCode(document.getElementById("print-qr"), {
                                text: "${currentTicketData.kode}",
                                width: 150,
                                height: 150,
                                colorDark: "#000000",
                                colorLight: "#ffffff"
                            });
                            setTimeout(() => {
                                window.print();
                            }, 500);
                        <\/script>
                    </body>
                    </html>
                `);
                printWindow.document.close();
            }
        }
        
        function printTickets() {
            window.print();
        }
    </script>
</body>
</html>