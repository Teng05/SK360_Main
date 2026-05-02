-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 01, 2026 at 03:56 PM
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
-- Database: `sk360_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `accomplishment_reports`
--

CREATE TABLE `accomplishment_reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `report_type` enum('monthly','quarterly','annual') NOT NULL,
  `submission_method` enum('direct_input','file_upload') NOT NULL,
  `title` varchar(255) NOT NULL,
  `reporting_year` year(4) NOT NULL,
  `reporting_month` tinyint(4) DEFAULT NULL,
  `reporting_quarter` enum('Q1','Q2','Q3','Q4') DEFAULT NULL,
  `generated_pdf_path` varchar(255) DEFAULT NULL,
  `uploaded_file_name` varchar(255) DEFAULT NULL,
  `uploaded_file_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','approved','rejected') DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accomplishment_reports`
--

INSERT INTO `accomplishment_reports` (`report_id`, `user_id`, `barangay_id`, `slot_id`, `report_type`, `submission_method`, `title`, `reporting_year`, `reporting_month`, `reporting_quarter`, `generated_pdf_path`, `uploaded_file_name`, `uploaded_file_path`, `status`, `remarks`, `submitted_at`, `created_at`) VALUES
(1, 7, 2, NULL, 'monthly', 'file_upload', 'asd', '2026', 4, NULL, NULL, 'SK360.pdf', 'uploads/reports/REP_1777574199_2.pdf', 'submitted', NULL, '2026-04-30 18:36:40', '2026-04-30 10:36:40'),
(2, 7, 2, NULL, 'monthly', 'file_upload', 'April report', '2026', 4, NULL, NULL, 'Welcome_to_Our_Solar_System.pdf', 'uploads/reports/REP_1777575254_2.pdf', 'submitted', NULL, '2026-04-30 18:54:14', '2026-04-30 10:54:14');

-- --------------------------------------------------------

--
-- Table structure for table `accomplishment_report_items`
--

CREATE TABLE `accomplishment_report_items` (
  `item_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `activity_title` varchar(255) NOT NULL,
  `activity_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `participants_count` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `accomplishments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `visibility` enum('public','officials_only') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `user_id`, `title`, `content`, `visibility`, `created_at`, `updated_at`) VALUES
(1, 1, 'April dup', 'ASDASDASDA', 'public', '2026-04-27 08:14:21', '2026-04-27 08:14:21'),
(2, 1, 'Reports', 'ADSAS', 'public', '2026-04-29 20:02:42', '2026-04-29 20:02:42');

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `barangay_id` int(11) NOT NULL,
  `barangay_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`barangay_id`, `barangay_name`) VALUES
(1, 'Adya'),
(2, 'Anilao'),
(3, 'Anilao-Labac'),
(4, 'Antipolo del Norte'),
(5, 'Antipolo del Sur'),
(6, 'Bagong Pook'),
(7, 'Balintawak'),
(8, 'Banaybanay'),
(9, 'Barangay 12'),
(10, 'Bolbok'),
(11, 'Bugtong na Pulo'),
(12, 'Bulacnin'),
(13, 'Bulaklakan'),
(14, 'Calamias'),
(15, 'Cumba'),
(16, 'Dagatan'),
(17, 'Duhatan'),
(18, 'Halang'),
(19, 'Inosluban'),
(20, 'Kayumanggi'),
(21, 'Latag'),
(22, 'Lodlod'),
(23, 'Lumbang'),
(24, 'Mabini'),
(25, 'Malagonlong'),
(26, 'Malitlit'),
(27, 'Marauoy'),
(28, 'Mataas na Lupa'),
(29, 'Munting Pulo'),
(30, 'Pagolingin Bata'),
(31, 'Pagolingin East'),
(32, 'Pagolingin West'),
(33, 'Pangao'),
(34, 'Pinagkawitan'),
(35, 'Pinagtongulan'),
(36, 'Plaridel'),
(37, 'Poblacion Barangay 1'),
(38, 'Poblacion Barangay 2'),
(39, 'Poblacion Barangay 3'),
(40, 'Poblacion Barangay 4'),
(41, 'Poblacion Barangay 5'),
(42, 'Poblacion Barangay 6'),
(43, 'Poblacion Barangay 7'),
(44, 'Poblacion Barangay 8'),
(45, 'Poblacion Barangay 9'),
(46, 'Poblacion Barangay 9-A'),
(47, 'Poblacion Barangay 10'),
(48, 'Poblacion Barangay 11'),
(49, 'Pusil'),
(50, 'Quezon'),
(51, 'Rizal'),
(52, 'Sabang'),
(53, 'Sampaguita'),
(54, 'San Benito'),
(55, 'San Carlos'),
(56, 'San Celestino'),
(57, 'San Francisco'),
(58, 'San Guillermo'),
(59, 'San Jose'),
(60, 'San Lucas'),
(61, 'San Salvador'),
(62, 'San Sebastian'),
(63, 'Santo Niño'),
(64, 'Santo Toribio'),
(65, 'Sapac'),
(66, 'Sico'),
(67, 'Talisay'),
(68, 'Tambo'),
(69, 'Tangob'),
(70, 'Tanguay'),
(71, 'Tibig'),
(72, 'Tipacan');

