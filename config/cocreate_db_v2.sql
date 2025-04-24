-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 23, 2025 at 03:24 PM
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
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `user_id`, `action`, `ip_address`, `created_at`) VALUES
(1, 3, 'Deactivated 2 users (IDs: 19,4)', '::1', '2025-04-17 16:23:05'),
(2, 3, 'Deactivated 1 users (IDs: 19)', '::1', '2025-04-17 17:02:34'),
(3, 3, 'Updated user: officer_tech_av', '::1', '2025-04-19 09:58:08'),
(4, 3, 'Updated user: officer_op_finance', '::1', '2025-04-19 09:58:26'),
(5, 3, 'Deactivated 1 users', '::1', '2025-04-19 09:59:28'),
(6, 3, 'Deactivated 1 users', '::1', '2025-04-19 09:59:28'),
(7, 3, 'Updated user: dl_marketing', '::1', '2025-04-19 10:00:22'),
(8, 3, 'Activated 4 users', '::1', '2025-04-19 10:03:03'),
(9, 3, 'Activated 4 users', '::1', '2025-04-19 10:03:03'),
(10, 3, 'Deactivated 2 users', '::1', '2025-04-20 04:22:28'),
(11, 3, 'Activated 2 users', '::1', '2025-04-20 04:22:51'),
(12, 3, 'Activated 2 users', '::1', '2025-04-20 05:01:00'),
(13, 3, 'Deactivated 9 users', '::1', '2025-04-20 06:14:29'),
(14, 3, 'Updated user: dl_marketing', '::1', '2025-04-20 06:14:49'),
(15, 3, 'Updated user: dl_marketing', '::1', '2025-04-20 06:14:58'),
(16, 3, 'Reset password for user ID: 5', '::1', '2025-04-20 06:18:18'),
(17, 3, 'Activated 10 users', '::1', '2025-04-23 03:39:15'),
(18, 3, 'Updated user: dl_marketing', '::1', '2025-04-23 04:37:45'),
(19, 3, 'TEST LOG: Created from test_log.php at 2025-04-23 11:34:55', '::1', '2025-04-23 11:34:55'),
(20, 3, 'TEST LOG: Created from test_log.php at 2025-04-23 11:35:37', '::1', '2025-04-23 11:35:37'),
(21, 3, 'TEST LOG: Table structure fixed at 2025-04-23 11:38:19', '::1', '2025-04-23 11:38:19'),
(22, 3, 'TEST LOG: Table structure fixed at 2025-04-23 11:39:20', '::1', '2025-04-23 11:39:20'),
(23, 3, 'TEST LOG: Table structure fixed at 2025-04-23 11:39:24', '::1', '2025-04-23 11:39:24'),
(24, 3, 'Created new event: sdfghjkl (Event ID: 17)', '::1', '2025-04-23 11:42:01'),
(25, 3, 'Updated event: testunghghghgjj (Event ID: 15)', '::1', '2025-04-23 11:42:38'),
(26, 3, 'Updated event: testunghghghgjjjhb (Event ID: 15)', '::1', '2025-04-23 11:51:57'),
(27, 3, 'Updated event: testunghghghgjjjhb (Event ID: 15)', '::1', '2025-04-23 11:52:10');

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
  `created_by` int(11) NOT NULL,
  `visibility` enum('draft','private','unlisted','public') DEFAULT 'draft',
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `max_participants` int(11) DEFAULT NULL,
  `speakers` text DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completion_status` int(11) DEFAULT 0,
  `ready_for_publish` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `start_date`, `end_date`, `location`, `location_map_url`, `type_id`, `created_by`, `visibility`, `status`, `max_participants`, `speakers`, `featured_image`, `created_at`, `updated_at`, `completion_status`, `ready_for_publish`) VALUES
(3, 'Google I/O Extended 2025', 'Join us for Google I/O Extended, where we\'ll livestream the keynote and host local workshops on the latest Google technologies. This is a great opportunity to connect with fellow developers and learn about the newest tools and frameworks.', '2025-05-15 09:00:00', '2025-05-15 17:00:00', 'WMSU College of Computing Studies Auditorium', 'https://maps.google.com/?q=WMSU+College+of+Computing+Studies', 1, 3, 'public', 'upcoming', 100, 'John Doe (Google Developer Expert)\nJane Smith (Android Developer)\nMike Johnson (Web Technologies Lead)', 'public/assets/img/events/io_extended_2025.jpg', '2025-04-14 10:22:05', '2025-04-22 14:07:23', 100, 1),
(4, 'Annual Tech Summit 2025', 'Our flagship technology conference bringing together industry leaders, innovators, and tech enthusiasts. This year\'s theme is \"AI and Sustainable Development\" featuring keynotes, workshops, and networking opportunities.', '2025-06-20 08:00:00', '2025-06-22 17:00:00', 'WMSU Convention Center', 'https://maps.google.com/?q=WMSU+Convention+Center', 1, 3, 'public', 'upcoming', 250, 'Dr. Maria Santos (AI Research Lead)\nEngr. Robert Chen (Sustainability Expert)\nProf. Elena Garcia (Computer Science Department)', 'public/assets/img/events/tech_summit_2025.png', '2025-04-15 10:39:27', '2025-04-22 14:07:23', 100, 1),
(5, 'Budget Planning Workshop', 'A hands-on workshop for student organizations to learn effective budget planning and financial management. Participants will develop practical skills in creating and managing event budgets.', '2025-05-10 13:00:00', '2025-05-10 17:00:00', 'WMSU College of Business Room 201', 'https://maps.google.com/?q=WMSU+College+of+Business', 1, 7, 'private', 'upcoming', 30, 'Officer OP-Finance (Financial Planning Expert)', 'public/assets/img/events/budget_workshop.jpg', '2025-04-15 10:39:27', '2025-04-15 10:39:27', 0, 0),
(6, 'Event Management Masterclass', 'Learn the ins and outs of successful event planning and execution. This masterclass covers venue selection, vendor management, scheduling, and day-of coordination.', '2025-05-25 09:00:00', '2025-05-25 16:00:00', 'WMSU College of Computing Studies Room 105', 'https://maps.google.com/?q=WMSU+College+of+Computing+Studies', 1, 6, 'public', 'upcoming', 40, 'Officer OP-Events (Event Planning Specialist)\nGuest Speaker: Ms. Jennifer Lopez (Professional Event Coordinator)', 'public/assets/img/events/event_masterclass.png', '2025-04-15 10:39:27', '2025-04-22 14:07:23', 100, 1),
(7, 'Digital Marketing Bootcamp', 'An intensive three-day bootcamp covering social media marketing, content creation, SEO, and digital advertising. Perfect for beginners and those looking to enhance their digital marketing skills.', '2025-07-05 09:00:00', '2025-07-07 17:00:00', 'WMSU College of Business Auditorium', 'https://maps.google.com/?q=WMSU+College+of+Business+Auditorium', 1, 10, 'public', 'upcoming', 50, 'Officer MK-Digital (Social Media Specialist)\nMr. David Kim (SEO Expert)\nMs. Sarah Johnson (Content Marketing Strategist)', 'public/assets/img/events/digital_marketing_bootcamp.jpg', '2025-04-15 10:39:27', '2025-04-22 14:07:23', 100, 1),
(8, 'Creative Design Workshop Series', 'A series of workshops focusing on graphic design principles, branding, and visual storytelling. Participants will work on real-world projects and receive feedback from industry professionals.', '2025-06-10 14:00:00', '2025-07-01 17:00:00', 'WMSU College of Fine Arts Design Lab', 'https://maps.google.com/?q=WMSU+College+of+Fine+Arts', 1, 9, 'public', 'upcoming', 25, 'Officer MK-Creative (Lead Designer)\nProf. Michael Torres (Visual Arts Department)', 'public/assets/img/events/design_workshop.jpg', '2025-04-15 10:39:27', '2025-04-22 14:07:23', 100, 1),
(9, 'Web Development Hackathon', 'A 24-hour hackathon challenging participants to build innovative web applications. Teams will compete for prizes and the opportunity to present their projects to industry recruiters.', '2025-08-15 10:00:00', '2025-08-16 10:00:00', 'WMSU College of Computing Studies Labs', 'https://maps.google.com/?q=WMSU+College+of+Computing+Studies', 1, 12, 'public', 'upcoming', 100, 'Officer Tech-IT (Web Development Lead)\nEngr. James Wilson (Software Engineer, Google)\nMs. Patricia Garcia (UX Designer, Microsoft)', 'public/assets/img/events/web_hackathon.jpg', '2025-04-15 10:39:27', '2025-04-22 14:07:23', 100, 1),
(10, 'Audio-Visual Production Workshop', 'Learn professional techniques for audio-visual production including camera operation, lighting, sound recording, and basic video editing. Ideal for students interested in media production.', '2025-05-30 13:00:00', '2025-05-31 17:00:00', 'WMSU Media Center', 'https://maps.google.com/?q=WMSU+Media+Center', 1, 11, 'public', 'upcoming', 20, 'Officer Tech-AV (AV Production Specialist)\nMr. Carlos Reyes (Documentary Filmmaker)', 'public/assets/img/events/av_workshop.png', '2025-04-15 10:39:27', '2025-04-22 14:07:23', 100, 1),
(11, 'GDSC Developer Festival', 'A week-long celebration of technology and innovation featuring workshops, talks, coding competitions, and networking events. Open to all students interested in technology and software development.', '2025-09-10 08:00:00', '2025-09-17 20:00:00', 'WMSU Campus-wide', 'https://maps.google.com/?q=WMSU+Campus', 1, 3, 'public', 'upcoming', 500, 'Multiple speakers from Google, Microsoft, and local tech companies', 'public/assets/img/events/developer_festival.png', '2025-04-15 10:39:27', '2025-04-22 14:07:23', 100, 1),
(12, 'Career Fair 2025: Tech & Business', 'Connect with potential employers, explore internship opportunities, and attend career development workshops. Bring your resume and be ready to network with representatives from top companies.', '2025-10-05 09:00:00', '2025-10-05 16:00:00', 'WMSU Gymnasium', 'https://maps.google.com/?q=WMSU+Gymnasium', 1, 4, 'public', 'ongoing', NULL, 'Representatives from 25+ companies including Google, Microsoft, IBM, and local tech firms', 'public/assets/img/events/career_fair.jpg', '2025-04-15 10:39:27', '2025-04-23 10:21:52', 100, 1),
(15, 'testunghghghgjjjhb', 'testung', '2025-04-21 09:00:00', '2025-04-21 17:00:00', 'Western Mindanao State University', 'https://maps.app.goo.gl/kBvuHt8eVWCyWSpT9', 2, 3, 'draft', 'completed', NULL, 'testung', 'uploads/event_images/event_68068ccbc8bb2.jpg', '2025-04-21 18:22:03', '2025-04-23 11:52:10', 0, 0),
(16, 'bilnagu', 'bilnagu', '2025-04-23 09:00:00', '2025-04-23 17:00:00', 'Western Mindanao State University', 'https://maps.app.goo.gl/kBvuHt8eVWCyWSpT9', 4, 3, 'unlisted', 'upcoming', NULL, 'bilnagu\r\nbilnagu', 'uploads/event_images/event_68086834d935a.png', '2025-04-23 04:10:28', '2025-04-23 04:10:28', 0, 0),
(17, 'sdfghjkl', 'sdfghjkl', '2025-04-23 09:00:00', '2025-04-23 17:00:00', 'Western Mindanao State University', 'https://maps.app.goo.gl/kBvuHt8eVWCyWSpT9', 5, 3, 'unlisted', 'upcoming', NULL, 'sdfghjkl', 'uploads/event_images/event_6808d209a4e0f.png', '2025-04-23 11:42:01', '2025-04-23 11:42:01', 0, 0);

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
-- Table structure for table `task_comments`
--

CREATE TABLE `task_comments` (
  `comment_id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
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
(3, 'lead', 'admin@example.com', '$2y$10$9SL8NYqRvNx.JUq.tdtsru7qOQmi59PO0dZE8rSMD.jTbCsx3iAh2', 'Chief', 'Lead', 1, 1, 'uploads/profile_images/user_3_67ffb1d5dd01e5.57697392.jpg', NULL, NULL, 1, '2025-04-23 04:11:37', '2025-04-11 09:55:52', '2025-04-23 04:11:37'),
(4, 'dl_operations', 'operations.lead@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DL', 'Operations', 2, 1, 'uploads/profile_images/default_profile.png', 'Department Lead for Operations division, overseeing both Event and Finance teams.', '09123456781', 1, '2025-04-16 09:13:41', '2025-04-15 10:33:18', '2025-04-23 03:39:15'),
(5, 'dl_marketing', 'marketing.lead@gmail.com', '$2y$10$7rF2YVzWLUJ5S017AyTseueFd/EKENGxz1.ikJ5UZw40R4IFmSOsK', 'DL', 'Marketing', 2, 2, 'uploads/profile_images/default_profile.png', 'Department Lead for Marketing division, managing Creative and Digital teams.', '09123456782', 0, NULL, '2025-04-15 10:33:18', '2025-04-23 04:37:45'),
(6, 'dl_technical', 'technical.lead@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'DL', 'Technical', 2, 3, 'uploads/profile_images/default_profile.png', 'Department Lead for Technical division, responsible for all technical aspects of events.', '09123456783', 1, '2025-04-15 12:48:52', '2025-04-15 10:33:18', '2025-04-23 03:39:15'),
(7, 'officer_op_events', 'events.officer@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Officer', 'OP-Events', 3, 1, 'uploads/profile_images/default_profile.png', 'Operations Department Officer specializing in Event planning and execution. Responsible for venue coordination, scheduling, and on-site management.', '09123456784', 1, NULL, '2025-04-15 10:33:18', '2025-04-23 03:39:15'),
(8, 'officer_op_finance', 'finance.officer@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Officer', 'OP-Finance', 3, 1, 'uploads/profile_images/default_profile.png', 'Operations Department Officer focusing on Finance. Handles budgeting, expense tracking, and financial reporting for all events and activities.', '09123456785', 1, NULL, '2025-04-15 10:33:18', '2025-04-23 03:39:15'),
(9, 'officer_mk_creative', 'creative.officer@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Officer', 'MK-Creative', 3, 2, 'uploads/profile_images/user_9_67fe532c7970a4.38557956.jpg', 'Marketing Department Officer on the Creative team. Specializes in graphic design, branding, and visual content creation for all organization events and campaigns.', '09123456786', 1, '2025-04-15 12:18:04', '2025-04-15 10:33:18', '2025-04-23 03:39:15'),
(10, 'officer_mk_digital', 'digital.officer@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Officer', 'MK-Digital', 3, 2, 'uploads/profile_images/default_profile.png', 'Marketing Department Officer handling Digital Marketing. Manages social media, email campaigns, and online presence.', '09123456787', 1, NULL, '2025-04-15 10:33:18', '2025-04-23 03:39:15'),
(11, 'officer_tech_av', 'av.officer@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Officer', 'Tech-AV', 3, 3, 'uploads/profile_images/user_11_67fe54cc2d8a37.67800127.jpg', 'Technical Department Officer specializing in Audio-Visual equipment and setup.', '09123456788', 1, '2025-04-15 12:38:32', '2025-04-15 10:33:18', '2025-04-23 03:39:15'),
(12, 'officer_tech_it', 'it.officer@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Officer', 'Tech-IT', 3, 3, 'uploads/profile_images/default_profile.png', 'Technical Department Officer focusing on IT infrastructure and support.', '09123456789', 1, NULL, '2025-04-15 10:33:18', '2025-04-15 10:33:18'),
(19, 'general_member', 'member@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'General', 'Member', 4, NULL, 'uploads/profile_images/user_19_67ff74882b30b1.33021649.jpg', 'Active participant in organization events and activities. Interested in technology and community building.', '09123456799', 1, '2025-04-23 04:11:10', '2025-04-15 10:43:49', '2025-04-23 04:11:10');

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `task_notifications` tinyint(1) DEFAULT 1,
  `department_updates` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`setting_id`, `user_id`, `email_notifications`, `event_reminders`, `community_updates`, `created_at`, `updated_at`, `task_notifications`, `department_updates`) VALUES
(1, 5, 0, 1, 1, '2025-04-14 09:56:40', '2025-04-14 10:12:09', 1, 1),
(2, 8, 1, 1, 1, '2025-04-14 14:44:34', '2025-04-14 14:44:51', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_comment_id` (`parent_comment_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

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
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `event_attachments`
--
ALTER TABLE `event_attachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `task_comments`
--
ALTER TABLE `task_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `task_comments`
--
ALTER TABLE `task_comments`
  ADD CONSTRAINT `task_comments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`task_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `task_comments_ibfk_3` FOREIGN KEY (`parent_comment_id`) REFERENCES `task_comments` (`comment_id`) ON DELETE CASCADE;

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
