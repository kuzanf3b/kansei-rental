-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 20, 2026 at 02:39 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_bayar`
--

DROP TABLE IF EXISTS `tbl_bayar`;
CREATE TABLE IF NOT EXISTS `tbl_bayar` (
  `id_bayar` int NOT NULL AUTO_INCREMENT,
  `id_kembali` int DEFAULT NULL,
  `tgl_bayar` date DEFAULT NULL,
  `total_bayar` decimal(10,2) NOT NULL,
  `status` enum('lunas','belum lunas') DEFAULT NULL,
  PRIMARY KEY (`id_bayar`),
  KEY `fk_bayar_kembali` (`id_kembali`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_kembali`
--

DROP TABLE IF EXISTS `tbl_kembali`;
CREATE TABLE IF NOT EXISTS `tbl_kembali` (
  `id_kembali` int NOT NULL AUTO_INCREMENT,
  `id_transaksi` int DEFAULT NULL,
  `tgl_kembali` date DEFAULT NULL,
  `kondisi_mobil` text,
  `denda` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_kembali`),
  KEY `fk_kembali_transaksi` (`id_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_member`
--

DROP TABLE IF EXISTS `tbl_member`;
CREATE TABLE IF NOT EXISTS `tbl_member` (
  `nik` int NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jk` enum('L','P') DEFAULT NULL,
  `telp` varchar(15) DEFAULT NULL,
  `alamat` text,
  `user` varchar(50) DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`nik`),
  UNIQUE KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tbl_member`
--

INSERT INTO `tbl_member` (`nik`, `nama`, `jk`, `telp`, `alamat`, `user`, `pass`) VALUES
(2147483647, 'Budi Santoso', 'L', '081234567890', 'Jakarta Selatan', 'budi', '$2y$10$PNq3qu/gYc/h6U5kqwfldu95uvoWILnvhm9KsZOzOeWIHlVzWwf96');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_mobil`
--

DROP TABLE IF EXISTS `tbl_mobil`;
CREATE TABLE IF NOT EXISTS `tbl_mobil` (
  `nopol` varchar(10) NOT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `tahun` year DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT NULL,
  `foto` varchar(50) DEFAULT NULL,
  `status` enum('tersedia','tidak') NOT NULL,
  PRIMARY KEY (`nopol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_transaksi`
--

DROP TABLE IF EXISTS `tbl_transaksi`;
CREATE TABLE IF NOT EXISTS `tbl_transaksi` (
  `id_transaksi` int NOT NULL AUTO_INCREMENT,
  `nik` int DEFAULT NULL,
  `nopol` varchar(10) DEFAULT NULL,
  `tgl_booking` date DEFAULT NULL,
  `tgl_ambil` date DEFAULT NULL,
  `tgl_kembali` date DEFAULT NULL,
  `supir` tinyint(1) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `downpayment` decimal(10,2) DEFAULT NULL,
  `kekurangan` decimal(10,2) DEFAULT NULL,
  `status` enum('booking','approve','ambil','kembali') DEFAULT NULL,
  PRIMARY KEY (`id_transaksi`),
  KEY `fk_transaksi_member` (`nik`),
  KEY `fk_transaksi_mobil` (`nopol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

DROP TABLE IF EXISTS `tbl_user`;
CREATE TABLE IF NOT EXISTS `tbl_user` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `user` varchar(50) DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  `lvl` enum('admin','petugas') NOT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_bayar`
--
ALTER TABLE `tbl_bayar`
  ADD CONSTRAINT `fk_bayar_kembali` FOREIGN KEY (`id_kembali`) REFERENCES `tbl_kembali` (`id_kembali`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_kembali`
--
ALTER TABLE `tbl_kembali`
  ADD CONSTRAINT `fk_kembali_transaksi` FOREIGN KEY (`id_transaksi`) REFERENCES `tbl_transaksi` (`id_transaksi`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tbl_transaksi`
--
ALTER TABLE `tbl_transaksi`
  ADD CONSTRAINT `fk_transaksi_member` FOREIGN KEY (`nik`) REFERENCES `tbl_member` (`nik`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_transaksi_mobil` FOREIGN KEY (`nopol`) REFERENCES `tbl_mobil` (`nopol`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
