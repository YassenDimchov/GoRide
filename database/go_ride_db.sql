-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Време на генериране: 24 фев 2026 в 18:32
-- Версия на сървъра: 10.4.32-MariaDB
-- Версия на PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данни: `go_ride`
--

-- --------------------------------------------------------

--
-- Структура на таблица `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL COMMENT 'Primary key, auto increment',
  `user_id` int(11) NOT NULL COMMENT 'links to users.id',
  `vehicle_make` varchar(50) DEFAULT NULL COMMENT 'The make of the driver''s vehicle',
  `vehicle_model` varchar(50) DEFAULT NULL COMMENT 'The model of the driver''s vehicle',
  `vehicle_color` varchar(30) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL COMMENT 'The license plate of the driver''s vehicle',
  `passenger_capacity` tinyint(3) UNSIGNED NOT NULL DEFAULT 4,
  `status` enum('available','busy','offline') NOT NULL DEFAULT 'offline',
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `current_lat` decimal(10,7) DEFAULT NULL,
  `current_lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `drivers`
--

INSERT INTO `drivers` (`id`, `user_id`, `vehicle_make`, `vehicle_model`, `vehicle_color`, `license_plate`, `passenger_capacity`, `status`, `last_seen_at`, `current_lat`, `current_lng`, `created_at`, `updated_at`) VALUES
(6, 12, 'VW', 'Golf', 'Silver', 'CB1234AA', 4, 'available', '2026-02-21 16:39:21', 42.6718705, 23.2887166, '2026-01-27 13:12:39', '2026-02-21 16:39:21'),
(7, 16, 'Honda', 'Fit', 'black', 'SSSSSSS', 4, 'offline', '2026-02-21 14:12:26', 42.6720776, 23.2886904, '2026-02-21 14:10:23', '2026-02-21 14:12:26');

-- --------------------------------------------------------

--
-- Структура на таблица `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Схема на данните от таблица `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_01_03_223341_create_personal_access_tokens_table', 1),
(2, '2026_01_04_170911_add_started_at_to_rides_table', 2),
(3, '2026_01_13_195726_add_last_ride_completed_at_to_drivers_table', 3),
(4, '2026_02_06_184815_add_last_seen_at_to_drivers_table', 4),
(5, '2026_02_06_184817_add_last_seen_at_to_drivers_table', 4),
(6, '2026_02_07_185316_add_trip_fields_to_rides_table', 5),
(7, '2026_02_19_204704_add_account_state_to_users_table', 6),
(8, '2026_02_20_210000_add_vehicle_color_to_drivers_table', 7),
(9, '2026_02_20_220000_add_capacity_and_passengers_to_drivers_and_rides', 8),
(10, '2026_02_21_000000_add_suspended_to_users_table', 9);

-- --------------------------------------------------------

