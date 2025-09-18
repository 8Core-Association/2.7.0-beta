-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 17, 2025 at 12:19 PM
-- Server version: 10.5.26-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `demo_seup1`
--

-- --------------------------------------------------------

--
-- Table structure for table `llx_a_posiljatelji`
--

CREATE TABLE `llx_a_posiljatelji` (
  `rowid` int(10) UNSIGNED NOT NULL,
  `naziv` varchar(255) NOT NULL,
  `adresa` varchar(255) DEFAULT NULL,
  `oib` varchar(32) DEFAULT NULL,
  `telefon` varchar(64) DEFAULT NULL,
  `kontakt_osoba` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `datec` datetime NOT NULL DEFAULT current_timestamp(),
  `tms` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `llx_a_posiljatelji`
--

INSERT INTO `llx_a_posiljatelji` (`rowid`, `naziv`, `adresa`, `oib`, `telefon`, `kontakt_osoba`, `email`, `datec`, `tms`) VALUES
(1, 'Tomislav G', 'Grgin dol 46, Požega', '32165498733', '+385998510718', 'Marko Fušer', '8core.eu@gmail.com', '2025-09-17 11:41:37', '2025-09-17 09:41:37'),
(2, 'Perica Ešegović-Bosanac', '', '32165498788', '+385998510718', '', 'hrvojesertic@gmail.com', '2025-09-17 12:06:52', '2025-09-17 10:06:52'),
(3, 'Perica Ešegović-Krkan', 'Grgin dol 11, Požega', '32165498755', '+385998510333', 'Marko Peder', 'andrea@8core.hr', '2025-09-17 12:08:34', '2025-09-17 10:08:34'),
(4, 'Marijana grizelj-paulić', 'kalvarija 29', '12345678910', '09944598306', 'Marijana', 'marijana@pszupanija.hr', '2025-09-17 13:12:57', '2025-09-17 11:12:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `llx_a_posiljatelji`
--
ALTER TABLE `llx_a_posiljatelji`
  ADD PRIMARY KEY (`rowid`),
  ADD UNIQUE KEY `uq_oib` (`oib`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `llx_a_posiljatelji`
--
ALTER TABLE `llx_a_posiljatelji`
  MODIFY `rowid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
