<?php
session_start();
include '../config/koneksi.php';

// =======================
// PROSES TAMBAH
// =======================
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_event'];
    $tanggal = $_POST['tanggal'];
    $id_venue = $_POST['id_venue'];
    $deskripsi = $_POST['deskripsi'] ?? '';
    
    // Proses upload gambar
    $foto = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../uploads/event/";
        
        // Buat folder jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $foto;
        
        // Validasi file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
        }
    }

    mysqli_query($conn, "
        INSERT INTO event (nama_event, tanggal, id_venue, deskripsi, foto)
        VALUES ('$nama','$tanggal','$id_venue','$deskripsi','$foto')
    ");
    header("Location: event.php");
}

// =======================
// PROSES EDIT
// =======================
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama_event'];
    $tanggal = $_POST['tanggal'];
    $id_venue = $_POST['id_venue'];
    $deskripsi = $_POST['deskripsi'] ?? '';
    
    // Ambil foto lama
    $query_foto = mysqli_query($conn, "SELECT foto FROM event WHERE id_event=$id");
    $foto_lama = mysqli_fetch_assoc($query_foto)['foto'];
    $foto = $foto_lama;
    
    // Proses upload gambar baru
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../uploads/event/";
        
        // Buat folder jika belum ada
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = time() . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $foto;
        
        // Validasi file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array(strtolower($file_extension), $allowed_types)) {
            move_uploaded_file($_FILES['foto']['tmp_name'], $target_file);
            
            // Hapus foto lama jika ada
            if ($foto_lama && file_exists($target_dir . $foto_lama)) {
                unlink($target_dir . $foto_lama);
            }
        }
    }

    mysqli_query($conn, "
        UPDATE event 
        SET nama_event='$nama', tanggal='$tanggal', id_venue='$id_venue', deskripsi='$deskripsi', foto='$foto'
        WHERE id_event=$id
    ");
    header("Location: event.php");
}

// =======================
// PROSES HAPUS
// =======================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Ambil foto untuk dihapus
    $query_foto = mysqli_query($conn, "SELECT foto FROM event WHERE id_event=$id");
    $foto = mysqli_fetch_assoc($query_foto)['foto'];
    
    // Hapus file foto jika ada
    if ($foto && file_exists("../uploads/event/" . $foto)) {
        unlink("../uploads/event/" . $foto);
    }
    
    mysqli_query($conn, "DELETE FROM event WHERE id_event=$id");
    header("Location: event.php");
}

// =======================
// AMBIL DATA
// =======================
$data = mysqli_query($conn, "
    SELECT event.*, venue.nama_venue, venue.alamat, venue.kapasitas
    FROM event
    JOIN venue ON event.id_venue = venue.id_venue
    ORDER BY event.tanggal DESC
");

$venue = mysqli_query($conn, "SELECT * FROM venue ORDER BY nama_venue ASC");

// =======================
// MODE EDIT
// =======================
$editData = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT * FROM event WHERE id_event=$id
    "));
}