--
-- Структура на таблица `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL COMMENT 'Primary key, auto increment, unique payment id',
  `ride_id` int(11) NOT NULL COMMENT 'Links to rides.id (one payment per ride)',
  `amount` decimal(8,2) NOT NULL COMMENT 'Total payment amount\r\n',
  `method` enum('cash','card') NOT NULL DEFAULT 'cash' COMMENT 'Payment method used\r\n',
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending' COMMENT 'Current payment status\r\n',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT 'Time when payment was completed\r\n',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Payment creation time',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Payment update time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `payments`
--

INSERT INTO `payments` (`id`, `ride_id`, `amount`, `method`, `status`, `paid_at`, `created_at`, `updated_at`) VALUES
(4, 16, 10.00, 'cash', 'paid', NULL, '2026-01-27 13:13:35', '2026-01-27 13:13:35'),
(26, 173, 7.79, 'cash', 'pending', NULL, '2026-02-16 17:22:00', '2026-02-16 17:22:00'),
(27, 175, 7.12, 'cash', 'pending', NULL, '2026-02-17 09:42:57', '2026-02-17 09:42:57'),
(29, 177, 5.00, 'cash', 'paid', '2026-02-21 00:23:52', '2026-02-18 16:05:22', '2026-02-21 00:23:52'),
(30, 178, 5.00, 'cash', 'paid', '2026-02-21 00:23:50', '2026-02-20 18:21:29', '2026-02-21 00:23:50'),
(31, 179, 7.42, 'card', 'paid', '2026-02-20 23:31:01', '2026-02-20 18:23:35', '2026-02-20 23:31:01'),
(32, 181, 5.00, 'card', 'paid', '2026-02-20 23:40:16', '2026-02-20 23:35:02', '2026-02-20 23:40:16'),
(33, 182, 18.07, 'card', 'pending', NULL, '2026-02-20 23:37:43', '2026-02-20 23:37:43'),
(34, 183, 5.00, 'card', 'pending', NULL, '2026-02-20 23:38:54', '2026-02-20 23:38:54'),
(35, 185, 9.11, 'card', 'pending', NULL, '2026-02-20 23:53:40', '2026-02-20 23:53:40'),
(36, 189, 3.50, 'cash', 'pending', NULL, '2026-02-21 14:11:52', '2026-02-21 14:11:52');

-- --------------------------------------------------------

--
-- Структура на таблица `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Схема на данните от таблица `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 5, 'postman', '5f936c82a40722c2a6c7f7704d8e8b56b76bfe469286c8efd51d2024632496aa', '[\"*\"]', '2026-01-13 18:12:44', NULL, '2026-01-03 20:38:27', '2026-01-13 18:12:44'),
(2, 'App\\Models\\User', 6, 'driver-postman', '219cd3092ecae4fa6c3b8cf29157e7740ab57e8a03fd506b0bc7115b460b71a1', '[\"*\"]', NULL, NULL, '2026-01-04 14:39:00', '2026-01-04 14:39:00'),
(3, 'App\\Models\\User', 6, 'driver-postman', 'e9800d9cec880be7fa049dcb2b8fbe519a44d39ac5ebbf9d3c37b64c812e8309', '[\"*\"]', '2026-01-13 21:04:13', NULL, '2026-01-04 14:46:20', '2026-01-13 21:04:13'),
(4, 'App\\Models\\User', 10, 'driver-10-postman', '3c67cc200b9899a8e9c78a45a935432005089c427f04938e33fec3927673991b', '[\"*\"]', '2026-01-13 21:00:02', NULL, '2026-01-04 15:21:09', '2026-01-13 21:00:02'),
(99, 'App\\Models\\User', 14, 'api', 'a1a11acb95d48d315d5961cc5f671e29b8c635e2392f6c9cfbad7bacc4cd4e81', '[\"*\"]', '2026-02-19 19:01:18', NULL, '2026-02-18 22:45:26', '2026-02-19 19:01:18'),
(100, 'App\\Models\\User', 14, 'api', '749935fe9417bbd7488d77a94f5202af2be96fda95b2e591fef2367c07f8bc53', '[\"*\"]', NULL, NULL, '2026-02-19 19:01:22', '2026-02-19 19:01:22'),
(101, 'App\\Models\\User', 14, 'api', 'f476f2e6d6849a2c1971cf3d35e9f1e275ae723bf2c411717f1b9776ccff207d', '[\"*\"]', NULL, NULL, '2026-02-19 19:01:25', '2026-02-19 19:01:25'),
(102, 'App\\Models\\User', 14, 'api', '6525c7ee467b5be7c55e6973b47b1ad19e2d8952da0df4e9d49910009ec7f9a0', '[\"*\"]', NULL, NULL, '2026-02-19 19:01:34', '2026-02-19 19:01:34'),
(104, 'App\\Models\\User', 14, 'api', '1350e3239cc3a5175486a46efb629081b42f51be018854d329a4ee04a5eb268e', '[\"*\"]', NULL, NULL, '2026-02-19 19:02:56', '2026-02-19 19:02:56'),
(106, 'App\\Models\\User', 14, 'api', '25f26aeb28b041f50120578258f40ad52a6a0a8664efc4c3c2c3bcb033cbe671', '[\"*\"]', NULL, NULL, '2026-02-19 19:08:12', '2026-02-19 19:08:12'),
(146, 'App\\Models\\User', 13, 'api', 'bc1b4f4faf1eafe28e255d9026a5fd9a2a1fd9604be56bcb62b2c751d4985c66', '[\"*\"]', '2026-02-21 00:42:22', NULL, '2026-02-20 17:03:13', '2026-02-21 00:42:22'),
(148, 'App\\Models\\User', 15, 'api', '6184115872461a2cf97646360b4c62fa0eb7e7576c3729e031b10f28362ba567', '[\"*\"]', '2026-02-20 20:39:37', NULL, '2026-02-20 20:39:28', '2026-02-20 20:39:37'),
(155, 'App\\Models\\User', 12, 'api', 'c0e6481520ff77d9f337ac54590ba8b5b2cab1467e70a8891818c71539d2f632', '[\"*\"]', '2026-02-21 00:42:40', NULL, '2026-02-20 23:34:36', '2026-02-21 00:42:40'),
(162, 'App\\Models\\User', 14, 'api', '7d8570cfd62eb91d09969de35c7cd758f64dafdc032b58d3ef31aeaccaaebf78', '[\"*\"]', '2026-02-21 16:39:16', NULL, '2026-02-21 14:12:19', '2026-02-21 16:39:16'),
(163, 'App\\Models\\User', 12, 'api', '8e3ab83446bd887965c9a0f06e1d091568249c219f5702a56b234c77071bf3d3', '[\"*\"]', '2026-02-21 16:39:21', NULL, '2026-02-21 14:54:13', '2026-02-21 16:39:21');

-- --------------------------------------------------------

--
-- Структура на таблица `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL COMMENT 'Primary key, unique review id',
  `ride_id` int(11) NOT NULL COMMENT 'Links to rides.id (one review only)',
  `user_id` int(11) NOT NULL COMMENT 'Reviewer user ID (links to users.id)',
  `driver_id` int(11) NOT NULL COMMENT 'Reviewed driver ID (links to drivers.id)',
  `rating` tinyint(4) NOT NULL COMMENT 'Rating value from 1 to 5',
  `review_text` text DEFAULT NULL COMMENT 'Optional written feedback about the ride',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Review creation time',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Last review update time'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `reviews`
