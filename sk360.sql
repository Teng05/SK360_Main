-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2026 at 09:40 AM
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
-- Database: `sk360`
--

-- --------------------------------------------------------

--
-- Table structure for table `accomplishment_reports`
--

CREATE TABLE `accomplishment_reports` (
  `report_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `barangay_id` int(11) NOT NULL,
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
  `submission_method` enum('direct_input','file_upload') NOT NULL,
  `document_type` enum('budget_proposal','liquidation_report','financial_record') NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `title` varchar(255) NOT NULL,
  `generated_pdf_path` varchar(255) DEFAULT NULL,
  `uploaded_file_name` varchar(255) DEFAULT NULL,
  `uploaded_file_path` varchar(255) DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('draft','submitted','recorded','archived') DEFAULT 'draft',
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `visibility` enum('public','officials_only') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `method` enum('email','phone') DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accomplishment_reports`
--
ALTER TABLE `accomplishment_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `barangay_id` (`barangay_id`);

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
  ADD KEY `barangay_id` (`barangay_id`);

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
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accomplishment_report_items`
--
ALTER TABLE `accomplishment_report_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `budget_reports`
--
ALTER TABLE `budget_reports`
  MODIFY `budget_report_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `verification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leadership_profiles`
--
ALTER TABLE `leadership_profiles`
  MODIFY `leadership_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `submission_slots`
--
ALTER TABLE `submission_slots`
  MODIFY `slot_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

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
