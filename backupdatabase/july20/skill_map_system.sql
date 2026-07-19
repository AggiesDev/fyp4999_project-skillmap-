-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 20, 2026 at 01:33 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skill_map_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_roles`
--

CREATE TABLE `access_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_roles`
--

INSERT INTO `access_roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'Full system access', '2026-07-05 17:36:24'),
(2, 'lecturer', 'Review and support student skills', '2026-07-05 17:36:24'),
(3, 'staff', 'Operational support access', '2026-07-05 17:36:24'),
(4, 'student', 'Personal skill tracking access', '2026-07-05 17:36:24'),
(1381, 'special lecturer', 'special role 1', '2026-07-19 20:54:11'),
(1498, 'admin2', 'Created by admin user management', '2026-07-19 21:12:40');

-- --------------------------------------------------------

--
-- Table structure for table `access_role_permissions`
--

CREATE TABLE `access_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_role_permissions`
--

INSERT INTO `access_role_permissions` (`role_id`, `permission_id`, `enabled`, `created_at`) VALUES
(1, 1, 1, '2026-07-19 21:13:21'),
(1, 2, 1, '2026-07-19 21:13:21'),
(1, 3, 1, '2026-07-19 21:13:21'),
(1, 4, 1, '2026-07-19 21:13:21'),
(1, 8, 1, '2026-07-19 21:13:21'),
(1, 9, 1, '2026-07-19 21:13:21'),
(1, 340, 1, '2026-07-19 21:13:21'),
(2, 1, 1, '2026-07-19 21:13:21'),
(2, 4, 1, '2026-07-19 21:13:21'),
(2, 8, 1, '2026-07-19 21:13:21'),
(2, 340, 1, '2026-07-19 21:13:21'),
(3, 1, 1, '2026-07-19 21:13:21'),
(3, 4, 1, '2026-07-19 21:13:21'),
(3, 8, 1, '2026-07-19 21:13:21'),
(3, 340, 1, '2026-07-19 21:13:21'),
(1498, 1, 1, '2026-07-19 21:13:21'),
(1498, 3, 1, '2026-07-19 21:13:21'),
(1498, 4, 1, '2026-07-19 21:13:21'),
(1498, 340, 1, '2026-07-19 21:13:21');

-- --------------------------------------------------------

--
-- Table structure for table `account_approval_requests`
--

CREATE TABLE `account_approval_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `request_type` enum('programme','role') NOT NULL,
  `requested_value` varchar(190) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_approval_requests`
--