-- --------------------------------------------------------

--
-- Table structure for table `budget_reports`
--

CREATE TABLE `budget_reports` (
  `budget_report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `slot_id` int(11) DEFAULT NULL,
  `submission_method` enum('direct_input','file_upload') NOT NULL,
  `document_type` enum('budget_proposal','liquidation_report','financial_record') NOT NULL,
  `budget_period_type` enum('monthly','quarterly','annual') NOT NULL DEFAULT 'annual',
  `fiscal_year` year(4) NOT NULL,
  `fiscal_month` tinyint(4) DEFAULT NULL,
  `fiscal_quarter` enum('Q1','Q2','Q3','Q4') DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `generated_pdf_path` varchar(255) DEFAULT NULL,
  `template_data` longtext DEFAULT NULL,
  `uploaded_file_name` varchar(255) DEFAULT NULL,
  `uploaded_file_path` varchar(255) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','submitted','recorded','archived') DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_reports`
--

INSERT INTO `budget_reports` (`budget_report_id`, `user_id`, `barangay_id`, `slot_id`, `submission_method`, `document_type`, `budget_period_type`, `fiscal_year`, `fiscal_month`, `fiscal_quarter`, `title`, `generated_pdf_path`, `template_data`, `uploaded_file_name`, `uploaded_file_path`, `total_amount`, `status`, `submitted_at`, `created_at`) VALUES
(1, 7, 2, 5, 'direct_input', 'financial_record', 'annual', '2026', NULL, NULL, 'Budget', 'TEMPLATE_GEN', '{\"slot_id\":\"5\",\"monitoring_officer\":\"Chairman Sad\",\"sheet_no\":\"2026-005\",\"city\":\"LIPA CITY\",\"province\":\"BATANGAS\",\"program_project_activity\":\"NOT APPLICABLE\",\"spf_carried_forward\":null,\"commitments_carried_forward\":null,\"payments_carried_forward\":null,\"available_balance\":null,\"unpaid_commitments\":null,\"certified_date\":\"2026-05-01\",\"rows\":[{\"particulars\":\"asd\",\"date\":\"2026-05-01\",\"reference\":null,\"total_amount\":\"1111\",\"object_1\":\"0.04\",\"object_2\":null,\"object_3\":null,\"object_4\":null}]}', NULL, NULL, 1111.00, 'recorded', '2026-05-01 08:45:01', '2026-05-01 00:45:01'),
(2, 7, 2, 6, 'direct_input', 'financial_record', 'annual', '2026', NULL, NULL, 'dsa', 'TEMPLATE_GEN', '{\"slot_id\":\"6\",\"monitoring_officer\":\"Chairman Sad\",\"sheet_no\":\"2026-006\",\"city\":\"LIPA CITY\",\"province\":\"BATANGAS\",\"program_project_activity\":\"NOT APPLICABLE\",\"spf_carried_forward\":\"10\",\"commitments_carried_forward\":\"20\",\"payments_carried_forward\":\"31\",\"available_balance\":\"12\",\"unpaid_commitments\":\"321\",\"certified_date\":\"2026-05-01\",\"rows\":[{\"particulars\":\"A. Budget\",\"date\":null,\"reference\":null,\"total_amount\":\"100\",\"object_1\":null,\"object_2\":null,\"object_3\":null,\"object_4\":null},{\"particulars\":\"B. Court\",\"date\":\"2026-05-01\",\"reference\":null,\"total_amount\":\"30\",\"object_1\":null,\"object_2\":null,\"object_3\":null,\"object_4\":\"30\"}]}', NULL, NULL, 130.00, 'recorded', '2026-05-01 08:55:52', '2026-05-01 00:55:52');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_archive`
--

CREATE TABLE `document_archive` (
  `archive_id` int(11) NOT NULL,
  `source_type` enum('accomplishment_report','budget_report','other_document') NOT NULL,
  `source_id` int(11) NOT NULL,
  `archived_by` int(11) NOT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `verification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`verification_id`, `user_id`, `verification_code`, `expires_at`, `created_at`) VALUES
