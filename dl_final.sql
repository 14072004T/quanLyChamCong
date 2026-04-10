-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 01:30 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dl_final`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_corrections`
--

CREATE TABLE `attendance_corrections` (
  `id` int(11) NOT NULL,
  `maND` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `old_time` datetime DEFAULT NULL,
  `new_time` datetime NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `hr_note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_corrections`
--

INSERT INTO `attendance_corrections` (`id`, `maND`, `attendance_date`, `old_time`, `new_time`, `reason`, `status`, `hr_note`, `created_at`, `updated_at`) VALUES
(1, 4, '2026-03-20', NULL, '2026-03-21 20:27:00', 'quên', 'pending', NULL, '2026-03-21 20:27:17', NULL),
(2, 1, '2026-03-20', '2026-03-21 22:06:00', '2026-03-21 23:06:00', 'out', 'rejected', '', '2026-03-21 22:07:23', '2026-03-22 18:22:57');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_daily_summary`
--

CREATE TABLE `attendance_daily_summary` (
  `id` int(11) NOT NULL,
  `maND` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `first_in` datetime DEFAULT NULL,
  `last_out` datetime DEFAULT NULL,
  `work_minutes` int(11) NOT NULL DEFAULT 0,
  `overtime_minutes` int(11) NOT NULL DEFAULT 0,
  `late_minutes` int(11) NOT NULL DEFAULT 0,
  `status` enum('normal','late','absent','leave') NOT NULL DEFAULT 'normal',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_daily_summary`
--

INSERT INTO `attendance_daily_summary` (`id`, `maND`, `work_date`, `first_in`, `last_out`, `work_minutes`, `overtime_minutes`, `late_minutes`, `status`, `created_at`, `updated_at`) VALUES
(1, 6, '2026-03-23', '2026-03-23 08:05:00', '2026-03-23 17:00:00', 475, 0, 5, 'normal', '2026-03-23 19:27:41', NULL),
(2, 7, '2026-03-23', '2026-03-23 08:25:00', '2026-03-23 17:05:00', 460, 0, 25, 'late', '2026-03-23 19:27:41', NULL),
(3, 8, '2026-03-23', NULL, NULL, 0, 0, 0, 'absent', '2026-03-23 19:27:41', NULL),
(4, 9, '2026-03-23', '2026-03-23 08:00:00', '2026-03-23 17:00:00', 480, 0, 0, 'normal', '2026-03-23 19:27:41', NULL),
(5, 10, '2026-03-23', '2026-03-23 08:00:00', '2026-03-23 18:00:00', 540, 60, 0, 'normal', '2026-03-23 19:27:41', NULL),
(6, 11, '2026-03-23', '2026-03-23 14:00:00', '2026-03-23 22:00:00', 480, 0, 0, 'normal', '2026-03-23 19:27:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_employee_shift`
--

CREATE TABLE `attendance_employee_shift` (
  `id` int(11) NOT NULL,
  `maND` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_employee_shift`
--

INSERT INTO `attendance_employee_shift` (`id`, `maND`, `shift_id`, `effective_from`, `effective_to`, `created_at`) VALUES
(1, 1, 1, '2026-03-21', NULL, '2026-03-21 12:56:34'),
(2, 2, 1, '2026-03-21', NULL, '2026-03-21 12:56:34'),
(3, 3, 1, '2026-03-21', NULL, '2026-03-21 12:56:34'),
(4, 4, 1, '2026-03-21', NULL, '2026-03-21 12:56:34'),
(5, 6, 1, '2026-03-20', NULL, '2026-03-23 19:27:03'),
(6, 7, 1, '2026-03-20', NULL, '2026-03-23 19:27:03'),
(7, 8, 1, '2026-03-20', NULL, '2026-03-23 19:27:03'),
(8, 9, 1, '2026-03-20', NULL, '2026-03-23 19:27:03'),
(9, 10, 1, '2026-03-20', NULL, '2026-03-23 19:27:03'),
(10, 11, 2, '2026-03-20', NULL, '2026-03-23 19:27:03');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_logs`
--

CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL,
  `maND` int(11) NOT NULL,
  `action` enum('IN','OUT') NOT NULL,
  `method` enum('LAN','QR') NOT NULL DEFAULT 'LAN',
  `wifi_name` varchar(120) DEFAULT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_logs`
--

INSERT INTO `attendance_logs` (`id`, `maND`, `action`, `method`, `wifi_name`, `device_info`, `note`, `created_at`) VALUES
(1, 2, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:01:17'),
(2, 2, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:01:18'),
(3, 2, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:01:19'),
(4, 2, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:22:16'),
(5, 2, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:22:17'),
(6, 2, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:22:18'),
(7, 1, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:22:57'),
(8, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:23:01'),
(9, 3, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:23:29'),
(10, 3, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:23:33'),
(11, 3, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:40:34'),
(12, 2, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:45:22'),
(13, 2, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:45:24'),
(14, 2, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 20:58:44'),
(15, 1, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:00:26'),
(16, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:00:28'),
(17, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:05:31'),
(18, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:05:32'),
(19, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:05:33'),
(20, 1, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:05:34'),
(21, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:05:34'),
(22, 1, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:13:55'),
(23, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:23:35'),
(24, 1, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:23:46'),
(25, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:23:50'),
(26, 1, 'IN', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:38:35'),
(27, 1, 'OUT', 'LAN', 'INTERNAL_NETWORK', NULL, '', '2026-03-21 21:38:45'),
(28, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 18:19:54'),
(29, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 18:32:07'),
(30, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 18:32:13'),
(31, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 18:32:20'),
(32, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 19:10:29'),
(33, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 19:19:40'),
(34, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 19:22:47'),
(35, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 19:22:51'),
(36, 1, 'IN', 'LAN', 'wifi-free', NULL, '', '2026-03-22 19:37:15'),
(37, 1, 'OUT', 'LAN', 'wifi-free', NULL, '', '2026-03-22 19:37:17'),
(38, 5, 'IN', 'LAN', 'wifi-free', NULL, '', '2026-03-22 19:40:30'),
(39, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 20:20:19'),
(40, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 20:20:33'),
(41, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 20:20:35'),
(42, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 20:21:10'),
(43, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 20:21:13'),
(44, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 20:21:40'),
(45, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-22 20:23:02'),
(46, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:54:53'),
(47, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:54:56'),
(48, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:55:16'),
(49, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:55:37'),
(50, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:58:59'),
(51, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:59:01'),
(52, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:59:31'),
(53, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:59:36'),
(54, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 17:59:50'),
(55, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:15:34'),
(56, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:17:01'),
(57, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:17:30'),
(58, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:17:52'),
(59, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:17:54'),
(60, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:26:25'),
(61, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:28:10'),
(62, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:29:28'),
(63, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:29:33'),
(64, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:35:47'),
(65, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:35:48'),
(66, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:36:38'),
(67, 1, 'OUT', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:36:40'),
(68, 1, 'IN', 'LAN', 'RFT_INTERNAL_WIFI', NULL, '', '2026-03-23 18:37:28'),
(69, 1, 'OUT', 'LAN', 'RFT_BACKUP_WIFI', NULL, '', '2026-03-23 18:42:14'),
(70, 1, 'IN', 'LAN', 'RFT_BACKUP_WIFI', NULL, '', '2026-03-23 18:42:15'),
(71, 1, 'OUT', 'LAN', 'RFT_BACKUP_WIFI', NULL, '', '2026-03-23 18:42:19'),
(72, 1, 'IN', 'LAN', 'RFT_BACKUP_WIFI', NULL, '', '2026-03-23 18:51:27'),
(73, 1, 'OUT', 'LAN', 'RFT_BACKUP_WIFI', NULL, '', '2026-03-23 18:51:32'),
(74, 1, 'IN', 'LAN', 'RFT_BACKUP_WIFI', NULL, '', '2026-03-23 19:14:36'),
(75, 1, 'OUT', 'LAN', 'RFT_BACKUP_WIFI', NULL, '', '2026-03-23 19:15:17'),
(76, 6, 'IN', 'LAN', 'Wifi Công ty', NULL, NULL, '2026-03-23 08:05:00'),
(77, 6, 'OUT', 'LAN', 'Wifi Công ty', NULL, NULL, '2026-03-23 17:00:00'),
(78, 7, 'IN', 'LAN', 'Wifi Công ty', NULL, NULL, '2026-03-23 08:25:00'),
(79, 7, 'OUT', 'LAN', 'Wifi Công ty', NULL, NULL, '2026-03-23 17:05:00'),
(80, 8, 'IN', 'QR', NULL, NULL, NULL, '2026-03-23 08:10:00'),
(81, 9, 'IN', 'LAN', 'Wifi VP Floor 2', NULL, NULL, '2026-03-23 08:00:00'),
(82, 9, 'OUT', 'LAN', 'Wifi VP Floor 2', NULL, NULL, '2026-03-23 17:00:00'),
(83, 10, 'IN', 'LAN', 'Wifi Công ty', NULL, NULL, '2026-03-23 08:00:00'),
(84, 10, 'OUT', 'LAN', 'Wifi Công ty', NULL, NULL, '2026-03-23 18:00:00'),
(85, 11, 'IN', 'LAN', 'Wifi Meeting Room', NULL, NULL, '2026-03-23 14:00:00'),
(86, 11, 'OUT', 'LAN', 'Wifi Meeting Room', NULL, NULL, '2026-03-23 22:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_monthly_approval`
--

CREATE TABLE `attendance_monthly_approval` (
  `id` int(11) NOT NULL,
  `month_key` char(7) NOT NULL COMMENT 'YYYY-MM',
  `hr_sender_id` int(11) NOT NULL,
  `manager_approver_id` int(11) DEFAULT NULL,
  `status` enum('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_shifts`
--

CREATE TABLE `attendance_shifts` (
  `id` int(11) NOT NULL,
  `shift_name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_shifts`
--

INSERT INTO `attendance_shifts` (`id`, `shift_name`, `start_time`, `end_time`, `is_active`, `created_at`) VALUES
(1, 'Ca hanh chinh', '08:00:00', '17:00:00', 1, '2026-03-21 12:56:34'),
(2, 'Ca toi', '14:00:00', '22:00:00', 1, '2026-03-21 12:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_wifi`
--

CREATE TABLE `attendance_wifi` (
  `id` int(11) NOT NULL,
  `wifi_name` varchar(120) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_range` varchar(50) DEFAULT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance_wifi`
--

INSERT INTO `attendance_wifi` (`id`, `wifi_name`, `is_active`, `created_at`, `ip_range`, `gateway`, `description`) VALUES
(4, 'Wifi Công ty', 1, '2026-03-23 17:58:32', '192.168.1', '192.168.1.1', 'Mạng nội bộ công ty - Dải IP 192.168.1.x');

-- --------------------------------------------------------

--
-- Table structure for table `nguoidung`
--

CREATE TABLE `nguoidung` (
  `maND` int(11) NOT NULL,
  `maTK` int(11) NOT NULL,
  `hoTen` varchar(120) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `soDienThoai` varchar(20) DEFAULT NULL,
  `chucVu` enum('Nhân viên','Bộ phận Nhân sự','Quản lý / Ban lãnh đạo','Bộ phận Kỹ thuật') NOT NULL DEFAULT 'Nhân viên',
  `phongBan` varchar(100) DEFAULT NULL,
  `trangThai` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nguoidung`
--

INSERT INTO `nguoidung` (`maND`, `maTK`, `hoTen`, `email`, `soDienThoai`, `chucVu`, `phongBan`, `trangThai`, `created_at`, `updated_at`) VALUES
(1, 1, 'Nguyen Van Nhan Vien', 'nhanvien01@company.local', '0901000001', 'Nhân viên', 'Sản xuất', 1, '2026-03-21 12:56:34', NULL),
(2, 2, 'Tran Thi HR', 'hr01@company.local', '0901000002', 'Bộ phận Nhân sự', 'Nhân sự', 1, '2026-03-21 12:56:34', NULL),
(3, 3, 'Le Van Quan Ly', 'manager01@company.local', '0901000003', 'Quản lý / Ban lãnh đạo', 'Điều hành', 1, '2026-03-21 12:56:34', NULL),
(4, 4, 'Pham Van Ky Thuat', 'tech01@company.local', '0901000004', 'Bộ phận Kỹ thuật', 'CNTT', 1, '2026-03-21 12:56:34', NULL),
(5, 5, 'Cẩm Vi', 'camvii93@gmail.com', '0932086236', 'Nhân viên', '', 1, '2026-03-22 18:20:56', NULL),
(6, 6, 'Nguyễn Văn A', 'nv02@company.local', '0902000001', 'Nhân viên', 'Sản xuất', 1, '2026-03-23 19:26:51', NULL),
(7, 7, 'Nguyễn Văn B', 'nv03@company.local', '0902000002', 'Nhân viên', 'Kho', 1, '2026-03-23 19:26:51', NULL),
(8, 8, 'Nguyễn Văn C', 'nv04@company.local', '0902000003', 'Nhân viên', 'IT', 1, '2026-03-23 19:26:51', NULL),
(9, 9, 'Trần Thị Hoa', 'hr02@company.local', '0902000004', 'Bộ phận Nhân sự', 'Nhân sự', 1, '2026-03-23 19:26:51', NULL),
(10, 10, 'Lê Văn Mạnh', 'manager02@company.local', '0902000005', 'Quản lý / Ban lãnh đạo', 'Điều hành', 1, '2026-03-23 19:26:51', NULL),
(11, 11, 'Phạm Văn D', 'tech02@company.local', '0902000006', 'Bộ phận Kỹ thuật', 'CNTT', 1, '2026-03-23 19:26:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'ALLOW_QR_CHECKIN', '1', 'Cho phep cham cong QR du phong', NULL, '2026-03-21 12:56:34'),
(2, 'MAX_CORRECTION_DAYS', '7', 'So ngay toi da cho phep gui yeu cau chinh sua', NULL, '2026-03-21 12:56:34'),
(3, 'DEFAULT_WORK_MINUTES', '480', 'So phut cong chuan moi ngay', NULL, '2026-03-21 12:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `taikhoan`
--

CREATE TABLE `taikhoan` (
  `maTK` int(11) NOT NULL,
  `tenDangNhap` varchar(50) NOT NULL,
  `matKhau` varchar(255) NOT NULL,
  `trangThai` enum('Hoạt động','Ngừng hoạt động') NOT NULL DEFAULT 'Hoạt động',
  `lanDangNhapCuoi` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `taikhoan`
--

INSERT INTO `taikhoan` (`maTK`, `tenDangNhap`, `matKhau`, `trangThai`, `lanDangNhapCuoi`, `created_at`, `updated_at`) VALUES
(1, 'nhanvien01', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-21 12:56:34', NULL),
(2, 'hr01', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-21 12:56:34', NULL),
(3, 'manager01', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-21 12:56:34', NULL),
(4, 'tech01', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-21 12:56:34', NULL),
(5, 'camvii9319', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-22 18:20:56', NULL),
(6, 'nhanvien02', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-23 19:26:11', NULL),
(7, 'nhanvien03', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-23 19:26:11', NULL),
(8, 'nhanvien04', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-23 19:26:11', NULL),
(9, 'hr02', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-23 19:26:11', NULL),
(10, 'manager02', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-23 19:26:11', NULL),
(11, 'tech02', 'e10adc3949ba59abbe56e057f20f883e', 'Hoạt động', NULL, '2026-03-23 19:26:11', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_correction_mand_status` (`maND`,`status`),
  ADD KEY `idx_correction_date` (`attendance_date`);

--
-- Indexes for table `attendance_daily_summary`
--
ALTER TABLE `attendance_daily_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_summary_user_date` (`maND`,`work_date`),
  ADD KEY `idx_summary_date` (`work_date`);

--
-- Indexes for table `attendance_employee_shift`
--
ALTER TABLE `attendance_employee_shift`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emp_shift_mand` (`maND`),
  ADD KEY `idx_emp_shift_shift` (`shift_id`);

--
-- Indexes for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_mand_created` (`maND`,`created_at`),
  ADD KEY `idx_logs_created` (`created_at`);

--
-- Indexes for table `attendance_monthly_approval`
--
ALTER TABLE `attendance_monthly_approval`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_month_key` (`month_key`),
  ADD KEY `idx_monthly_status` (`status`),
  ADD KEY `fk_monthly_hr_sender` (`hr_sender_id`),
  ADD KEY `fk_monthly_manager` (`manager_approver_id`);

--
-- Indexes for table `attendance_shifts`
--
ALTER TABLE `attendance_shifts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance_wifi`
--
ALTER TABLE `attendance_wifi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_wifi_name` (`wifi_name`);

--
-- Indexes for table `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD PRIMARY KEY (`maND`),
  ADD KEY `idx_nguoidung_matk` (`maTK`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_setting_key` (`setting_key`),
  ADD KEY `fk_settings_user` (`updated_by`);

--
-- Indexes for table `taikhoan`
--
ALTER TABLE `taikhoan`
  ADD PRIMARY KEY (`maTK`),
  ADD UNIQUE KEY `uk_taikhoan_tendangnhap` (`tenDangNhap`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance_daily_summary`
--
ALTER TABLE `attendance_daily_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance_employee_shift`
--
ALTER TABLE `attendance_employee_shift`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `attendance_monthly_approval`
--
ALTER TABLE `attendance_monthly_approval`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_shifts`
--
ALTER TABLE `attendance_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance_wifi`
--
ALTER TABLE `attendance_wifi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `nguoidung`
--
ALTER TABLE `nguoidung`
  MODIFY `maND` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `taikhoan`
--
ALTER TABLE `taikhoan`
  MODIFY `maTK` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_corrections`
--
ALTER TABLE `attendance_corrections`
  ADD CONSTRAINT `fk_correction_user` FOREIGN KEY (`maND`) REFERENCES `nguoidung` (`maND`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_daily_summary`
--
ALTER TABLE `attendance_daily_summary`
  ADD CONSTRAINT `fk_summary_user` FOREIGN KEY (`maND`) REFERENCES `nguoidung` (`maND`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_employee_shift`
--
ALTER TABLE `attendance_employee_shift`
  ADD CONSTRAINT `fk_emp_shift_shift` FOREIGN KEY (`shift_id`) REFERENCES `attendance_shifts` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_emp_shift_user` FOREIGN KEY (`maND`) REFERENCES `nguoidung` (`maND`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_logs`
--
ALTER TABLE `attendance_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`maND`) REFERENCES `nguoidung` (`maND`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `attendance_monthly_approval`
--
ALTER TABLE `attendance_monthly_approval`
  ADD CONSTRAINT `fk_monthly_hr_sender` FOREIGN KEY (`hr_sender_id`) REFERENCES `nguoidung` (`maND`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_monthly_manager` FOREIGN KEY (`manager_approver_id`) REFERENCES `nguoidung` (`maND`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `nguoidung`
--
ALTER TABLE `nguoidung`
  ADD CONSTRAINT `fk_nguoidung_taikhoan` FOREIGN KEY (`maTK`) REFERENCES `taikhoan` (`maTK`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `fk_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `nguoidung` (`maND`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
