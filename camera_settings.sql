-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2024 at 11:46 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sales`
--

-- --------------------------------------------------------

--
-- Table structure for table `camera_settings`
--

CREATE TABLE `camera_settings` (
  `id` int(11) NOT NULL,
  `camera_name` varchar(100) NOT NULL,
  `camera_bleak_name` varchar(50) DEFAULT NULL,
  `camera_ip_address` varchar(50) DEFAULT NULL,
  `camera_username` varchar(50) DEFAULT NULL,
  `camera_password` varchar(50) DEFAULT NULL,
  `camera_crt_path` varchar(5000) DEFAULT NULL,
  `camera_crt` varchar(50) DEFAULT NULL,
  `camera_macaddress` varchar(50) DEFAULT NULL,
  `output` varchar(1000) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `camera_settings`
--

INSERT INTO `camera_settings` (`id`, `camera_name`, `camera_bleak_name`, `camera_ip_address`, `camera_username`, `camera_password`, `camera_crt_path`, `camera_crt`, `camera_macaddress`, `output`, `status`) VALUES
(1, 'GoPro 7363', NULL, '10.3.47.24', 'gopro', '5yZp6LfijIWw', 'certificates/GoPro7363.crt', NULL, '04574738d7c3', '\nPinging 10.3.47.24 with 32 bytes of data:\nReply from 10.3.47.24: bytes=32 time=175ms TTL=64\n\nPing statistics for 10.3.47.24:\n    Packets: Sent = 1, Received = 1, Lost = 0 (0% loss),\nApproximate round trip times in milli-seconds:\n    Minimum = 175ms, Maximum = 175ms, Average = 175ms\n', 1),
(2, 'GoPro 7418', NULL, '10.3.47.34', 'gopro', 'EOoN8LdV64m4', 'certificates/GoPro7418.crt', NULL, '04574725f97d', NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `camera_settings`
--
ALTER TABLE `camera_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `camera_settings`
--
ALTER TABLE `camera_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