// =======================
// AMBIL DATA UNTUK DETAIL
// =======================
$detailData = null;
if (isset($_GET['detail'])) {
    $id = $_GET['detail'];
    $detailData = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT event.*, venue.nama_venue, venue.alamat, venue.kapasitas
        FROM event
        JOIN venue ON event.id_venue = venue.id_venue
        WHERE event.id_event=$id
    "));
    
    // Ambil tiket untuk event ini
    $tiketEvent = mysqli_query($conn, "
        SELECT * FROM tiket WHERE id_event = $id
    ");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Event | Event Ticket</title>
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
            color: var(--text-dark);
            -webkit-font-smoothing: antialiased;
        }

        /* Navbar */
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

        /* Container */
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

        /* Page Header */
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

        /* Buttons */
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

        .btn-info {
            background-color: #dbeafe;
            color: var(--info);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: var(--radius-md);
        }

        .btn-info:hover {
            background-color: #bfdbfe;
        }

        /* Card */
        .card {
            background-color: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        /* Modal */
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

        .modal-content-large {
            max-width: 800px;
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

        /* Form */
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

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        select.form-control {
            appearance: none;
            cursor: pointer;
        }

        .input-group {
            position: relative;
        }

        .input-group .dropdown-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        /* File input styling */
        .file-input-wrapper {
            position: relative;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background-color: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-input-label:hover {
            background-color: var(--soft-blue);
            border-color: var(--accent-blue);
        }

        .image-preview {
            margin-top: 1rem;
            display: none;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--radius-md);
            object-fit: cover;
        }

        .current-image {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: #f8fafc;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .current-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-md);
        }

        /* Detail Event Styles */
        .detail-section {
            margin-bottom: 1.5rem;
        }

        .detail-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-dark);
        }

        .detail-card {
            background-color: #f8fafc;
            border-radius: var(--radius-lg);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .event-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: var(--radius-lg);
            margin-bottom: 1rem;
        }

        .tiket-item {
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 0;
        }

        .tiket-item:last-child {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-upcoming {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-today {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-past {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        /* Table */
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
            background-color: #e0e7ff;
            color: #4338ca;
        }

        .event-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: var(--radius-md);
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
            .event-thumbnail {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-crown"></i>
            <span>Admin Panel</span>
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Manajemen Event</h1>
                <p class="page-subtitle">Kelola dan atur jadwal acara serta lokasi venue</p>
            </div>
            <button type="button" class="btn btn-primary" id="btnTambahEvent">
                <i class="fas fa-plus-circle"></i> Tambah Event Baru
            </button>
        </div>

        <!-- Tabel Daftar Event -->
        <div class="card">
            <div class="table-responsive" style="padding: 1.5rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="10%">Foto</th>
                            <th width="25%">Nama Event</th>
                            <th width="15%">Tanggal</th>
                            <th width="15%">Venue</th>
                            <th width="30%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no=1; 
                        if(mysqli_num_rows($data) > 0) {
                            while($row = mysqli_fetch_assoc($data)) { 
                                $today = date('Y-m-d');
                                $event_date = $row['tanggal'];
                                if($event_date > $today) {
                                    $status_class = 'status-upcoming';
                                    $status_text = 'Akan Datang';
                                } elseif($event_date == $today) {
                                    $status_class = 'status-today';
                                    $status_text = 'Hari Ini';
                                } else {
                                    $status_class = 'status-past';
                                    $status_text = 'Selesai';
                                }
                                
                                $foto_path = !empty($row['foto']) ? "../uploads/event/" . $row['foto'] : "https://placehold.co/400x400?text=No+Image";
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <img src="<?= $foto_path ?>" class="event-thumbnail" alt="<?= htmlspecialchars($row['nama_event']) ?>"
                                     onerror="this.src='https://placehold.co/400x400?text=No+Image'">
                            </td>
                            <td style="font-weight: 500; color: var(--navy);">
                                <?= htmlspecialchars($row['nama_event']) ?>
                                <span class="status-badge <?= $status_class ?>" style="margin-left: 0.5rem;"><?= $status_text ?></span>
                            </td>
                            <td>
                                <div><i class="far fa-calendar-alt" style="color: var(--accent-blue); margin-right: 0.5rem;"></i> <?= date('d F Y', strtotime($row['tanggal'])) ?></div>
                            </td>
                            <td>
                                <span class="badge"><i class="fas fa-map-marker-alt" style="margin-right: 0.25rem;"></i> <?= htmlspecialchars($row['nama_venue']) ?></span>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="?detail=<?= $row['id_event'] ?>" class="btn btn-info" title="Detail Event">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?edit=<?= $row['id_event'] ?>" class="btn btn-edit" title="Edit Event">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?hapus=<?= $row['id_event'] ?>" class="btn btn-danger" title="Hapus Event"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus event ini? Data yang dihapus tidak dapat dikembalikan.')">
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
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-inbox" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                                Belum ada data event.
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH EVENT -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Tambah Event Baru
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('modalTambah')">&times;</button>
            </div>
            <form method="POST" id="formTambah" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="nama_event">Nama Event</label>
                        <input type="text" id="nama_event" name="nama_event" class="form-control" 
                               placeholder="Masukkan nama event..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tanggal">Tanggal Pelaksanaan</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="id_venue">Venue Lokasi</label>
                        <div class="input-group">
                            <select id="id_venue" name="id_venue" class="form-control" required>
                                <option value="" disabled selected>Pilih Venue...</option>
                                <?php 
                                mysqli_data_seek($venue, 0);
                                while($v = mysqli_fetch_assoc($venue)) { ?>
                                    <option value="<?= $v['id_venue'] ?>">
                                        <?= htmlspecialchars($v['nama_venue']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="deskripsi">Deskripsi Event</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" 
                                  placeholder="Masukkan deskripsi event..." rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Foto Event</label>
                        <div class="file-input-wrapper">
                            <div class="file-input-label">
                                <i class="fas fa-upload"></i>
                                <span id="fileName">Pilih file gambar...</span>
                            </div>
                            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewImage(this)">
                        </div>
                        <div id="imagePreview" class="image-preview">
                            <img id="previewImg" src="#" alt="Preview">
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-info-circle"></i> Format: JPG, PNG, GIF, WEBP. Maksimal 2MB
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Event
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT EVENT -->
    <?php if ($editData): ?>
    <div id="modalEdit" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    Edit Data Event
                </h3>
                <a href="event.php" class="modal-close" style="text-decoration: none;">&times;</a>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $editData['id_event'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_nama_event">Nama Event</label>
                        <input type="text" id="edit_nama_event" name="nama_event" class="form-control" 
                               value="<?= htmlspecialchars($editData['nama_event']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_tanggal">Tanggal Pelaksanaan</label>
                        <input type="date" id="edit_tanggal" name="tanggal" class="form-control" 
                               value="<?= $editData['tanggal'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_id_venue">Venue Lokasi</label>
                        <div class="input-group">
                            <select id="edit_id_venue" name="id_venue" class="form-control" required>
                                <option value="" disabled>Pilih Venue...</option>
                                <?php 
                                mysqli_data_seek($venue, 0);
                                while($v = mysqli_fetch_assoc($venue)) { ?>
                                    <option value="<?= $v['id_venue'] ?>"
                                        <?= ($editData['id_venue'] == $v['id_venue']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['nama_venue']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_deskripsi">Deskripsi Event</label>
                        <textarea id="edit_deskripsi" name="deskripsi" class="form-control" 
                                  placeholder="Masukkan deskripsi event..." rows="4"><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Foto Event Saat Ini</label>
                        <?php if (!empty($editData['foto'])): ?>
                            <div class="current-image">
                                <img src="../uploads/event/<?= $editData['foto'] ?>" alt="Current Image">
                                <span style="font-size: 0.875rem;"><?= $editData['foto'] ?></span>
                            </div>
                        <?php else: ?>
                            <div class="current-image">
                                <img src="https://placehold.co/400x400?text=No+Image" alt="No Image" style="width: 80px; height: 80px;">
                                <span style="font-size: 0.875rem;">Tidak ada foto</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ganti Foto Event (Opsional)</label>
                        <div class="file-input-wrapper">
                            <div class="file-input-label">
                                <i class="fas fa-upload"></i>
                                <span id="editFileName">Pilih file gambar baru...</span>
                            </div>
                            <input type="file" id="edit_foto" name="foto" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewEditImage(this)">
                        </div>
                        <div id="editImagePreview" class="image-preview">
                            <img id="editPreviewImg" src="#" alt="Preview">
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-info-circle"></i> Kosongkan jika tidak ingin mengubah foto
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="event.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="edit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL DETAIL EVENT -->
    <?php if ($detailData): ?>
    <div id="modalDetail" class="modal show">
        <div class="modal-content modal-content-large">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-info-circle"></i>
                    Detail Event
                </h3>
                <a href="event.php" class="modal-close" style="text-decoration: none;">&times;</a>
            </div>
            <div class="modal-body">
                <!-- Foto Event -->
                <?php 
                $detail_foto = !empty($detailData['foto']) ? "../uploads/event/" . $detailData['foto'] : "https://placehold.co/800x400?text=No+Image";
                ?>
                <img src="<?= $detail_foto ?>" class="event-image" alt="<?= htmlspecialchars($detailData['nama_event']) ?>"
                     onerror="this.src='https://placehold.co/800x400?text=No+Image'">

                <!-- Informasi Event -->
                <div class="detail-card">
                    <div class="detail-label">Nama Event</div>
                    <div class="detail-value" style="font-size: 1.25rem; font-weight: 700; color: var(--navy);">
                        <?= htmlspecialchars($detailData['nama_event']) ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="detail-card">
                        <div class="detail-label">
                            <i class="far fa-calendar-alt"></i> Tanggal
                        </div>
                        <div class="detail-value">
                            <?= date('l, d F Y', strtotime($detailData['tanggal'])) ?>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">
                            <i class="fas fa-map-marker-alt"></i> Venue
                        </div>
                        <div class="detail-value">
                            <?= htmlspecialchars($detailData['nama_venue']) ?>
                        </div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">
                            <i class="fas fa-users"></i> Kapasitas Venue
                        </div>
                        <div class="detail-value">
                            <?= number_format($detailData['kapasitas'], 0, ',', '.') ?> orang
                        </div>
                    </div>
                </div>

                <!-- Alamat Venue -->
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-location-dot"></i> Alamat Venue
                    </div>
                    <div class="detail-value">
                        <?= htmlspecialchars($detailData['alamat']) ?>
                    </div>
                </div>

                <!-- Deskripsi Event -->
                <?php if (!empty($detailData['deskripsi'])): ?>
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-align-left"></i> Deskripsi Event
                    </div>
                    <div class="detail-value" style="white-space: pre-wrap; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($detailData['deskripsi'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Daftar Tiket -->
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-ticket-alt"></i> Daftar Tiket
                    </div>
                    <?php if (isset($tiketEvent) && mysqli_num_rows($tiketEvent) > 0): ?>
                        <?php while($tiket = mysqli_fetch_assoc($tiketEvent)): ?>
                        <div class="tiket-item">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                <div>
                                    <strong><?= htmlspecialchars($tiket['nama_tiket']) ?></strong>
                                </div>
                                <div>
                                    <span class="status-badge" style="background-color: #d1fae5; color: #065f46;">
                                        Rp <?= number_format($tiket['harga'], 0, ',', '.') ?>
                                    </span>
                                    <span class="status-badge" style="background-color: #dbeafe; color: #1e40af; margin-left: 0.5rem;">
                                        <i class="fas fa-users"></i> Kuota: <?= number_format($tiket['kuota'], 0, ',', '.') ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 1rem;">
                            <i class="fas fa-ticket-alt" style="font-size: 1.5rem;"></i>
                            <p style="margin-top: 0.5rem;">Belum ada tiket untuk event ini.</p>
                           
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="?edit=<?= $detailData['id_event'] ?>" class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Event
                </a>
                <a href="event.php" class="btn btn-primary">Tutup</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Preview gambar untuk tambah event
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const fileName = document.getElementById('fileName');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
                fileName.textContent = input.files[0].name;
            } else {
                preview.style.display = 'none';
                fileName.textContent = 'Pilih file gambar...';
            }
        }

        // Preview gambar untuk edit event
        function previewEditImage(input) {
            const preview = document.getElementById('editImagePreview');
            const previewImg = document.getElementById('editPreviewImg');
            const fileName = document.getElementById('editFileName');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
                fileName.textContent = input.files[0].name;
            } else {
                preview.style.display = 'none';
                fileName.textContent = 'Pilih file gambar baru...';
            }
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

        // Event listener untuk tombol tambah event
        const btnTambah = document.getElementById('btnTambahEvent');
        if (btnTambah) {
            btnTambah.addEventListener('click', function() {
                openModal('modalTambah');
            });
        }

        // Tutup modal jika klik di luar modal
        window.addEventListener('click', function(event) {
            const modalTambah = document.getElementById('modalTambah');
            const modalEdit = document.getElementById('modalEdit');
            const modalDetail = document.getElementById('modalDetail');
            
            if (event.target === modalTambah) {
                closeModal('modalTambah');
            }
            if (event.target === modalEdit) {
                window.location.href = 'event.php';
            }
            if (event.target === modalDetail) {
                window.location.href = 'event.php';
            }
        });
    </script>
</body>
</html>