(5, 8, '356150', '2026-03-30 00:37:05', '2026-03-29 15:36:50');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_type` enum('meeting','deadline','program','other') DEFAULT 'other',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `dyte_meeting_id` varchar(255) DEFAULT NULL,
  `dyte_room_name` varchar(255) DEFAULT NULL,
  `visibility` enum('public','officials_only','chairman_only','secretary_only') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `created_by`, `title`, `description`, `location`, `event_type`, `start_datetime`, `end_datetime`, `dyte_meeting_id`, `dyte_room_name`, `visibility`, `created_at`) VALUES
(4, 1, 'Meet', NULL, NULL, 'meeting', '2026-04-26 00:00:00', '2026-04-26 23:59:59', 'bbb4a185-d8a7-4fde-8c65-4669d7c4b95c', NULL, 'public', '2026-04-24 06:13:27'),
(5, 1, 'Report submission', NULL, NULL, 'deadline', '2026-04-25 00:00:00', '2026-04-28 23:59:59', NULL, NULL, 'public', '2026-04-25 01:23:54');

-- --------------------------------------------------------

--
-- Table structure for table `leadership_profiles`
--

CREATE TABLE `leadership_profiles` (
  `leadership_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `barangay_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `position` enum('sk_president','sk_chairman','sk_secretary') NOT NULL,
  `term_start` date NOT NULL,
  `term_end` date DEFAULT NULL,
  `status` enum('current','former') DEFAULT 'current',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `meeting_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `agenda` text DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` time NOT NULL,
  `location_or_link` varchar(255) DEFAULT NULL,
  `dyte_meeting_id` varchar(100) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`meeting_id`, `title`, `agenda`, `meeting_date`, `meeting_time`, `location_or_link`, `dyte_meeting_id`, `created_by`, `status`, `created_at`, `updated_at`) VALUES
(1, 'April meeting', NULL, '2026-04-24', '22:30:00', NULL, 'bbb4a185-d8a7-4fde-8c65-4669d7c4b95c', 1, 'scheduled', '2026-04-24 06:31:02', '2026-04-24 06:49:44'),
(2, 'April meet', 'asd', '2026-04-25', '17:25:00', NULL, NULL, 1, 'scheduled', '2026-04-25 01:25:05', '2026-04-25 01:25:05'),
(3, 'aps', NULL, '2026-04-30', '11:56:00', NULL, NULL, 1, 'scheduled', '2026-04-29 19:56:35', '2026-04-29 19:56:35'),
(4, 'DSA', NULL, '2026-05-01', '01:33:00', NULL, NULL, 1, 'scheduled', '2026-04-30 09:33:46', '2026-04-30 09:33:46');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_05_01_000002_create_notifications_table', 1),
(2, '2026_05_01_000003_add_slot_id_to_submission_tables', 2),
(3, '2026_05_01_000004_add_template_data_to_budget_reports', 3),
(4, '2026_05_02_000001_add_period_fields_to_budget_reports', 4),
(5, '2026_05_02_000002_create_ranking_point_logs_table', 5);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `actor_id`, `type`, `title`, `message`, `url`, `is_read`, `created_at`, `read_at`) VALUES
(1, 7, 1, 'report_slot', 'New submission slot', 'asd is now open from 2026-05-01 to 2026-05-03', 'http://127.0.0.1:8000/sk_chairman/reports', 1, '2026-04-30 23:57:40', '2026-04-30 23:59:24'),
(2, 8, 1, 'report_slot', 'New submission slot', 'asd is now open from 2026-05-01 to 2026-05-03', 'http://127.0.0.1:8000/sk_secretary/reports', 0, '2026-04-30 23:57:40', NULL),
(3, 7, 1, 'budget_slot', 'New submission slot', 'Budget is now open from 2026-05-01 to 2026-05-03', 'http://127.0.0.1:8000/sk_chairman/budget', 1, '2026-05-01 00:29:53', '2026-05-01 00:30:39'),
(4, 7, 1, 'budget_slot', 'New submission slot', 'dsa is now open from 2026-05-01 to 2026-05-01', 'http://127.0.0.1:8000/sk_chairman/budget', 1, '2026-05-01 00:51:15', '2026-05-01 00:51:50'),
(5, 8, 1, 'budget_slot', 'New submission slot', 'dsa is now open from 2026-05-01 to 2026-05-01', 'http://127.0.0.1:8000/sk_secretary/budget', 0, '2026-05-01 00:51:15', NULL),
(6, 7, 1, 'budget_slot', 'New submission slot', 'dsa is now open from 2026-05-01 to 2026-05-01', 'http://127.0.0.1:8000/sk_chairman/budget', 1, '2026-05-01 00:51:15', '2026-05-01 05:54:10'),
(7, 8, 1, 'budget_slot', 'New submission slot', 'dsa is now open from 2026-05-01 to 2026-05-01', 'http://127.0.0.1:8000/sk_secretary/budget', 0, '2026-05-01 00:51:15', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `method` enum('email','phone') DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rankings`
--

