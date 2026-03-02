-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 02, 2026 at 02:02 AM
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
-- Database: `crowdfunding_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'upload/images/default-avatar.png',
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `full_name`, `email`, `profile_pic`, `password`, `created_at`) VALUES
(1, 'admin', 'Ritu Akter Samia', 'admin@gmail.com', 'uploads/admins/admin_1_1772292981.png', '$2y$10$L.d7e7wbjUIuFdomJmnlTerotvYDWEvCeAjHcuPWJJdCXJNHxtcGK', '2026-02-28 07:54:44');

-- --------------------------------------------------------

--
-- Table structure for table `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `target_amount` decimal(10,2) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campaigns`
--

INSERT INTO `campaigns` (`id`, `user_id`, `category_id`, `title`, `description`, `target_amount`, `image_path`, `status`, `created_at`) VALUES
(5, 1, 4, 'Cat food', 'sdfjkgfkjgjkgjk', 50.00, 'uploads/1772265513_cat.png', 'approved', '2026-02-28 07:58:33'),
(6, 1, 7, 'Play Badminton', 'badminton khelte parena  ayrehjgk jgj kjhgj esi ioiert ieuip ihgeu  uhf w jl ffijil ig ehti j our bti jljkhdfj hghkthj igji h;s', 3000.00, 'uploads/campaigns/1772273344_bdmntn.png', 'approved', '2026-02-28 10:09:04'),
(7, 1, 4, 'dog fund', 'What is Lorem Ipsum?\r\nLorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\r\n\r\nWhy do we use it?\r\nIt is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using \'Content here, content here\', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for \'lorem ipsum\' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).', 1000.00, 'uploads/campaigns/1772283511_pexelsgeorgedesipris792381.jpg', 'approved', '2026-02-28 12:58:31'),
(8, 2, 3, 'Music Album Production 1', 'This is a sample campaign description for Music Album Production 1. This demonstrates how campaigns look on the platform.', 37558.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-25 15:44:56'),
(9, 1, 3, 'Clean Water Project 2', 'This is a sample campaign description for Clean Water Project 2. This demonstrates how campaigns look on the platform.', 11908.00, 'uploads/default-campaign.jpg', 'approved', '2026-02-09 15:44:56'),
(10, 2, 2, 'Support Local Art Gallery 3', 'This is a sample campaign description for Support Local Art Gallery 3. This demonstrates how campaigns look on the platform.', 8643.00, 'uploads/default-campaign.jpg', 'approved', '2026-02-17 15:44:56'),
(12, 1, 7, 'Support Local Art Gallery 1', 'This is a sample campaign description for Support Local Art Gallery 1. This demonstrates how campaigns look on the platform.', 21681.00, 'uploads/default-campaign.jpg', 'rejected', '2026-01-17 15:48:04'),
(13, 2, 6, 'Animal Shelter Renovation 2', 'This is a sample campaign description for Animal Shelter Renovation 2. This demonstrates how campaigns look on the platform.', 42510.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-27 15:48:04'),
(14, 2, 10, 'Startup Tech Innovation 3', 'This is a sample campaign description for Startup Tech Innovation 3. This demonstrates how campaigns look on the platform.', 18089.00, 'uploads/default-campaign.jpg', 'rejected', '2026-01-23 15:48:04'),
(15, 2, 7, 'Medical Treatment for Child 4', 'This is a sample campaign description for Medical Treatment for Child 4. This demonstrates how campaigns look on the platform.', 35655.00, 'uploads/default-campaign.jpg', 'pending', '2026-02-22 15:48:04'),
(16, 1, 10, 'Documentary Film Project 5', 'This is a sample campaign description for Documentary Film Project 5. This demonstrates how campaigns look on the platform.', 2738.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-28 15:48:04'),
(17, 1, 8, 'Clean Water Project 6', 'This is a sample campaign description for Clean Water Project 6. This demonstrates how campaigns look on the platform.', 9593.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-27 15:48:04'),
(19, 1, 2, 'Documentary Film Project 1', 'This is a sample campaign description for Documentary Film Project 1. This demonstrates how campaigns look on the platform.', 12216.00, 'uploads/default-campaign.jpg', 'approved', '2026-02-15 21:13:53'),
(20, 2, 3, 'Clean Water Project 2', 'This is a sample campaign description for Clean Water Project 2. This demonstrates how campaigns look on the platform.', 11426.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-21 21:13:53'),
(21, 4, 6, 'Support Local Art Gallery 3', 'This is a sample campaign description for Support Local Art Gallery 3. This demonstrates how campaigns look on the platform.', 9289.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-12 21:13:53'),
(22, 2, 10, 'Help Build a Community Library 4', 'This is a sample campaign description for Help Build a Community Library 4. This demonstrates how campaigns look on the platform.', 19288.00, 'uploads/default-campaign.jpg', 'pending', '2026-01-29 21:13:53'),
(23, 3, 8, 'Music Album Production 5', 'This is a sample campaign description for Music Album Production 5. This demonstrates how campaigns look on the platform.', 5375.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-10 21:13:53'),
(24, 5, 1, 'Environmental Conservation 6', 'This is a sample campaign description for Environmental Conservation 6. This demonstrates how campaigns look on the platform.', 14190.00, 'uploads/default-campaign.jpg', 'approved', '2026-02-21 21:13:53'),
(25, 2, 6, 'Support Local Art Gallery 7', 'This is a sample campaign description for Support Local Art Gallery 7. This demonstrates how campaigns look on the platform.', 13812.00, 'uploads/default-campaign.jpg', 'pending', '2026-02-05 21:13:53'),
(26, 4, 5, 'Medical Treatment for Child 8', 'This is a sample campaign description for Medical Treatment for Child 8. This demonstrates how campaigns look on the platform.', 46051.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-19 21:13:53'),
(27, 1, 6, 'Clean Water Project 9', 'This is a sample campaign description for Clean Water Project 9. This demonstrates how campaigns look on the platform.', 47745.00, 'uploads/default-campaign.jpg', 'pending', '2026-01-01 21:13:53'),
(28, 3, 4, 'Medical Treatment for Child 10', 'This is a sample campaign description for Medical Treatment for Child 10. This demonstrates how campaigns look on the platform.', 22829.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-26 21:13:53'),
(29, 2, 1, 'Startup Tech Innovation 11', 'This is a sample campaign description for Startup Tech Innovation 11. This demonstrates how campaigns look on the platform.', 17344.00, 'uploads/default-campaign.jpg', 'pending', '2026-01-13 21:13:53'),
(30, 4, 4, 'Environmental Conservation 12', 'This is a sample campaign description for Environmental Conservation 12. This demonstrates how campaigns look on the platform.', 3209.00, 'uploads/default-campaign.jpg', 'approved', '2026-02-08 21:13:53'),
(31, 1, 1, 'Support Local Art Gallery 13', 'This is a sample campaign description for Support Local Art Gallery 13. This demonstrates how campaigns look on the platform.', 43810.00, 'uploads/default-campaign.jpg', 'approved', '2026-02-10 21:13:53'),
(32, 4, 4, 'Help Build a Community Library 14', 'This is a sample campaign description for Help Build a Community Library 14. This demonstrates how campaigns look on the platform.', 37749.00, 'uploads/default-campaign.jpg', 'rejected', '2026-02-26 21:13:53'),
(33, 4, 5, 'Help Build a Community Library 15', 'This is a sample campaign description for Help Build a Community Library 15. This demonstrates how campaigns look on the platform.', 43018.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-02 21:13:53'),
(34, 1, 4, 'Clean Water Project 16', 'This is a sample campaign description for Clean Water Project 16. This demonstrates how campaigns look on the platform.', 3692.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-24 21:13:53'),
(35, 3, 8, 'Support Local Art Gallery 17', 'This is a sample campaign description for Support Local Art Gallery 17. This demonstrates how campaigns look on the platform.', 30997.00, 'uploads/default-campaign.jpg', 'rejected', '2026-02-25 21:13:53'),
(36, 5, 1, 'Music Album Production 18', 'This is a sample campaign description for Music Album Production 18. This demonstrates how campaigns look on the platform.', 42327.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-27 21:13:53'),
(37, 3, 2, 'Medical Treatment for Child 19', 'This is a sample campaign description for Medical Treatment for Child 19. This demonstrates how campaigns look on the platform.', 25336.00, 'uploads/default-campaign.jpg', 'approved', '2026-02-03 21:13:53'),
(38, 1, 2, 'Startup Tech Innovation 20', 'This is a sample campaign description for Startup Tech Innovation 20. This demonstrates how campaigns look on the platform.', 38495.00, 'uploads/default-campaign.jpg', 'approved', '2026-01-06 21:13:53'),
(39, 6, 5, 'iftar', 'Iftar i Ramadan  dgfgh hgkj hhfll df ose the most relevant category for your campaign for', 15000.00, 'uploads/campaigns/1772401863_TiroBangla.png', 'approved', '2026-03-01 21:51:04');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Medical', '2026-02-28 07:25:26'),
(2, 'Education', '2026-02-28 07:25:26'),
(3, 'Disaster Relief', '2026-02-28 07:25:26'),
(4, 'Animal Welfare', '2026-02-28 07:25:26'),
(5, 'Community Development', '2026-02-28 07:25:26'),
(6, 'Arts & Culture', '2026-02-28 07:25:26'),
(7, 'Sports', '2026-02-28 07:25:26'),
(8, 'Technology', '2026-02-28 07:25:26'),
(10, 'Art', '2026-02-28 15:44:56'),
(11, 'Music', '2026-02-28 15:44:56'),
(12, 'Film', '2026-02-28 15:44:56'),
(14, 'Health', '2026-02-28 15:44:56'),
(15, 'Community', '2026-02-28 15:44:56'),
(16, 'Environment', '2026-02-28 15:44:56'),
(17, 'Business', '2026-02-28 15:44:56');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `donor_name` varchar(100) DEFAULT NULL,
  `donor_email` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `message` text DEFAULT NULL,
  `is_anonymous` tinyint(4) DEFAULT 0,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`id`, `campaign_id`, `user_id`, `donor_name`, `donor_email`, `amount`, `message`, `is_anonymous`, `payment_method`, `transaction_id`, `status`, `created_at`) VALUES
(1, 7, 1, 'samia', 'demo@example.com', 25.00, 'love to donate', 0, 'bkash', 'DEMO_69A310F156CC8', 'completed', '2026-02-28 15:59:45'),
(2, 6, 3, 'maya', 'demo@example.com', 100.00, '', 0, 'nagad', 'DEMO_69A3449308AC8', 'completed', '2026-02-28 19:40:03'),
(3, 7, 3, 'maya', 'demo@example.com', 25.00, '', 0, 'card', 'DEMO_69A34A7920E45', 'completed', '2026-02-28 20:05:13'),
(4, 6, 4, 'arisha', 'demo@example.com', 100.00, '', 0, 'rocket', 'DEMO_69A34B43C8A8B', 'completed', '2026-02-28 20:08:35'),
(5, 10, 4, 'arisha', 'demo@example.com', 50.00, '', 0, 'bkash', 'DEMO_69A34EEDDA156', 'completed', '2026-02-28 20:24:13'),
(6, 34, 5, 'Jane Smith', 'demo@example.com', 43.00, NULL, 0, 'bkash', 'TXN69a35a9195a19', 'completed', '2026-01-11 21:13:53'),
(7, 35, 5, 'Chris Lee', 'demo@example.com', 230.00, NULL, 0, 'nagad', 'TXN69a35a9196004', 'completed', '2026-02-22 21:13:53'),
(8, 22, 5, 'Lisa Anderson', 'demo@example.com', 485.00, NULL, 0, 'bank_transfer', 'TXN69a35a91972dc', 'completed', '2026-01-14 21:13:53'),
(9, 7, 5, 'David Brown', 'demo@example.com', 92.00, NULL, 0, 'bkash', 'TXN69a35a91978a6', 'completed', '2026-01-24 21:13:53'),
(10, 23, 5, 'John Doe', 'demo@example.com', 130.00, NULL, 0, 'rocket', 'TXN69a35a9197d54', 'completed', '2026-01-10 21:13:53'),
(11, 28, 2, 'Jane Smith', 'demo@example.com', 425.00, NULL, 0, 'rocket', 'TXN69a35a91984ab', 'completed', '2026-02-10 21:13:53'),
(12, 26, 5, 'Emma Davis', 'demo@example.com', 12.00, NULL, 0, 'bank_transfer', 'TXN69a35a9198a90', 'completed', '2026-01-07 21:13:53'),
(13, 19, 3, 'John Doe', 'demo@example.com', 210.00, NULL, 0, 'nagad', 'TXN69a35a919903b', 'completed', '2026-01-16 21:13:53'),
(14, 33, 3, 'Sarah Wilson', 'demo@example.com', 326.00, NULL, 0, 'nagad', 'TXN69a35a919952a', 'completed', '2026-02-01 21:13:53'),
(15, 33, 2, 'David Brown', 'demo@example.com', 28.00, NULL, 0, 'nagad', 'TXN69a35a9199cab', 'completed', '2026-02-27 21:13:53'),
(16, 25, 5, 'Jane Smith', 'demo@example.com', 402.00, NULL, 0, 'bank_transfer', 'TXN69a35a919a3b4', 'completed', '2026-02-07 21:13:53'),
(17, 34, 1, 'Chris Lee', 'demo@example.com', 368.00, NULL, 0, 'card', 'TXN69a35a919aaa3', 'completed', '2025-12-31 21:13:53'),
(18, 37, 2, 'David Brown', 'demo@example.com', 110.00, NULL, 0, 'rocket', 'TXN69a35a919b62c', 'completed', '2026-01-17 21:13:53'),
(19, 31, 3, 'Lisa Anderson', 'demo@example.com', 244.00, NULL, 0, 'bank_transfer', 'TXN69a35a919bb95', 'completed', '2026-02-17 21:13:53'),
(20, 27, 4, 'John Doe', 'demo@example.com', 407.00, NULL, 0, 'bkash', 'TXN69a35a919c0d2', 'completed', '2026-02-27 21:13:53'),
(21, 34, 4, 'John Doe', 'demo@example.com', 486.00, NULL, 0, 'rocket', 'TXN69a35a919c6bc', 'completed', '2026-01-20 21:13:53'),
(22, 31, 3, 'Jane Smith', 'demo@example.com', 212.00, NULL, 0, 'card', 'TXN69a35a919ce91', 'completed', '2026-01-20 21:13:53'),
(23, 38, 3, 'John Doe', 'demo@example.com', 399.00, NULL, 0, 'rocket', 'TXN69a35a919d5b1', 'completed', '2026-02-11 21:13:53'),
(24, 30, 5, 'Emma Davis', 'demo@example.com', 82.00, NULL, 0, 'rocket', 'TXN69a35a919dbe6', 'completed', '2026-02-19 21:13:53'),
(25, 8, 5, 'Emma Davis', 'demo@example.com', 375.00, NULL, 0, 'card', 'TXN69a35a919e24a', 'completed', '2026-01-22 21:13:53'),
(26, 9, 3, 'John Doe', 'demo@example.com', 209.00, NULL, 0, 'bank_transfer', 'TXN69a35a919e885', 'completed', '2026-01-02 21:13:53'),
(27, 38, 3, 'Mike Johnson', 'demo@example.com', 79.00, NULL, 0, 'card', 'TXN69a35a919f14e', 'completed', '2026-02-05 21:13:53'),
(28, 14, 2, 'Emma Davis', 'demo@example.com', 34.00, NULL, 0, 'bkash', 'TXN69a35a919ffd6', 'completed', '2026-01-31 21:13:53'),
(29, 5, 5, 'Jane Smith', 'demo@example.com', 182.00, NULL, 0, 'rocket', 'TXN69a35a91a05e1', 'completed', '2026-01-11 21:13:53'),
(30, 9, 2, 'Mike Johnson', 'demo@example.com', 212.00, NULL, 0, 'nagad', 'TXN69a35a91a0bb3', 'completed', '2026-02-26 21:13:53'),
(31, 15, 3, 'Jane Smith', 'demo@example.com', 363.00, NULL, 0, 'bank_transfer', 'TXN69a35a91a1183', 'completed', '2026-02-07 21:13:53'),
(32, 25, 2, 'John Doe', 'demo@example.com', 83.00, NULL, 0, 'bank_transfer', 'TXN69a35a91a187c', 'completed', '2026-02-13 21:13:53'),
(33, 8, 4, 'Lisa Anderson', 'demo@example.com', 194.00, NULL, 0, 'bkash', 'TXN69a35a91a1c95', 'completed', '2026-02-01 21:13:53'),
(34, 29, 1, 'Mike Johnson', 'demo@example.com', 138.00, NULL, 0, 'nagad', 'TXN69a35a91a210e', 'completed', '2026-01-19 21:13:53'),
(35, 38, 3, 'Jane Smith', 'demo@example.com', 312.00, NULL, 0, 'card', 'TXN69a35a91a2629', 'completed', '2026-01-14 21:13:53'),
(36, 34, 3, 'Sarah Wilson', 'demo@example.com', 17.00, NULL, 0, 'bkash', 'TXN69a35a91a2c3e', 'completed', '2026-01-12 21:13:53'),
(37, 8, 2, 'Chris Lee', 'demo@example.com', 311.00, NULL, 0, 'bkash', 'TXN69a35a91a396d', 'completed', '2026-01-19 21:13:53'),
(38, 30, 1, 'Chris Lee', 'demo@example.com', 420.00, NULL, 0, 'card', 'TXN69a35a91a40fb', 'completed', '2026-01-24 21:13:53'),
(39, 28, 4, 'Lisa Anderson', 'demo@example.com', 494.00, NULL, 0, 'rocket', 'TXN69a35a91a4679', 'completed', '2026-02-25 21:13:53'),
(40, 12, 4, 'Sarah Wilson', 'demo@example.com', 383.00, NULL, 0, 'rocket', 'TXN69a35a91a4b6a', 'completed', '2026-01-26 21:13:53'),
(41, 6, 4, 'Lisa Anderson', 'demo@example.com', 488.00, NULL, 0, 'card', 'TXN69a35a91a50a0', 'completed', '2026-02-25 21:13:53'),
(42, 14, 4, 'Jane Smith', 'demo@example.com', 408.00, NULL, 0, 'nagad', 'TXN69a35a91a56c0', 'completed', '2026-01-09 21:13:53'),
(43, 5, 2, 'Chris Lee', 'demo@example.com', 368.00, NULL, 0, 'nagad', 'TXN69a35a91a5c99', 'completed', '2026-01-01 21:13:53'),
(44, 14, 1, 'Emma Davis', 'demo@example.com', 167.00, NULL, 0, 'bank_transfer', 'TXN69a35a91a630e', 'completed', '2026-02-18 21:13:53'),
(45, 30, 4, 'Emma Davis', 'demo@example.com', 494.00, NULL, 0, 'bank_transfer', 'TXN69a35a91a68e6', 'completed', '2026-02-19 21:13:53'),
(46, 17, 4, 'Mike Johnson', 'demo@example.com', 135.00, NULL, 0, 'nagad', 'TXN69a35a91a7311', 'completed', '2026-01-30 21:13:53'),
(47, 17, 2, 'Jane Smith', 'demo@example.com', 89.00, NULL, 0, 'card', 'TXN69a35a91a787d', 'completed', '2026-02-26 21:13:53'),
(48, 7, 2, 'John Doe', 'demo@example.com', 298.00, NULL, 0, 'bank_transfer', 'TXN69a35a91a7d83', 'completed', '2026-02-04 21:13:53'),
(49, 27, 1, 'David Brown', 'demo@example.com', 365.00, NULL, 0, 'nagad', 'TXN69a35a91a848c', 'completed', '2026-02-03 21:13:53'),
(50, 28, 1, 'David Brown', 'demo@example.com', 72.00, NULL, 0, 'bkash', 'TXN69a35a91a88df', 'completed', '2026-01-04 21:13:53'),
(51, 22, 2, 'Mike Johnson', 'demo@example.com', 59.00, NULL, 0, 'nagad', 'TXN69a35a91a8ced', 'completed', '2026-01-27 21:13:53'),
(52, 10, 2, 'Sarah Wilson', 'demo@example.com', 240.00, NULL, 0, 'card', 'TXN69a35a91a9054', 'completed', '2026-02-02 21:13:53'),
(53, 13, 3, 'Sarah Wilson', 'demo@example.com', 121.00, NULL, 0, 'bkash', 'TXN69a35a91a9397', 'completed', '2026-02-08 21:13:53'),
(54, 36, 1, 'John Doe', 'demo@example.com', 472.00, NULL, 0, 'bank_transfer', 'TXN69a35a91a976c', 'completed', '2026-01-26 21:13:53'),
(55, 9, 4, 'Jane Smith', 'demo@example.com', 32.00, NULL, 0, 'card', 'TXN69a35a91a9b30', 'completed', '2026-01-22 21:13:53'),
(56, 9, 5, 'Jane Smith', 'demo@example.com', 492.00, NULL, 0, 'nagad', 'TXN69a35a91a9eb7', 'completed', '2026-02-21 21:13:53'),
(57, 27, 1, 'David Brown', 'demo@example.com', 22.00, NULL, 0, 'rocket', 'TXN69a35a91aa1ed', 'completed', '2026-01-15 21:13:53'),
(58, 37, 1, 'Chris Lee', 'demo@example.com', 476.00, NULL, 0, 'nagad', 'TXN69a35a91aa542', 'completed', '2026-02-21 21:13:53'),
(59, 27, 5, 'Jane Smith', 'demo@example.com', 228.00, NULL, 0, 'bank_transfer', 'TXN69a35a91aa8fd', 'completed', '2026-02-20 21:13:53'),
(60, 34, 1, 'David Brown', 'demo@example.com', 479.00, NULL, 0, 'bank_transfer', 'TXN69a35a91aad5c', 'completed', '2026-01-14 21:13:53'),
(61, 7, 2, 'Chris Lee', 'demo@example.com', 187.00, NULL, 0, 'card', 'TXN69a35a91ab8d7', 'completed', '2026-02-22 21:13:53'),
(62, 29, 1, 'David Brown', 'demo@example.com', 388.00, NULL, 0, 'bkash', 'TXN69a35a91abe0b', 'completed', '2026-02-08 21:13:53'),
(63, 26, 1, 'Mike Johnson', 'demo@example.com', 404.00, NULL, 0, 'bank_transfer', 'TXN69a35a91ac1aa', 'completed', '2026-02-16 21:13:53'),
(64, 19, 4, 'Sarah Wilson', 'demo@example.com', 413.00, NULL, 0, 'bank_transfer', 'TXN69a35a91ac4e6', 'completed', '2026-01-28 21:13:53'),
(65, 34, 1, 'Chris Lee', 'demo@example.com', 26.00, NULL, 0, 'card', 'TXN69a35a91ac81e', 'completed', '2026-01-25 21:13:53'),
(66, 6, 5, 'David Brown', 'demo@example.com', 430.00, NULL, 0, 'nagad', 'TXN69a35a91acb65', 'completed', '2026-02-14 21:13:53'),
(67, 30, 4, 'John Doe', 'demo@example.com', 42.00, NULL, 0, 'rocket', 'TXN69a35a91acec1', 'completed', '2026-01-27 21:13:53'),
(68, 9, 1, 'John Doe', 'demo@example.com', 454.00, NULL, 0, 'bank_transfer', 'TXN69a35a91ad25b', 'completed', '2026-01-16 21:13:53'),
(69, 21, 5, 'John Doe', 'demo@example.com', 88.00, NULL, 0, 'rocket', 'TXN69a35a91ad5b1', 'completed', '2026-01-27 21:13:53'),
(70, 7, 4, 'John Doe', 'demo@example.com', 382.00, NULL, 0, 'nagad', 'TXN69a35a91ad995', 'completed', '2026-02-17 21:13:53'),
(71, 37, 5, 'Lisa Anderson', 'demo@example.com', 465.00, NULL, 0, 'rocket', 'TXN69a35a91add25', 'completed', '2026-02-16 21:13:53'),
(72, 38, 3, 'Lisa Anderson', 'demo@example.com', 112.00, NULL, 0, 'rocket', 'TXN69a35a91ae0a9', 'completed', '2026-02-02 21:13:53'),
(73, 38, 5, 'Mike Johnson', 'demo@example.com', 390.00, NULL, 0, 'nagad', 'TXN69a35a91ae406', 'completed', '2026-02-17 21:13:53'),
(74, 6, 4, 'Sarah Wilson', 'demo@example.com', 120.00, NULL, 0, 'nagad', 'TXN69a35a91ae73a', 'completed', '2026-01-29 21:13:53'),
(75, 23, 5, 'John Doe', 'demo@example.com', 29.00, NULL, 0, 'bank_transfer', 'TXN69a35a91ae9a3', 'completed', '2026-02-17 21:13:53'),
(76, 8, 2, 'Lisa Anderson', 'demo@example.com', 214.00, NULL, 0, 'rocket', 'TXN69a35a91aed97', 'completed', '2026-02-08 21:13:53'),
(77, 32, 2, 'Sarah Wilson', 'demo@example.com', 435.00, NULL, 0, 'bkash', 'TXN69a35a91af08f', 'completed', '2026-01-23 21:13:53'),
(78, 29, 1, 'Sarah Wilson', 'demo@example.com', 108.00, NULL, 0, 'rocket', 'TXN69a35a91af2e6', 'completed', '2026-02-03 21:13:53'),
(79, 21, 4, 'Jane Smith', 'demo@example.com', 85.00, NULL, 0, 'nagad', 'TXN69a35a91af545', 'completed', '2026-02-07 21:13:53'),
(80, 8, 5, 'John Doe', 'demo@example.com', 183.00, NULL, 0, 'nagad', 'TXN69a35a91af78f', 'completed', '2026-02-03 21:13:53'),
(81, 27, 5, 'David Brown', 'demo@example.com', 495.00, NULL, 0, 'nagad', 'TXN69a35a91af9e8', 'completed', '2026-01-03 21:13:53'),
(82, 22, 5, 'Sarah Wilson', 'demo@example.com', 465.00, NULL, 0, 'nagad', 'TXN69a35a91afc70', 'completed', '2026-01-17 21:13:53'),
(83, 7, 1, 'Lisa Anderson', 'demo@example.com', 78.00, NULL, 0, 'bkash', 'TXN69a35a91affaf', 'completed', '2026-01-16 21:13:53'),
(84, 31, 5, 'Chris Lee', 'demo@example.com', 107.00, NULL, 0, 'rocket', 'TXN69a35a91b0221', 'completed', '2026-02-21 21:13:53'),
(85, 30, 4, 'Sarah Wilson', 'demo@example.com', 277.00, NULL, 0, 'bkash', 'TXN69a35a91b04a0', 'completed', '2026-01-23 21:13:53'),
(86, 6, 5, 'John Doe', 'demo@example.com', 22.00, NULL, 0, 'nagad', 'TXN69a35a91b06fb', 'completed', '2026-01-30 21:13:53'),
(87, 29, 3, 'Emma Davis', 'demo@example.com', 279.00, NULL, 0, 'bank_transfer', 'TXN69a35a91b0929', 'completed', '2025-12-31 21:13:53'),
(88, 21, 1, 'Jane Smith', 'demo@example.com', 107.00, NULL, 0, 'rocket', 'TXN69a35a91b0b77', 'completed', '2026-01-11 21:13:53'),
(89, 22, 5, 'Lisa Anderson', 'demo@example.com', 414.00, NULL, 0, 'bkash', 'TXN69a35a91b0d9f', 'completed', '2026-01-17 21:13:53'),
(90, 36, 1, 'Emma Davis', 'demo@example.com', 60.00, NULL, 0, 'rocket', 'TXN69a35a91b1095', 'completed', '2026-01-23 21:13:53'),
(91, 35, 4, 'Emma Davis', 'demo@example.com', 126.00, NULL, 0, 'card', 'TXN69a35a91b136b', 'completed', '2026-02-25 21:13:53'),
(92, 14, 5, 'John Doe', 'demo@example.com', 128.00, NULL, 0, 'bkash', 'TXN69a35a91b15ba', 'completed', '2026-01-23 21:13:53'),
(93, 28, 5, 'Jane Smith', 'demo@example.com', 269.00, NULL, 0, 'bkash', 'TXN69a35a91b17f8', 'completed', '2026-01-01 21:13:53'),
(94, 36, 4, 'Mike Johnson', 'demo@example.com', 180.00, NULL, 0, 'rocket', 'TXN69a35a91b1a11', 'completed', '2026-02-20 21:13:53'),
(95, 33, 3, 'David Brown', 'demo@example.com', 197.00, NULL, 0, 'nagad', 'TXN69a35a91b1c4d', 'completed', '2026-02-19 21:13:53'),
(96, 29, 4, 'David Brown', 'demo@example.com', 101.00, NULL, 0, 'nagad', 'TXN69a35a91b1f93', 'completed', '2026-01-16 21:13:53'),
(97, 23, 3, 'Jane Smith', 'demo@example.com', 323.00, NULL, 0, 'rocket', 'TXN69a35a91b22f6', 'completed', '2026-01-18 21:13:53'),
(98, 36, 2, 'David Brown', 'demo@example.com', 272.00, NULL, 0, 'bkash', 'TXN69a35a91b2572', 'completed', '2026-01-28 21:13:53'),
(99, 8, 5, 'Lisa Anderson', 'demo@example.com', 60.00, NULL, 0, 'rocket', 'TXN69a35a91b2991', 'completed', '2026-02-19 21:13:53'),
(100, 34, 1, 'Jane Smith', 'demo@example.com', 311.00, NULL, 0, 'card', 'TXN69a35a91b2c64', 'completed', '2026-01-29 21:13:53'),
(101, 35, 3, 'Sarah Wilson', 'demo@example.com', 39.00, NULL, 0, 'bank_transfer', 'TXN69a35a91b2ee7', 'completed', '2026-02-12 21:13:53'),
(102, 8, 5, 'Lisa Anderson', 'demo@example.com', 98.00, NULL, 0, 'card', 'TXN69a35a91b315c', 'completed', '2026-01-11 21:13:53'),
(103, 29, 5, 'David Brown', 'demo@example.com', 49.00, NULL, 0, 'bkash', 'TXN69a35a91b33aa', 'completed', '2026-01-24 21:13:53'),
(104, 19, 1, 'Sarah Wilson', 'demo@example.com', 380.00, NULL, 0, 'nagad', 'TXN69a35a91b3620', 'completed', '2026-01-12 21:13:53'),
(105, 26, 4, 'Chris Lee', 'demo@example.com', 261.00, NULL, 0, 'bkash', 'TXN69a35a91b38e9', 'completed', '2026-01-08 21:13:53'),
(106, 6, 6, 'nuha', 'demo@example.com', 25.00, '', 0, 'bkash', 'DEMO_69A4A40C42BBA', 'completed', '2026-03-01 20:39:40');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `enabled` tinyint(4) DEFAULT 1,
  `account_number` varchar(100) DEFAULT NULL,
  `merchant_number` varchar(100) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `routing_number` varchar(50) DEFAULT NULL,
  `branch_name` varchar(100) DEFAULT NULL,
  `additional_info` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `payment_method`, `enabled`, `account_number`, `merchant_number`, `account_name`, `bank_name`, `routing_number`, `branch_name`, `additional_info`, `created_at`, `updated_at`) VALUES
(1, 'bkash', 1, '01953311485', '01753311485', NULL, NULL, NULL, NULL, NULL, '2026-02-28 14:58:56', '2026-02-28 14:59:12'),
(2, 'nagad', 1, '01953311485', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-28 14:58:56', '2026-02-28 14:58:56'),
(3, 'rocket', 1, '01953311485', NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-28 14:58:56', '2026-02-28 14:58:56'),
(4, 'bank_transfer', 1, '12345678901', NULL, 'Crowdfunding Platform', 'Islami Bank Bangladesh Ltd.', '123456789', 'Motijheel, Dhaka', NULL, '2026-02-28 14:58:56', '2026-02-28 14:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `privacy_settings`
--

CREATE TABLE `privacy_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_visibility` enum('public','private','friends') DEFAULT 'public',
  `show_donations` tinyint(4) DEFAULT 1,
  `show_campaigns` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Crowdfunding Platform', 'general', '2026-02-28 14:48:43', '2026-02-28 14:48:43'),
(2, 'site_description', 'A platform for crowdfunding campaigns', 'general', '2026-02-28 14:48:43', '2026-02-28 14:48:43'),
(3, 'site_email', 'info@crowdfund.com', 'general', '2026-02-28 14:48:43', '2026-02-28 14:48:43'),
(4, 'site_phone', '+1 (555) 123-4567', 'general', '2026-02-28 14:48:43', '2026-02-28 14:48:43'),
(5, 'site_address', 'Barishal,Bangladesh', 'general', '2026-02-28 14:48:43', '2026-02-28 14:48:43'),
(6, 'site_currency', 'BDT', 'general', '2026-02-28 14:48:43', '2026-02-28 14:48:43'),
(7, 'site_timezone', 'Asia/Dhaka', 'general', '2026-02-28 14:48:43', '2026-02-28 14:48:43');

-- --------------------------------------------------------

--
-- Table structure for table `social_links`
--

CREATE TABLE `social_links` (
  `id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `social_links`
