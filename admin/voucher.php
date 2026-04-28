<?php
session_start();
include '../config/koneksi.php';

/* =======================
   DELETE
======================= */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM voucher WHERE id_voucher=$id");
    header("Location: voucher.php");
    exit;
}

/* =======================
   INSERT
======================= */
if (isset($_POST['simpan'])) {
    $kode = strtoupper($_POST['kode_voucher']);
    $potongan = $_POST['potongan'];
    $kuota = $_POST['kuota'];
    $status = $_POST['status'];
    $id_event = !empty($_POST['id_event']) ? $_POST['id_event'] : 'NULL';
    $id_venue = !empty($_POST['id_venue']) ? $_POST['id_venue'] : 'NULL';

    // Validasi potongan tidak boleh 0, minus, atau 100%
    if ($potongan <= 0) {
        $_SESSION['error_message'] = "Potongan diskon harus lebih dari 0%!";
        header("Location: voucher.php");
        exit;
    }
    if ($potongan >= 100) {
        $_SESSION['error_message'] = "Potongan diskon tidak boleh 100%! Maksimal potongan adalah 99%.";
        header("Location: voucher.php");
        exit;
    }
    
    // Validasi kuota tidak boleh 0 atau minus
    if ($kuota <= 0) {
        $_SESSION['error_message'] = "Kuota voucher harus lebih dari 0!";
        header("Location: voucher.php");
        exit;
    }

    $query = "INSERT INTO voucher (kode_voucher, potongan, kuota, status, id_event, id_venue) 
              VALUES ('$kode','$potongan','$kuota','$status', " . ($id_event == 'NULL' ? 'NULL' : $id_event) . ", " . ($id_venue == 'NULL' ? 'NULL' : $id_venue) . ")";
    
    mysqli_query($conn, $query);
    header("Location: voucher.php");
    exit;
}

/* =======================
   UPDATE
======================= */
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $kode = strtoupper($_POST['kode_voucher']);
    $potongan = $_POST['potongan'];
    $kuota = $_POST['kuota'];
    $status = $_POST['status'];
    $id_event = !empty($_POST['id_event']) ? $_POST['id_event'] : 'NULL';
    $id_venue = !empty($_POST['id_venue']) ? $_POST['id_venue'] : 'NULL';

    // Validasi potongan tidak boleh 0, minus, atau 100%
    if ($potongan <= 0) {
        $_SESSION['error_message'] = "Potongan diskon harus lebih dari 0%!";
        header("Location: voucher.php");
        exit;
    }
    if ($potongan >= 100) {
        $_SESSION['error_message'] = "Potongan diskon tidak boleh 100%! Maksimal potongan adalah 99%.";
        header("Location: voucher.php");
        exit;
    }
    
    // Validasi kuota tidak boleh 0 atau minus
    if ($kuota <= 0) {
        $_SESSION['error_message'] = "Kuota voucher harus lebih dari 0!";
        header("Location: voucher.php");
        exit;
    }

    $query = "UPDATE voucher 
              SET kode_voucher='$kode',
                  potongan='$potongan',
                  kuota='$kuota',
                  status='$status',
                  id_event = " . ($id_event == 'NULL' ? 'NULL' : $id_event) . ",
                  id_venue = " . ($id_venue == 'NULL' ? 'NULL' : $id_venue) . "
              WHERE id_voucher=$id";
    
    mysqli_query($conn, $query);
    header("Location: voucher.php");
    exit;
}

/* =======================
   DATA EDIT
======================= */
$edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM voucher WHERE id_voucher=$id"));
}

/* =======================
   AMBIL DATA EVENT & VENUE
======================= */
$events = mysqli_query($conn, "SELECT * FROM event ORDER BY tanggal DESC");
$venues = mysqli_query($conn, "SELECT * FROM venue ORDER BY nama_venue ASC");

