-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 12:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `event_tiketizza`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendee`
--

CREATE TABLE `attendee` (
  `id_attendee` int(11) NOT NULL,
  `id_detail` int(11) DEFAULT NULL,
  `kode_tiket` varchar(50) DEFAULT NULL,
  `status_checkin` enum('belum','sudah') DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `waktu_checkin` datetime DEFAULT NULL,
  `cancel_request` enum('pending','approved','rejected') DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `cancel_request_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendee`
--

INSERT INTO `attendee` (`id_attendee`, `id_detail`, `kode_tiket`, `status_checkin`, `created_at`, `waktu_checkin`, `cancel_request`, `cancel_reason`, `cancel_request_date`) VALUES
(40, 31, 'TKT-20260428-9A08A4-01', 'sudah', '2026-04-28 13:41:29', '2026-04-28 13:43:22', NULL, NULL, NULL),
(41, 32, 'TKT-20260428-958304-01', 'sudah', '2026-04-28 13:50:49', '2026-04-28 13:56:04', NULL, NULL, NULL),
(42, 33, 'TKT-20260428-9125DB-01', 'sudah', '2026-04-28 14:00:09', '2026-04-28 14:07:06', NULL, NULL, NULL),
(45, 35, 'TKT-20260428-9EABB7-02', 'sudah', '2026-04-28 14:06:33', '2026-04-28 14:07:09', NULL, NULL, NULL),
(46, 36, 'TKT-20260428-C1D9A3-01', 'sudah', '2026-04-28 14:54:52', '2026-04-28 14:55:11', NULL, NULL, NULL),
(47, 37, 'TKT-20260429-A6293B-01', 'sudah', '2026-04-28 15:31:22', '2026-04-28 15:33:06', 'rejected', 'tidak jadi beli', '2026-04-28 15:31:50'),
(48, 38, 'TKT-20260429-9B9F80-01', 'sudah', '2026-04-28 15:32:41', '2026-04-28 15:33:02', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `event`
--

CREATE TABLE `event` (
  `id_event` int(11) NOT NULL,
  `nama_event` varchar(150) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `id_venue` int(11) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event`
--

INSERT INTO `event` (`id_event`, `nama_event`, `tanggal`, `id_venue`, `deskripsi`, `foto`) VALUES
(17, 'Nadin Amizah', '2026-04-30', 15, 'Nadina', '1777408790_69f11b166ae6e.jpg'),
(18, 'DJ Asyik', '2026-04-29', 16, 'Asyikkk', '1777409876_69f11f54986f1.jpg'),
(19, 'pamungkas', '2026-05-01', 17, 'pamungkas', '1777410297_69f120f9e31c5.jpg'),
(20, 'tari jaranan', '2026-04-30', 18, 'tari jaranan', '1777415359_69f134bfd1cb3.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL,
  `no_order` varchar(50) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_event` int(11) DEFAULT NULL,
  `tanggal_order` datetime DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL,
  `potongan` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `status` enum('pending','paid','cancel') DEFAULT NULL,
  `id_voucher` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id_order`, `no_order`, `id_user`, `id_event`, `tanggal_order`, `subtotal`, `potongan`, `total`, `status`, `id_voucher`) VALUES
(32, 'ORD-20260428-69F11B799CBA1', 13, 17, '2026-04-28 22:41:29', 230000, 34500, 195500, 'pending', 22),
(33, 'ORD-20260428-69F11DA95095B', 14, 17, '2026-04-28 22:50:49', 230000, 34500, 195500, 'pending', 22),
(34, 'ORD-20260428-69F11FD90E6A5', 14, 18, '2026-04-28 23:00:09', 50000, 5000, 45000, 'pending', 23),
(35, 'ORD-20260428-69F121476763E', 14, 19, '2026-04-28 23:06:15', 280000, 0, 280000, 'cancel', NULL),
(36, 'ORD-20260428-69F12159E5029', 15, 19, '2026-04-28 23:06:33', 560000, 0, 560000, 'cancel', NULL),
(37, 'ORD-20260428-69F12CAC165AD', 13, 19, '2026-04-28 23:54:52', 280000, 39200, 240800, 'pending', 24),
(38, 'ORD-20260429-69F1353A5E80C', 16, 20, '2026-04-29 00:31:22', 250000, 35000, 215000, 'pending', 25),
(39, 'ORD-20260429-69F13589B225C', 16, 19, '2026-04-29 00:32:41', 280000, 39200, 240800, 'pending', 24);

