<?php
session_start();
include '../config/koneksi.php';

// =======================
// TAMBAH TIKET
// =======================
if (isset($_POST['tambah'])) {
    $id_event = $_POST['id_event'];
    $nama = $_POST['nama_tiket'];
    $harga = $_POST['harga'];
    $kuota = $_POST['kuota'];

    mysqli_query($conn, "
        INSERT INTO tiket (id_event, nama_tiket, harga, kuota)
        VALUES ('$id_event','$nama','$harga','$kuota')
    ");

    header("Location: tiket.php");
}

// =======================
// EDIT TIKET
// =======================
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $id_event = $_POST['id_event'];
    $nama = $_POST['nama_tiket'];
    $harga = $_POST['harga'];
    $kuota = $_POST['kuota'];

    mysqli_query($conn, "
        UPDATE tiket 
        SET id_event='$id_event',
            nama_tiket='$nama',
            harga='$harga',
            kuota='$kuota'
        WHERE id_tiket=$id
    ");

    header("Location: tiket.php");
}

// =======================
// HAPUS TIKET
// =======================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    mysqli_query($conn, "DELETE FROM tiket WHERE id_tiket=$id");

    header("Location: tiket.php");
}

// =======================
// AMBIL DATA TIKET
// =======================
$data = mysqli_query($conn, "
    SELECT tiket.*, event.nama_event 
    FROM tiket
    JOIN event ON tiket.id_event = event.id_event
    ORDER BY event.tanggal DESC
");

// event list
$event = mysqli_query($conn, "SELECT * FROM event ORDER BY nama_event ASC");

// =======================
// MODE EDIT
// =======================
$editData = null;

if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    $editData = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT * FROM tiket WHERE id_tiket=$id
    "));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tiket | Event Ticket</title>
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

        select.form-control {
            appearance: none;
            cursor: pointer;
        }

        .input-group {
            position: relative;
        }

        .input-group .currency {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-weight: 500;
            pointer-events: none;
        }

        .input-group .form-control {
            padding-left: 2.5rem;
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

        .badge-event {
            background-color: #e0e7ff;
            color: #4338ca;
        }

        .badge-price {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-quota {
            background-color: #fed7aa;
            color: #9a3412;
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
                <h1 class="page-title">Manajemen Tiket</h1>
                <p class="page-subtitle">Kelola jenis tiket, harga, dan kuota untuk setiap event</p>
            </div>
            <button type="button" class="btn btn-primary" id="btnTambahTiket">
                <i class="fas fa-plus-circle"></i> Tambah Tiket Baru
            </button>
        </div>

        <!-- TABEL DATA TIKET -->
        <div class="card">
            <div class="table-responsive" style="padding: 1.5rem;">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="25%">Event</th>
                            <th width="25%">Nama Tiket</th>
                            <th width="20%">Harga</th>
                            <th width="15%">Kuota</th>
                            <th width="10%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if(mysqli_num_rows($data) > 0) {
                            while($row = mysqli_fetch_assoc($data)) { 
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <span class="badge badge-event">
                                    <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($row['nama_event']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($row['nama_tiket']) ?></td>
                            <td>
                                <span class="badge badge-price">
                                    <i class="fas fa-tag"></i> Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-quota">
                                    <i class="fas fa-users"></i> <?= number_format($row['kuota'], 0, ',', '.') ?> tiket
                                </span>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <a href="?edit=<?= $row['id_tiket'] ?>" class="btn btn-edit" title="Edit Tiket">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?hapus=<?= $row['id_tiket'] ?>" class="btn btn-danger" title="Hapus Tiket"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus tiket ini? Data yang dihapus tidak dapat dikembalikan.')">
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
                                <i class="fas fa-ticket-alt"></i>
                                Belum ada data tiket.
                                <p style="margin-top: 0.5rem; font-size: 0.875rem;">Silakan tambah tiket baru dengan tombol di atas.</p>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH TIKET -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-plus-circle"></i>
                    Tambah Tiket Baru
                </h3>
                <button type="button" class="modal-close" onclick="closeModal('modalTambah')">&times;</button>
            </div>
            <form method="POST" id="formTambah">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="id_event">Event</label>
                        <div class="input-group" style="position: relative;">
                            <select id="id_event" name="id_event" class="form-control" required>
                                <option value="" disabled selected>-- Pilih Event --</option>
                                <?php 
                                mysqli_data_seek($event, 0);
                                while($e = mysqli_fetch_assoc($event)) { ?>
                                    <option value="<?= $e['id_event'] ?>">
                                        <?= htmlspecialchars($e['nama_event']) ?> - <?= date('d F Y', strtotime($e['tanggal'])) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nama_tiket">Nama Tiket</label>
                        <input type="text" id="nama_tiket" name="nama_tiket" class="form-control" 
                               placeholder="Contoh: VIP, Festival, Early Bird..." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="harga">Harga Tiket</label>
                        <div class="input-group" style="position: relative;">
                        <span class="currency">Rp</span>
                        <input type="number" id="harga" name="harga" class="form-control" style="padding-left: 2.5rem;" placeholder="0" required min="1" oninput="this.value = this.value.replace(/^-|^0+/, '')">
                    </div>
                    </div>

       <div class="form-group">
    <label class="form-label" for="kuota">Kuota Tiket</label>
    <input type="number" id="kuota" name="kuota" class="form-control" 
           placeholder="Jumlah maksimum tiket yang tersedia" required
           min="1" oninput="this.value = this.value.replace(/^-|^0+/, '')">
    <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 0.25rem; display: block;">
        <i class="fas fa-info-circle"></i> Jumlah tiket yang tersedia untuk dijual
    </small>
</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Tiket
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT TIKET -->
    <?php if ($editData): ?>
    <div id="modalEdit" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-edit"></i>
                    Edit Data Tiket
                </h3>
                <a href="tiket.php" class="modal-close" style="text-decoration: none;">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $editData['id_tiket'] ?>">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="edit_id_event">Event</label>
                        <div class="input-group" style="position: relative;">
                            <select id="edit_id_event" name="id_event" class="form-control" required>
                                <option value="" disabled>-- Pilih Event --</option>
                                <?php 
                                mysqli_data_seek($event, 0);
                                while($e = mysqli_fetch_assoc($event)) { ?>
                                    <option value="<?= $e['id_event'] ?>"
                                        <?= (isset($editData) && $editData['id_event'] == $e['id_event']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['nama_event']) ?> - <?= date('d F Y', strtotime($e['tanggal'])) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <i class="fas fa-chevron-down dropdown-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_nama_tiket">Nama Tiket</label>
                        <input type="text" id="edit_nama_tiket" name="nama_tiket" class="form-control" 
                               value="<?= htmlspecialchars($editData['nama_tiket']) ?>" required>
                    </div>

                 <div class="form-group">
    <label class="form-label" for="edit_harga">Harga Tiket</label>
    <div class="input-group" style="position: relative;">
        <span class="currency">Rp</span>
        <input type="number" id="edit_harga" name="harga" class="form-control" 
               style="padding-left: 2.5rem;" value="<?= $editData['harga'] ?>" required
               min="1" oninput="this.value = this.value.replace(/^-|^0+/, '')">
    </div>
</div>
                    <div class="form-group">
    <label class="form-label" for="edit_kuota">Kuota Tiket</label>
    <input type="number" id="edit_kuota" name="kuota" class="form-control" 
           value="<?= $editData['kuota'] ?>" required
           min="1" oninput="this.value = this.value.replace(/^-|^0+/, '')">
</div>
                </div>
                <div class="modal-footer">
                    <a href="tiket.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="edit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
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

        // Event listener untuk tombol tambah tiket
        const btnTambah = document.getElementById('btnTambahTiket');
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
                window.location.href = 'tiket.php';
            }
        });
    </script>
</body>
</html>