CREATE TABLE `rankings` (
  `ranking_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `reporting_period` varchar(50) NOT NULL,
  `total_points` int(11) DEFAULT 0,
  `timely_submission_points` int(11) DEFAULT 0,
  `completeness_points` int(11) DEFAULT 0,
  `participation_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ranking_point_logs`
--

CREATE TABLE `ranking_point_logs` (
  `ranking_point_log_id` bigint(20) UNSIGNED NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reporting_period` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `points` int(11) NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `source_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sk_council`
--

CREATE TABLE `sk_council` (
  `council_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `position` enum('SK Chairman','SK Secretary','SK Treasurer','SK Councilor') NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `term` varchar(50) DEFAULT '2024-2026',
  `profile_img` varchar(255) DEFAULT 'default.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sk_council`
--

INSERT INTO `sk_council` (`council_id`, `barangay_id`, `name`, `position`, `email`, `phone`, `term`, `profile_img`, `created_at`) VALUES
(1, 2, 'Teng Monje', 'SK Councilor', 'asd@gmail.com', '09123123122', '2023-2026', 'default.png', '2026-04-30 12:54:42'),
(2, 2, 'Paul Monje', 'SK Councilor', 'p@gmail.com', '09123123122', '2023-2026', 'default.png', '2026-04-30 12:56:24');

-- --------------------------------------------------------

--
-- Table structure for table `submission_slots`
--

CREATE TABLE `submission_slots` (
  `slot_id` int(11) NOT NULL,
  `submission_type` varchar(100) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `submission_slots`
--

INSERT INTO `submission_slots` (`slot_id`, `submission_type`, `title`, `description`, `role`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(2, 'a', 'a', 'a', 'SK Chairman', '2026-04-02', '2026-04-02', 'closed', '2026-04-02 07:29:37'),
(3, 'accomplishment_report', 'April report', 'asd', 'Both', '2026-05-01', '2026-05-02', 'open', '2026-04-30 18:53:08'),
(4, 'accomplishment_report', 'asd', 'asd', 'Both', '2026-05-01', '2026-05-03', 'open', '2026-05-01 07:57:40'),
(5, 'budget_report', 'Budget', NULL, 'SK Chairman', '2026-05-01', '2026-05-03', 'open', '2026-05-01 08:29:53'),
(6, 'budget_report', 'dsa', 'das', 'Both', '2026-05-01', '2026-05-01', 'open', '2026-05-01 08:51:15'),
(7, 'budget_report', 'dsa', 'das', 'Both', '2026-05-01', '2026-05-01', 'open', '2026-05-01 08:51:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `barangay_id` int(11) DEFAULT NULL,
  `role` enum('youth','sk_president','sk_chairman','sk_secretary') DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `phone_number`, `password`, `barangay_id`, `role`, `is_verified`, `status`, `created_at`, `profile_pic`) VALUES
(1, 'Paul', 'Monje', 'paulmonje123@gmail.com', '+63991792401', '$2y$10$cN1gDriF84ABIOMsc31vjO2IWcocm254g/Dzaj7yB4ab.VPpUpQk2', 8, 'sk_president', 1, 'active', '2026-03-22 16:12:10', NULL),
(3, 'Marielle', 'Bautista', 'bautistamarielle1226@gmail.com', '09184659874', '$2y$10$LouylMGrUVgNXufevyNDCemSqMudgM7l9XFXGVIL39T9hTymAIqBG', 43, 'youth', 1, 'active', '2026-03-23 04:36:42', NULL),
(7, 'Chairman', 'Sad', 'chairman@gmail.com', '09123123122', '$2y$10$IzVPkdqTS9uUJh5INapt.Oto6kBzZvd11PkscVsGD0B/to2xreY3a', 2, 'sk_chairman', 1, 'active', '2026-03-29 15:07:17', NULL),
(8, 'Secretary', 'Sad', 'sec@gmail.com', '09123123122', '$2y$10$nogOXKztdQgGzZ.aQpYyfOQRPEY.44zANBHoYbv37biGwvY4nEYbW', 1, 'sk_secretary', 1, 'active', '2026-03-29 15:36:50', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accomplishment_reports`
--
ALTER TABLE `accomplishment_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barangay_id` (`barangay_id`),
  ADD KEY `accomplishment_reports_slot_id_index` (`slot_id`);

--
-- Indexes for table `accomplishment_report_items`
--
ALTER TABLE `accomplishment_report_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`barangay_id`);

--
-- Indexes for table `budget_reports`
--
ALTER TABLE `budget_reports`
  ADD PRIMARY KEY (`budget_report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barangay_id` (`barangay_id`),
  ADD KEY `budget_reports_slot_id_index` (`slot_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `document_archive`
--
ALTER TABLE `document_archive`
  ADD PRIMARY KEY (`archive_id`),
  ADD KEY `archived_by` (`archived_by`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`verification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `leadership_profiles`
--
ALTER TABLE `leadership_profiles`
  ADD PRIMARY KEY (`leadership_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barangay_id` (`barangay_id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`meeting_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`),
  ADD KEY `notifications_actor_fk` (`actor_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`reset_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `rankings`
--
ALTER TABLE `rankings`
  ADD PRIMARY KEY (`ranking_id`),
  ADD KEY `barangay_id` (`barangay_id`);

--
-- Indexes for table `ranking_point_logs`
--
ALTER TABLE `ranking_point_logs`
  ADD PRIMARY KEY (`ranking_point_log_id`),
  ADD UNIQUE KEY `ranking_point_logs_unique_source` (`reporting_period`,`barangay_id`,`action`,`source_type`,`source_id`),
  ADD KEY `ranking_point_logs_barangay_id_index` (`barangay_id`),
  ADD KEY `ranking_point_logs_user_id_index` (`user_id`);

--
-- Indexes for table `sk_council`
--
ALTER TABLE `sk_council`
  ADD PRIMARY KEY (`council_id`);

--
-- Indexes for table `submission_slots`
--
ALTER TABLE `submission_slots`
  ADD PRIMARY KEY (`slot_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accomplishment_reports`
--
ALTER TABLE `accomplishment_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `accomplishment_report_items`
--
ALTER TABLE `accomplishment_report_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `budget_reports`
--
ALTER TABLE `budget_reports`
  MODIFY `budget_report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_archive`
--
ALTER TABLE `document_archive`
  MODIFY `archive_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `leadership_profiles`
--
ALTER TABLE `leadership_profiles`
  MODIFY `leadership_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `meeting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `reset_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rankings`
--
ALTER TABLE `rankings`
  MODIFY `ranking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ranking_point_logs`
--
ALTER TABLE `ranking_point_logs`
  MODIFY `ranking_point_log_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sk_council`
--
ALTER TABLE `sk_council`
  MODIFY `council_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `submission_slots`
--
ALTER TABLE `submission_slots`
  MODIFY `slot_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accomplishment_reports`
--
ALTER TABLE `accomplishment_reports`
  ADD CONSTRAINT `accomplishment_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `accomplishment_reports_ibfk_2` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`);

--
-- Constraints for table `accomplishment_report_items`
--
ALTER TABLE `accomplishment_report_items`
  ADD CONSTRAINT `accomplishment_report_items_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `accomplishment_reports` (`report_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `budget_reports`
--
ALTER TABLE `budget_reports`
  ADD CONSTRAINT `budget_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `budget_reports_ibfk_2` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`);

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `document_archive`
--
ALTER TABLE `document_archive`
  ADD CONSTRAINT `document_archive_ibfk_1` FOREIGN KEY (`archived_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `leadership_profiles`
--
ALTER TABLE `leadership_profiles`
  ADD CONSTRAINT `leadership_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `leadership_profiles_ibfk_2` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_actor_fk` FOREIGN KEY (`actor_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `notifications_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `rankings`
--
ALTER TABLE `rankings`
  ADD CONSTRAINT `rankings_ibfk_1` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
