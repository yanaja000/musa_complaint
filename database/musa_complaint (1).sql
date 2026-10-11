-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 11, 2026 at 01:46 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `musa_complaint`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int UNSIGNED NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `nama`, `email`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Brian Pc', 'pcbrian899@gmail.com', 'yannpccc', '$2y$10$YcnINe9Mc9MWssSpHNQxguQeFVzZa3NPc8SQeACmiZhIOIx1hfMjK', 'admin', '2026-10-07 11:29:40'),
(2, 'adminn', 'b1685236@gmail.com', 'adminn kuuu', '$2y$10$jTUWj8Q0I56ErTxXblWloua8YJqgIIF6vYXq119wP1RwVKsR272ua', 'admin', '2026-10-08 14:08:33');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int UNSIGNED NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`) VALUES
(1, 'Fasilitas Sekolah', 'Pengaduan mengenai fasilitas dan sarana sekolah.'),
(2, 'Kebersihan', 'Pengaduan mengenai kebersihan lingkungan sekolah.'),
(3, 'Keamanan', 'Pengaduan mengenai keamanan di lingkungan sekolah.'),
(4, 'Guru & Staff', 'Pengaduan mengenai guru dan staff sekolah.'),
(5, 'Pembelajaran', 'Pengaduan mengenai kegiatan dan proses pembelajaran.'),
(6, 'Perundungan / Bullying', 'Pengaduan mengenai perundungan atau bullying.'),
(7, 'Organisasi & Kegiatan', 'Pengaduan mengenai organisasi dan kegiatan sekolah.'),
(8, 'Lingkungan Sekolah', 'Pengaduan mengenai kondisi lingkungan sekolah.'),
(9, 'Pelayanan Sekolah', 'Pengaduan mengenai pelayanan sekolah.'),
(10, 'Saran', 'Saran untuk meningkatkan kualitas sekolah.'),
(11, 'Lainnya', 'Pengaduan yang tidak termasuk kategori lainnya.');

-- --------------------------------------------------------

--
-- Table structure for table `lampiran`
--

CREATE TABLE `lampiran` (
  `id_lampiran` int UNSIGNED NOT NULL,
  `id_pengaduan` int UNSIGNED NOT NULL,
  `nama_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_file` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe_file` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id_pengaduan` int UNSIGNED NOT NULL,
  `id_siswa` int UNSIGNED NOT NULL,
  `id_kategori` int UNSIGNED NOT NULL,
  `id_status` int UNSIGNED NOT NULL,
  `judul` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isi_pengaduan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lokasi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `dilihat_siswa_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaduan`
--

INSERT INTO `pengaduan` (`id_pengaduan`, `id_siswa`, `id_kategori`, `id_status`, `judul`, `isi_pengaduan`, `lokasi`, `created_at`, `updated_at`, `dilihat_siswa_at`) VALUES
(31, 4, 1, 4, 'efejfjefe', 'wdwdwdw', 'uuuu', '2026-10-10 18:49:17', '2026-10-10 18:50:46', '2026-10-11 20:36:04'),
(32, 4, 4, 2, 'satpam', 'wwwew', 'ewfwdas', '2026-10-11 06:52:19', '2026-10-11 06:58:26', '2026-10-11 20:35:54');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_status`
--

CREATE TABLE `riwayat_status` (
  `id_riwayat` int UNSIGNED NOT NULL,
  `id_pengaduan` int UNSIGNED NOT NULL,
  `id_status` int UNSIGNED NOT NULL,
  `id_admin` int UNSIGNED DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `riwayat_status`
--

INSERT INTO `riwayat_status` (`id_riwayat`, `id_pengaduan`, `id_status`, `id_admin`, `catatan`, `created_at`) VALUES
(34, 31, 1, NULL, 'Pengaduan dibuat oleh siswa.', '2026-10-10 18:49:17'),
(35, 31, 3, 1, '', '2026-10-10 18:49:48'),
(36, 31, 4, 1, '', '2026-10-10 18:50:31'),
(37, 32, 1, NULL, 'Pengaduan dibuat oleh siswa.', '2026-10-11 06:52:19'),
(38, 32, 2, 1, '', '2026-10-11 06:58:26');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int UNSIGNED NOT NULL,
  `nis` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kelas` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('siswa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `foto` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nis`, `nama`, `kelas`, `email`, `username`, `password`, `role`, `created_at`, `foto`) VALUES