-- --------------------------------------------------------

--
-- Table structure for table `order_detail`
--

CREATE TABLE `order_detail` (
  `id_detail` int(11) NOT NULL,
  `id_order` int(11) DEFAULT NULL,
  `id_tiket` int(11) DEFAULT NULL,
  `nama_tiket` varchar(100) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_detail`
--

INSERT INTO `order_detail` (`id_detail`, `id_order`, `id_tiket`, `nama_tiket`, `harga`, `qty`, `subtotal`) VALUES
(31, 32, 18, 'Soraina', 230000, 1, 230000),
(32, 33, 18, 'Soraina', 230000, 1, 230000),
(33, 34, 19, 'DJ Asyik', 50000, 1, 50000),
(34, 35, 20, 'Pamungkas', 280000, 1, 280000),
(35, 36, 20, 'Pamungkas', 280000, 2, 560000),
(36, 37, 20, 'Pamungkas', 280000, 1, 280000),
(37, 38, 21, 'tari', 250000, 1, 250000),
(38, 39, 20, 'Pamungkas', 280000, 1, 280000);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `used`, `created_at`) VALUES
(13, 15, '27be6b12f85f55eba2b86ca18013c5d9bc12cf7a397bb2cdd59136ea85c3e4a8', '2026-04-29 00:15:36', 1, '2026-04-28 21:15:36'),
(14, 13, 'e3bbc80db9d993d16260e0714f7af2c6356f6101197fa631675569874d0f8f0c', '2026-04-29 00:16:15', 1, '2026-04-28 21:16:15'),
(15, 16, 'a5ba23bc97e84a2e3329b57dc507a256a5a2b6705b5466495e2cb150037fd539', '2026-04-29 01:33:22', 1, '2026-04-28 22:33:22');

-- --------------------------------------------------------

--
-- Table structure for table `tiket`
--

CREATE TABLE `tiket` (
  `id_tiket` int(11) NOT NULL,
  `id_event` int(11) DEFAULT NULL,
  `nama_tiket` varchar(50) DEFAULT NULL,
  `harga` int(11) DEFAULT NULL,
  `kuota` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tiket`
--

INSERT INTO `tiket` (`id_tiket`, `id_event`, `nama_tiket`, `harga`, `kuota`) VALUES
(18, 17, 'Soraina', 230000, 48),
(19, 18, 'DJ Asyik', 50000, 29),
(20, 19, 'Pamungkas', 280000, 195),
(21, 20, 'tari', 250000, 99);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('user','petugas','admin') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `role`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$kIn/N0wbAvd0872Fqjoye.WNfXxFM8/artEUuAu0GOWqqm9lXUYAy', 'admin'),
(3, 'Petugas', 'petugas@gmail.com', '$2y$10$PYLRU93FK4.55164zVL0yet5LEQZYLBUsJyNkIkKZxGrG3rjN57A.', 'petugas'),
(13, 'Izzatun Nissa', 'izza@gmail.com', '$2y$10$.V/NezAih2OYaAlHEQP1nO.RwXlDL6ejtalbo3l2tizPcYI0MdxOS', 'user'),
(14, 'Tasya Husna', 'tasya@gmail.com', '$2y$10$gSBpDOR6Rmw0OEnp0mhPyugw6a.T3rEd8NTDVbNcY0qWjWTSxge4.', 'user'),
(15, 'Allen Heath', 'allen@gmail.com', '$2y$10$eTRLPFjmPuHTnXmjGQpFr.UuB4gmHQrZuzTEJMVWyZQCfLoP6zo3q', 'user'),
(16, 'fina rohmatul', 'fina@gmail.com', '$2y$10$GzqqiCUCpq0eGoK0vcpWd.xhNWdJOVJ24zCLHQdHbjrpvWavjr1u6', 'user');

-- --------------------------------------------------------

--
-- Table structure for table `venue`
--

CREATE TABLE `venue` (
  `id_venue` int(11) NOT NULL,
  `nama_venue` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venue`
--

INSERT INTO `venue` (`id_venue`, `nama_venue`, `alamat`, `kapasitas`) VALUES
(15, 'Stadion Utama', 'Magelang, Jawa Tengah', 50),
(16, 'Stadion Selatan', 'Solo, Jawa Tengah', 30),
(17, 'Alun Alun', 'kota magelang', 300),
(18, 'Lapangan Utara', 'Grabag Magelang', 200);

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

CREATE TABLE `voucher` (
  `id_voucher` int(11) NOT NULL,
  `kode_voucher` varchar(20) DEFAULT NULL,
  `potongan` int(11) DEFAULT NULL,
  `id_event` int(11) DEFAULT NULL,
  `id_venue` int(11) DEFAULT NULL,
  `kuota` int(11) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`id_voucher`, `kode_voucher`, `potongan`, `id_event`, `id_venue`, `kuota`, `status`) VALUES
(22, 'SORAI01', 15, 17, NULL, 8, 'aktif'),
(23, 'ASYIK02', 10, 18, NULL, 9, 'aktif'),
(24, 'BAMBINA03', 14, 19, NULL, 17, 'aktif'),
(25, 'TARI04', 14, 20, NULL, 14, 'aktif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendee`
--
ALTER TABLE `attendee`
  ADD PRIMARY KEY (`id_attendee`),
  ADD KEY `id_detail` (`id_detail`);

--
-- Indexes for table `event`
--
ALTER TABLE `event`
  ADD PRIMARY KEY (`id_event`),
  ADD KEY `id_venue` (`id_venue`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id_order`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_voucher` (`id_voucher`);

--
-- Indexes for table `order_detail`
--
ALTER TABLE `order_detail`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_order` (`id_order`),
  ADD KEY `id_tiket` (`id_tiket`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tiket`
--
ALTER TABLE `tiket`
  ADD PRIMARY KEY (`id_tiket`),
  ADD KEY `id_event` (`id_event`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- Indexes for table `venue`
--
ALTER TABLE `venue`
  ADD PRIMARY KEY (`id_venue`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`id_voucher`),
  ADD KEY `fk_voucher_to_event` (`id_event`),
  ADD KEY `fk_voucher_to_venue` (`id_venue`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendee`
--
ALTER TABLE `attendee`
  MODIFY `id_attendee` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `event`
--
ALTER TABLE `event`
  MODIFY `id_event` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id_order` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `order_detail`
--
ALTER TABLE `order_detail`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tiket`
--
ALTER TABLE `tiket`
  MODIFY `id_tiket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `venue`
--
ALTER TABLE `venue`
  MODIFY `id_venue` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `id_voucher` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendee`
--
ALTER TABLE `attendee`
  ADD CONSTRAINT `attendee_ibfk_1` FOREIGN KEY (`id_detail`) REFERENCES `order_detail` (`id_detail`);

--
-- Constraints for table `event`
--
ALTER TABLE `event`
  ADD CONSTRAINT `event_ibfk_1` FOREIGN KEY (`id_venue`) REFERENCES `venue` (`id_venue`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`id_voucher`) REFERENCES `voucher` (`id_voucher`);

--
-- Constraints for table `order_detail`
--
ALTER TABLE `order_detail`
  ADD CONSTRAINT `order_detail_ibfk_1` FOREIGN KEY (`id_order`) REFERENCES `orders` (`id_order`),
  ADD CONSTRAINT `order_detail_ibfk_2` FOREIGN KEY (`id_tiket`) REFERENCES `tiket` (`id_tiket`);

--
-- Constraints for table `tiket`
--
ALTER TABLE `tiket`
  ADD CONSTRAINT `tiket_ibfk_1` FOREIGN KEY (`id_event`) REFERENCES `event` (`id_event`);

--
-- Constraints for table `voucher`
--
ALTER TABLE `voucher`
  ADD CONSTRAINT `fk_voucher_to_event` FOREIGN KEY (`id_event`) REFERENCES `event` (`id_event`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_voucher_to_venue` FOREIGN KEY (`id_venue`) REFERENCES `venue` (`id_venue`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