INSERT INTO `account_approval_requests` (`id`, `user_id`, `request_type`, `requested_value`, `status`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(1, 6, 'programme', 'Networking', 'Approved', 1, '2026-07-19 21:21:40', '2026-07-19 21:21:09');

-- --------------------------------------------------------

--
-- Table structure for table `analyses`
--

CREATE TABLE `analyses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_role_id` int(11) NOT NULL,
  `match_score` decimal(5,2) NOT NULL,
  `ai_summary` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `analyses`
--

INSERT INTO `analyses` (`id`, `user_id`, `target_role_id`, `match_score`, `ai_summary`, `created_at`) VALUES
(1, 6, 1, 96.97, 'You match 96.97% of the Web Developer role requirements.', '2026-07-19 21:36:04');

-- --------------------------------------------------------

--
-- Table structure for table `analysis_results`
--

CREATE TABLE `analysis_results` (
  `id` int(11) NOT NULL,
  `analysis_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `status` enum('Have','Partial','Missing') NOT NULL,
  `your_rating` tinyint(3) UNSIGNED NOT NULL,
  `required_rating` tinyint(3) UNSIGNED NOT NULL,
  `gap_value` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `analysis_results`
--

INSERT INTO `analysis_results` (`id`, `analysis_id`, `skill_id`, `status`, `your_rating`, `required_rating`, `gap_value`) VALUES
(1, 1, 13, 'Have', 4, 3, 0),
(2, 1, 6, 'Partial', 4, 5, 1),
(3, 1, 4, 'Have', 5, 5, 0),
(4, 1, 5, 'Have', 5, 5, 0),
(5, 1, 3, 'Have', 5, 5, 0),
(6, 1, 2, 'Have', 5, 5, 0),
(7, 1, 1, 'Have', 5, 5, 0);

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `tier` enum('bronze','silver','gold') NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id`, `name`, `tier`, `description`, `icon`) VALUES
(1, 'Bronze Explorer', 'bronze', 'Completed first gap analysis', 'bi-award'),
(2, 'Skill Builder', 'silver', 'Improved three core skills', 'bi-stars'),
(3, 'Roadmap Runner', 'gold', 'Completed a full learning roadmap', 'bi-trophy');

-- --------------------------------------------------------

--
-- Table structure for table `career_roles`
--

CREATE TABLE `career_roles` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('Career','Lead') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `career_roles`
--

INSERT INTO `career_roles` (`id`, `name`, `type`, `description`, `created_at`) VALUES
(1, 'Web Developer', 'Career', 'Build modern web applications', '2026-07-05 16:34:09'),
(2, 'Data Analyst', 'Career', 'Analyse and present data insights', '2026-07-05 16:34:09'),
(3, 'IT Support', 'Career', 'Provide technical support', '2026-07-05 16:34:09'),
(4, 'Student Club President', 'Lead', 'Lead student organisations', '2026-07-05 16:34:09'),
(5, 'Computer Scient', 'Career', NULL, '2026-07-19 20:52:43');

-- --------------------------------------------------------

--
-- Table structure for table `learning_resources`
--

CREATE TABLE `learning_resources` (
  `id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `platform` varchar(120) NOT NULL,
  `url` varchar(500) NOT NULL,
  `duration_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_free` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_streaks`
--

CREATE TABLE `learning_streaks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `best_streak` int(11) NOT NULL DEFAULT 0,
  `last_activity` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `learning_streaks`
--

INSERT INTO `learning_streaks` (`id`, `user_id`, `current_streak`, `best_streak`, `last_activity`) VALUES
(1, 6, 1, 1, '2026-07-19');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `sender_user_id` int(11) DEFAULT NULL,
  `sender_role` enum('admin','lecturer','staff') DEFAULT NULL,
  `recipient_user_id` int(11) DEFAULT NULL,
  `recipient_role` enum('admin','student','lecturer','staff','all') NOT NULL DEFAULT 'all',
  `notification_type` enum('message','info','alert','reminder') NOT NULL DEFAULT 'message',
  `title` varchar(190) NOT NULL,
  `body` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `sender_user_id`, `sender_role`, `recipient_user_id`, `recipient_role`, `notification_type`, `title`, `body`, `is_read`, `read_at`, `created_at`) VALUES
(1, 1, 'admin', 6, 'all', 'info', 'Welcome new User', 'Welcome to new user and thanks for using our system!', 1, '2026-07-19 22:23:11', '2026-07-19 21:25:26'),
(2, 1, 'admin', 6, 'all', 'info', 'Welcome new User', 'Welcome to new user and thanks for using our system!', 1, '2026-07-19 22:23:11', '2026-07-19 21:34:11');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `description`) VALUES
(1, 'manage_users', 'Create, update, and deactivate user accounts'),
(2, 'manage_roles', 'Maintain target roles and skill benchmarks'),
(3, 'manage_skills', 'Maintain the skill library and categories'),
(4, 'view_admin_dashboard', 'View the main admin dashboard'),
(8, 'review_student_skills', 'Review and edit student skill ratings'),
(9, 'manage_permissions', 'Manage access for system roles'),
(340, 'send_notifications', 'Send notifications and messages to users');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('Career','Lead') NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `type`, `description`, `created_at`) VALUES
(1, 'Web Developer', 'Career', 'Build modern web applications using PHP and JavaScript', '2026-07-05 16:24:05'),
(2, 'Data Analyst', 'Career', 'Analyse data and communicate insights', '2026-07-05 16:24:05'),
(3, 'IT Support', 'Career', 'Support users with hardware and software issues', '2026-07-05 16:24:05'),
(4, 'Student Club President', 'Lead', 'Coordinate and lead student organisation activities', '2026-07-05 16:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_skill_benchmarks`
--

CREATE TABLE `role_skill_benchmarks` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `required_rating` tinyint(3) UNSIGNED NOT NULL,
  `priority` enum('Critical','Important','Optional') NOT NULL DEFAULT 'Important',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_skill_benchmarks`
--

INSERT INTO `role_skill_benchmarks` (`id`, `role_id`, `skill_id`, `required_rating`, `priority`, `created_at`, `updated_at`) VALUES
(1, 2, 13, 4, 'Important', '2026-07-19 20:51:09', '2026-07-19 20:51:09'),
(2, 1, 5, 5, 'Important', '2026-07-19 20:51:17', '2026-07-19 20:51:17'),
(3, 1, 4, 5, 'Important', '2026-07-19 20:51:23', '2026-07-19 20:51:23'),
(4, 1, 6, 5, 'Important', '2026-07-19 20:51:27', '2026-07-19 20:51:27'),
(5, 1, 1, 5, 'Important', '2026-07-19 20:51:32', '2026-07-19 20:51:32'),
(6, 1, 13, 3, 'Critical', '2026-07-19 20:51:39', '2026-07-19 20:51:39'),
(7, 1, 3, 5, 'Important', '2026-07-19 20:51:44', '2026-07-19 20:51:44'),
(8, 1, 2, 5, 'Important', '2026-07-19 20:51:49', '2026-07-19 20:51:49');

-- --------------------------------------------------------

--
-- Table structure for table `skills`
--

CREATE TABLE `skills` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `difficulty` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skills`
--

INSERT INTO `skills` (`id`, `category_id`, `name`, `description`, `difficulty`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'PHP', 'Server-side scripting for backend logic.', 3, 'Active', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(2, 1, 'MySQL', 'Structured querying and relational database design.', 3, 'Active', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(3, 1, 'JavaScript', 'Interactive client-side application behaviour.', 3, 'Active', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(4, 1, 'Bootstrap 5', 'Responsive layout and UI components.', 2, 'Active', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(5, 1, 'Git', 'Version control and collaboration workflow.', 2, 'Active', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(6, 1, 'API Integration', 'Connect systems through web APIs.', 4, 'Active', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(13, 2, 'Business Analysis Skill', 'this skill is necessary for all student.', 4, 'Active', '2026-07-19 20:50:50', '2026-07-19 20:50:50');

-- --------------------------------------------------------

--
-- Table structure for table `skill_categories`
--

CREATE TABLE `skill_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `type` enum('Skill Category','Target Role') NOT NULL DEFAULT 'Skill Category',
  `icon` varchar(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skill_categories`
--

INSERT INTO `skill_categories` (`id`, `name`, `type`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Technical', 'Skill Category', 'bi-code-square', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(2, 'Leadership', 'Skill Category', 'bi-people-fill', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(3, 'Interpersonal', 'Skill Category', 'bi-chat-dots-fill', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(4, 'Academic', 'Skill Category', 'bi-journal-text', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(5, 'Organisational', 'Skill Category', 'bi-folder-check', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(6, 'Web Developer', 'Target Role', 'bi-code-slash', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(7, 'Data Analyst', 'Target Role', 'bi-bar-chart-line', '2026-07-05 16:24:05', '2026-07-05 16:24:05'),
(8, 'IT Support', 'Target Role', 'bi-pc-display', '2026-07-05 16:24:05', '2026-07-05 16:24:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `username` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(80) NOT NULL DEFAULT 'student',
  `programme` varchar(150) NOT NULL,
  `year_level` varchar(50) NOT NULL,
  `avatar_initials` varchar(8) NOT NULL,
  `gender` enum('male','female') NOT NULL DEFAULT 'male',
  `profile_icon` varchar(190) NOT NULL DEFAULT 'profileicons/icons8-add-user-male-100.png',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `email`, `password_hash`, `role`, `programme`, `year_level`, `avatar_initials`, `gender`, `profile_icon`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@gmail.com', 'admin@gmail.com', '$2y$10$wD08lmaueYT.1/QHAl63q.99WXPomKTsVyIYGKrs0bozsj.N1ALV2', 'admin', 'FDSIT', 'Staff', 'AU', 'male', 'profileicons/icons8-administrator-male-100.png', 'Active', '2026-07-05 16:34:09', '2026-07-19 22:08:06'),
(2, 'Demo Student', 'demostudent', 'student@gmail.com', '$2y$10$WAhc1B6/Ul7XRbNcXYpuD.HTMcKZqDKnCuaBqC/X7WHsHi/hYBCOy', 'student', 'Information Systems', 'Year 4', 'DE', 'male', 'profileicons/icons8-add-user-male-100.png', 'Active', '2026-07-05 16:34:09', '2026-07-19 22:09:09'),
(3, 'Demo Lecturer', 'demolecturer', 'lecturer@gmail.com', '$2y$10$SSn8Vmrxf7EQVjdxcedMG.o.hl/ehe52ODEBmviThS1y0GA57hJhG', 'lecturer', 'Information Systems', 'Staff', 'DL', 'male', 'profileicons/icons8-administrator-male-100.png', 'Active', '2026-07-05 16:34:09', '2026-07-19 22:08:06'),
(4, 'Chit Phone Shwe', 'chitphone', 'chitphone@gmail.com', '$2y$10$6a2TuAuuo0izPYxBFypJPeRuG4BO8EeeDmlrPPhb8UeK998zZJCM.', 'student', 'Information Systems', 'Year 4', 'CH', 'male', 'profileicons/icons8-add-user-male-100.png', 'Active', '2026-07-19 20:08:14', '2026-07-19 20:08:14'),
(5, 'admin2', 'admin2@gmail.com', 'specialuser1@gmail.com', '$2y$10$yQmRjkjuLstk6SVKck5o5./phcaK.XsZ9nctrPh5GULTfH9KI5RBO', 'admin2', 'FDSIT', 'Year 1', 'AD', 'male', 'profileicons/icons8-add-user-male-100.png', 'Active', '2026-07-19 21:12:40', '2026-07-19 21:12:40'),
(6, 'Aggies', 'Aggies', 'aggies@gmail.com', '$2y$10$CyQbeoBrp6QDfk0C3pWgJuoO.lP6MiqdIz4gHmnQdNxAcWYharYkO', 'student', 'Networking', 'Year 1', 'AG', 'male', 'profileicons/icons8-administrator-male-100.png', 'Active', '2026-07-19 21:21:09', '2026-07-19 22:14:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_badges`
--

CREATE TABLE `user_badges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `badge_id` int(11) NOT NULL,
  `earned_at` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_badges`
--

INSERT INTO `user_badges` (`id`, `user_id`, `badge_id`, `earned_at`) VALUES
(1, 6, 1, '2026-07-20');

-- --------------------------------------------------------

--
-- Table structure for table `user_credentials`
--

CREATE TABLE `user_credentials` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entry_type` enum('Skill','Certification') NOT NULL,
  `title` varchar(190) NOT NULL,
  `issuer` varchar(190) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `earned_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_credentials`
--

INSERT INTO `user_credentials` (`id`, `user_id`, `entry_type`, `title`, `issuer`, `notes`, `earned_at`, `created_at`, `updated_at`) VALUES
(1, 4, 'Certification', 'Japan Certification', 'Japan International School', NULL, '2024-02-14', '2026-07-19 20:29:42', '2026-07-19 20:29:42'),
(2, 4, 'Certification', 'English 4 Skills', 'International School', NULL, '2024-06-22', '2026-07-19 20:30:35', '2026-07-19 20:30:35'),
(3, 6, 'Certification', 'English 4 Skills', 'international School', NULL, '2024-05-16', '2026-07-19 21:38:00', '2026-07-19 21:38:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_roadmap_progress`
--

CREATE TABLE `user_roadmap_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `status` enum('Missing','Partial','Completed') NOT NULL,
  `progress_pct` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `started_at` date DEFAULT NULL,
  `completed_at` date DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_skill_ratings`
--

CREATE TABLE `user_skill_ratings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_skill_ratings`
--

INSERT INTO `user_skill_ratings` (`id`, `user_id`, `skill_id`, `rating`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 6, 5, NULL, '2026-07-05 16:36:09', '2026-07-05 17:56:22'),
(2, 2, 4, 5, NULL, '2026-07-05 16:36:09', '2026-07-05 17:56:22'),
(3, 2, 5, 5, NULL, '2026-07-05 16:36:09', '2026-07-05 17:56:22'),
(4, 2, 3, 5, NULL, '2026-07-05 16:36:09', '2026-07-05 17:56:22'),
(5, 2, 2, 5, NULL, '2026-07-05 16:36:09', '2026-07-05 17:56:22'),
(6, 2, 1, 5, NULL, '2026-07-05 16:36:09', '2026-07-05 17:56:22'),
(7, 4, 6, 4, NULL, '2026-07-19 20:21:19', '2026-07-19 20:21:19'),
(8, 4, 4, 5, NULL, '2026-07-19 20:21:19', '2026-07-19 20:21:19'),
(9, 4, 5, 5, NULL, '2026-07-19 20:21:19', '2026-07-19 20:21:19'),
(10, 4, 3, 5, NULL, '2026-07-19 20:21:19', '2026-07-19 20:21:19'),
(11, 4, 2, 5, NULL, '2026-07-19 20:21:19', '2026-07-19 20:21:19'),
(12, 4, 1, 5, NULL, '2026-07-19 20:21:19', '2026-07-19 20:21:19'),
(13, 6, 6, 4, NULL, '2026-07-19 21:35:59', '2026-07-19 21:35:59'),
(14, 6, 4, 5, NULL, '2026-07-19 21:35:59', '2026-07-19 21:35:59'),
(15, 6, 5, 5, NULL, '2026-07-19 21:35:59', '2026-07-19 21:35:59'),
(16, 6, 3, 5, NULL, '2026-07-19 21:35:59', '2026-07-19 21:35:59'),
(17, 6, 2, 5, NULL, '2026-07-19 21:35:59', '2026-07-19 21:35:59'),
(18, 6, 1, 5, NULL, '2026-07-19 21:35:59', '2026-07-19 21:35:59'),
(19, 6, 13, 4, NULL, '2026-07-19 21:35:59', '2026-07-19 21:35:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_roles`
--
ALTER TABLE `access_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `access_role_permissions`
--
ALTER TABLE `access_role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_access_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `account_approval_requests`
--
ALTER TABLE `account_approval_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_account_approval_requests_user` (`user_id`),
  ADD KEY `fk_account_approval_requests_reviewer` (`reviewed_by`);

--
-- Indexes for table `analyses`
--
ALTER TABLE `analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_analyses_user` (`user_id`),
  ADD KEY `fk_analyses_role` (`target_role_id`);

--
-- Indexes for table `analysis_results`
--
ALTER TABLE `analysis_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_analysis_results_analysis` (`analysis_id`),
  ADD KEY `fk_analysis_results_skill` (`skill_id`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `career_roles`
--
ALTER TABLE `career_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `learning_resources`
--
ALTER TABLE `learning_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_learning_resources_skill` (`skill_id`);

--
-- Indexes for table `learning_streaks`
--
ALTER TABLE `learning_streaks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_sender` (`sender_user_id`),
  ADD KEY `fk_notifications_recipient` (`recipient_user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `role_skill_benchmarks`
--
ALTER TABLE `role_skill_benchmarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_role_skill_benchmarks_role` (`role_id`),
  ADD KEY `fk_role_skill_benchmarks_skill` (`skill_id`);

--
-- Indexes for table `skills`
--
ALTER TABLE `skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `fk_skills_category` (`category_id`);

--
-- Indexes for table `skill_categories`
--
ALTER TABLE `skill_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_badges_user` (`user_id`),
  ADD KEY `fk_user_badges_badge` (`badge_id`);

--
-- Indexes for table `user_credentials`
--
ALTER TABLE `user_credentials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_credentials_user` (`user_id`);

--
-- Indexes for table `user_roadmap_progress`
--
ALTER TABLE `user_roadmap_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_roadmap_skill` (`user_id`,`skill_id`),
  ADD KEY `fk_roadmap_skill` (`skill_id`);

--
-- Indexes for table `user_skill_ratings`
--
ALTER TABLE `user_skill_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_skill` (`user_id`,`skill_id`),
  ADD KEY `fk_user_skill_ratings_skill` (`skill_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_roles`
--
ALTER TABLE `access_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2979;

--
-- AUTO_INCREMENT for table `account_approval_requests`
--
ALTER TABLE `account_approval_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `analyses`
--
ALTER TABLE `analyses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `analysis_results`
--
ALTER TABLE `analysis_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `career_roles`
--
ALTER TABLE `career_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `learning_resources`
--
ALTER TABLE `learning_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_streaks`
--
ALTER TABLE `learning_streaks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5157;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_skill_benchmarks`
--
ALTER TABLE `role_skill_benchmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `skills`
--
ALTER TABLE `skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `skill_categories`
--
ALTER TABLE `skill_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_badges`
--
ALTER TABLE `user_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_credentials`
--
ALTER TABLE `user_credentials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_roadmap_progress`
--
ALTER TABLE `user_roadmap_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_skill_ratings`
--
ALTER TABLE `user_skill_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_role_permissions`
--
ALTER TABLE `access_role_permissions`
  ADD CONSTRAINT `fk_access_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_access_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `access_roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `account_approval_requests`
--
ALTER TABLE `account_approval_requests`
  ADD CONSTRAINT `fk_account_approval_requests_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_account_approval_requests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `analyses`
--
ALTER TABLE `analyses`
  ADD CONSTRAINT `fk_analyses_role` FOREIGN KEY (`target_role_id`) REFERENCES `career_roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analyses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `analysis_results`
--
ALTER TABLE `analysis_results`
  ADD CONSTRAINT `fk_analysis_results_analysis` FOREIGN KEY (`analysis_id`) REFERENCES `analyses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_analysis_results_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `learning_resources`
--
ALTER TABLE `learning_resources`
  ADD CONSTRAINT `fk_learning_resources_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `learning_streaks`
--
ALTER TABLE `learning_streaks`
  ADD CONSTRAINT `fk_learning_streaks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_skill_benchmarks`
--
ALTER TABLE `role_skill_benchmarks`
  ADD CONSTRAINT `fk_role_skill_benchmarks_role` FOREIGN KEY (`role_id`) REFERENCES `career_roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_role_skill_benchmarks_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skills`
--
ALTER TABLE `skills`
  ADD CONSTRAINT `fk_skills_category` FOREIGN KEY (`category_id`) REFERENCES `skill_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_badges`
--
ALTER TABLE `user_badges`
  ADD CONSTRAINT `fk_user_badges_badge` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_badges_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_credentials`
--
ALTER TABLE `user_credentials`
  ADD CONSTRAINT `fk_user_credentials_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roadmap_progress`
--
ALTER TABLE `user_roadmap_progress`
  ADD CONSTRAINT `fk_roadmap_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_roadmap_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_skill_ratings`
--
ALTER TABLE `user_skill_ratings`
  ADD CONSTRAINT `fk_user_skill_ratings_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_user_skill_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
