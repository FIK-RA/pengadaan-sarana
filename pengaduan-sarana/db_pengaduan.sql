-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 03, 2026 at 06:25 AM
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
-- Database: `db_pengaduan`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`) VALUES
(1, 'admin', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `aspirasi`
--

CREATE TABLE `aspirasi` (
  `id_aspirasi` int(5) NOT NULL,
  `id_pelaporan` int(5) NOT NULL,
  `status` enum('Menunggu','Proses','Selesai') DEFAULT 'Menunggu',
  `id_kategori` int(5) NOT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aspirasi`
--

INSERT INTO `aspirasi` (`id_aspirasi`, `id_pelaporan`, `status`, `id_kategori`, `feedback`) VALUES
(1, 1, 'Selesai', 4, 'wed'),
(2, 2, 'Selesai', 2, 'ndak nio'),
(3, 3, 'Proses', 1, 'okok'),
(4, 4, 'Selesai', 1, 'apa cona'),
(5, 5, 'Menunggu', 1, NULL),
(6, 6, 'Menunggu', 1, NULL),
(7, 7, 'Selesai', 2, 'alah, co cek labor 3 tadi');

-- --------------------------------------------------------

--
-- Table structure for table `input_aspirasi`
--

CREATE TABLE `input_aspirasi` (
  `id_pelaporan` int(5) NOT NULL,
  `nis` int(10) NOT NULL,
  `id_kategori` int(5) NOT NULL,
  `lokasi` varchar(50) NOT NULL,
  `ket` varchar(255) NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `input_aspirasi`
--

INSERT INTO `input_aspirasi` (`id_pelaporan`, `nis`, `id_kategori`, `lokasi`, `ket`, `tanggal`) VALUES
(1, 123, 4, 'labor 4', 'UPGRADE PC!!!', '2026-09-03'),
(2, 98989092, 2, 'Ruangan Teori', 'meja nnya burik banget njir, udah bolong bolong, dan sudah nyit nyut nyit nyut', '2026-09-03'),
(3, 98989092, 1, 'labor 4', 'atap bocor njir', '2026-09-03'),
(4, 98989092, 1, 'labor 4', 'wd', '2026-09-03'),
(5, 98989092, 1, 'Ruang Teori 17', 'kami butuh pintu', '2026-09-03'),
(6, 5050505, 1, 'Labor 3', 'aku mw pisi geming', '2026-09-03'),
(7, 5050505, 2, 'Labor 3', 'aku mw pici geming baru', '2026-09-03');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(5) NOT NULL,
  `ket_kategori` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `ket_kategori`) VALUES
(1, 'Kebersihan'),
(2, 'Fasilitas Kelas'),
(3, 'Infrastruktur'),
(4, 'Laboratorium');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `nis` int(10) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`nis`, `nama`, `kelas`) VALUES
(123, '', 'XII DPIB'),
(12345, 'Siswa Dummy 1', 'XII RPL 1'),
(12346, 'Siswa Dummy 2', 'XII RPL 2'),
(5050505, 'Ilham Fachri', 'XII RPL'),
(98989092, 'Fikri Ramadhan', 'XII RPL');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `aspirasi`
--
ALTER TABLE `aspirasi`
  ADD PRIMARY KEY (`id_aspirasi`),
  ADD KEY `id_pelaporan` (`id_pelaporan`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `input_aspirasi`
--
ALTER TABLE `input_aspirasi`
  ADD PRIMARY KEY (`id_pelaporan`),
  ADD KEY `nis` (`nis`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`nis`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `aspirasi`
--
ALTER TABLE `aspirasi`
  MODIFY `id_aspirasi` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `input_aspirasi`
--
ALTER TABLE `input_aspirasi`
  MODIFY `id_pelaporan` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `aspirasi`
--
ALTER TABLE `aspirasi`
  ADD CONSTRAINT `aspirasi_ibfk_1` FOREIGN KEY (`id_pelaporan`) REFERENCES `input_aspirasi` (`id_pelaporan`) ON DELETE CASCADE,
  ADD CONSTRAINT `aspirasi_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE;

--
-- Constraints for table `input_aspirasi`
--
ALTER TABLE `input_aspirasi`
  ADD CONSTRAINT `input_aspirasi_ibfk_1` FOREIGN KEY (`nis`) REFERENCES `siswa` (`nis`) ON DELETE CASCADE,
  ADD CONSTRAINT `input_aspirasi_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
