<?php
session_start();
include 'config/koneksi.php';

// Ambil 6 event terbaru
$query_events = mysqli_query($conn, "
    SELECT event.*, venue.nama_venue, venue.alamat,
           (SELECT MIN(harga) FROM tiket WHERE tiket.id_event = event.id_event) as harga_termurah
    FROM event 
    JOIN venue ON event.id_venue = venue.id_venue 
    WHERE event.tanggal >= CURDATE()
    ORDER BY event.tanggal ASC 
    LIMIT 6
");

// Statistik
$total_events = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM event WHERE tanggal >= CURDATE()"))['total'] ?? 0;
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'"))['total'] ?? 0;
$total_venues = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM venue"))['total'] ?? 0;

function safe($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

function formatTanggal($tanggal) {
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($tanggal);
    return date('d', $t) . ' ' . $bulan[(int)date('m', $t)-1] . ' ' . date('Y', $t);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TiketMoo - Platform Tiket Event Online</title>
    
    <!-- CSS Libraries -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { 'sans': ['Inter'] },
                    colors: { 'navy': '#0a2540', 'accent': '#0066cc' }
                }
            }
        };
    </script>
    
    <!-- Custom CSS -->
    <style>
        body { background: #f8fafc; }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: #0066cc;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background: #0a2540;
            transform: translateY(-1px);
        }
        
        .nav-link {
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #0066cc;
            transition: width 0.2s;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="font-sans antialiased">

<!-- ==================== NAVBAR ==================== -->
<nav class="fixed w-full bg-white/90 backdrop-blur-md shadow-sm z-50">
    <div class="max-w-7xl mx-auto px-5 py-3">
        <div class="flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center gap-2">
                <i class="fas fa-ticket-alt text-accent text-xl"></i>
                <span class="font-bold text-xl text-navy">TiketMoo</span>
            </div>
            
            <!-- Menu Desktop -->
            <div class="hidden md:flex gap-6 text-sm">
                <a href="#home" class="nav-link text-gray-600 hover:text-accent">Beranda</a>
                <a href="#events" class="nav-link text-gray-600 hover:text-accent">Event</a>
                <a href="#testimonials" class="nav-link text-gray-600 hover:text-accent">Testimoni</a>
            </div>
            
            <!-- Tombol Auth -->
            <div class="flex gap-3">
                <a href="auth/login.php" class="text-accent hover:text-navy px-4 py-2 transition">Masuk</a>
                <a href="auth/register.php" class="btn-primary text-white px-5 py-2 rounded-lg text-sm font-semibold">Daftar</a>
            </div>
        </div>
    </div>
</nav>

<!-- ==================== HERO SECTION ==================== -->
<section id="home" class="pt-32 pb-20 px-5">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-2 gap-12 items-center">
            <!-- Kiri: Teks -->
            <div data-aos="fade-up">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 leading-tight">
                    Temukan & Pesan
                    <br>
                    <span class="text-accent">Tiket Event</span> Favoritmu
                </h1>
                <p class="text-gray-500 text-lg mt-4 mb-8">
                    Konser, festival, workshop — semua dalam satu platform.
                </p>
                
                <div class="flex gap-4">
                    <a href="auth/register.php" class="btn-primary text-white px-6 py-3 rounded-xl font-semibold">
                        Pesan Sekarang
                    </a>
                    <a href="#events" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-xl hover:border-accent hover:text-accent transition">
                        Lihat Event
                    </a>
                </div>
                
                <!-- Statistik -->
                <div class="flex gap-6 mt-10 pt-6 border-t border-gray-100">
                    <div>
                        <p class="text-2xl font-bold text-navy"><?= number_format($total_events) ?>+</p>
                        <p class="text-xs text-gray-500">Event</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-navy"><?= number_format($total_users) ?>+</p>
                        <p class="text-xs text-gray-500">Pengguna</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-navy"><?= number_format($total_venues) ?>+</p>
                        <p class="text-xs text-gray-500">Venue</p>
                    </div>
                </div>
            </div>
            
            <!-- Kanan: Gambar -->
            <div data-aos="fade-left">
                <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-4 shadow-lg">
                    <img src="assets/1.jpg" alt="Hero" class="rounded-xl w-full" onerror="this.src='https://picsum.photos/600/400'">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== EVENT SECTION ==================== -->
<section id="events" class="py-16 px-5 bg-white">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8" data-aos="fade-up">
            <div>
                <span class="text-accent text-sm font-semibold">Event Mendatang</span>
                <h2 class="text-2xl font-bold text-gray-800">Event Populer</h2>
            </div>
            <a href="events.php" class="text-accent text-sm hover:underline">
                Lihat semua →
            </a>
        </div>
        
        <!-- Grid Event -->
        <?php if (mysqli_num_rows($query_events) > 0): ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($event = mysqli_fetch_assoc($query_events)): 
                    $foto = !empty($event['foto']) ? "uploads/event/" . $event['foto'] : "https://picsum.photos/400/250";
                    $harga = ($event['harga_termurah'] ?? 0) > 0 
                        ? 'Rp ' . number_format($event['harga_termurah'], 0, ',', '.') 
                        : 'Gratis';
                ?>
                    <div class="card-hover bg-white rounded-xl shadow-sm border overflow-hidden" data-aos="fade-up">
                        <img src="<?= $foto ?>" class="h-44 w-full object-cover" alt="<?= safe($event['nama_event']) ?>">
                        
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-gray-800"><?= safe($event['nama_event']) ?></h3>
                                <span class="text-xs font-semibold text-accent"><?= $harga ?></span>
                            </div>
                            
                            <div class="flex items-center gap-3 text-xs text-gray-500 mb-3">
                                <span>
                                    <i class="far fa-calendar-alt mr-1"></i> 
                                    <?= formatTanggal($event['tanggal']) ?>
                                </span>
                                <span>
                                    <i class="fas fa-map-marker-alt mr-1"></i> 
                                    <?= safe($event['nama_venue']) ?>
                                </span>
                            </div>
                            
                            <p class="text-gray-500 text-sm line-clamp-2 mb-4">
                                <?= safe(substr($event['deskripsi'] ?? '', 0, 100)) ?>
                            </p>
                            
                            <a href="auth/login.php" class="block text-center bg-gray-100 text-navy py-2 rounded-lg text-sm font-medium hover:bg-accent hover:text-white transition">
                                Pesan Tiket
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">Belum ada event yang akan datang.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ==================== TESTIMONIAL SECTION ==================== -->
<section id="testimonials" class="py-16 px-5 bg-gray-50">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-10" data-aos="fade-up">
            <span class="text-accent text-sm font-semibold">Testimoni</span>
            <h2 class="text-2xl font-bold text-gray-800 mt-1">Apa Kata Mereka?</h2>
        </div>
        
        <!-- Grid Testimoni -->
        <div class="grid md:grid-cols-3 gap-6">
            <?php 
            $testimonials = [
                ['Proses pemesanan sangat mudah dan cepat. Tiket langsung masuk email. Recomended!', 'Andi Wijaya'],
                ['Customer service responsif. Saya mendapat bantuan dengan cepat.', 'Siti Rahma'],
                ['Banyak pilihan event menarik. Harga terjangkau dan promo berlimpah!', 'Budi Santoso']
            ];
            
            foreach ($testimonials as $t): 
            ?>
                <div class="card-hover bg-white p-6 rounded-xl shadow-sm" data-aos="fade-up">
                    <div class="flex text-yellow-400 text-sm mb-3">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    
                    <p class="text-gray-600 text-sm">"<?= $t[0] ?>"</p>
                    
                    <div class="flex items-center gap-3 mt-4">
                        <div class="w-9 h-9 bg-accent rounded-full flex items-center justify-center text-white text-sm font-semibold">
                            <?= substr($t[1], 0, 1) ?>
                        </div>
                        <div>
                            <p class="font-semibold text-sm"><?= $t[1] ?></p>
                            <p class="text-xs text-gray-400">Pengguna TiketMoo</p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==================== CTA SECTION ==================== -->
<section class="py-16 px-5 bg-navy text-white text-center">
    <div class="max-w-4xl mx-auto" data-aos="fade-up">
        <h2 class="text-2xl md:text-3xl font-bold mb-3">
            Siap untuk Pengalaman Tak Terlupakan?
        </h2>
        <p class="mb-6 text-blue-100">
            Daftar sekarang dan dapatkan akses ke ribuan event menarik
        </p>
        
        <div class="flex gap-4 justify-center">
            <a href="auth/register.php" class="bg-white text-navy px-6 py-2 rounded-lg font-semibold hover:shadow-lg transition">
                Daftar Sekarang
            </a>
            <a href="#events" class="border border-white px-6 py-2 rounded-lg hover:bg-white/10 transition">
                Lihat Event
            </a>
        </div>
    </div>
</section>

<!-- ==================== FOOTER ==================== -->
<footer class="bg-gray-900 text-gray-400 py-10 px-5 text-sm">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-4 gap-6">
            <!-- Kolom 1: Brand -->
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-ticket-alt text-accent"></i>
                    <span class="font-bold text-white">TiketMoo</span>
                </div>
                <p>Platform tiket event terpercaya di Indonesia.</p>
            </div>
            
            <!-- Kolom 2: Perusahaan -->
            <div>
                <h4 class="font-semibold text-white mb-3">Perusahaan</h4>
                <ul class="space-y-1">
                    <li class="hover:text-white cursor-pointer transition">Tentang Kami</li>
                    <li class="hover:text-white cursor-pointer transition">Karir</li>
                    <li class="hover:text-white cursor-pointer transition">Blog</li>
                </ul>
            </div>
            
            <!-- Kolom 3: Bantuan -->
            <div>
                <h4 class="font-semibold text-white mb-3">Bantuan</h4>
                <ul class="space-y-1">
                    <li class="hover:text-white cursor-pointer transition">FAQ</li>
                    <li class="hover:text-white cursor-pointer transition">Kebijakan Privasi</li>
                    <li class="hover:text-white cursor-pointer transition">Syarat & Ketentuan</li>
                </ul>
            </div>
            
            <!-- Kolom 4: Sosial Media -->
            <div>
                <h4 class="font-semibold text-white mb-3">Ikuti Kami</h4>
                <div class="flex gap-3">
                    <i class="fab fa-instagram hover:text-white cursor-pointer transition text-xl"></i>
                    <i class="fab fa-twitter hover:text-white cursor-pointer transition text-xl"></i>
                    <i class="fab fa-facebook hover:text-white cursor-pointer transition text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="text-center text-xs pt-6 border-t border-gray-800 mt-6">
            <i class="fas fa-shield-alt mr-1"></i>
            © <?= date('Y') ?> TiketMoo. All rights reserved.
        </div>
    </div>
</footer>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Inisialisasi AOS
    AOS.init({
        once: true,
        duration: 600
    });
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const nav = document.querySelector('nav');
        if (window.scrollY > 50) {
            nav.style.background = 'rgba(255,255,255,0.98)';
            nav.style.boxShadow = '0 4px 12px rgba(0,0,0,0.05)';
        } else {
            nav.style.background = 'rgba(255,255,255,0.9)';
            nav.style.boxShadow = 'none';
        }
    });
    
    // Smooth scroll untuk anchor link
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
</script>
</body>
</html>