--

INSERT INTO `reviews` (`id`, `ride_id`, `user_id`, `driver_id`, `rating`, `review_text`, `created_at`, `updated_at`) VALUES
(4, 16, 13, 6, 3, 'Very Nice!', '2026-01-27 13:23:19', '2026-01-27 13:23:19'),
(7, 175, 13, 6, 3, NULL, '2026-02-17 09:43:06', '2026-02-17 09:43:06'),
(27, 177, 13, 6, 4, NULL, '2026-02-18 16:05:27', '2026-02-18 16:05:27');

-- --------------------------------------------------------

--
-- Структура на таблица `rides`
--

CREATE TABLE `rides` (
  `id` int(11) NOT NULL COMMENT 'Primary key, auto increment,  unique ride id\r\n\r\n',
  `user_id` int(11) NOT NULL COMMENT 'Passenger user ID (links to users.id)\r\n',
  `driver_id` int(11) DEFAULT NULL COMMENT 'Assigned driver ID (links to drivers.id)\r\n',
  `start_lat` decimal(10,7) NOT NULL COMMENT 'Starting latitude of the ride\r\n',
  `start_lng` decimal(10,7) NOT NULL COMMENT 'Starting longitude of the ride\r\n',
  `end_lat` decimal(10,7) NOT NULL COMMENT 'Destination latitude of the ride\r\n',
  `end_lng` decimal(10,7) NOT NULL COMMENT 'Destination longitude of the ride\r\n',
  `trip_distance_m` int(10) UNSIGNED DEFAULT NULL,
  `trip_duration_s` int(10) UNSIGNED DEFAULT NULL,
  `estimated_fare` decimal(8,2) DEFAULT NULL,
  `start_address` varchar(255) NOT NULL COMMENT 'readable pickup address',
  `end_address` varchar(255) NOT NULL COMMENT 'readable destination address',
  `passenger_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `fare` decimal(8,2) DEFAULT NULL COMMENT 'total fare',
  `status` enum('pending','accepted','ongoing','pending_confirmation','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT 'Current status of the ride\r\n',
  `accepted_at` timestamp NULL DEFAULT NULL COMMENT 'Time when the driver accepted the ride\r\n',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL COMMENT 'Time when the ride was completed\r\n',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Ride creation timestamp\r\n',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Last ride update timestamp\r\n'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `rides`
--

INSERT INTO `rides` (`id`, `user_id`, `driver_id`, `start_lat`, `start_lng`, `end_lat`, `end_lng`, `trip_distance_m`, `trip_duration_s`, `estimated_fare`, `start_address`, `end_address`, `passenger_count`, `fare`, `status`, `accepted_at`, `started_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(16, 13, 6, 42.6977000, 23.3219000, 42.6800000, 23.3000000, NULL, NULL, NULL, 'Sofia, NDK', 'Borovo', 1, 10.00, 'completed', '2026-01-27 17:07:19', '2026-01-27 17:07:24', '2026-01-27 17:09:12', '2026-01-27 13:13:09', '2026-01-27 13:13:09'),
(173, 13, 6, 42.6721735, 23.2888448, 42.6885551, 23.2953743, 2738, 489, 7.79, '3 Родопски извор, Sofia, SF, Bulgaria', 'Mokrenski prohod, Sofia, SF, Bulgaria', 1, 7.79, 'completed', '2026-02-16 17:21:52', '2026-02-16 17:21:54', '2026-02-16 17:22:00', '2026-02-16 17:21:42', '2026-02-16 17:22:00'),
(174, 13, 6, 42.6559931, 23.3549127, 42.6927935, 23.3320719, 6523, 728, 13.33, '3-ти блок, Sofia, SF, Bulgaria', '133 СОУ \"А.С.Пушкин, Sofia, SF, Bulgaria', 1, NULL, 'cancelled', '2026-02-17 09:40:19', NULL, NULL, '2026-02-17 09:40:04', '2026-02-17 09:41:05'),
(175, 13, 6, 42.6529016, 23.3539758, 42.6661610, 23.3668227, 2601, 383, 7.12, '8-ми блок, Sofia, SF, Bulgaria', 'Сервиз Гуми, Sofia, SF, Bulgaria', 1, 7.12, 'completed', '2026-02-17 09:41:25', '2026-02-17 09:41:31', '2026-02-17 09:42:57', '2026-02-17 09:41:15', '2026-02-17 09:42:57'),
(177, 13, 6, 42.6709509, 23.2879257, 42.7234343, 23.3263779, NULL, NULL, NULL, '38 Родопски извор, Sofia, SF, Bulgaria', 'Борса за хранителни стоки, Sofia, SF, Bulgaria', 1, 5.00, 'completed', '2026-02-18 16:05:10', '2026-02-18 16:05:14', '2026-02-18 16:05:22', '2026-02-18 16:04:56', '2026-02-18 16:05:22'),
(178, 13, 6, 42.6721262, 23.2887626, 42.6781447, 23.2907796, 894, 161, 5.00, '3 Родопски извор, Sofia, SF, Bulgaria', '38 Нишава, Sofia, SF, Bulgaria', 1, 5.00, 'completed', '2026-02-20 18:20:08', '2026-02-20 18:20:23', '2026-02-20 18:21:29', '2026-02-20 18:20:01', '2026-02-20 18:21:29'),
(179, 13, 6, 42.6721893, 23.2888591, 42.6852747, 23.2985687, 2643, 412, 7.42, 'Болина, Sofia, SF, Bulgaria', 'Ami Bue, Sofia, SF, Bulgaria', 1, 7.42, 'completed', '2026-02-20 18:23:17', '2026-02-20 18:23:34', '2026-02-20 18:23:35', '2026-02-20 18:23:10', '2026-02-20 18:23:35'),
(180, 13, 6, 42.6719448, 23.2885158, 42.6860949, 23.2957363, 2572, 451, 7.59, '3 Родопски извор, Sofia, SF, Bulgaria', '5 Мъглен, Sofia, SF, Bulgaria', 1, NULL, 'cancelled', '2026-02-20 18:36:01', NULL, NULL, '2026-02-20 18:35:53', '2026-02-20 18:38:17'),
(181, 13, 6, 42.6721183, 23.2887518, 42.6788073, 23.2917452, 1020, 179, 5.00, '3 Родопски извор, Sofia, SF, Bulgaria', 'р. Боянска, Sofia, SF, Bulgaria', 1, 5.00, 'completed', '2026-02-20 23:34:56', '2026-02-20 23:35:00', '2026-02-20 23:35:02', '2026-02-20 23:34:47', '2026-02-20 23:35:02'),
(182, 13, 6, 42.6720947, 23.2887733, 42.7257042, 23.2965088, 8601, 1236, 18.07, '3 Родопски извор, Sofia, SF, Bulgaria', 'бл.119 ж.к. Надежда 1, Sofia, SF, Bulgaria', 1, 18.07, 'completed', '2026-02-20 23:37:38', '2026-02-20 23:37:40', '2026-02-20 23:37:43', '2026-02-20 23:37:33', '2026-02-20 23:37:43'),
(183, 13, 6, 42.6720789, 23.2886660, 42.6771193, 23.2893848, 769, 155, 5.00, '3 Родопски извор, Sofia, SF, Bulgaria', '33 Хайдушка гора, Sofia, SF, Bulgaria', 1, 5.00, 'completed', '2026-02-20 23:38:52', '2026-02-20 23:38:53', '2026-02-20 23:38:54', '2026-02-20 23:38:46', '2026-02-20 23:38:54'),
(184, 13, 6, 42.6716135, 23.2882261, 42.6988382, 23.2899857, 4387, 673, 10.51, '30 Родопски извор, Sofia, SF, Bulgaria', 'Aleko Turandzha, Sofia, SF, Bulgaria', 1, NULL, 'cancelled', '2026-02-20 23:52:30', NULL, NULL, '2026-02-20 23:52:19', '2026-02-20 23:52:36'),
(185, 13, 6, 42.6716135, 23.2882690, 42.6914575, 23.3000278, 3637, 517, 9.11, '30 Родопски извор, Sofia, SF, Bulgaria', 'Балкан САСТ, Sofia, SF, Bulgaria', 1, 9.11, 'completed', '2026-02-20 23:53:23', '2026-02-20 23:53:31', '2026-02-20 23:53:40', '2026-02-20 23:53:18', '2026-02-20 23:53:40'),
(186, 13, NULL, 42.6950533, 23.3579636, 42.7275958, 23.3368492, NULL, NULL, NULL, '18 Георги Пеячевич, Sofia, SF, Bulgaria', '251, Sofia, SF, Bulgaria', 5, NULL, 'cancelled', NULL, NULL, NULL, '2026-02-21 00:15:50', '2026-02-21 00:16:11'),
(187, 13, 6, 42.7075426, 23.3589077, 42.7200923, 23.3360767, NULL, NULL, NULL, 'ул. Проф. Иван Шишманов, Sofia, SF, Bulgaria', 'Dimitar Bochukov, Sofia, SF, Bulgaria', 4, NULL, 'cancelled', '2026-02-21 00:16:28', NULL, NULL, '2026-02-21 00:16:17', '2026-02-21 00:16:59'),
(188, 13, 6, 42.6708246, 23.2868958, 42.7069118, 23.3109283, NULL, NULL, NULL, '42 Родопски извор, Sofia, SF, Bulgaria', '36-34 Враня, Sofia, SF, Bulgaria', 1, NULL, 'cancelled', '2026-02-21 00:34:12', NULL, NULL, '2026-02-21 00:34:05', '2026-02-21 00:34:33'),
(189, 13, 7, 42.6721341, 23.2887626, 42.6859687, 23.2952213, 2522, 439, 3.50, '3 Родопски извор, Sofia, SF, Bulgaria', '8 Боговец, Sofia, SF, Bulgaria', 1, 3.50, 'completed', '2026-02-21 14:11:48', '2026-02-21 14:11:49', '2026-02-21 14:11:52', '2026-02-21 14:11:20', '2026-02-21 14:11:52');

-- --------------------------------------------------------

--
-- Структура на таблица `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL COMMENT 'Primary key, auto increment',
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Full name of user',
  `email` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'User for login',
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Hashed password',
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL COMMENT 'Optional ( phone number ) ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Auto timestamp',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Auto update timestamp',
  `role` enum('user','driver','admin') NOT NULL DEFAULT 'user' COMMENT 'Role where the person chooses to be a rider or user',
  `suspended` tinyint(1) NOT NULL DEFAULT 0,
  `preferred_payment` enum('cash','online') NOT NULL DEFAULT 'cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `created_at`, `updated_at`, `role`, `suspended`, `preferred_payment`) VALUES
(12, 'Brian Kenned', 'bk@gmail.com', '$2y$12$uaCgxCyKfNZP2vkfNzfIreCSkkiGcy74nmXypvH/WvG52rIXgfr6e', '0888888888', '2026-01-24 14:17:06', '2026-02-20 18:00:58', 'driver', 0, 'online'),
(13, 'Yassen Dimchov', 'yassen.dimchov@gmail.com', '$2y$12$f5DSqMti7mO.uHBLbCeT6eaeReKqrkqAF1g.kton0w3Tc3A.0B90C', '0889917046', '2026-01-24 15:45:43', '2026-02-21 00:39:01', 'user', 0, 'cash'),
(14, 'Admin Admin', 'Admin@gmail.com', '$2y$12$0r.4dj.bfnXnclEc4JbU8OWj19UWvnuZLVJC4rizABUrnFOXl4oR2', NULL, '2026-02-18 20:56:31', '2026-02-18 20:56:31', 'admin', 0, 'cash'),
(16, 'Fortnite Duos', 'duosfortnite90@gmail.com', '$2y$12$H8E7zdkQduZ/b81WBjFNuu2DpZIdoUKa0ebQkbaMPCwMOjjKsgPMm', NULL, '2026-02-20 20:41:21', '2026-02-21 14:12:27', 'user', 0, 'cash');

--
-- Indexes for dumped tables
--

--
-- Индекси за таблица `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id_unique` (`user_id`);

--
-- Индекси за таблица `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Индекси за таблица `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ride_id_unique` (`ride_id`);

--
-- Индекси за таблица `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Индекси за таблица `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ride_id_unique` (`ride_id`),
  ADD KEY `user_id_index` (`user_id`),
  ADD KEY `driver_id_index` (`driver_id`);

--
-- Индекси за таблица `rides`
--
ALTER TABLE `rides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `driver_id_index` (`driver_id`),
  ADD KEY `user_id_index` (`user_id`);

--
-- Индекси за таблица `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key, auto increment', AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key, auto increment, unique payment id', AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=164;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key, unique review id', AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `rides`
--
ALTER TABLE `rides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key, auto increment,  unique ride id\r\n\r\n', AUTO_INCREMENT=190;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary key, auto increment', AUTO_INCREMENT=17;

--
-- Ограничения за дъмпнати таблици
--

--
-- Ограничения за таблица `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `fk_drivers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_ride` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_ride` FOREIGN KEY (`ride_id`) REFERENCES `rides` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения за таблица `rides`
--
ALTER TABLE `rides`
  ADD CONSTRAINT `fk_rides_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rides_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
