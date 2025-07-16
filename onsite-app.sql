-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 07 Jul 2025 pada 08.05
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onsite-app`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `anggota`
--

CREATE TABLE `anggota` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `anggota`
--

INSERT INTO `anggota` (`id`, `nama`) VALUES
(1, 'Muhammad Fajar Septiawan'),
(2, 'Asy Syams'),
(3, 'Muhammad Akbar Emur Hermawan'),
(4, 'Farzaliano Dwi Putra Heryadi'),
(5, 'Zen Azura'),
(6, 'Revaldi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tambah_onsite`
--

CREATE TABLE `tambah_onsite` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `lokasi` varchar(50) NOT NULL,
  `latitude` varchar(50) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  `keterangan_kegiatan` varchar(255) NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `estimasi_biaya` decimal(10,0) NOT NULL,
  `dokumentasi` varchar(50) NOT NULL,
  `file_csv` varchar(255) DEFAULT NULL,
  `status_pembayaran` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tambah_onsite`
--

INSERT INTO `tambah_onsite` (`id`, `user_id`, `tanggal`, `lokasi`, `latitude`, `longitude`, `keterangan_kegiatan`, `jam_mulai`, `jam_selesai`, `estimasi_biaya`, `dokumentasi`, `file_csv`, `status_pembayaran`) VALUES
(123, 1, '2025-07-04', '', '-6.191098', '106.7615022', 'Maintenance Server', '09:00:00', '12:00:00', 300000, '1751599613-css-3_5968242.png', '1751599613-686749fd2acd3-4juli2025.csv', 'Menunggu'),
(124, 1, '2025-07-04', '', '-6.1911023', '106.7614999', 'Customization Script', '10:00:00', '13:00:00', 200000, '1751600006-css-3_5968242.png', '1751600006-68674b86b8f13-4juli2025.csv', 'Menunggu'),
(125, 1, '2025-07-04', '', '-6.1887663', '106.758861', 'Technical Meeting', '07:00:00', '09:00:00', 150000, '1751600153-css-3_5968242.png', '1751600153-68674c1927e05-4juli2025.csv', 'Menunggu'),
(126, 1, '2025-07-04', '', '-6.1887663', '106.758861', 'test doank', '10:00:00', '11:00:00', 200000, '', '', 'Menunggu'),
(127, 1, '2025-07-04', '', '-6.1887663', '106.758861', 'test lagi', '11:00:00', '12:00:00', 150000, '', '', 'Menunggu'),
(128, 1, '2025-07-04', '', '-6.1887663', '106.758861', 'test lagi', '11:00:00', '12:00:00', 150000, '', '', 'Menunggu'),
(129, 1, '2025-07-04', '', '-6.1887663', '106.758861', 'test ah', '10:00:00', '20:00:00', 150000, '', '', 'Menunggu'),
(130, 1, '2025-07-04', '', '-6.1887663', '106.758861', 'aaaa', '10:00:00', '11:00:00', 111111, '', '', 'Menunggu');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tim_onsite`
--

CREATE TABLE `tim_onsite` (
  `id` int(11) NOT NULL,
  `id_onsite` int(11) NOT NULL,
  `id_anggota` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tim_onsite`
--

INSERT INTO `tim_onsite` (`id`, `id_onsite`, `id_anggota`) VALUES
(89, 123, 2),
(90, 123, 1),
(91, 124, 6),
(92, 124, 5),
(93, 125, 1),
(94, 125, 3),
(95, 126, 2),
(96, 127, 2),
(97, 128, 2),
(98, 129, 2),
(99, 130, 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') NOT NULL,
  `telegram_chat_id` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `nama`, `password`, `role`, `telegram_chat_id`) VALUES
(1, 'ASY.SYAMS', 'Asy Syams', NULL, 'user', '7570636987'),
(2, 'min.syam', 'Admin Syam', NULL, 'admin', '7570636987'),
(3, 'MUHAMMAD.SEPTIAWAN', 'Muhammad Fajar Septiawan', NULL, 'user', '6867744081'),
(4, 'FARZALIANO.HERYADI', 'Farzaliano Putra Heryadi', '', 'user', NULL),
(5, 'MUHAMMAD.HERMAWAN', 'Muhammad Akbar Emur Hermawan', NULL, 'user', NULL),
(6, 'REVALDI.SETIANTO', 'Revaldi Setianto', NULL, 'user', NULL),
(7, 'min.revaldi', 'Admin Revaldi', NULL, 'admin', '5041732059'),
(8, 'ZEN.AZURA', 'Zen Azura', NULL, 'user', NULL),
(9, 'min.zenazura', 'Admin Zen', NULL, 'admin', '6867744081'),
(10, 'maruli.panjaitan', NULL, NULL, 'admin', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `tambah_onsite`
--
ALTER TABLE `tambah_onsite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user` (`user_id`);

--
-- Indeks untuk tabel `tim_onsite`
--
ALTER TABLE `tim_onsite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_onsite` (`id_onsite`),
  ADD KEY `id_anggota` (`id_anggota`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `tambah_onsite`
--
ALTER TABLE `tambah_onsite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT untuk tabel `tim_onsite`
--
ALTER TABLE `tim_onsite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tambah_onsite`
--
ALTER TABLE `tambah_onsite`
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tim_onsite`
--
ALTER TABLE `tim_onsite`
  ADD CONSTRAINT `tim_onsite_ibfk_1` FOREIGN KEY (`id_onsite`) REFERENCES `tambah_onsite` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tim_onsite_ibfk_2` FOREIGN KEY (`id_anggota`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
