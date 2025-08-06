-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 06, 2025 at 06:18 AM
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
-- premium_key format: dd0000000000mmyy (day + 10-digit ID + month/year)
-- Example: 0600000000010825 (6th August 2025, subscription ID 1)
--

CREATE TABLE `premium_subscriptions_json` (
  `premium_key` char(16) NOT NULL COMMENT 'Format: dd0000000000mmyy (day + 10-digit auto-increment + month/year)',
  `young_person_key` char(8) NOT NULL COMMENT 'Private key of the young person (main user)',
  `elderly_keys` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`elderly_keys`)) COMMENT 'JSON array of elderly persons private keys',
  `start_date` datetime NOT NULL COMMENT 'Premium subscription start date',
  `end_date` datetime NOT NULL COMMENT 'Premium subscription end date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Premium subscription tracking with family grouping';

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