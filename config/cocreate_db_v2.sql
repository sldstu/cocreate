-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2025 at 12:20 PM
-- Server version: 11.4.5-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cocreate_db_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `analytics_data`
--

CREATE TABLE `analytics_data` (
  `data_id` int(11) NOT NULL,
  `data_type` varchar(50) NOT NULL,
  `data_date` date NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`metrics`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `is_system_wide` tinyint(1) DEFAULT 0,
  `start_date` datetime DEFAULT current_timestamp(),
  `end_date` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Community Development', 'Community engagement and growth', '2025-04-08 08:51:18', '2025-04-14 08:29:29'),
(2, 'Marketing', 'Marketing and communications', '2025-04-08 08:51:18', '2025-04-08 08:51:18'),
(3, 'Operations', 'Event operations and logistics', '2025-04-08 08:51:18', '2025-04-08 08:51:18'),
(4, 'Research and Development', 'Technical research and development', '2025-04-08 08:51:18', '2025-04-14 08:29:29'),
(5, 'Executive', 'Executive leadership team', '2025-04-08 08:51:18', '2025-04-14 08:29:29');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `location_map_url` varchar(255) DEFAULT NULL,
  `type_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `visibility` enum('draft','private','unlisted','public') DEFAULT 'draft',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `max_participants` int(11) DEFAULT NULL,
  `speakers` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_attachments`
--

CREATE TABLE `event_attachments` (
  `attachment_id` int(11) NOT NULL,
  `event_id` int(11) DEFAULT NULL,
  `sub_event_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_comments`
--

CREATE TABLE `event_comments` (
  `comment_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `content` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_ratings`
--

CREATE TABLE `event_ratings` (
  `rating_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_rsvps`
--

CREATE TABLE `event_rsvps` (
  `rsvp_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `status` enum('going','interested','not_going') NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_types`
--

CREATE TABLE `event_types` (
  `type_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_types`
--

INSERT INTO `event_types` (`type_id`, `name`, `description`, `color`, `icon`, `created_at`) VALUES
(1, 'Conference', 'Large scale multi-day events', '#4285F4', 'conference', '2025-04-08 08:51:18'),
(2, 'Workshop', 'Interactive learning sessions', '#34A853', 'workshop', '2025-04-08 08:51:18'),
(3, 'Meeting', 'Internal team meetings', '#FBBC05', 'meeting', '2025-04-08 08:51:18'),
(4, 'Social', 'Networking and social events', '#EA4335', 'social', '2025-04-08 08:51:18'),
(5, 'Webinar', 'Online presentations', '#673AB7', 'webinar', '2025-04-08 08:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `type` enum('task','event','announcement','feedback','system') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patch_notes`
--

CREATE TABLE `patch_notes` (
  `patch_id` int(11) NOT NULL,
  `version` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `update_type` enum('feature','bugfix','improvement','security') NOT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `related_feedback_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `name`, `description`, `permissions`, `created_at`) VALUES
(1, 'Chapter Lead', 'GDG on Campus Chapter Lead with full system access', '{\"all\": true}', '2025-04-08 08:51:18'),
(2, 'Department Lead', 'Department management access', '{\"department_management\": true, \"event_creation\": true, \"task_assignment\": true, \"user_management\": true}', '2025-04-08 08:51:18'),
(3, 'Officer', 'Junior Core Officer with task execution access', '{\"event_view\": true, \"task_view\": true, \"task_execution\": true, \"feedback_submit\": true}', '2025-04-08 08:51:18'),
(4, 'Member', 'Community member with limited access', '{\"public_view\": true}', '2025-04-08 08:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `subtasks`
--

CREATE TABLE `subtasks` (
  `subtask_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('to_do','in_progress','done') DEFAULT 'to_do',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_events`
--

CREATE TABLE `sub_events` (
  `sub_event_id` int(11) NOT NULL,
  `parent_event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_feedback`
--

CREATE TABLE `system_feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `category` enum('bug','suggestion','compliment') NOT NULL,
  `content` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `visibility` enum('public','private') DEFAULT 'private',
  `status` enum('new','in_review','implemented','rejected') DEFAULT 'new',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `task_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_id` int(11) DEFAULT NULL,
  `sub_event_id` int(11) DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('to_do','in_progress','done') DEFAULT 'to_do',
  `is_team_task` tinyint(1) DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `assignment_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_attachments`
--

CREATE TABLE `task_attachments` (
  `attachment_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `first_name`, `last_name`, `role_id`, `department_id`, `profile_image`, `bio`, `phone`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(3, 'LeadAdmin', 'admin@example.com', '$2y$10$9SL8NYqRvNx.JUq.tdtsru7qOQmi59PO0dZE8rSMD.jTbCsx3iAh2', 'Lead', 'Admin', 1, 1, NULL, NULL, NULL, 1, '2025-04-14 06:22:21', '2025-04-11 09:55:52', '2025-04-14 06:22:21'),
(4, 'DepLead', 'deplead@mail.com', '$2y$10$woDpAs3wyqYCQU8rzMJp5.M.3t7/33vPvRgcENEZQZ2woYqCpLFmW', 'Department', 'Lead', 2, 1, NULL, NULL, NULL, 1, '2025-04-11 12:55:59', '2025-04-11 10:47:46', '2025-04-11 12:55:59'),
(5, 'mu', 'member@mail.com', '$2y$10$KxMJfqR2N35PrAdFLgggEOG0WFEoTS7CF0.u9ca2gXUwU2lDazTDO', 'Member', 'User', 4, 1, NULL, 'Just a GDG Member here hehe', '09360232198', 1, '2025-04-14 09:11:22', '2025-04-14 09:11:07', '2025-04-14 09:18:22');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `setting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(1) DEFAULT 1,
  `event_reminders` tinyint(1) DEFAULT 1,
  `community_updates` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`setting_id`, `user_id`, `email_notifications`, `event_reminders`, `community_updates`, `created_at`, `updated_at`) VALUES
(1, 5, 0, 1, 1, '2025-04-14 09:56:40', '2025-04-14 10:12:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `analytics_data`
--
ALTER TABLE `analytics_data`
  ADD PRIMARY KEY (`data_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `type_id` (`type_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `event_attachments`
--
ALTER TABLE `event_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `sub_event_id` (`sub_event_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `event_comments`
--
ALTER TABLE `event_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_comment_id` (`parent_comment_id`);

--
-- Indexes for table `event_ratings`
--
ALTER TABLE `event_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_rsvps`
--
ALTER TABLE `event_rsvps`
  ADD PRIMARY KEY (`rsvp_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_types`
--
ALTER TABLE `event_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patch_notes`
--
ALTER TABLE `patch_notes`
  ADD PRIMARY KEY (`patch_id`),
  ADD KEY `related_feedback_id` (`related_feedback_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `subtasks`
--
ALTER TABLE `subtasks`
  ADD PRIMARY KEY (`subtask_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sub_events`
--
ALTER TABLE `sub_events`
  ADD PRIMARY KEY (`sub_event_id`),
  ADD KEY `parent_event_id` (`parent_event_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `system_feedback`
--
ALTER TABLE `system_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `sub_event_id` (`sub_event_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `analytics_data`
--
ALTER TABLE `analytics_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `event_attachments`
--
ALTER TABLE `event_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_comments`
--
ALTER TABLE `event_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_ratings`
--
ALTER TABLE `event_ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_rsvps`
--
ALTER TABLE `event_rsvps`
  MODIFY `rsvp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patch_notes`
--
ALTER TABLE `patch_notes`
  MODIFY `patch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subtasks`
--
ALTER TABLE `subtasks`
  MODIFY `subtask_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sub_events`
--
ALTER TABLE `sub_events`
  MODIFY `sub_event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_feedback`
--
ALTER TABLE `system_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_attachments`
--
ALTER TABLE `task_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `analytics_data`
--
ALTER TABLE `analytics_data`
  ADD CONSTRAINT `analytics_data_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `analytics_data_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE SET NULL;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `event_types` (`type_id`),
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `event_attachments`
--
ALTER TABLE `event_attachments`
  ADD CONSTRAINT `event_attachments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attachments_ibfk_2` FOREIGN KEY (`sub_event_id`) REFERENCES `sub_events` (`sub_event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_attachments_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `event_comments`
--
ALTER TABLE `event_comments`
  ADD CONSTRAINT `event_comments_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `event_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `event_comments` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_ratings`
--
ALTER TABLE `event_ratings`
  ADD CONSTRAINT `event_ratings_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `event_rsvps`
--
ALTER TABLE `event_rsvps`
  ADD CONSTRAINT `event_rsvps_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_rsvps_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patch_notes`
--
ALTER TABLE `patch_notes`
  ADD CONSTRAINT `patch_notes_ibfk_1` FOREIGN KEY (`related_feedback_id`) REFERENCES `system_feedback` (`feedback_id`),
  ADD CONSTRAINT `patch_notes_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `subtasks`
--
ALTER TABLE `subtasks`
  ADD CONSTRAINT `subtasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subtasks_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `sub_events`
--
ALTER TABLE `sub_events`
  ADD CONSTRAINT `sub_events_ibfk_1` FOREIGN KEY (`parent_event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sub_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `system_feedback`
--
ALTER TABLE `system_feedback`
  ADD CONSTRAINT `system_feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`sub_event_id`) REFERENCES `sub_events` (`sub_event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `task_attachments`
--
ALTER TABLE `task_attachments`
  ADD CONSTRAINT `task_attachments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_attachments_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