(3, '000000', 'Brian Pc', 'rpl', 'pcbrian899@gmail.com', 'yann', '$2y$10$Ls1jd3sodK/xFfRwkuxMWOCsrYuRwxZ7jWAHiXhx3RqtzZa8z9KEe', 'siswa', '2026-10-05 16:30:56', NULL),
(4, '333333', 'Brian 123', 'X RPL', 'b1685236@gmail.com', 'yanadmin', '$2y$10$ROk6NILzza.smRr8sIiAv.1YzqswUvtvW.U6uWej78VKity0HxH9G', 'siswa', '2026-10-07 11:22:14', 'https://i.pinimg.com/736x/aa/1d/a8/aa1da8404c288c51dd63e6384bba8a4f.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `status_pengaduan`
--

CREATE TABLE `status_pengaduan` (
  `id_status` int UNSIGNED NOT NULL,
  `nama_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `status_pengaduan`
--

INSERT INTO `status_pengaduan` (`id_status`, `nama_status`, `deskripsi`) VALUES
(1, 'MENUNGGU', 'Pengaduan baru dan belum diproses.'),
(2, 'DIPROSES', 'Pengaduan sedang diperiksa atau ditindaklanjuti.'),
(3, 'DITANGGAPI', 'Admin telah memberikan tanggapan.'),
(4, 'SELESAI', 'Pengaduan telah selesai ditangani.'),
(5, 'DITOLAK', 'Pengaduan tidak dapat diproses.');

-- --------------------------------------------------------

--
-- Table structure for table `tanggapan`
--

CREATE TABLE `tanggapan` (
  `id_tanggapan` int UNSIGNED NOT NULL,
  `id_pengaduan` int UNSIGNED NOT NULL,
  `id_admin` int UNSIGNED DEFAULT NULL,
  `isi_tanggapan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `pengirim` enum('admin','siswa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `id_siswa` int DEFAULT NULL,
  `dihapus_siswa_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tanggapan`
--

INSERT INTO `tanggapan` (`id_tanggapan`, `id_pengaduan`, `id_admin`, `isi_tanggapan`, `created_at`, `pengirim`, `id_siswa`, `dihapus_siswa_at`) VALUES
(9, 32, 1, 'sabarrr ya banggg', '2026-10-11 06:55:15', 'admin', NULL, NULL),
(10, 32, NULL, 'siapp adminnn', '2026-10-11 06:55:37', 'siswa', 4, NULL),
(11, 32, 1, 'siappp bosss', '2026-10-11 06:58:20', 'admin', NULL, NULL),
(12, 32, 1, 'ini siapa yaa', '2026-10-11 07:31:02', 'admin', NULL, NULL),
(13, 32, NULL, 'otwwww', '2026-10-11 07:31:36', 'siswa', 4, NULL),
(14, 32, 1, 'iwak tempee', '2026-10-11 08:00:28', 'admin', NULL, NULL),
(15, 32, 1, 'kkkkk', '2026-10-11 08:01:05', 'admin', NULL, NULL),
(17, 32, NULL, 'iwak tempe', '2026-10-11 13:28:09', 'siswa', 4, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `lampiran`
--
ALTER TABLE `lampiran`
  ADD PRIMARY KEY (`id_lampiran`),
  ADD KEY `idx_lampiran_pengaduan` (`id_pengaduan`);

--
-- Indexes for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id_pengaduan`),
  ADD KEY `idx_pengaduan_siswa` (`id_siswa`),
  ADD KEY `idx_pengaduan_kategori` (`id_kategori`),
  ADD KEY `idx_pengaduan_status` (`id_status`),
  ADD KEY `idx_pengaduan_created` (`created_at`);

--
-- Indexes for table `riwayat_status`
--
ALTER TABLE `riwayat_status`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `idx_riwayat_pengaduan` (`id_pengaduan`),
  ADD KEY `idx_riwayat_status` (`id_status`),
  ADD KEY `idx_riwayat_admin` (`id_admin`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `status_pengaduan`
--
ALTER TABLE `status_pengaduan`
  ADD PRIMARY KEY (`id_status`),
  ADD UNIQUE KEY `nama_status` (`nama_status`);

--
-- Indexes for table `tanggapan`
--
ALTER TABLE `tanggapan`
  ADD PRIMARY KEY (`id_tanggapan`),
  ADD KEY `idx_tanggapan_pengaduan` (`id_pengaduan`),
  ADD KEY `idx_tanggapan_admin` (`id_admin`),
  ADD KEY `idx_tanggapan_thread` (`id_pengaduan`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `lampiran`
--
ALTER TABLE `lampiran`
  MODIFY `id_lampiran` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id_pengaduan` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `riwayat_status`
--
ALTER TABLE `riwayat_status`
  MODIFY `id_riwayat` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `status_pengaduan`
--
ALTER TABLE `status_pengaduan`
  MODIFY `id_status` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tanggapan`
--
ALTER TABLE `tanggapan`
  MODIFY `id_tanggapan` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lampiran`
--
ALTER TABLE `lampiran`
  ADD CONSTRAINT `fk_lampiran_pengaduan` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `fk_pengaduan_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengaduan_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengaduan_status` FOREIGN KEY (`id_status`) REFERENCES `status_pengaduan` (`id_status`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `riwayat_status`
--
ALTER TABLE `riwayat_status`
  ADD CONSTRAINT `fk_riwayat_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_pengaduan` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_riwayat_status` FOREIGN KEY (`id_status`) REFERENCES `status_pengaduan` (`id_status`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tanggapan`
--
ALTER TABLE `tanggapan`
  ADD CONSTRAINT `fk_tanggapan_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tanggapan_pengaduan` FOREIGN KEY (`id_pengaduan`) REFERENCES `pengaduan` (`id_pengaduan`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
