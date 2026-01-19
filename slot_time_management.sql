-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 22, 2025 at 03:50 AM
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
-- Database: `slot_time_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `activity_type` enum('gate_change','status_change','late_arrival','early_arrival','waiting_time','gate_activation','gate_deactivation') NOT NULL,
  `description` text NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `slot_id`, `activity_type`, `description`, `old_value`, `new_value`, `created_by`, `created_at`) VALUES
(1, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-02 03:41:10'),
(2, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-02 04:39:39'),
(3, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-02 04:39:42'),
(4, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-02 04:39:58'),
(5, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-02 04:41:26'),
(6, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-02 04:41:31'),
(7, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-02 04:41:33'),
(8, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-02 04:41:34'),
(9, 1, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 04:46:59'),
(10, 1, 'early_arrival', 'Truck arrived on time/early', NULL, NULL, 3, '2025-12-02 04:47:05'),
(11, 1, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 04:47:05'),
(12, 1, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 04:47:39'),
(13, 2, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 04:54:23'),
(14, 2, 'late_arrival', 'Truck arrived late', NULL, NULL, 3, '2025-12-02 04:54:28'),
(15, 2, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 04:54:28'),
(16, 2, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 04:54:30'),
(17, 3, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 06:04:51'),
(18, 3, 'late_arrival', 'Truck arrived late', NULL, NULL, 3, '2025-12-02 06:05:07'),
(19, 3, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 06:05:07'),
(20, 3, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 06:05:31'),
(21, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-02 06:10:21'),
(22, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-02 06:10:28'),
(23, 4, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 07:08:12'),
(24, 4, 'early_arrival', 'Truck arrived on time/early', NULL, NULL, 3, '2025-12-02 07:08:27'),
(25, 4, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 07:08:27'),
(26, 4, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 07:09:14'),
(27, 5, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 07:12:51'),
(28, 6, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 07:14:05'),
(29, 5, 'early_arrival', 'Truck arrived on time/early', NULL, NULL, 3, '2025-12-02 07:14:14'),
(30, 5, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 07:14:14'),
(31, 6, 'early_arrival', 'Truck arrived on time/early', NULL, NULL, 3, '2025-12-02 07:14:28'),
(32, 6, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 07:14:28'),
(33, 7, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 07:15:04'),
(34, 7, 'early_arrival', 'Truck arrived on time/early', NULL, NULL, 3, '2025-12-02 07:15:13'),
(35, 7, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 07:15:13'),
(36, 7, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 07:15:37'),
(37, 6, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 07:15:44'),
(38, 5, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 07:15:55'),
(39, 8, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 07:39:23'),
(40, 8, 'early_arrival', 'Truck arrived on time/early', NULL, NULL, 3, '2025-12-02 07:47:56'),
(41, 8, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 07:47:56'),
(42, 8, 'status_change', 'Slot completed', NULL, NULL, 3, '2025-12-02 08:21:44'),
(43, 9, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 09:29:58'),
(44, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-02 09:31:52'),
(45, 10, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 09:32:39'),
(46, 11, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-02 09:34:51'),
(47, 10, 'late_arrival', 'Truck arrived late', NULL, NULL, 3, '2025-12-02 09:35:32'),
(48, 10, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 09:35:32'),
(49, 11, 'early_arrival', 'Truck arrived on time/early', NULL, NULL, 3, '2025-12-02 09:35:49'),
(50, 11, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-02 09:35:49'),
(51, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-03 00:49:22'),
(52, 12, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 01:04:25'),
(53, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-03 01:14:28'),
(54, 13, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 01:46:25'),
(55, 14, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 01:48:00'),
(56, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-03 01:48:58'),
(57, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-03 01:50:34'),
(58, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-03 01:54:58'),
(59, NULL, 'status_change', 'User logged out', NULL, NULL, 3, '2025-12-03 01:54:59'),
(60, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-03 01:55:16'),
(61, 15, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 02:07:57'),
(62, 15, 'late_arrival', 'Truck arrived late with ticket TWH22512030002 and SJ 2314123142321', NULL, NULL, 3, '2025-12-03 02:29:10'),
(63, 15, 'status_change', 'Slot started', NULL, NULL, 3, '2025-12-03 02:29:10'),
(64, 15, 'status_change', 'Slot completed with MAT DOC 21232d22d1131`3, SJ 2314123142321, truck wingbox, vehicle B672818921HTY, driver 902i9921', NULL, NULL, 3, '2025-12-03 02:29:44'),
(65, 16, 'status_change', 'Unplanned transaction recorded as completed', NULL, NULL, 3, '2025-12-03 03:47:17'),
(66, 17, 'status_change', 'Unplanned transaction recorded as completed', NULL, NULL, 3, '2025-12-03 04:00:29'),
(67, 18, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 06:30:15'),
(68, 19, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 06:46:02'),
(69, 20, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 06:46:54'),
(70, 21, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-03 09:41:52'),
(71, 21, 'early_arrival', 'Truck arrived on time/early at WH2 - Gate G1 with ticket TWH22512030006 and SJ 13242423', NULL, NULL, 3, '2025-12-03 09:42:37'),
(72, 21, 'status_change', 'Slot started at WH2 - Gate G1', NULL, NULL, 3, '2025-12-03 09:42:37'),
(73, 21, 'status_change', 'Slot completed with MAT DOC 34436354345, SJ 13242423, truck wingbox, vehicle B66577YTR, driver 32RI9329', NULL, NULL, 3, '2025-12-03 09:43:07'),
(74, 22, 'status_change', 'Unplanned transaction recorded as completed', NULL, NULL, 3, '2025-12-03 09:54:05'),
(75, 23, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-04 01:25:49'),
(76, 23, 'late_arrival', 'Truck arrived late at WH2 - Gate G1 with ticket TWH22512040001 and SJ 34324234232', NULL, NULL, 3, '2025-12-04 01:56:16'),
(77, 23, 'status_change', 'Slot started at WH2 - Gate G1', NULL, NULL, 3, '2025-12-04 01:56:16'),
(78, 19, 'late_arrival', 'Truck arrived late at WH2 - Gate G1 with ticket TWH22512030004 and SJ 323324233432', NULL, NULL, 3, '2025-12-04 04:35:46'),
(79, 19, 'status_change', 'Slot started at WH2 - Gate G1', NULL, NULL, 3, '2025-12-04 04:35:46'),
(80, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-05 03:44:55'),
(81, NULL, 'status_change', 'User logged in', NULL, NULL, 4, '2025-12-05 03:49:14'),
(82, NULL, 'status_change', 'User logged out', NULL, NULL, 3, '2025-12-05 03:52:31'),
(83, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-05 03:52:38'),
(84, 20, 'status_change', 'Slot cancelled', NULL, NULL, 4, '2025-12-05 06:04:02'),
(85, 23, 'status_change', 'Slot completed with MAT DOC w3w3232, SJ 34324234232, truck Fuso, vehicle B8U92302KHT, driver B8U92302KHT', NULL, NULL, 3, '2025-12-05 06:06:33'),
(86, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-05 08:01:04'),
(87, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-05 08:01:07'),
(88, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-05 08:51:45'),
(89, 24, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-05 09:39:26'),
(90, 24, 'early_arrival', 'Truck arrived on time/early at WH1 - Gate G1 with ticket A25L0001 and SJ 234reree', NULL, NULL, 3, '2025-12-05 09:42:29'),
(91, 24, 'status_change', 'Slot started at WH1 - Gate G1', NULL, NULL, 3, '2025-12-05 09:42:29'),
(92, 24, 'status_change', 'Slot completed with MAT DOC dsdas, SJ 234reree, truck CDD/CDE, vehicle 334356, driver tes', NULL, NULL, 3, '2025-12-05 09:47:09'),
(93, 25, 'status_change', 'Unplanned transaction recorded as completed', NULL, NULL, 3, '2025-12-05 09:58:07'),
(94, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-05 09:59:15'),
(95, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-05 09:59:19'),
(96, NULL, 'gate_deactivation', 'Gate WH2 G1 deactivated', '{\"gate_id\":2,\"old\":1}', '{\"gate_id\":2,\"new\":0}', 3, '2025-12-05 09:59:20'),
(97, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-05 09:59:25'),
(98, NULL, 'gate_activation', 'Gate WH2 G1 activated', '{\"gate_id\":2,\"old\":0}', '{\"gate_id\":2,\"new\":1}', 3, '2025-12-05 10:01:39'),
(99, NULL, 'gate_activation', 'Gate WH2 G2 activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-05 10:01:41'),
(100, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-08 00:44:09'),
(101, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-08 01:21:17'),
(102, 18, 'status_change', 'Slot cancelled', NULL, NULL, 3, '2025-12-08 02:07:56'),
(103, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-08 02:22:57'),
(104, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-08 02:22:59'),
(105, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-08 02:22:59'),
(106, NULL, 'gate_deactivation', 'Gate WH2 G2 deactivated', '{\"gate_id\":3,\"old\":1}', '{\"gate_id\":3,\"new\":0}', 3, '2025-12-08 02:22:59'),
(107, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-08 06:03:44'),
(108, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-09 01:15:43'),
(109, 26, 'status_change', 'Unplanned transaction recorded as completed', NULL, NULL, 3, '2025-12-09 01:20:21'),
(110, NULL, 'status_change', 'User logged out', NULL, NULL, 3, '2025-12-09 03:09:54'),
(111, NULL, 'status_change', 'User logged in', NULL, NULL, NULL, '2025-12-09 03:10:07'),
(112, NULL, 'status_change', 'User logged out', NULL, NULL, NULL, '2025-12-09 03:10:13'),
(113, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-09 03:13:29'),
(114, NULL, 'gate_deactivation', 'WH2 - Gate B deactivated', '{\"gate_id\":2,\"old\":1}', '{\"gate_id\":2,\"new\":0}', 3, '2025-12-09 04:43:06'),
(115, NULL, 'gate_activation', 'WH2 - Gate B activated', '{\"gate_id\":2,\"old\":0}', '{\"gate_id\":2,\"new\":1}', 3, '2025-12-09 04:45:25'),
(116, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-09 06:34:45'),
(117, 27, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-09 07:15:56'),
(118, 28, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-09 07:19:38'),
(119, 29, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-09 07:25:12'),
(120, 27, 'status_change', 'Arrival recorded at WH1 - Gate A with ticket A25L0002 and SJ tesss', NULL, NULL, 3, '2025-12-09 08:39:19'),
(121, 27, 'status_change', 'Arrival details updated', NULL, NULL, 3, '2025-12-09 08:39:26'),
(122, 27, 'status_change', 'Arrival details updated', NULL, NULL, 3, '2025-12-09 08:39:34'),
(123, 27, 'status_change', 'Arrival details updated', NULL, NULL, 3, '2025-12-09 08:39:48'),
(124, 30, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-09 08:48:29'),
(125, 30, 'status_change', 'Arrival recorded at WH1 - Gate A with ticket A25L0004 and SJ 43243243232342', NULL, NULL, 3, '2025-12-09 08:49:31'),
(126, 28, 'status_change', 'Slot cancelled', NULL, NULL, 3, '2025-12-09 09:12:13'),
(127, 31, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-09 09:23:52'),
(128, 10, 'status_change', 'Slot completed with MAT DOC 325325323, SJ 3224233242, truck Kontainer 40ft (Louse), vehicle B66577YTR, driver effesf', NULL, NULL, 3, '2025-12-09 09:31:08'),
(129, 19, 'status_change', 'Slot completed with MAT DOC 45432432, SJ 323324233432, truck Kontainer 20ft (Louse), vehicle B8U92302KHT, driver RTTG', NULL, NULL, 3, '2025-12-09 09:31:38'),
(130, 9, 'status_change', 'Slot cancelled', NULL, NULL, 3, '2025-12-09 09:33:41'),
(131, 11, 'status_change', 'Slot completed with MAT DOC 4442563, SJ 565334543, truck Kontainer 40ft (paletize), vehicle B672818921HTY, driver effesf', NULL, NULL, 3, '2025-12-09 09:34:09'),
(132, 30, 'status_change', 'Slot cancelled', NULL, NULL, 3, '2025-12-09 09:41:33'),
(133, 27, 'late_arrival', 'Truck arrived late at WH1 - Gate A', NULL, NULL, 3, '2025-12-09 09:41:50'),
(134, 27, 'status_change', 'Slot started at WH1 - Gate A', NULL, NULL, 3, '2025-12-09 09:41:50'),
(135, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-09 10:01:15'),
(136, NULL, 'status_change', 'User logged out', NULL, NULL, 3, '2025-12-09 10:03:58'),
(137, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-10 00:57:07'),
(138, 31, 'status_change', 'Arrival recorded at WH1 - Gate A with ticket A25L0005 and SJ 35464324364', NULL, NULL, 3, '2025-12-10 01:22:40'),
(139, 31, 'early_arrival', 'Truck arrived on time/early at WH2 - Gate B', NULL, NULL, 3, '2025-12-10 01:22:54'),
(140, 31, 'status_change', 'Slot started at WH2 - Gate B', NULL, NULL, 3, '2025-12-10 01:22:54'),
(141, 27, 'status_change', 'Slot completed with MAT DOC 4523643543, SJ tesss, truck Kontainer 40ft (paletize), vehicle B66577YTR, driver mkdmfkwm', NULL, NULL, 3, '2025-12-10 01:23:26'),
(142, NULL, 'gate_activation', 'WH2 - Gate C activated', '{\"gate_id\":3,\"old\":0}', '{\"gate_id\":3,\"new\":1}', 3, '2025-12-11 00:54:47'),
(143, 32, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-11 00:55:57'),
(144, 33, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-11 00:59:28'),
(145, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-11 01:20:07'),
(146, 34, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-11 01:20:57'),
(147, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-12 01:41:01'),
(148, 35, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-12 01:41:36'),
(149, 36, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-12 01:43:43'),
(150, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-12 02:05:49'),
(151, 37, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-12 02:07:17'),
(152, 38, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-12 02:26:58'),
(153, 39, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-12 02:27:52'),
(154, 40, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-12 02:29:01'),
(155, 36, 'status_change', 'Arrival recorded at WH2 - Gate C with ticket C25L0002 and SJ huhihiio0', NULL, NULL, 3, '2025-12-12 03:02:50'),
(156, 36, 'late_arrival', 'Truck arrived late at WH1 - Gate A', NULL, NULL, 3, '2025-12-12 03:12:39'),
(157, 36, 'status_change', 'Slot started at WH1 - Gate A', NULL, NULL, 3, '2025-12-12 03:12:39'),
(158, 36, 'status_change', 'Slot completed with MAT DOC klklio0-, SJ huhihiio0, truck CDD/CDE, vehicle 00i0kok, driver kkkkk', NULL, NULL, 3, '2025-12-12 03:15:22'),
(159, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-15 02:08:19'),
(160, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-15 08:43:53'),
(161, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-16 01:25:24'),
(162, 41, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-16 04:07:18'),
(163, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-16 05:57:03'),
(164, 42, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-16 06:06:01'),
(165, 43, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-16 06:16:52'),
(166, 44, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-16 09:18:08'),
(167, 45, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-16 09:20:17'),
(168, 46, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-16 09:21:23'),
(169, 47, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-16 09:31:25'),
(170, 46, 'status_change', 'Status changed to pre-arrival', NULL, NULL, 3, '2025-12-16 09:34:33'),
(171, 48, 'status_change', 'Unplanned arrival recorded as waiting', NULL, NULL, 3, '2025-12-16 09:45:16'),
(172, 49, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-16 10:00:29'),
(173, 50, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-16 10:01:50'),
(174, 41, 'status_change', 'Status changed to waiting after arrival at WH2 - Gate B', NULL, NULL, 3, '2025-12-16 10:22:39'),
(175, 41, '', 'Arrival recorded with ticket B25L0008 and SJ sddfdsd2', NULL, NULL, 3, '2025-12-16 10:22:39'),
(176, 41, 'late_arrival', 'Truck arrived late at WH1 - Gate A', NULL, NULL, 3, '2025-12-16 10:23:07'),
(177, 41, 'status_change', 'Slot started at WH1 - Gate A', NULL, NULL, 3, '2025-12-16 10:23:07'),
(178, 41, 'status_change', 'Slot completed with MAT DOC mkm435ker, SJ sddfdsd2, truck Kontainer 40ft (Louse), vehicle 334356, driver kkkkk', NULL, NULL, 3, '2025-12-16 10:23:19'),
(179, 42, 'status_change', 'Status changed to arrived after arrival at WH2 - Gate B', NULL, NULL, 3, '2025-12-16 10:24:27'),
(180, 42, '', 'Arrival recorded with ticket B25L0009 and SJ tesss', NULL, NULL, 3, '2025-12-16 10:24:27'),
(181, 42, '', 'Arrival details updated', NULL, NULL, 3, '2025-12-16 10:25:03'),
(182, 42, '', 'Arrival details updated', NULL, NULL, 3, '2025-12-16 10:26:36'),
(183, 42, 'status_change', 'Status changed to waiting after arrival update', NULL, NULL, 3, '2025-12-16 10:26:36'),
(184, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-17 01:14:01'),
(185, 51, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-17 01:14:44'),
(186, 52, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-17 01:15:14'),
(187, 53, 'status_change', 'Unplanned arrival recorded as waiting', NULL, NULL, 3, '2025-12-17 01:15:32'),
(188, 54, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-17 01:38:57'),
(189, 54, 'status_change', 'Status changed to arrived after arrival at WH2 - Gate B', NULL, NULL, 3, '2025-12-17 01:42:34'),
(190, 54, '', 'Arrival recorded with ticket B25L0012 and SJ tesss', NULL, NULL, 3, '2025-12-17 01:42:34'),
(191, 53, 'early_arrival', 'Truck arrived on time/early at WH1 - Gate A', NULL, NULL, 3, '2025-12-17 01:47:38'),
(192, 53, 'status_change', 'Slot started at WH1 - Gate A', NULL, NULL, 3, '2025-12-17 01:47:38'),
(193, 53, 'status_change', 'Slot completed with MAT DOC mkm435ker, SJ tesss, truck Fuso, vehicle 334356, driver kkkkk', NULL, NULL, 3, '2025-12-17 01:49:11'),
(194, 48, 'late_arrival', 'Truck arrived late at WH1 - Gate A', NULL, NULL, 3, '2025-12-17 02:54:40'),
(195, 48, 'status_change', 'Slot started at WH1 - Gate A', NULL, NULL, 3, '2025-12-17 02:54:40'),
(196, NULL, 'status_change', 'User logged in', NULL, NULL, 3, '2025-12-17 04:45:18'),
(197, 55, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-17 04:47:02'),
(198, 56, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-17 04:48:58'),
(199, 57, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-17 06:52:56'),
(200, 58, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-17 06:55:14'),
(201, 59, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-17 09:18:27'),
(202, 60, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-17 09:22:55'),
(203, 61, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-18 01:30:04'),
(205, 61, 'status_change', 'Status changed to arrived after arrival at WH1 - Gate A', NULL, NULL, 3, '2025-12-18 02:09:10'),
(206, 61, 'status_change', 'Arrival recorded with ticket A25L0009 and SJ sddfdsd2', NULL, NULL, 3, '2025-12-18 02:09:10'),
(207, 61, 'early_arrival', 'Truck arrived on time/early at WH2 - Gate C', NULL, NULL, 3, '2025-12-18 02:09:53'),
(208, 61, 'status_change', 'Slot started at WH2 - Gate C', NULL, NULL, 3, '2025-12-18 02:09:53'),
(209, 61, 'status_change', 'Slot completed with MAT DOC mkm435ker, SJ sddfdsd2, truck fuso, vehicle 334356, driver kkkkk', NULL, NULL, 3, '2025-12-18 02:10:48'),
(210, 62, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-18 02:18:14'),
(211, 63, 'status_change', 'Unplanned arrival recorded as waiting', NULL, NULL, 3, '2025-12-18 02:18:54'),
(212, 64, 'status_change', 'Unplanned arrival recorded as waiting', NULL, NULL, 3, '2025-12-18 03:16:35'),
(213, 65, 'status_change', 'Unplanned arrival recorded as waiting', NULL, NULL, 3, '2025-12-18 04:51:24'),
(214, 66, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-18 06:41:45'),
(215, 67, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-18 06:42:12'),
(216, 68, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 05:18:08'),
(217, 68, 'status_change', 'Slot updated', NULL, NULL, 3, '2025-12-19 05:18:47'),
(218, 69, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 06:52:23'),
(219, 70, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 06:53:18'),
(220, 71, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 06:54:31'),
(221, 73, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 07:25:42'),
(222, 74, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 08:26:34'),
(223, 75, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 08:27:38'),
(224, 76, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 08:30:09'),
(225, 77, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-19 08:31:25'),
(226, 78, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-20 11:25:17'),
(227, 79, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-20 11:33:33'),
(228, 80, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 15:42:11'),
(229, 81, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:26:56'),
(230, 82, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:23'),
(231, 83, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:24'),
(232, 84, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:24'),
(233, 85, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:25'),
(234, 86, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:25'),
(235, 87, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:25'),
(236, 88, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:26'),
(237, 89, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:31:48'),
(238, 90, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:41:45'),
(239, 91, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 16:46:35'),
(240, 92, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 19:11:17'),
(241, 93, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-21 19:43:54'),
(242, 94, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-21 20:09:30'),
(243, 95, 'status_change', 'Unplanned arrival recorded as arrived', NULL, NULL, 3, '2025-12-21 20:41:41'),
(244, 96, 'status_change', 'Slot created', NULL, NULL, 3, '2025-12-22 01:38:07'),
(245, 96, 'status_change', 'Status changed to arrived after arrival at WH1 - Gate A', NULL, NULL, 3, '2025-12-22 02:07:29'),
(246, 96, 'status_change', 'Arrival recorded with ticket A25L0010 and SJ tes', NULL, NULL, 3, '2025-12-22 02:07:29'),
(247, 96, 'status_change', 'Arrival details updated', NULL, NULL, 3, '2025-12-22 02:07:29'),
(248, 96, 'early_arrival', 'Truck arrived on time/early at WH2 - Gate C', NULL, NULL, 3, '2025-12-22 02:07:38'),
(249, 96, 'status_change', 'Slot started at WH2 - Gate C', NULL, NULL, 3, '2025-12-22 02:07:38');

-- --------------------------------------------------------

--
-- Table structure for table `gates`
--

CREATE TABLE `gates` (
  `id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `gate_number` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_backup` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `gates`
--

INSERT INTO `gates` (`id`, `warehouse_id`, `gate_number`, `is_active`, `is_backup`, `created_at`, `updated_at`) VALUES
(1, 1, 'G1', 1, 0, '2025-12-02 03:53:30', '2025-12-02 03:53:30'),
(2, 2, 'G1', 1, 0, '2025-12-02 03:53:30', '2025-12-09 04:45:25'),
(3, 2, 'G2', 1, 1, '2025-12-02 03:53:30', '2025-12-11 00:54:47');

-- --------------------------------------------------------

--
-- Table structure for table `po`
--

CREATE TABLE `po` (
  `id` int(11) NOT NULL,
  `po_number` varchar(20) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `po`
--

INSERT INTO `po` (`id`, `po_number`, `vendor_id`, `created_at`) VALUES
(1, 'PO00B0405Y', 228, '2025-12-02 04:46:59'),
(2, 'PO00405Y23', 166, '2025-12-02 04:54:23'),
(3, 'PO000sefse', 229, '2025-12-02 07:08:12'),
(4, 'POsCAwq224', 192, '2025-12-02 07:12:51'),
(5, 'PO01421321', 133, '2025-12-02 07:14:05'),
(6, 'PO00223123', 150, '2025-12-02 07:15:04'),
(7, 'PO00003456', 122, '2025-12-02 07:39:23'),
(8, 'PO00344533', 149, '2025-12-02 09:29:58'),
(9, 'PO00222313', 218, '2025-12-02 09:32:39'),
(10, 'PO00000212', 126, '2025-12-02 09:34:51'),
(11, 'PO12321412', 157, '2025-12-03 01:04:25'),
(12, 'PO01123213', 134, '2025-12-03 01:46:25'),
(13, 'PO00001213', 202, '2025-12-03 01:48:00'),
(14, 'PO25245233', 157, '2025-12-03 02:07:57'),
(15, 'PO0002e2eq', 191, '2025-12-03 04:00:29'),
(16, 'PO01231242', 171, '2025-12-03 06:30:15'),
(17, 'PO00123131', 4, '2025-12-03 06:46:02'),
(18, 'PO00124124', 191, '2025-12-03 06:46:54'),
(19, 'PO12132135', 159, '2025-12-03 09:41:52'),
(20, 'PO00012312', 145, '2025-12-03 09:54:05'),
(21, 'PO00223535', 122, '2025-12-04 01:25:49'),
(22, 'POfddgdhd1', 236, '2025-12-05 09:39:26'),
(23, 'PO000fddgd', 126, '2025-12-05 09:39:46'),
(24, 'PO0000jksk', 139, '2025-12-05 09:58:07'),
(25, 'PO00000tes', 215, '2025-12-09 07:15:56'),
(26, 'PO00010tes', 162, '2025-12-09 07:19:38'),
(27, 'PO00200tes', 187, '2025-12-09 07:25:12'),
(28, 'PO41432432', 187, '2025-12-09 08:48:29'),
(29, 'PO00000vbb', 213, '2025-12-09 09:23:52'),
(30, 'PO00243243', 231, '2025-12-11 00:55:57'),
(31, 'PO00345435', 228, '2025-12-11 00:59:28'),
(32, 'PO000cbccr', 237, '2025-12-11 01:20:57'),
(33, 'PO00004354', 156, '2025-12-12 01:41:36'),
(34, 'PO00323242', 180, '2025-12-12 01:43:43'),
(35, 'PO1243trtr', 124, '2025-12-12 02:07:17'),
(36, 'PO00434324', 140, '2025-12-12 02:26:58'),
(37, 'PO00024234', 144, '2025-12-12 02:27:52'),
(38, 'PO00004214', 149, '2025-12-12 02:29:01'),
(39, 'PO000202i1', 124, '2025-12-16 04:07:18'),
(40, 'PO000000tr', 189, '2025-12-16 06:06:01'),
(41, 'PO00004353', 172, '2025-12-16 06:16:52'),
(42, 'PO00ddds11', 223, '2025-12-16 09:18:08'),
(43, 'PO0oouoij9', 176, '2025-12-16 09:31:25'),
(44, 'PO05656464', 216, '2025-12-17 01:38:57'),
(45, 'PO45363643', 194, '2025-12-17 06:52:56'),
(46, 'PO06536536', 171, '2025-12-17 06:55:14'),
(47, 'PO0000000e', 210, '2025-12-18 03:16:35'),
(48, 'PO00000yy7', 215, '2025-12-18 06:41:45'),
(49, 'PO000000aa', 200, '2025-12-18 06:42:12'),
(50, 'PO00000ere', 122, '2025-12-19 05:18:08'),
(51, 'PO00000000', 139, '2025-12-19 05:18:47'),
(52, 'DO12345678', 3, '2025-12-19 06:31:13');

-- --------------------------------------------------------

--
-- Table structure for table `slots`
--

CREATE TABLE `slots` (
  `id` int(11) NOT NULL,
  `truck_id` int(11) NOT NULL,
  `ticket_number` varchar(50) DEFAULT NULL,
  `sj_start_number` varchar(50) DEFAULT NULL,
  `sj_complete_number` varchar(50) DEFAULT NULL,
  `mat_doc` varchar(50) DEFAULT NULL,
  `truck_type` varchar(50) DEFAULT NULL,
  `vehicle_number_snap` varchar(50) DEFAULT NULL,
  `driver_number` varchar(50) DEFAULT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `planned_gate_id` int(11) DEFAULT NULL,
  `actual_gate_id` int(11) DEFAULT NULL,
  `planned_start` datetime NOT NULL,
  `arrival_time` datetime DEFAULT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_finish` datetime DEFAULT NULL,
  `planned_duration` int(11) DEFAULT 60,
  `status` enum('scheduled','arrived','waiting','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `is_late` tinyint(1) DEFAULT 0,
  `late_reason` text DEFAULT NULL,
  `cancelled_reason` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `moved_gate` tinyint(1) DEFAULT 0,
  `blocking_risk` tinyint(4) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `slot_type` enum('planned','unplanned') NOT NULL DEFAULT 'planned'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `slots`
--

INSERT INTO `slots` (`id`, `truck_id`, `ticket_number`, `sj_start_number`, `sj_complete_number`, `mat_doc`, `truck_type`, `vehicle_number_snap`, `driver_number`, `direction`, `warehouse_id`, `vendor_id`, `planned_gate_id`, `actual_gate_id`, `planned_start`, `arrival_time`, `actual_start`, `actual_finish`, `planned_duration`, `status`, `is_late`, `late_reason`, `cancelled_reason`, `cancelled_at`, `moved_gate`, `blocking_risk`, `created_by`, `created_at`, `updated_at`, `slot_type`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, 1, 1, NULL, '2025-12-02 15:50:00', '2025-12-02 11:47:05', '2025-12-02 11:47:05', '2025-12-02 11:47:39', 240, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 04:46:59', '2025-12-02 04:47:39', 'planned'),
(2, 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 1, 2, NULL, '2025-12-02 07:10:00', '2025-12-02 11:54:28', '2025-12-02 11:54:28', '2025-12-02 11:54:30', 60, 'completed', 1, NULL, NULL, NULL, 0, 1, 3, '2025-12-02 04:54:23', '2025-12-02 04:54:30', 'planned'),
(3, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, 1, 1, NULL, '2025-12-02 13:03:00', '2025-12-02 13:05:07', '2025-12-02 13:05:07', '2025-12-02 13:05:31', 60, 'completed', 1, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 06:04:51', '2025-12-02 06:05:31', 'planned'),
(4, 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 1, 2, NULL, '2025-12-02 14:08:00', '2025-12-02 14:08:27', '2025-12-02 14:08:27', '2025-12-02 14:09:14', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 07:08:12', '2025-12-02 07:09:14', 'planned'),
(5, 4, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, 3, 2, NULL, '2025-12-02 14:12:00', '2025-12-02 14:14:14', '2025-12-02 14:14:14', '2025-12-02 14:15:55', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 07:12:51', '2025-12-02 07:15:55', 'planned'),
(6, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 1, 1, NULL, '2025-12-02 14:13:00', '2025-12-02 14:14:28', '2025-12-02 14:14:28', '2025-12-02 14:15:44', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 07:14:05', '2025-12-02 07:15:44', 'planned'),
(7, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, 3, 1, NULL, '2025-12-02 14:15:00', '2025-12-02 14:15:13', '2025-12-02 14:15:13', '2025-12-02 14:15:37', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 07:15:04', '2025-12-02 07:15:37', 'planned'),
(8, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, 1, 1, NULL, '2025-12-02 14:40:00', '2025-12-02 14:47:56', '2025-12-02 14:47:56', '2025-12-02 15:21:44', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 07:39:23', '2025-12-02 08:21:44', 'planned'),
(9, 8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, 1, 1, NULL, '2025-12-02 16:29:00', NULL, NULL, NULL, 60, 'cancelled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 09:29:58', '2025-12-09 09:33:41', 'planned'),
(10, 9, NULL, NULL, '3224233242', '325325323', 'Kontainer 40ft (Louse)', 'B66577YTR', 'effesf', 'outbound', 2, 1, 2, NULL, '2025-12-02 16:00:00', '2025-12-02 16:35:32', '2025-12-02 16:35:32', '2025-12-09 16:31:08', 120, 'completed', 1, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 09:32:39', '2025-12-09 09:31:08', 'planned'),
(11, 10, NULL, NULL, '565334543', '4442563', 'Kontainer 40ft (paletize)', 'B672818921HTY', 'effesf', 'outbound', 2, 1, 3, NULL, '2025-12-02 16:33:00', '2025-12-02 16:35:49', '2025-12-02 16:35:49', '2025-12-09 16:34:09', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-02 09:34:51', '2025-12-09 09:34:09', 'planned'),
(12, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 2, NULL, '2025-12-03 08:05:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 01:04:25', '2025-12-03 01:04:25', 'planned'),
(13, 12, 'TWH12512030001', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, 3, 1, NULL, '2025-12-03 08:07:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 01:46:25', '2025-12-03 01:46:25', 'planned'),
(14, 13, 'TWH22512030001', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 1, 3, NULL, '2025-12-03 08:47:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-03 01:48:00', '2025-12-03 01:48:00', 'planned'),
(15, 14, 'TWH22512030002', '2314123142321', '2314123142321', '21232d22d1131`3', 'wingbox', 'B672818921HTY', '902i9921', 'outbound', 2, 3, 2, NULL, '2025-12-03 09:10:00', '2025-12-03 09:29:10', '2025-12-03 09:29:10', '2025-12-03 09:29:44', 60, 'completed', 1, NULL, NULL, NULL, 0, 1, 3, '2025-12-03 02:07:57', '2025-12-03 02:29:44', 'planned'),
(16, 2, NULL, NULL, '313142132213', '1321321321', '', '', '', 'inbound', 1, NULL, NULL, 1, '2025-12-03 10:42:00', '2025-12-03 10:42:00', '2025-12-03 10:42:00', '2025-12-03 10:42:00', 0, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 03:47:17', '2025-12-03 03:47:17', 'unplanned'),
(17, 15, NULL, NULL, '', '21e1411232121', '', '', '', 'inbound', 1, NULL, NULL, NULL, '2025-12-03 11:00:00', '2025-12-03 11:00:00', '2025-12-03 11:00:00', NULL, 0, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 04:00:29', '2025-12-03 04:00:29', 'unplanned'),
(18, 16, 'TWH22512030003', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 1, 2, NULL, '2025-12-03 13:30:00', NULL, NULL, NULL, 60, 'cancelled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 06:30:15', '2025-12-08 02:07:56', 'planned'),
(19, 17, 'TWH22512030004', '323324233432', '323324233432', '45432432', 'Kontainer 20ft (Louse)', 'B8U92302KHT', 'RTTG', 'outbound', 2, 3, 2, 2, '2025-12-03 14:30:00', '2025-12-04 11:35:46', '2025-12-04 11:35:46', '2025-12-09 16:31:38', 60, 'completed', 1, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 06:46:02', '2025-12-09 09:31:38', 'planned'),
(20, 18, 'TWH22512030005', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 3, NULL, '2025-12-03 13:46:00', NULL, NULL, NULL, 60, 'cancelled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-03 06:46:54', '2025-12-05 06:04:02', 'planned'),
(21, 19, 'TWH22512030006', '13242423', '13242423', '34436354345', 'wingbox', 'B66577YTR', '32RI9329', 'outbound', 2, 3, 2, 2, '2025-12-03 16:41:00', '2025-12-03 16:42:37', '2025-12-03 16:42:37', '2025-12-03 16:43:07', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 09:41:52', '2025-12-03 09:43:07', 'planned'),
(22, 20, NULL, NULL, '', '2144212', '', '', '', 'outbound', 1, 3, NULL, 1, '2025-12-03 16:53:00', '2025-12-03 16:53:00', '2025-12-03 16:53:00', NULL, 0, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-03 09:54:05', '2025-12-03 09:54:05', 'unplanned'),
(23, 21, 'TWH22512040001', '34324234232', '34324234232', 'w3w3232', 'Fuso', 'B8U92302KHT', 'B8U92302KHT', 'inbound', 2, 1, 2, 2, '2025-12-04 08:25:00', '2025-12-04 08:56:16', '2025-12-04 08:56:16', '2025-12-05 13:06:33', 60, 'completed', 1, NULL, NULL, NULL, 0, 0, 3, '2025-12-04 01:25:49', '2025-12-05 06:06:33', 'planned'),
(24, 23, 'A25L0001', '234reree', '234reree', 'dsdas', 'CDD/CDE', '334356', 'tes', 'inbound', 1, 1, 1, 1, '2025-12-05 17:39:00', '2025-12-05 16:42:29', '2025-12-05 16:42:29', '2025-12-05 16:47:09', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-05 09:39:26', '2025-12-05 09:47:09', 'planned'),
(25, 24, NULL, NULL, 'kn3ke', 'mkm435ker', 'mk45n34 mkm', 'ergtkemtge', 'mkdmfkwm', 'inbound', 1, 3, NULL, NULL, '2025-12-05 17:56:00', '2025-12-05 17:56:00', '2025-12-05 17:56:00', NULL, 0, 'completed', 0, 'tes tes', NULL, NULL, 0, 0, 3, '2025-12-05 09:58:07', '2025-12-05 09:58:36', 'unplanned'),
(26, 24, NULL, NULL, '', '', 'CDD/CDE', '', '', 'inbound', 1, 3, NULL, NULL, '2025-12-09 09:20:00', '2025-12-09 09:20:00', '2025-12-09 09:20:00', NULL, 0, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-09 01:20:21', '2025-12-09 01:21:59', 'unplanned'),
(27, 25, 'A25L0002', 'tesss', 'tesss', '4523643543', 'Kontainer 40ft (paletize)', 'B66577YTR', 'mkdmfkwm', 'inbound', 1, 185, 1, 1, '2025-12-09 15:15:00', '2025-12-09 15:39:19', '2025-12-09 16:41:50', '2025-12-10 08:23:26', 120, 'completed', 1, NULL, NULL, NULL, 0, 0, 3, '2025-12-09 07:15:56', '2025-12-10 01:23:26', 'planned'),
(28, 26, 'A25L0003', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, 3, 1, NULL, '2025-12-09 15:15:00', NULL, NULL, NULL, 60, 'cancelled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-09 07:19:38', '2025-12-09 09:12:13', 'planned'),
(29, 27, 'B25L0001', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 187, 2, NULL, '2025-12-09 15:15:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-09 07:25:12', '2025-12-09 07:25:12', 'planned'),
(30, 28, 'A25L0004', '43243243232342', NULL, NULL, 'Wingbox (paletize)', NULL, NULL, 'inbound', 1, 135, 1, NULL, '2025-12-09 17:15:00', '2025-12-09 15:49:31', NULL, NULL, 60, 'cancelled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-09 08:48:29', '2025-12-09 09:41:33', 'planned'),
(31, 29, 'A25L0005', '35464324364', NULL, NULL, 'Kontainer 20ft (Louse)', NULL, NULL, 'inbound', 1, 185, 1, 2, '2025-12-10 08:23:00', '2025-12-10 08:22:40', '2025-12-10 08:22:54', NULL, 60, 'in_progress', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-09 09:23:52', '2025-12-10 01:22:54', 'planned'),
(32, 30, 'B25L0002', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 135, 2, NULL, '2025-12-11 08:00:00', NULL, NULL, NULL, 120, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-11 00:55:57', '2025-12-11 00:55:57', 'planned'),
(33, 31, 'C25L0001', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 3, NULL, '2025-12-11 08:00:00', NULL, NULL, NULL, 180, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-11 00:59:28', '2025-12-11 00:59:28', 'planned'),
(34, 32, 'B25L0003', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 211, 2, NULL, '2025-12-11 12:30:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-11 01:20:57', '2025-12-11 01:20:57', 'planned'),
(35, 33, 'B25L0004', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 185, 2, NULL, '2025-12-12 09:00:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-12 01:41:36', '2025-12-12 01:41:36', 'planned'),
(36, 34, 'C25L0002', 'huhihiio0', 'huhihiio0', 'klklio0-', 'CDD/CDE', '00i0kok', 'kkkkk', 'outbound', 2, 3, 3, 1, '2025-12-12 09:00:00', '2025-12-12 10:02:50', '2025-12-12 10:12:39', '2025-12-12 10:15:22', 54, 'completed', 1, NULL, NULL, NULL, 0, 2, 3, '2025-12-12 01:43:43', '2025-12-12 03:15:22', 'planned'),
(37, 35, 'B25L0005', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 2, NULL, '2025-12-12 12:00:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-12 02:07:17', '2025-12-12 02:07:17', 'planned'),
(38, 36, 'C25L0003', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 3, NULL, '2025-12-12 12:00:00', NULL, NULL, NULL, 240, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-12 02:26:58', '2025-12-12 02:26:58', 'planned'),
(39, 37, 'B25L0006', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 229, 2, NULL, '2025-12-12 13:00:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-12 02:27:52', '2025-12-12 02:27:52', 'planned'),
(40, 38, 'B25L0007', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 2, NULL, '2025-12-12 14:00:00', NULL, NULL, NULL, 120, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-12 02:29:01', '2025-12-12 02:29:01', 'planned'),
(41, 39, 'B25L0008', 'sddfdsd2', 'sddfdsd2', 'mkm435ker', 'Kontainer 40ft (Louse)', '334356', 'kkkkk', 'inbound', 2, 185, 2, 1, '2025-12-16 11:07:00', '2025-12-16 17:22:39', '2025-12-16 17:23:07', '2025-12-16 17:23:19', 60, 'completed', 1, NULL, NULL, NULL, 0, 0, 3, '2025-12-16 04:07:18', '2025-12-16 10:23:19', 'planned'),
(42, 40, 'B25L0009', 'tesss', NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 2, NULL, '2025-12-16 14:05:00', '2025-12-16 17:24:27', NULL, NULL, 240, 'waiting', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-16 06:06:01', '2025-12-16 10:26:36', 'planned'),
(43, 41, 'C25L0004', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 135, 3, NULL, '2025-12-16 14:05:00', NULL, NULL, NULL, 240, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-16 06:16:52', '2025-12-16 06:16:52', 'planned'),
(44, 42, 'B25L0010', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, NULL, 2, NULL, '2025-12-17 08:20:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-16 09:18:08', '2025-12-16 09:18:08', 'planned'),
(45, 42, 'C25L0005', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 135, 3, NULL, '2025-12-17 08:20:00', NULL, NULL, NULL, 70, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-16 09:20:17', '2025-12-16 09:20:17', 'planned'),
(46, 42, 'B25L0011', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 135, 2, NULL, '2025-12-17 09:20:00', NULL, NULL, NULL, 10, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-16 09:21:23', '2025-12-16 10:30:55', 'planned'),
(47, 43, 'C25L0006', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 169, 3, NULL, '2025-12-16 18:30:00', NULL, NULL, NULL, 180, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-16 09:31:25', '2025-12-16 09:31:25', 'planned'),
(48, 43, NULL, NULL, '', '', '', '', '', 'outbound', 1, 185, NULL, 1, '2025-12-16 17:44:00', '2025-12-16 17:44:00', '2025-12-17 09:54:40', NULL, 0, 'in_progress', 1, NULL, NULL, NULL, 0, 0, 3, '2025-12-16 09:45:16', '2025-12-17 02:54:40', 'unplanned'),
(49, 43, NULL, NULL, '', '', '', '', '', 'inbound', 2, 187, NULL, 3, '2025-12-16 18:00:00', '2025-12-16 18:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-16 10:00:29', '2025-12-16 10:00:29', 'unplanned'),
(50, 27, NULL, NULL, '', '', '', '', '', 'outbound', 1, NULL, NULL, NULL, '2025-12-17 13:01:00', '2025-12-17 13:01:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-16 10:01:50', '2025-12-16 10:01:50', 'unplanned'),
(51, 43, NULL, NULL, '', '', '', '', '', 'outbound', 2, NULL, NULL, NULL, '2025-12-17 09:14:00', '2025-12-17 09:14:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 01:14:44', '2025-12-17 01:14:44', 'unplanned'),
(52, 42, NULL, NULL, '', '', '', '', '', 'outbound', 2, NULL, NULL, NULL, '2025-12-17 09:15:00', '2025-12-17 09:15:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 01:15:14', '2025-12-17 01:15:14', 'unplanned'),
(53, 42, NULL, NULL, 'tesss', 'mkm435ker', 'Fuso', '334356', 'kkkkk', 'inbound', 1, NULL, NULL, 1, '2025-12-17 09:15:00', '2025-12-17 09:15:00', '2025-12-17 08:47:38', '2025-12-17 08:49:11', 0, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 01:15:32', '2025-12-17 01:49:11', 'unplanned'),
(54, 44, 'B25L0012', 'tesss', NULL, NULL, 'Kontainer 20ft (paletize)', NULL, NULL, 'inbound', 2, 144, 2, NULL, '2025-12-17 12:38:00', '2025-12-17 08:42:34', NULL, NULL, 60, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 01:38:57', '2025-12-17 01:42:34', 'planned'),
(55, 24, 'A25L0006', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, 185, 1, NULL, '2025-12-17 12:46:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-17 04:47:02', '2025-12-17 04:47:02', 'planned'),
(56, 26, 'A25L0007', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, 3, 1, NULL, '2025-12-17 07:48:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 04:48:58', '2025-12-17 04:48:58', 'planned'),
(57, 45, 'C25L0007', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 229, 3, NULL, '2025-12-18 08:00:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 06:52:56', '2025-12-17 06:52:56', 'planned'),
(58, 46, 'B25L0013', NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 2, 3, 2, NULL, '2025-12-18 08:00:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-17 06:55:14', '2025-12-17 06:55:14', 'planned'),
(59, 42, 'A25L0008', NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, 185, 1, NULL, '2025-12-18 12:00:00', NULL, NULL, NULL, 60, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 09:18:27', '2025-12-17 09:18:27', 'planned'),
(60, 42, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 185, NULL, NULL, '2025-12-19 12:00:00', '2025-12-19 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-17 09:22:55', '2025-12-17 09:22:55', 'unplanned'),
(61, 43, 'A25L0009', 'sddfdsd2', 'sddfdsd2', 'mkm435ker', 'fuso', '334356', 'kkkkk', 'inbound', 1, 229, 1, 3, '2025-12-18 13:00:00', '2025-12-18 09:09:10', '2025-12-18 09:09:53', '2025-12-18 09:10:48', 60, 'completed', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-18 01:30:04', '2025-12-18 02:10:48', 'planned'),
(62, 26, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, 229, NULL, 2, '2025-12-18 10:00:00', '2025-12-18 10:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-18 02:18:14', '2025-12-18 02:18:14', 'unplanned'),
(63, 42, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-18 10:00:00', '2025-12-18 10:00:00', NULL, NULL, 0, 'waiting', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-18 02:18:54', '2025-12-18 02:18:54', 'unplanned'),
(64, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, NULL, NULL, NULL, '2025-12-18 11:00:00', '2025-12-18 11:00:00', NULL, NULL, 0, 'waiting', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-18 03:16:35', '2025-12-18 03:16:35', 'unplanned'),
(65, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, 229, NULL, NULL, '2025-12-18 12:00:00', '2025-12-18 12:00:00', NULL, NULL, 0, 'waiting', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-18 04:51:24', '2025-12-18 04:51:24', 'unplanned'),
(66, 48, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 2, NULL, NULL, NULL, '2025-12-18 12:00:00', '2025-12-18 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-18 06:41:45', '2025-12-18 06:41:45', 'unplanned'),
(67, 49, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-18 11:00:00', '2025-12-18 11:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-18 06:42:12', '2025-12-18 06:42:12', 'unplanned'),
(68, 51, NULL, NULL, NULL, NULL, 'Fuso', NULL, NULL, 'inbound', 2, NULL, 2, NULL, '2025-12-19 12:00:00', NULL, NULL, NULL, 240, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-19 05:18:08', '2025-12-19 05:18:47', 'planned'),
(69, 49, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 2, 200, 2, NULL, '2025-12-19 08:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-19 06:52:23', '2025-12-19 06:52:23', 'planned'),
(70, 51, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 2, 139, 3, NULL, '2025-12-22 08:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-19 06:53:18', '2025-12-19 06:53:18', 'planned'),
(71, 40, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 2, 189, 2, NULL, '2025-12-22 08:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-19 06:54:31', '2025-12-19 06:54:31', 'planned'),
(72, 25, NULL, NULL, NULL, NULL, 'Kontainer 20ft (Louse)', NULL, NULL, 'inbound', 2, 215, 3, NULL, '2025-12-22 08:50:00', NULL, NULL, NULL, 240, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-19 06:59:48', '2025-12-19 06:59:48', 'planned'),
(73, 47, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 2, 210, 2, NULL, '2025-12-22 12:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-19 07:25:42', '2025-12-19 07:25:42', 'planned'),
(74, 27, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 2, 187, 2, NULL, '2025-12-23 08:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-19 08:26:34', '2025-12-19 08:26:34', 'planned'),
(75, 47, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 2, 210, 3, NULL, '2025-12-23 08:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-19 08:27:38', '2025-12-19 08:27:38', 'planned'),
(76, 49, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 2, 200, 2, NULL, '2025-12-23 08:50:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-19 08:30:09', '2025-12-19 08:30:09', 'planned'),
(77, 51, NULL, NULL, NULL, NULL, 'Kontainer 20ft (Louse)', NULL, NULL, 'inbound', 2, 139, 3, NULL, '2025-12-23 08:50:00', NULL, NULL, NULL, 240, 'scheduled', 0, NULL, NULL, NULL, 0, 1, 3, '2025-12-19 08:31:25', '2025-12-19 08:31:25', 'planned'),
(78, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, NULL, NULL, 1, '2025-12-20 12:00:00', '2025-12-20 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-20 11:25:17', '2025-12-20 11:25:17', 'unplanned'),
(79, 51, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-20 12:00:00', '2025-12-20 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-20 11:33:33', '2025-12-20 11:33:33', 'unplanned'),
(80, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, NULL, NULL, NULL, '2025-12-21 12:00:00', '2025-12-21 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 15:42:11', '2025-12-21 15:42:11', 'unplanned'),
(81, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:26:56', '2025-12-21 16:26:56', 'unplanned'),
(82, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:23', '2025-12-21 16:31:23', 'unplanned'),
(83, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:24', '2025-12-21 16:31:24', 'unplanned'),
(84, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:24', '2025-12-21 16:31:24', 'unplanned'),
(85, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:25', '2025-12-21 16:31:25', 'unplanned'),
(86, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:25', '2025-12-21 16:31:25', 'unplanned'),
(87, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:25', '2025-12-21 16:31:25', 'unplanned'),
(88, 47, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:26', '2025-12-21 16:31:26', 'unplanned'),
(89, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 16:31:48', '2025-12-21 16:31:48', 'unplanned'),
(90, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, 'tes', NULL, NULL, 0, 0, 3, '2025-12-21 16:41:45', '2025-12-21 16:41:45', 'unplanned'),
(91, 52, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'outbound', 1, NULL, NULL, NULL, '2025-12-23 12:00:00', '2025-12-23 12:00:00', NULL, NULL, 0, 'arrived', 0, 'tes', NULL, NULL, 0, 0, 3, '2025-12-21 16:46:35', '2025-12-21 16:46:35', 'unplanned'),
(92, 51, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 19:11:17', '2025-12-21 19:11:17', 'unplanned'),
(93, 51, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'inbound', 1, 139, 1, NULL, '2025-12-22 12:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 19:43:54', '2025-12-21 19:43:54', 'planned'),
(94, 52, NULL, NULL, NULL, NULL, 'CDD/CDE', NULL, NULL, 'outbound', 1, 3, 1, NULL, '2025-12-22 14:00:00', NULL, NULL, NULL, 50, 'scheduled', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 20:09:30', '2025-12-21 20:09:30', 'planned'),
(95, 40, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'inbound', 1, NULL, NULL, NULL, '2025-12-22 12:00:00', '2025-12-22 12:00:00', NULL, NULL, 0, 'arrived', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-21 20:41:41', '2025-12-21 20:41:41', 'unplanned'),
(96, 52, 'A25L0010', 'tes', NULL, NULL, 'CDD/CDE', NULL, NULL, 'outbound', 1, 3, 1, 3, '2025-12-23 12:00:00', '2025-12-22 09:07:29', '2025-12-22 09:07:38', NULL, 50, 'in_progress', 0, NULL, NULL, NULL, 0, 0, 3, '2025-12-22 01:38:07', '2025-12-22 02:07:38', 'planned');

-- --------------------------------------------------------

--
-- Table structure for table `truck_type_durations`
--

CREATE TABLE `truck_type_durations` (
  `id` int(11) NOT NULL,
  `truck_type` varchar(100) NOT NULL,
  `target_duration_minutes` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `truck_type_durations`
--

INSERT INTO `truck_type_durations` (`id`, `truck_type`, `target_duration_minutes`, `created_at`) VALUES
(1, 'CDD/CDE', 50, '2025-12-19 09:32:07'),
(2, 'Fuso', 240, '2025-12-19 09:32:07'),
(3, 'Wingbox (paletize)', 120, '2025-12-19 09:32:07'),
(4, 'Wingbox (Louse)', 240, '2025-12-19 09:32:07'),
(5, 'Kontainer 20ft (paletize)', 120, '2025-12-19 09:32:07'),
(6, 'Kontainer 20ft (Louse)', 240, '2025-12-19 09:32:07'),
(7, 'Kontainer 40ft (paletize)', 120, '2025-12-19 09:32:07'),
(8, 'Kontainer 40ft (Louse)', 240, '2025-12-19 09:32:07'),
(9, 'Cargo', NULL, '2025-12-19 09:32:07'),
(11, 'tes', 60, '2025-12-21 17:51:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','operator') NOT NULL DEFAULT 'operator',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'admin', '$2y$12$BPunWX7xbU54940BGuBEiudaOIQAbOrZrEeoneeSpB/LIdMcHcJSi', 'Administrator', 'admin', 1, '2025-12-02 03:40:47', '2025-12-17 07:20:05'),
(4, 'operator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operator Satuu', 'operator', 1, '2025-12-02 03:40:47', '2025-12-09 04:54:03'),
(6, 'operator 2', '$2y$10$9Hi2OgQNTSr48EVkRlWi2emrQMtFSTVgadLz5zEIsfVAbbRQ6s8Ui', 'operator dua', 'operator', 1, '2025-12-09 03:32:24', '2025-12-09 03:32:24'),
(7, 'operator 3', '$2y$10$BloTK89dRUqUKqt2EDpv6u9o5Bp8FezwheuX2Ge7wBibD4bz53sS.', 'operator tiga', 'operator', 1, '2025-12-09 03:41:23', '2025-12-09 03:41:23'),
(9, 'admin2', '$2y$10$UpVM8k.KHe3wpENRfIqYHO6oMh46reaFkbp5SIxQ92Up1W6sLXxKy', 'admin dua', 'admin', 1, '2025-12-09 03:42:09', '2025-12-09 04:16:05'),
(10, 'tes', '$2y$12$/9U6zbAU.UP4f7JEngsHEunHJK7BUf1ZTmxa/tcDJDiAgN6M0Ug86', 'tes34', 'operator', 1, '2025-12-21 16:32:45', '2025-12-21 16:32:45'),
(11, 'satu', '$2y$12$wYOq63LR8iTy6BfBzo8lpecTAk9IXcfyulGJRjHGWF7MXU/DeanO2', 'satu', 'operator', 1, '2025-12-21 16:51:28', '2025-12-21 16:51:28'),
(12, 'user', '$2y$12$W4GyETD8FFC9AZXI2EK9oOUiM2UIDWpwRyIPjI7aBenNqmwlq5j8u', 'user', 'operator', 1, '2025-12-21 16:59:20', '2025-12-21 16:59:20');

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `type` enum('supplier','customer') NOT NULL DEFAULT 'supplier',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `name`, `code`, `type`, `created_at`) VALUES
(1, 'Default Vendor', 'V00112', 'supplier', '2025-12-02 03:58:05'),
(3, 'aSdadaww', '2E21131221', 'customer', '2025-12-02 07:11:53'),
(4, 'Tes23', 'V002', 'supplier', '2025-12-08 03:19:33'),
(5, 'Tes3', 'V003', 'supplier', '2025-12-08 03:20:10'),
(118, 'wes', 'V01111', 'supplier', '2025-12-09 04:52:56'),
(119, 'PT Karya Trading', '4532952285', 'supplier', '2025-12-09 06:04:02'),
(120, 'PT Indah Development', '3487262570', 'supplier', '2025-12-09 06:04:02'),
(121, 'PT Prima Corp', '8720616634', 'supplier', '2025-12-09 06:04:02'),
(122, 'PT Mandiri Resources', '7506910814', 'supplier', '2025-12-09 06:04:02'),
(123, 'CV Gemilang Internasional', '3079273289', 'supplier', '2025-12-09 06:04:02'),
(124, 'PT Cemerlang Produksi', '7008001247', 'supplier', '2025-12-09 06:04:02'),
(125, 'CV Makmur Solutions', '2008734221', 'supplier', '2025-12-09 06:04:02'),
(126, 'PT Cemerlang Development', '1182000645', 'supplier', '2025-12-09 06:04:02'),
(127, 'PT Nusantara Resources', '7179792637', 'supplier', '2025-12-09 06:04:02'),
(128, 'PT Sejahtera Digital', '2691064770', 'supplier', '2025-12-09 06:04:02'),
(130, 'PT Mandiri Teknologi', '1988602019', 'supplier', '2025-12-09 06:04:02'),
(131, 'PT Agung Corp', '6107881875', 'supplier', '2025-12-09 06:04:02'),
(132, 'PT Inti Corp', '2626890263', 'supplier', '2025-12-09 06:04:02'),
(133, 'PT Gemilang Internasional 1', '5620463999', 'supplier', '2025-12-09 06:04:02'),
(134, 'PT Prima Energi', '2152969402', 'supplier', '2025-12-09 06:04:02'),
(135, 'CV Bersama Teknologi', '7910408245', 'supplier', '2025-12-09 06:04:02'),
(136, 'CV Inti Logistics', '3806934428', 'supplier', '2025-12-09 06:04:02'),
(137, 'PT Multi Digital', '4226694404', 'supplier', '2025-12-09 06:04:02'),
(138, 'CV Jaya Partner', '8662230609', 'supplier', '2025-12-09 06:04:02'),
(139, 'PT Makmur Produksi', '1918684850', 'supplier', '2025-12-09 06:04:02'),
(140, 'PT Nusantara Corp', '2438376757', 'supplier', '2025-12-09 06:04:02'),
(141, 'PT Sejahtera Resources', '7013753608', 'supplier', '2025-12-09 06:04:02'),
(142, 'PT Cemerlang Energi', '6912923223', 'supplier', '2025-12-09 06:04:02'),
(143, 'PT Mandiri Persada', '2709547366', 'supplier', '2025-12-09 06:04:02'),
(144, 'CV Gemilang Teknologi', '3948576916', 'supplier', '2025-12-09 06:04:02'),
(145, 'PT Berkah Energi', '9573266024', 'supplier', '2025-12-09 06:04:02'),
(146, 'PT Prima Energi', '6647285001', 'supplier', '2025-12-09 06:04:02'),
(147, 'PT Cipta Persada', '1276929717', 'supplier', '2025-12-09 06:04:02'),
(148, 'CV Sukses Development', '7268784831', 'supplier', '2025-12-09 06:04:02'),
(149, 'CV Nusantara Consulting', '8116661456', 'supplier', '2025-12-09 06:04:02'),
(150, 'PT Karya Produksi', '6027149595', 'supplier', '2025-12-09 06:04:02'),
(151, 'PT Mandiri Internasional', '8270733136', 'supplier', '2025-12-09 06:04:02'),
(152, 'PT Nusantara Abadi', '7896354277', 'supplier', '2025-12-09 06:04:02'),
(153, 'PT Cemerlang Resources', '8327131919', 'supplier', '2025-12-09 06:04:02'),
(154, 'PT Makmur Group', '1707476153', 'supplier', '2025-12-09 06:04:02'),
(155, 'PT Sentosa Energi', '3518125999', 'supplier', '2025-12-09 06:04:02'),
(156, 'PT Prima Development', '1083691967', 'supplier', '2025-12-09 06:04:02'),
(157, 'PT Sukses Abadi', '2491160360', 'supplier', '2025-12-09 06:04:02'),
(158, 'CV Indah Industries', '3017789221', 'supplier', '2025-12-09 06:04:02'),
(159, 'CV Makmur Service', '5534435352', 'supplier', '2025-12-09 06:04:02'),
(160, 'PT Multi Produksi', '2847683016', 'supplier', '2025-12-09 06:04:02'),
(161, 'CV Karya Trading', '5522847633', 'supplier', '2025-12-09 06:04:02'),
(162, 'PT Inti Solutions', '3788594573', 'supplier', '2025-12-09 06:04:02'),
(163, 'PT Bersama Development', '4429123991', 'supplier', '2025-12-09 06:04:02'),
(164, 'PT Sukses Persada', '5397095341', 'supplier', '2025-12-09 06:04:02'),
(165, 'PT Mandiri Digital', '6677331379', 'supplier', '2025-12-09 06:04:02'),
(166, 'PT Prima Consulting', '6391235617', 'supplier', '2025-12-09 06:04:02'),
(167, 'PT Global Internasional', '3692880720', 'supplier', '2025-12-09 06:04:02'),
(168, 'PT Berkah Trading', '6525141570', 'supplier', '2025-12-09 06:04:02'),
(169, 'CV Cemerlang Consulting', '7946299428', 'supplier', '2025-12-09 06:04:02'),
(170, 'PT Sukses Utama', '8993191101', 'supplier', '2025-12-09 06:04:02'),
(171, 'PT Berkah Corp', '2769128098', 'supplier', '2025-12-09 06:04:02'),
(172, 'PT Global Trading', '1625487809', 'supplier', '2025-12-09 06:04:02'),
(173, 'PT Karya Trading', '2745485285', 'supplier', '2025-12-09 06:04:02'),
(174, 'PT Sejahtera Internasional', '2097648021', 'supplier', '2025-12-09 06:04:02'),
(175, 'PT Sentosa Internasional', '8416304446', 'supplier', '2025-12-09 06:04:02'),
(176, 'PT Agung Utama', '4561184653', 'supplier', '2025-12-09 06:04:02'),
(177, 'PT Nusantara Solutions', '4848586161', 'supplier', '2025-12-09 06:04:02'),
(178, 'PT Prima Logistics', '2725819241', 'supplier', '2025-12-09 06:04:02'),
(179, 'PT Multi Partner', '7575588138', 'supplier', '2025-12-09 06:04:02'),
(180, 'CV Mandiri Persada', '7195474823', 'supplier', '2025-12-09 06:04:02'),
(181, 'PT Nusantara Perkasa', '3847906570', 'supplier', '2025-12-09 06:04:02'),
(182, 'PT Sejahtera Logistics', '8752948447', 'supplier', '2025-12-09 06:04:02'),
(183, 'CV Indah Development', '8818831526', 'supplier', '2025-12-09 06:04:02'),
(184, 'PT Prima Group', '3855521264', 'supplier', '2025-12-09 06:04:02'),
(185, 'CV Berkah Service', '6068304655', 'supplier', '2025-12-09 06:04:02'),
(186, 'PT Prima Persada', '9358241629', 'supplier', '2025-12-09 06:04:02'),
(187, 'CV Cemerlang Persada', '8382495532', 'supplier', '2025-12-09 06:04:02'),
(188, 'PT Bersama Teknologi', '3270606100', 'supplier', '2025-12-09 06:04:02'),
(189, 'PT Multi Resources', '4782025757', 'supplier', '2025-12-09 06:04:02'),
(190, 'PT Prima Produksi', '9914292873', 'supplier', '2025-12-09 06:04:02'),
(191, 'PT Gemilang Service', '8112300978', 'supplier', '2025-12-09 06:04:02'),
(192, 'PT Karya Logistics', '5197228521', 'supplier', '2025-12-09 06:04:02'),
(193, 'PT Sentosa Resources', '3071444981', 'supplier', '2025-12-09 06:04:02'),
(194, 'PT Karya Consulting', '1008784447', 'supplier', '2025-12-09 06:04:02'),
(195, 'PT Multi Internasional', '7296540238', 'supplier', '2025-12-09 06:04:02'),
(196, 'CV Nusantara Resources', '9434485348', 'supplier', '2025-12-09 06:04:02'),
(197, 'CV Makmur Utama', '6988652455', 'supplier', '2025-12-09 06:04:02'),
(198, 'PT Sukses Development', '5951232102', 'supplier', '2025-12-09 06:04:02'),
(199, 'PT Sukses Logistics', '6197326323', 'supplier', '2025-12-09 06:04:02'),
(200, 'PT Cipta Resources', '2459226452', 'supplier', '2025-12-09 06:04:02'),
(201, 'CV Sejahtera Digital', '5179690273', 'supplier', '2025-12-09 06:04:02'),
(202, 'CV Indah Solutions', '6508772953', 'supplier', '2025-12-09 06:04:02'),
(203, 'PT Sukses Utama', '1317503003', 'supplier', '2025-12-09 06:04:02'),
(204, 'PT Mega Solutions', '5531015177', 'supplier', '2025-12-09 06:04:02'),
(205, 'CV Jaya Abadi', '7978647910', 'supplier', '2025-12-09 06:04:02'),
(206, 'PT Global Energi', '4249686931', 'supplier', '2025-12-09 06:04:02'),
(207, 'CV Cipta Industries', '5126430871', 'supplier', '2025-12-09 06:04:02'),
(208, 'PT Mandiri Consulting', '8262085982', 'supplier', '2025-12-09 06:04:02'),
(209, 'PT Mandiri Solutions', '1071260654', 'supplier', '2025-12-09 06:04:02'),
(210, 'PT Prima Trading', '3621940235', 'supplier', '2025-12-09 06:04:02'),
(211, 'CV Bersama Perkasa', '3432838644', 'supplier', '2025-12-09 06:04:02'),
(212, 'CV Inti Trading', '5928812650', 'supplier', '2025-12-09 06:04:02'),
(213, 'PT Cipta Corp', '7740632627', 'supplier', '2025-12-09 06:04:02'),
(214, 'PT Sentosa Service', '9364146860', 'supplier', '2025-12-09 06:04:02'),
(215, 'PT Mega Trading', '5385350610', 'supplier', '2025-12-09 06:04:02'),
(216, 'PT Berkah Persada', '6937632962', 'supplier', '2025-12-09 06:04:02'),
(217, 'PT Sukses Perkasa', '7342311530', 'supplier', '2025-12-09 06:04:02'),
(218, 'CV Multi Produksi', '5752992051', 'supplier', '2025-12-09 06:04:02'),
(219, 'PT Prima Trading', '7599174103', 'supplier', '2025-12-09 06:04:02'),
(220, 'CV Mandiri Perkasa', '4828361770', 'supplier', '2025-12-09 06:04:02'),
(221, 'PT Jaya Industries', '1533879043', 'supplier', '2025-12-09 06:04:02'),
(222, 'PT Sukses Digital', '5959469819', 'supplier', '2025-12-09 06:04:02'),
(223, 'PT Makmur Energi', '8250792959', 'supplier', '2025-12-09 06:04:02'),
(224, 'PT Cipta Digital', '6561402002', 'supplier', '2025-12-09 06:04:02'),
(225, 'PT Gemilang Energi', '9436734594', 'supplier', '2025-12-09 06:04:02'),
(226, 'PT Indah Utama', '9946057795', 'supplier', '2025-12-09 06:04:02'),
(227, 'PT Nusantara Persada', '1724048176', 'supplier', '2025-12-09 06:04:02'),
(228, 'PT Mandiri Perkasa', '6761559644', 'supplier', '2025-12-09 06:04:02'),
(229, 'CV Bersama Corp', '2810429710', 'supplier', '2025-12-09 06:04:02'),
(230, 'PT Cipta Group', '1529757046', 'supplier', '2025-12-09 06:04:02'),
(231, 'PT Inti Persada', '8713247934', 'supplier', '2025-12-09 06:04:02'),
(232, 'PT Cemerlang Persada', '8601026888', 'supplier', '2025-12-09 06:04:02'),
(233, 'PT Jaya Group', '5069332448', 'supplier', '2025-12-09 06:04:02'),
(234, 'CV Multi Produksi', '3661691789', 'supplier', '2025-12-09 06:04:02'),
(235, 'PT Jaya Resources', '4440514133', 'supplier', '2025-12-09 06:04:03'),
(236, 'PT Multi Utama', '8670616906', 'supplier', '2025-12-09 06:04:03'),
(237, 'PT Global Service', '5193223243', 'supplier', '2025-12-09 06:04:03'),
(238, 'PT Berkah Service', '4256498080', 'supplier', '2025-12-09 06:04:03'),
(239, 'Tes23', 'RTRY', 'customer', '2025-12-18 03:59:50'),
(240, 'TES', 'PO10923098', 'customer', '2025-12-21 17:00:32'),
(241, 'RED', '1234567890', 'customer', '2025-12-21 17:06:13');

-- --------------------------------------------------------

--
-- Table structure for table `warehouses`
--

CREATE TABLE `warehouses` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `code`, `created_at`) VALUES
(1, 'Warehouse 1', 'WH1', '2025-12-02 03:53:30'),
(2, 'Warehouse 2', 'WH2', '2025-12-02 03:53:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `slot_id` (`slot_id`),
  ADD KEY `fk_activity_logs_created_by` (`created_by`);

--
-- Indexes for table `gates`
--
ALTER TABLE `gates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_gate` (`warehouse_id`,`gate_number`);

--
-- Indexes for table `po`
--
ALTER TABLE `po`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `truck_number` (`po_number`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `slots`
--
ALTER TABLE `slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `truck_id` (`truck_id`),
  ADD KEY `warehouse_id` (`warehouse_id`),
  ADD KEY `planned_gate_id` (`planned_gate_id`),
  ADD KEY `actual_gate_id` (`actual_gate_id`),
  ADD KEY `fk_slots_vendor` (`vendor_id`),
  ADD KEY `fk_slots_created_by` (`created_by`);

--
-- Indexes for table `truck_type_durations`
--
ALTER TABLE `truck_type_durations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `warehouses`
--
ALTER TABLE `warehouses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT for table `gates`
--
ALTER TABLE `gates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `po`
--
ALTER TABLE `po`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `slots`
--
ALTER TABLE `slots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `truck_type_durations`
--
ALTER TABLE `truck_type_durations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=242;

--
-- AUTO_INCREMENT for table `warehouses`
--
ALTER TABLE `warehouses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`),
  ADD CONSTRAINT `fk_activity_logs_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gates`
--
ALTER TABLE `gates`
  ADD CONSTRAINT `fk_gates_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `po`
--
ALTER TABLE `po`
  ADD CONSTRAINT `fk_po_vendor_id` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `slots`
--
ALTER TABLE `slots`
  ADD CONSTRAINT `fk_slots_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_slots_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `slots_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `po` (`id`),
  ADD CONSTRAINT `slots_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`),
  ADD CONSTRAINT `slots_ibfk_3` FOREIGN KEY (`planned_gate_id`) REFERENCES `gates` (`id`),
  ADD CONSTRAINT `slots_ibfk_4` FOREIGN KEY (`actual_gate_id`) REFERENCES `gates` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
