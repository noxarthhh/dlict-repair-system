-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 07:24 AM
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
-- Database: `fixrequest`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `asset_id` int(11) NOT NULL COMMENT 'รหัสครุภัณฑ์',
  `asset_number` varchar(50) NOT NULL COMMENT 'หมายเลขทะเบียนครุภัณฑ์',
  `asset_type` varchar(50) NOT NULL COMMENT 'ชนิดอุปกรณ์',
  `location_group` varchar(100) NOT NULL COMMENT 'กลุ่มที่ครุภัณฑ์ตั้งอยู่',
  `responsible_staff_id` int(11) NOT NULL COMMENT 'ผู้รับผิดชอบเครื่อง',
  `status` varchar(20) NOT NULL COMMENT 'สถานะครุภัณฑ์',
  `model` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`asset_id`, `asset_number`, `asset_type`, `location_group`, `responsible_staff_id`, `status`, `model`) VALUES
(101, 'PC-A001', 'Desktop PC', 'กลุ่มบริหารงานบุคคล', 3, 'ใช้งานปกติ', ''),
(102, 'PR-B005', 'Printer Laser', 'กลุ่มบริหารงานบุคคล', 3, 'ใช้งานปกติ', ''),
(103, 'NB-C010', 'Notebook', 'กลุ่มส่งเสริมฯ (DLICT)', 2, 'ใช้งานปกติ', '');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `username` varchar(40) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `attempt_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `username`, `ip_address`, `success`, `attempt_time`) VALUES
(1, 'admin', '::1', 0, '2025-12-03 15:02:57'),
(2, 'admin', '::1', 1, '2025-12-03 15:03:13'),
(3, 'admin', '::1', 1, '2025-12-03 15:19:33'),
(4, 'admin', '::1', 1, '2025-12-03 15:24:55'),
(5, 'admin', '::1', 0, '2025-12-03 15:44:46'),
(6, 'admin', '::1', 1, '2025-12-03 15:44:53'),
(7, 'admin', '::1', 0, '2025-12-08 09:27:17'),
(8, 'admin', '::1', 0, '2025-12-08 09:28:05'),
(9, 'admin', '::1', 1, '2025-12-08 09:28:12'),
(10, 'admin', '::1', 1, '2025-12-08 09:44:45'),
(11, 'user01', '::1', 1, '2025-12-08 09:59:15'),
(12, 'admin', '::1', 1, '2025-12-08 10:01:23'),
(13, 'admin', '::1', 1, '2025-12-08 10:08:12'),
(14, 'user01', '::1', 0, '2025-12-08 10:08:41'),
(15, 'user01', '::1', 1, '2025-12-08 10:08:47'),
(16, 'admin', '::1', 1, '2025-12-08 10:09:20'),
(17, 'admin', '::1', 0, '2025-12-08 10:10:43'),
(18, 'admin', '::1', 1, '2025-12-08 10:10:48'),
(19, 'admin', '::1', 1, '2025-12-08 10:17:27'),
(20, 'admin', '::1', 1, '2025-12-08 10:20:07'),
(21, 'tech01', '::1', 1, '2025-12-08 10:20:24'),
(22, 'user01', '::1', 1, '2025-12-08 10:21:00'),
(23, 'admin', '::1', 1, '2025-12-08 10:21:36'),
(24, 'user02', '::1', 1, '2025-12-08 10:27:04'),
(25, 'admin', '::1', 1, '2025-12-08 10:27:40'),
(26, 'admin', '::1', 1, '2025-12-08 10:29:57'),
(27, 'panpitakppt@gmail.com', '::1', 0, '2025-12-08 10:37:19'),
(28, 'panpitakppt@gmail.com', '::1', 0, '2025-12-08 10:38:40'),
(29, 'panpitakppt@gmail.com', '::1', 0, '2025-12-08 10:38:44'),
(30, 'panpitakppt@gmail.com', '::1', 0, '2025-12-08 10:38:52'),
(31, 'panpitakppt@gmail.com', '::1', 0, '2025-12-08 10:38:59'),
(32, 'admin', '::1', 1, '2025-12-08 10:53:14'),
(33, 'admin', '::1', 1, '2025-12-08 11:16:10'),
(34, 'admin', '::1', 0, '2025-12-08 11:20:32'),
(35, 'admin', '::1', 1, '2025-12-08 11:20:36'),
(36, 'admin', '::1', 1, '2025-12-08 11:27:36'),
(37, 'admin', '::1', 1, '2025-12-08 11:30:52'),
(38, 'user01', '::1', 1, '2025-12-08 11:33:30'),
(39, 'admin', '::1', 1, '2025-12-08 11:36:22'),
(40, 'user03', '::1', 1, '2025-12-08 11:36:59'),
(41, 'admin', '::1', 1, '2025-12-08 11:37:18'),
(42, 'admin', '::1', 1, '2025-12-08 11:48:28'),
(43, 'admin', '::1', 1, '2025-12-08 11:53:54'),
(44, 'user01', '::1', 1, '2025-12-08 11:56:08'),
(45, 'admin', '::1', 1, '2025-12-08 11:57:06');

-- --------------------------------------------------------

--
-- Table structure for table `repair_requests`
--

CREATE TABLE `repair_requests` (
  `request_id` int(11) NOT NULL COMMENT 'รหัสใบแจ้งซ่อม',
  `request_no` varchar(20) NOT NULL COMMENT 'รันนิ่งนัมเบอร์ของใบแจ้งซ่อม',
  `status` varchar(20) NOT NULL COMMENT 'สถานะการซ่อม',
  `requester_id` int(11) NOT NULL COMMENT 'ผู้แจ้งซ่อม',
  `asset_id` int(11) DEFAULT NULL COMMENT 'เครื่องที่ต้องการซ่อม',
  `manual_asset` varchar(100) DEFAULT NULL COMMENT 'เก็บชื่อครุภัณฑ์กรณีพิมพ์เอง',
  `issue_details` text NOT NULL COMMENT 'อาการ/ปัญหาที่พบ',
  `image_path` varchar(255) DEFAULT NULL,
  `request_date` datetime NOT NULL COMMENT 'วันที่/เวลา ที่แจ้งซ่อม',
  `problem_types` text DEFAULT NULL COMMENT 'ชนิดปัญหาที่เลือก',
  `technician_id` int(11) DEFAULT NULL COMMENT 'ผู้ดำเนินการซ่อม',
  `action_taken` text DEFAULT NULL COMMENT 'การดำเนินการซ่อมของ DLICT',
  `repair_completion_date` datetime DEFAULT NULL COMMENT 'วันที่ซ่อมเสร็จ',
  `approver_id` int(11) DEFAULT NULL COMMENT 'ผู้อนุมัติ',
  `approval_date` datetime DEFAULT NULL COMMENT 'วันที่ผู้อนุมัติ (ผู้อำนวยการกลุ่มฯ) ลงนาม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `repair_requests`
--

INSERT INTO `repair_requests` (`request_id`, `request_no`, `status`, `requester_id`, `asset_id`, `manual_asset`, `issue_details`, `image_path`, `request_date`, `problem_types`, `technician_id`, `action_taken`, `repair_completion_date`, `approver_id`, `approval_date`) VALUES
(10, 'FIX-2512-001', 'Completed', 1, NULL, NULL, 'เครื่องติด แต่จอไม่ติด', NULL, '2025-12-08 10:54:13', NULL, 1, 'ทำความสะอาดแรม นำมาขัดหน้าสัมผัสเพราะเกิดจากฝุ่น', '2025-12-08 04:55:04', NULL, NULL),
(11, 'FIX-2512-002', 'Completed', 1, NULL, 'asd', 'asd', NULL, '2025-12-08 10:59:56', NULL, 1, 'ccc', '2025-12-08 05:05:26', NULL, NULL),
(12, 'FIX-2512-003', 'Completed', 1, NULL, 'asd', 'asd', NULL, '2025-12-08 11:03:59', NULL, 1, 'asdasd', '2025-12-08 05:05:15', NULL, NULL),
(13, 'FIX-2512-004', 'Completed', 1, NULL, '159768453132', 'เเเ', NULL, '2025-12-08 11:06:10', NULL, 1, 'ซื้อใหม่เลย', '2025-12-08 05:16:23', NULL, NULL),
(14, 'FIX-2512-005', 'In Progress', 1, NULL, 'ASD-QWDERFT258', 'จอฟ้า', 'uploads/img_6936522db2cc54.18181687.png', '2025-12-08 11:21:01', NULL, 1, NULL, NULL, NULL, NULL),
(15, 'FIX-2512-006', 'Pending', 3, NULL, 'asd', 'asd', NULL, '2025-12-08 11:56:13', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `staff_id` int(11) NOT NULL COMMENT 'รหัสบุคลากร',
  `username` varchar(100) NOT NULL COMMENT 'ชื่อผู้ใช้สำหรับ',
  `password_hash` varchar(256) NOT NULL COMMENT 'รหัสผ่านที่เข้ารหัส',
  `full_name` varchar(100) NOT NULL COMMENT 'ชื่อ-นามสกุลเต็ม',
  `group_name` varchar(100) NOT NULL COMMENT 'กลุ่ม/ฝ่าย',
  `position` varchar(50) NOT NULL COMMENT 'ตำแหน่ง',
  `role` varchar(20) NOT NULL COMMENT 'บทบาทในระบบ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`staff_id`, `username`, `password_hash`, `full_name`, `group_name`, `position`, `role`) VALUES
(1, 'admin', '$2y$12$mkf0lMlKVW7.7TPfiyiPuuahY9vGWdJYS41QoD7XMKGz8cLUEOq2q', 'นายเกริกขจร ใจถึงพึ่งตื่น', 'กลุ่มส่งเสริมฯ (DLICT)', 'ผู้อำนวยการกลุ่ม', 'admin'),
(2, 'tech01', '$2y$12$mkf0lMlKVW7.7TPfiyiPuuahY9vGWdJYS41QoD7XMKGz8cLUEOq2q', 'นายอเล็กซ์ บินมาดู', 'กลุ่มส่งเสริมฯ (DLICT)', 'เจ้าหน้าที่เทคนิค', 'technician'),
(3, 'user01', '$2y$12$mkf0lMlKVW7.7TPfiyiPuuahY9vGWdJYS41QoD7XMKGz8cLUEOq2q', 'นายพันธุ์พิทักษ์ พงษ์สะพัง', 'กลุ่มส่งเสริม (DLICT)', 'ผู้ช่วยนักวิชาการคอมพิเวเตอร์', 'requester'),
(4, 'user02', '$2y$12$mkf0lMlKVW7.7TPfiyiPuuahY9vGWdJYS41QoD7XMKGz8cLUEOq2q', 'นายเจมส์ เฮทฟิลด์', 'กลุ่มส่งเสริม (DLICT)', 'ผู้ช่วยนักวิชาการคอมพิเวเตอร์', 'requester'),
(5, 'panpitakppt@gmail.com', '$2y$10$7/Oa2jK5o4b5G8H9L6M3A2B1C0F4E8D7I6J1K0Z3Y2X1W9V8U7T6S5R4Q3P2O1N', 'นายพพ พพ', 'เล่นเกม', 'สตรีมเมอร์', 'admin'),
(6, 'user03', '$2y$10$DPloSJ79.JP2c9RlwtlDGeLVV9NKYPWqVkjI./cCr37O.lo7S90Rm', 'GGGG', 'GGGG', 'GGGG', 'requester'),
(7, 'admin1', '$2y$10$EXwczgxTwa/Uj3jVH95n4.Xrcc87v.9egAZ/qQ/FaQJ3jl/VCAdnm', 'admin1', 'admin1', 'admin1', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`asset_id`),
  ADD KEY `responsible_staff_id` (`responsible_staff_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_username_time` (`username`,`attempt_time`);

--
-- Indexes for table `repair_requests`
--
ALTER TABLE `repair_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `technician_id` (`technician_id`),
  ADD KEY `approver_id` (`approver_id`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `username_2` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `asset_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสครุภัณฑ์', AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `repair_requests`
--
ALTER TABLE `repair_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสใบแจ้งซ่อม', AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `staffs`
--
ALTER TABLE `staffs`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'รหัสบุคลากร', AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
