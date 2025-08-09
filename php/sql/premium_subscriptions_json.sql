-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2025 at 06:58 AM
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
-- Database: `viegrand`
--

-- --------------------------------------------------------

--
-- Table structure for table `premium_subscriptions_json`
--

CREATE TABLE `premium_subscriptions_json` (
  `premium_key` char(16) NOT NULL,
  `young_person_key` char(8) NOT NULL,
  `elderly_keys` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`elderly_keys`)),
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `note` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `premium_subscriptions_json`
--

INSERT INTO `premium_subscriptions_json` (`premium_key`, `young_person_key`, `elderly_keys`, `start_date`, `end_date`, `note`) VALUES
('0600000000010825', 'ebcc8lin', '[]', '2025-08-06 06:49:08', '2025-09-06 06:49:08', ''),
('0600000000020825', '0diyjwwt', '[]', '2025-08-06 06:56:26', '2025-09-05 06:56:26', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `premium_subscriptions_json`
--
ALTER TABLE `premium_subscriptions_json`
  ADD PRIMARY KEY (`premium_key`),
  ADD KEY `idx_young_person` (`young_person_key`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;