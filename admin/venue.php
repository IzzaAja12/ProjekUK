<?php
session_start();
include '../config/koneksi.php';

/* =======================
   DELETE
======================= */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM venue WHERE id_venue=$id");
    header("Location: venue.php");
    exit;
}

/* =======================
   INSERT
======================= */
if (isset($_POST['simpan'])) {
    $nama = $_POST['nama_venue'];
    $alamat = $_POST['alamat'];
    $kapasitas = $_POST['kapasitas'];

    mysqli_query($conn, "INSERT INTO venue (nama_venue, alamat, kapasitas)
    VALUES ('$nama','$alamat','$kapasitas')");

    header("Location: venue.php");
    exit;
}

/* =======================
   UPDATE
======================= */
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama_venue'];
    $alamat = $_POST['alamat'];
    $kapasitas = $_POST['kapasitas'];

    mysqli_query($conn, "UPDATE venue 
    SET nama_venue='$nama', alamat='$alamat', kapasitas='$kapasitas'
    WHERE id_venue=$id");

    header("Location: venue.php");
    exit;
}

/* =======================
   DATA EDIT (ambil data)
======================= */
$edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM venue WHERE id_venue=$id"));
}

/* =======================
   AMBIL DATA LIST
======================= */
$data = mysqli_query($conn, "SELECT * FROM venue ORDER BY nama_venue ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Venue | Event Ticket</title>
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
            max-width: 500px;
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

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
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
            min-width: 600px;
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

        .badge-capacity {
            background-color: #d1fae5;
            color: #065f46;
        }

        .address-text {
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.4;
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
            .address-text {
                max-width: 180px;
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
            <i class="fas fa-map-marker-alt"></i>
            <span>Admin Panel</span>
        </div>
        <div class="navbar-menu">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1 class="page-title">Manajemen Venue</h1>
                <p class="page-subtitle">Kelola lokasi dan kapasitas venue untuk event</p>
            </div>
            <button type="button" class="btn btn-primary" id="btnTambahVenue">
                <i class="fas fa-plus-circle"></i> Tambah Venue Baru
            </button>
        </div>

        <!-- TABEL DATA VENUE -->
        <div class="card">
            <div class="table-responsive" style="padding: 1.5rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="25%">Nama Venue</th>
                            <th width="35%">Alamat</th>
                            <th width="15%">Kapasitas</th>
                            <th width="20%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($data) > 0) {
                            while ($row = mysqli_fetch_assoc($data)) { 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td style="font-weight: 500; color: var(--navy);">
                                <i class="fas fa-building" style="color: var(--accent-blue); margin-right: 0.5rem;"></i>
                                <?= htmlspecialchars($row['nama_venue']) ?>
                            </td>
                            <td class="address-text">
                                <i class="fas fa-location-dot" style="color: var(--accent-blue); margin-right: 0.5rem;"></i>
                                <?= htmlspecialchars($row['alamat']) ?>
                            </td>
                            <td>
                                <span class="badge badge-capacity">
                                    <i class="fas fa-users"></i> <?= number_format($row['kapasitas'], 0, ',', '.') ?> orang
                                </span>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="?edit=<?= $row['id_venue'] ?>" class="btn btn-edit" title="Edit Venue">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?hapus=<?= $row['id_venue'] ?>" class="btn btn-danger" title="Hapus Venue"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus venue ini? Venue yang memiliki event tidak dapat dihapus.')">
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
                            <td colspan="5" class="empty-state">
                                <i class="fas fa-map-marker-alt"></i>
                                Belum ada data venue.
                                <p style="margin-top: 0.5rem; font-size: 0.875rem;">Silakan tambah venue baru dengan tombol di atas.</p>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH VENUE -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Tambah Venue Baru
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('modalTambah')">&times;</button>
            </div>
            <form method="POST" id="formTambah">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="nama_venue">Nama Venue</label>
                        <input type="text" id="nama_venue" name="nama_venue" class="form-control" 
                               placeholder="Contoh: Stadion Utama, Convention Hall, Gedung Serbaguna..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="alamat">Alamat Lengkap</label>
                        <textarea id="alamat" name="alamat" class="form-control" 
                                  placeholder="Masukkan alamat lengkap venue..." 
                                  rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="kapasitas">Kapasitas Venue</label>
                        <input type="number" id="kapasitas" name="kapasitas" class="form-control" 
                               placeholder="Jumlah maksimum orang yang dapat ditampung" required>
                        <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
                            <i class="fas fa-info-circle"></i> Masukkan angka maksimum kapasitas venue
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="simpan" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Venue
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT VENUE -->
    <?php if ($edit): ?>
    <div id="modalEdit" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    Edit Data Venue
                </h3>
                <a href="venue.php" class="modal-close" style="text-decoration: none;">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit['id_venue'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_nama_venue">Nama Venue</label>
                        <input type="text" id="edit_nama_venue" name="nama_venue" class="form-control" 
                               value="<?= htmlspecialchars($edit['nama_venue']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_alamat">Alamat Lengkap</label>
                        <textarea id="edit_alamat" name="alamat" class="form-control" 
                                  rows="3" required><?= htmlspecialchars($edit['alamat']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_kapasitas">Kapasitas Venue</label>
                        <input type="number" id="edit_kapasitas" name="kapasitas" class="form-control" 
                               value="<?= $edit['kapasitas'] ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="venue.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="update" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Venue
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
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

        // Event listener untuk tombol tambah venue
        const btnTambah = document.getElementById('btnTambahVenue');
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
                window.location.href = 'venue.php';
            }
        });
    </script>
</body>
</html>