/* =======================
   AMBIL DATA VOUCHER
======================= */
$data = mysqli_query($conn, "
    SELECT v.*, 
           e.nama_event, 
           vn.nama_venue,
           CASE 
               WHEN v.id_event IS NOT NULL THEN CONCAT('Event: ', e.nama_event)
               WHEN v.id_venue IS NOT NULL THEN CONCAT('Venue: ', vn.nama_venue)
               ELSE 'Global'
           END as target_info
    FROM voucher v
    LEFT JOIN event e ON v.id_event = e.id_event
    LEFT JOIN venue vn ON v.id_venue = vn.id_venue
    ORDER BY 
        CASE 
            WHEN v.id_event IS NOT NULL THEN 1
            WHEN v.id_venue IS NOT NULL THEN 2
            ELSE 3
        END,
        v.id_voucher DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Voucher | Event Ticket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0a2540;
            --accent-blue: #0066cc;
            --accent-hover: #005bb5;
            --soft-blue: #e6f0fa;
            --admin-bg: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--admin-bg);
            margin: 0;
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        .navbar {
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 50;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .navbar-brand i {
            color: var(--accent-blue);
            font-size: 1.5rem;
        }

        .navbar-brand span {
            background: linear-gradient(to right, var(--navy), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .navbar-menu a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
            font-weight: 500;
        }
        
        .navbar-menu a:hover {
            color: var(--accent-blue);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            color: var(--text-dark);
        }

        .page-subtitle {
            color: var(--text-muted);
            margin: 0;
        }

        .card {
            background-color: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background-color: var(--white);
            border-radius: var(--radius-xl);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--navy);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.2s;
            text-decoration: none;
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background-color: #f8fafc;
            color: var(--text-dark);
            transition: all 0.2s ease;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.15);
            background-color: var(--white);
        }

        select.form-control {
            appearance: none;
            cursor: pointer;
        }

        .input-group {
            position: relative;
        }

        .input-group .percentage {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-weight: 500;
            pointer-events: none;
        }

        .input-group .dropdown-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            text-decoration: none;
        }

        .btn-primary {
            background-color: var(--accent-blue);
            color: var(--white);
            box-shadow: 0 2px 4px rgba(0, 102, 204, 0.2);
        }

        .btn-primary:hover {
            background-color: var(--accent-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 102, 204, 0.3);
        }
        
        .btn-secondary {
            background-color: var(--soft-blue);
            color: var(--accent-blue);
        }
        
        .btn-secondary:hover {
            background-color: #d1e3f8;
        }

        .btn-danger {
            background-color: #fee2e2;
            color: var(--danger);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: var(--radius-md);
        }

        .btn-danger:hover {
            background-color: #fecaca;
        }
        
        .btn-edit {
            background-color: var(--soft-blue);
            color: var(--accent-blue);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: var(--radius-md);
        }
        
        .btn-edit:hover {
            background-color: #d1e3f8;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 800px;
        }

        .table th {
            background-color: #f1f5f9;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-dark);
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background-color 0.15s ease;
        }

        .table tbody tr:hover {
            background-color: var(--soft-blue);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .action-cell {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-code {
            background-color: #e0e7ff;
            color: #4338ca;
            font-family: 'Courier New', monospace;
            font-weight: 700;
        }

        .badge-discount {
            background-color: #fed7aa;
            color: #9a3412;
        }

        .badge-quota {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-status-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-event {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-venue {
            background-color: #fce7f3;
            color: #9d174d;
        }

        .badge-global {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
            display: block;
        }

        .info-box {
            background-color: var(--soft-blue);
            border-left: 4px solid var(--accent-blue);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
        }

        .info-box p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border-left: 4px solid var(--danger);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            color: #991b1b;
        }
        
        .alert-danger i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .action-cell {
                flex-direction: column;
            }
            .modal-content {
                width: 95%;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-ticket-alt"></i>
            <span>Admin Panel</span>
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Manajemen Voucher</h1>
                <p class="page-subtitle">Kelola kode voucher diskon untuk pembelian tiket</p>
            </div>
            <button type="button" class="btn btn-primary" id="btnTambahVoucher">
                <i class="fas fa-plus-circle"></i> Tambah Voucher Baru
            </button>
        </div>

        <!-- Alert Error -->
        <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert-danger">
            <i class="fas fa-exclamation-triangle"></i> 
            <?= $_SESSION['error_message'] ?>
        </div>
        <?php unset($_SESSION['error_message']); endif; ?>

        <!-- Info Box -->
        <div class="info-box">
            <p><i class="fas fa-info-circle"></i> <strong>Jenis Voucher:</strong></p>
            <p style="margin-top: 0.5rem;">• <span class="badge badge-event">Event Spesifik</span> - Hanya berlaku untuk event tertentu</p>
            <p>• <span class="badge badge-venue">Venue Spesifik</span> - Hanya berlaku untuk venue tertentu</p>
            <p>• <span class="badge badge-global">Global</span> - Berlaku untuk semua event</p>
        </div>

        <!-- TABEL DATA VOUCHER -->
        <div class="card">
            <div class="table-responsive" style="padding: 1.5rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Kode Voucher</th>
                            <th width="15%">Potongan</th>
                            <th width="15%">Kuota</th>
                            <th width="25%">Target</th>
                            <th width="10%">Status</th>
                            <th width="10%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($data) > 0) {
                            while($row = mysqli_fetch_assoc($data)) { 
                                // Tentukan badge untuk target
                                if($row['id_event']) {
                                    $target_badge = '<span class="badge badge-event"><i class="fas fa-calendar-alt"></i> ' . htmlspecialchars($row['nama_event']) . '</span>';
                                } elseif($row['id_venue']) {
                                    $target_badge = '<span class="badge badge-venue"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($row['nama_venue']) . '</span>';
                                } else {
                                    $target_badge = '<span class="badge badge-global"><i class="fas fa-globe"></i> Semua Event</span>';
                                }
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <span class="badge badge-code">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($row['kode_voucher']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-discount">
                                   <?= $row['potongan'] ?>% OFF
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-quota">
                                    <i class="fas fa-ticket-alt"></i> <?= number_format($row['kuota'], 0, ',', '.') ?>x
                                </span>
                            </td>
                            <td><?= $target_badge ?></td>
                            <td>
                                <?php if ($row['status'] == 'aktif') { ?>
                                    <span class="badge badge-status-active">
                                        <i class="fas fa-check-circle"></i> Aktif
                                    </span>
                                <?php } else { ?>
                                    <span class="badge badge-status-inactive">
                                        <i class="fas fa-ban"></i> Nonaktif
                                    </span>
                                <?php } ?>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="?edit=<?= $row['id_voucher'] ?>" class="btn btn-edit" title="Edit Voucher">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?hapus=<?= $row['id_voucher'] ?>" class="btn btn-danger" title="Hapus Voucher"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus voucher ini? Voucher yang sudah digunakan tidak dapat dihapus.')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                        ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-ticket-alt"></i>
                                Belum ada data voucher.
                                <p style="margin-top: 0.5rem; font-size: 0.875rem;">Silakan tambah voucher baru dengan tombol di atas.</p>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH VOUCHER -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Tambah Voucher Baru
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('modalTambah')">&times;</button>
            </div>
            <form method="POST" id="formTambah" onsubmit="return validateForm()">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="kode_voucher">Kode Voucher</label>
                        <input type="text" id="kode_voucher" name="kode_voucher" class="form-control" 
                               placeholder="Contoh: DISKON50, HEMAT20, WELCOME10..." required>
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-info-circle"></i> Gunakan huruf kapital dan tanpa spasi
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="potongan">Potongan Diskon (%)</label>
                        <div class="input-group" style="position: relative;">
                            <input type="number" id="potongan" name="potongan" class="form-control" 
                                   placeholder="1" min="1" max="99" value="1" required>
                            <span class="percentage">%</span>
                        </div>
                        <small style="color: var(--danger); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-exclamation-circle"></i> Potongan diskon minimal 1% dan maksimal 99% (tidak boleh 0% atau 100%).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="kuota">Kuota Penggunaan</label>
                        <input type="number" id="kuota" name="kuota" class="form-control" 
                               placeholder="Jumlah maksimal penggunaan voucher" min="1" value="1" required>
                        <small style="color: var(--danger); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-exclamation-circle"></i> Kuota minimal 1 (tidak boleh 0 atau minus).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="target_type">Target Voucher</label>
                        <select id="target_type" class="form-control" onchange="toggleTargetFields()">
                            <option value="global">Global (Semua Event)</option>
                            <option value="event">Event Spesifik</option>
                            <option value="venue">Venue Spesifik</option>
                        </select>
                    </div>

                    <div id="event_field" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="id_event">Pilih Event</label>
                            <div class="input-group" style="position: relative;">
                                <select id="id_event" name="id_event" class="form-control">
                                    <option value="">-- Pilih Event --</option>
                                    <?php 
                                    mysqli_data_seek($events, 0);
                                    while($e = mysqli_fetch_assoc($events)) { ?>
                                        <option value="<?= $e['id_event'] ?>">
                                            <?= htmlspecialchars($e['nama_event']) ?> - <?= date('d F Y', strtotime($e['tanggal'])) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <i class="fas fa-chevron-down dropdown-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div id="venue_field" style="display: none;">
                        <div class="form-group">
                            <label class="form-label" for="id_venue">Pilih Venue</label>
                            <div class="input-group" style="position: relative;">
                                <select id="id_venue" name="id_venue" class="form-control">
                                    <option value="">-- Pilih Venue --</option>
                                    <?php 
                                    mysqli_data_seek($venues, 0);
                                    while($v = mysqli_fetch_assoc($venues)) { ?>
                                        <option value="<?= $v['id_venue'] ?>">
                                            <?= htmlspecialchars($v['nama_venue']) ?> - Kapasitas: <?= number_format($v['kapasitas'], 0, ',', '.') ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <i class="fas fa-chevron-down dropdown-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Status Voucher</label>
                        <div class="input-group" style="position: relative;">
                            <select id="status" name="status" class="form-control" required>
                                <option value="aktif" selected>Aktif</option>
                                <option value="nonaktif">Nonaktif</option>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="simpan" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT VOUCHER -->
    <?php if ($edit): ?>
    <div id="modalEdit" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    Edit Data Voucher
                </h3>
                <a href="voucher.php" class="modal-close" style="text-decoration: none;">&times;</a>
            </div>
            <form method="POST" onsubmit="return validateEditForm()">
                <input type="hidden" name="id" value="<?= $edit['id_voucher'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_kode_voucher">Kode Voucher</label>
                        <input type="text" id="edit_kode_voucher" name="kode_voucher" class="form-control" 
                               value="<?= htmlspecialchars($edit['kode_voucher']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_potongan">Potongan Diskon (%)</label>
                        <div class="input-group" style="position: relative;">
                            <input type="number" id="edit_potongan" name="potongan" class="form-control" 
                                   value="<?= $edit['potongan'] ?>" min="1" max="99" required>
                            <span class="percentage">%</span>
                        </div>
                        <small style="color: var(--danger); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-exclamation-circle"></i> Potongan diskon minimal 1% dan maksimal 99% (tidak boleh 0% atau 100%).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_kuota">Kuota Penggunaan</label>
                        <input type="number" id="edit_kuota" name="kuota" class="form-control" 
                               value="<?= $edit['kuota'] ?>" min="1" required>
                        <small style="color: var(--danger); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-exclamation-circle"></i> Kuota minimal 1 (tidak boleh 0 atau minus).
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_target_type">Target Voucher</label>
                        <select id="edit_target_type" class="form-control" onchange="toggleEditTargetFields()">
                            <option value="global" <?= ($edit['id_event'] === null && $edit['id_venue'] === null) ? 'selected' : '' ?>>Global (Semua Event)</option>
                            <option value="event" <?= ($edit['id_event'] !== null) ? 'selected' : '' ?>>Event Spesifik</option>
                            <option value="venue" <?= ($edit['id_venue'] !== null) ? 'selected' : '' ?>>Venue Spesifik</option>
                        </select>
                    </div>

                    <div id="edit_event_field" style="display: <?= ($edit['id_event'] !== null) ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label class="form-label" for="edit_id_event">Pilih Event</label>
                            <div class="input-group" style="position: relative;">
                                <select id="edit_id_event" name="id_event" class="form-control">
                                    <option value="">-- Pilih Event --</option>
                                    <?php 
                                    mysqli_data_seek($events, 0);
                                    while($e = mysqli_fetch_assoc($events)) { ?>
                                        <option value="<?= $e['id_event'] ?>" <?= ($edit['id_event'] == $e['id_event']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($e['nama_event']) ?> - <?= date('d F Y', strtotime($e['tanggal'])) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <i class="fas fa-chevron-down dropdown-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div id="edit_venue_field" style="display: <?= ($edit['id_venue'] !== null) ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label class="form-label" for="edit_id_venue">Pilih Venue</label>
                            <div class="input-group" style="position: relative;">
                                <select id="edit_id_venue" name="id_venue" class="form-control">
                                    <option value="">-- Pilih Venue --</option>
                                    <?php 
                                    mysqli_data_seek($venues, 0);
                                    while($v = mysqli_fetch_assoc($venues)) { ?>
                                        <option value="<?= $v['id_venue'] ?>" <?= ($edit['id_venue'] == $v['id_venue']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($v['nama_venue']) ?> - Kapasitas: <?= number_format($v['kapasitas'], 0, ',', '.') ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <i class="fas fa-chevron-down dropdown-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_status">Status Voucher</label>
                        <div class="input-group" style="position: relative;">
                            <select id="edit_status" name="status" class="form-control" required>
                                <option value="aktif" <?= ($edit['status'] == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= ($edit['status'] == 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="voucher.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Voucher
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Validasi form tambah
        function validateForm() {
            const potongan = document.getElementById('potongan');
            const kuota = document.getElementById('kuota');
            const potonganValue = parseInt(potongan.value);
            const kuotaValue = parseInt(kuota.value);
            
            // Validasi potongan
            if (isNaN(potonganValue) || potonganValue <= 0) {
                alert('Potongan diskon harus lebih dari 0%!');
                potongan.value = 1;
                potongan.focus();
                return false;
            }
            if (potonganValue >= 100) {
                alert('Potongan diskon tidak boleh 100%! Maksimal potongan adalah 99%.');
                potongan.value = 99;
                potongan.focus();
                return false;
            }
            
            // Validasi kuota
            if (isNaN(kuotaValue) || kuotaValue <= 0) {
                alert('Kuota voucher harus lebih dari 0!');
                kuota.value = 1;
                kuota.focus();
                return false;
            }
            
            return true;
        }
        
        // Validasi form edit
        function validateEditForm() {
            const potongan = document.getElementById('edit_potongan');
            const kuota = document.getElementById('edit_kuota');
            const potonganValue = parseInt(potongan.value);
            const kuotaValue = parseInt(kuota.value);
            
            // Validasi potongan
            if (isNaN(potonganValue) || potonganValue <= 0) {
                alert('Potongan diskon harus lebih dari 0%!');
                potongan.value = 1;
                potongan.focus();
                return false;
            }
            if (potonganValue >= 100) {
                alert('Potongan diskon tidak boleh 100%! Maksimal potongan adalah 99%.');
                potongan.value = 99;
                potongan.focus();
                return false;
            }
            
            // Validasi kuota
            if (isNaN(kuotaValue) || kuotaValue <= 0) {
                alert('Kuota voucher harus lebih dari 0!');
                kuota.value = 1;
                kuota.focus();
                return false;
            }
            
            return true;
        }

        function toggleTargetFields() {
            const targetType = document.getElementById('target_type').value;
            const eventField = document.getElementById('event_field');
            const venueField = document.getElementById('venue_field');
            
            if (targetType === 'event') {
                eventField.style.display = 'block';
                venueField.style.display = 'none';
                document.getElementById('id_event').required = true;
                document.getElementById('id_venue').required = false;
                document.getElementById('id_venue').value = '';
            } else if (targetType === 'venue') {
                eventField.style.display = 'none';
                venueField.style.display = 'block';
                document.getElementById('id_event').required = false;
                document.getElementById('id_venue').required = true;
                document.getElementById('id_event').value = '';
            } else {
                eventField.style.display = 'none';
                venueField.style.display = 'none';
                document.getElementById('id_event').required = false;
                document.getElementById('id_venue').required = false;
                document.getElementById('id_event').value = '';
                document.getElementById('id_venue').value = '';
            }
        }

        function toggleEditTargetFields() {
            const targetType = document.getElementById('edit_target_type').value;
            const eventField = document.getElementById('edit_event_field');
            const venueField = document.getElementById('edit_venue_field');
            
            if (targetType === 'event') {
                eventField.style.display = 'block';
                venueField.style.display = 'none';
                if(document.getElementById('edit_id_event')) document.getElementById('edit_id_event').required = true;
                if(document.getElementById('edit_id_venue')) document.getElementById('edit_id_venue').required = false;
                if(document.getElementById('edit_id_venue')) document.getElementById('edit_id_venue').value = '';
            } else if (targetType === 'venue') {
                eventField.style.display = 'none';
                venueField.style.display = 'block';
                if(document.getElementById('edit_id_event')) document.getElementById('edit_id_event').required = false;
                if(document.getElementById('edit_id_venue')) document.getElementById('edit_id_venue').required = true;
                if(document.getElementById('edit_id_event')) document.getElementById('edit_id_event').value = '';
            } else {
                eventField.style.display = 'none';
                venueField.style.display = 'none';
                if(document.getElementById('edit_id_event')) document.getElementById('edit_id_event').required = false;
                if(document.getElementById('edit_id_venue')) document.getElementById('edit_id_venue').required = false;
                if(document.getElementById('edit_id_event')) document.getElementById('edit_id_event').value = '';
                if(document.getElementById('edit_id_venue')) document.getElementById('edit_id_venue').value = '';
            }
        }

        // Event listener untuk input potongan (real-time validation)
        const potonganInput = document.getElementById('potongan');
        const editPotonganInput = document.getElementById('edit_potongan');
        
        if (potonganInput) {
            potonganInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value <= 0) {
                    alert('Potongan diskon harus lebih dari 0%!');
                    this.value = 1;
                } else if (value >= 100) {
                    alert('Potongan diskon tidak boleh 100%! Maksimal potongan adalah 99%.');
                    this.value = 99;
                }
            });
            potonganInput.addEventListener('input', function() {
                let value = parseInt(this.value);
                if (value <= 0) {
                    this.value = 1;
                } else if (value >= 100) {
                    this.value = 99;
                }
            });
        }
        
        if (editPotonganInput) {
            editPotonganInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value <= 0) {
                    alert('Potongan diskon harus lebih dari 0%!');
                    this.value = 1;
                } else if (value >= 100) {
                    alert('Potongan diskon tidak boleh 100%! Maksimal potongan adalah 99%.');
                    this.value = 99;
                }
            });
            editPotonganInput.addEventListener('input', function() {
                let value = parseInt(this.value);
                if (value <= 0) {
                    this.value = 1;
                } else if (value >= 100) {
                    this.value = 99;
                }
            });
        }
        
        // Event listener untuk input kuota (real-time validation)
        const kuotaInput = document.getElementById('kuota');
        const editKuotaInput = document.getElementById('edit_kuota');
        
        if (kuotaInput) {
            kuotaInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value <= 0) {
                    alert('Kuota harus lebih dari 0!');
                    this.value = 1;
                }
            });
            kuotaInput.addEventListener('input', function() {
                let value = parseInt(this.value);
                if (value <= 0) {
                    this.value = 1;
                }
            });
        }
        
        if (editKuotaInput) {
            editKuotaInput.addEventListener('change', function() {
                let value = parseInt(this.value);
                if (isNaN(value) || value <= 0) {
                    alert('Kuota harus lebih dari 0!');
                    this.value = 1;
                }
            });
            editKuotaInput.addEventListener('input', function() {
                let value = parseInt(this.value);
                if (value <= 0) {
                    this.value = 1;
                }
            });
        }

        // Fungsi untuk membuka modal
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        // Fungsi untuk menutup modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = '';
        }

        // Event listener untuk tombol tambah voucher
        const btnTambah = document.getElementById('btnTambahVoucher');
        if (btnTambah) {
            btnTambah.addEventListener('click', function() {
                openModal('modalTambah');
            });
        }

        // Tutup modal jika klik di luar modal
        window.addEventListener('click', function(event) {
            const modalTambah = document.getElementById('modalTambah');
            const modalEdit = document.getElementById('modalEdit');
            
            if (event.target === modalTambah) {
                closeModal('modalTambah');
            }
            if (event.target === modalEdit) {
                window.location.href = 'voucher.php';
            }
        });
    </script>
</body>
</html>