--

INSERT INTO `social_links` (`id`, `platform`, `url`) VALUES
(1, 'facebook', 'https://facebook.com/yourpage'),
(2, 'twitter', 'https://twitter.com/yourpage'),
(3, 'instagram', 'https://instagram.com/yourpage'),
(4, 'youtube', 'https://youtube.com/yourpage'),
(5, 'linkedin', 'https://linkedin.com/yourpage');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('donor','organizer','both') DEFAULT 'donor',
  `status` enum('active','pending','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('donor','campaign_creator','admin') DEFAULT 'donor',
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `nid_number` varchar(20) DEFAULT NULL,
  `nid_image` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `nid_type` varchar(10) DEFAULT NULL,
  `country` varchar(10) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `referral_source` varchar(50) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `bio`, `location`, `website`, `profile_pic`, `password`, `user_type`, `status`, `created_at`, `role`, `full_name`, `phone`, `nid_number`, `nid_image`, `profile_image`, `address`, `dob`, `occupation`, `nid_type`, `country`, `city`, `state`, `postal_code`, `experience`, `facebook`, `twitter`, `linkedin`, `referral_source`, `rejection_reason`) VALUES
(1, 'samia', 'samia@gmail.com', 'asgae', 'Barishal,Bangladesh', '', 'uploads/profiles/user_1_1772272846.png', '$2y$10$oUkJrx0KO7wjVmKZvRWgO.Uu5baQ0e2PBk5nj/gWq0uT0XobTXNjW', 'donor', 'active', '2026-02-28 07:56:41', 'donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'moumita', 'moumita@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$16AyNaoPpLfZcfaHDcoFiesB6Iw.dDPTA4bI4L3oERgS6n4w4W7ni', 'donor', 'active', '2026-02-28 13:04:49', 'donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'maya', 'maya@gmail.com', '', '', '', 'uploads/profiles/user_3_1772307329.png', '$2y$10$SVo3xlLVqnWojTqadEWBvuJ1rSfvmjvSRztwyZ2GQqlC51ingc0Y6', 'both', 'active', '2026-02-28 19:33:47', 'donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'arisha', 'arisha@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$VfFJKjcdYUoSXZav/Louu.vx8JZiF/rIB9MDVhHhB8ftrbhWzcZeq', 'organizer', 'active', '2026-02-28 20:07:09', 'donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'habiba', 'habiba@gmail.com', '', '', '', 'uploads/profiles/user_5_1772314177.png', '$2y$10$kPT9DSz7v2wCVaqc71xi4eEmwpGL0DklMQvtjZQ2zA1HcOLzbk09O', 'donor', 'active', '2026-02-28 20:43:47', 'donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'nuha', 'nuha@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$2lM8/3283ogHI1tVsX11QujZyZQjEF75ExWSrcVpkKU7mp6s710CO', 'both', 'active', '2026-03-01 20:24:16', 'donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'faria', 'faria@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$bpza.tdSQBV3Kg6Up1EgN.77dWH5IZsg32ZYVgFJUDXfyiIA.6g7e', 'donor', 'active', '2026-03-01 21:27:11', 'donor', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'mim', 'mim@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$FVwsdZ0sc74v0HVhOiAewurz8/BBZp/lccNbupDOiOvofWI7fZFzu', 'organizer', 'active', '2026-03-01 23:34:52', 'donor', 'Ritu Akter Samia', '01533114856', '77868990357129340', 'uploads/verification/nid_1772408092_69a4cd1c304dc.png', 'uploads/verification/profile_1772408092_69a4cd1c306bb.jpg', 'bgadff,barishal', '2000-04-02', 'student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'puja', 'puja@gmail.com', NULL, NULL, NULL, NULL, '$2y$10$e44u3c3aX4ItGohpFZEzquvhTAYs.0OO0AM4QUCiMYKl8aGzvV/ya', 'both', 'pending', '2026-03-02 00:26:26', 'donor', 'Puja Ghose', '01779661873', '77868990357129342', 'uploads/verification/nid_1772411186_69a4d932477dc.jpg', 'uploads/verification/profile_1772411186_69a4d93247a76.png', 'barishal,bd', '2000-03-31', 'student', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'ritu', 'ritu@gmail.com', NULL, NULL, '', NULL, '$2y$10$zNO5OlNjvNrABSrw7eRowufMajF7gSu0hDhvKXvWkoNsW3F1l.rbG', 'both', 'pending', '2026-03-02 00:58:50', 'donor', 'Ritu Akter', '+88001779661873', '12345678901234567', 'uploads/verification/nid_1772413130_69a4e0ca8a9e3.png', 'uploads/verification/profile_1772413130_69a4e0ca8ae75.jpg', 'sodor road,barishal', '2004-01-28', 'student', 'BD', 'BD', 'Barishal', '', '8200', 'beginner', 'https://www.facebook.com/ritu.talucdar', 'https://x.com/samiaritu13', 'https://www.linkedin.com/in/ritu-akter-samia-9600712a7/', 'social_media', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_notifications` tinyint(4) DEFAULT 1,
  `campaign_updates` tinyint(4) DEFAULT 1,
  `new_donations` tinyint(4) DEFAULT 1,
  `newsletter` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `email_notifications`, `campaign_updates`, `new_donations`, `newsletter`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, '2026-02-28 10:04:13', '2026-02-28 10:04:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_campaign_per_user` (`title`,`user_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_method` (`payment_method`);

--
-- Indexes for table `privacy_settings`
--
ALTER TABLE `privacy_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `social_links`
--
ALTER TABLE `social_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `privacy_settings`
--
ALTER TABLE `privacy_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `social_links`
--
ALTER TABLE `social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaigns_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `donations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `privacy_settings`
--
ALTER TABLE `privacy_settings`
  ADD CONSTRAINT `privacy_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
