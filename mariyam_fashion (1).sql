-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2025 at 07:13 PM
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
-- Database: `mariyam_fashion`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(5, 'Accessories'),
(6, 'hoij'),
(1, 'mens'),
(2, 'Shirts'),
(4, 'Shoes'),
(7, 'Three piece'),
(3, 'Trousers');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `sender` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `session_id`, `sender`, `message`, `created_at`) VALUES
(17, 9, 'user', 'hi', '2025-10-03 12:34:55'),
(18, 9, 'admin', 'hello', '2025-10-03 12:39:36');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `user_name`, `user_email`, `started_at`) VALUES
(9, 'jahid', 'mdjhk300@gmail.com', '2025-10-03 12:34:52');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_name`, `user_email`, `message`, `admin_reply`, `created_at`) VALUES
(1, 'JAHID KHAN', 'mdjhk19@gmail.com', 'can i chat you\r\n', NULL, '2025-10-02 07:27:01'),
(2, 'JAHID KHAN', 'mdjhk19@gmail.com', 'can i chat you\r\n', NULL, '2025-10-02 07:36:37'),
(3, 'JAHID KHAN', 'mdjhk19@gmail.com', 'hello', NULL, '2025-10-02 07:36:58');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_address` text NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `shipping_status` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `customer_address`, `total_amount`, `shipping_fee`, `payment_method`, `note`, `created_at`, `status`, `shipping_status`) VALUES
(1, NULL, 'JAHID KHAN', '01957288638', 'mdjhk300@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 179.99, 120.00, 'COD', '', '2025-09-17 00:31:49', 0, 0),
(2, NULL, 'JAHID KHAN', '01957288638', 'mdjhk300@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 179.99, 120.00, 'COD', '', '2025-09-17 00:32:12', 0, 0),
(3, NULL, 'JAHID KHAN', '01957288638', 'mdjhk300@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 129.99, 70.00, 'Online Payment', '', '2025-09-17 00:37:10', 0, 0),
(4, NULL, 'JAHID KHAN', '01957288638', 'mdjhk19@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 5524.00, 70.00, 'Online Payment', '', '2025-09-17 00:37:47', 0, 0),
(5, NULL, 'JAHID KHAN', '01957288638', 'mdjhk19@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 5524.00, 70.00, 'Online Payment', '', '2025-09-17 00:40:10', 1, 0),
(6, NULL, 'sakib', '019875254554', 'mdjhk19@gmail.com', 'gsgsgsgsgsgsgsgsgs', 1008.00, 120.00, 'Online Payment', '', '2025-10-01 11:24:39', 0, 0),
(7, NULL, 'sakib', '019875254554', 'mdjhk19@gmail.com', '2fghdghdtghdh', 71.00, 70.00, 'Online Payment', '', '2025-10-01 11:25:37', 0, 0),
(8, NULL, 'sakib', '019875254554', 'mdjhk19@gmail.com', 'hjhfgjfghjgfjghjgjghj', 121.00, 120.00, 'Online Payment', '', '2025-10-01 11:26:33', 0, 0),
(9, NULL, 'sakib', '019875254554', 'mdjhk19@gmail.com', 'fghjfgjfjfgj', 71.00, 70.00, 'Online Payment', NULL, '2025-10-01 11:30:49', 0, 0),
(10, NULL, 'sakib', '019875254554', 'mdjhk19@gmail.com', 'ghgdhdhd', 71.00, 70.00, 'Online Payment', 'dfhdfhdfh', '2025-10-01 11:35:56', 0, 0),
(11, NULL, 'jp0', '019875254554', 'mdjhk19@gmail.com', 'etetge', 3120.00, 120.00, 'Online Payment', 'dfdf', '2025-10-01 11:40:11', 0, 0),
(12, NULL, 'JAHID KHAN', '01957288638', 'mdjhk19@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 1890.00, 70.00, 'Online Payment', '', '2025-10-07 23:06:16', 0, 0),
(13, NULL, 'JAHID KHAN', '01957288638', 'mdjhk19@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 90.00, 70.00, 'Online Payment', '', '2025-10-07 23:15:46', 0, 0),
(14, NULL, 'JAHID KHAN', '01957288638', 'mdjhk19@gmail.com', 'Dhaka,Gazipur,Boardbazar,National university,\r\nsouth khailkur,38no woard,sohid siddik road, holding no:446', 90.00, 70.00, 'Online Payment', '', '2025-10-07 23:22:07', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 2, 1, 59.99),
(2, 2, 2, 1, 59.99),
(3, 3, 2, 1, 59.99),
(4, 4, 6, 1, 5454.00),
(5, 5, 6, 1, 5454.00),
(6, 6, 9, 1, 888.00),
(7, 7, 14, 1, 1.00),
(8, 8, 14, 1, 1.00),
(9, 9, 14, 1, 1.00),
(10, 10, 14, 1, 1.00),
(11, 11, 8, 1, 3000.00),
(12, 12, 16, 1, 20.00),
(13, 12, 12, 1, 1800.00),
(14, 13, 16, 1, 20.00),
(15, 14, 16, 1, 20.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image_url`, `created_at`, `category_id`) VALUES
(2, 'Denim Jacket', 'Classic blue denim jacket', 59.99, 'https://via.placeholder.com/200', '2025-09-12 06:47:13', NULL),
(3, 'Leather Handbag', 'Stylish genuine leather handbag', 89.99, 'https://via.placeholder.com/200', '2025-09-12 06:47:13', NULL),
(4, 'Sneakers', 'Comfortable white sneakers', 49.99, 'https://via.placeholder.com/200', '2025-09-12 06:47:13', NULL),
(5, 'Sunglasses', 'UV-protected fashion sunglasses', 19.99, 'https://via.placeholder.com/200', '2025-09-12 06:47:13', NULL),
(6, 'fgfg', 'fgsgs', 10.00, 'upload/400.jpg', '2025-09-12 10:31:15', NULL),
(7, 'jahid', 'hjgfjj', 54454.00, NULL, '2025-09-29 09:02:05', 1),
(8, 'kkglkg', 'fgfgdg', 3000.00, NULL, '2025-09-29 09:09:42', 1),
(10, 'gpgpgf', 'ohijh', 100.00, NULL, '2025-09-29 09:16:04', 1),
(11, 'Blue Shirt', 'Comfortable cotton shirt', 1200.00, NULL, '2025-09-29 09:58:40', 1),
(12, 'Black Trousers', 'Slim fit trousers', 1800.00, NULL, '2025-09-29 09:58:40', 2),
(13, 'Sport Shoes', 'Lightweight running shoes', 2500.00, NULL, '2025-09-29 09:58:40', 3),
(16, 'Three piece', 'The best ever ther piece in the bangladesh', 20.00, NULL, '2025-10-03 11:44:12', 7);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`) VALUES
(1, 7, 'uploads/1759136525_555565656.png'),
(2, 7, 'uploads/1759136525_555565656.jpg'),
(3, 8, 'uploads/1759136982_6565565.jpg'),
(4, 8, 'uploads/1759136982_130837415_2732871253694981_7089881603732746937_n.jpg'),
(7, 10, 'uploads/1759137364_WhatsApp Image 2025-09-24 at 10.14.18 PM.jpeg'),
(8, 10, 'uploads/1759137364_WhatsApp Image 2025-09-24 at 9.21.45 PM (1).jpeg'),
(13, 12, 'uploads/products/1759488770_1.jpg'),
(14, 12, 'uploads/products/1759488770_5d507ee3-5077-4990-add8-8a2a21818fae.jpg'),
(15, 16, 'uploads/1759491852_555.png'),
(16, 16, 'uploads/1759491852_666.png'),
(17, 16, 'uploads/1759491852_777.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `image_url`, `created_at`) VALUES
(11, 'uploads/sliders/1759423130_100.jpg', '2025-10-02 16:38:50'),
(12, 'uploads/sliders/1759423653_b1123526-5a32-42c8-b705-eabf5575d16a.png', '2025-10-02 16:47:33'),
(13, 'uploads/sliders/1759483928_banner1.jpg', '2025-10-03 09:32:08');

-- --------------------------------------------------------

--
-- Table structure for table `upcoming_products`
--

CREATE TABLE `upcoming_products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `expected_date` date DEFAULT NULL,
  `expected_price` decimal(10,2) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upcoming_products`
--

INSERT INTO `upcoming_products` (`id`, `name`, `description`, `photo`, `expected_date`, `expected_price`, `category_id`, `created_at`) VALUES
(2, 'jfiofj0oif', 'fdfdf', 'uploads/upcoming/1759309425_Screenshot 2025-10-01 121512.png', '6452-05-04', 244524.00, 2, '2025-10-01 09:03:45'),
(5, 'njfghj', 'ghjhgj', 'uploads/upcoming/1759309621_Screenshot 2025-09-30 032131.png', '4524-04-05', 4141.00, 3, '2025-10-01 09:07:01'),
(8, 'jahid', 'hgfhdfhf', 'uploads/upcoming/1759494401_5.jpg', '0057-05-07', 75757.00, 7, '2025-10-03 12:03:52'),
(9, 'iio', 'ioi', 'uploads/upcoming/1759494015_jui-cv.png', '0075-07-05', 7.00, 3, '2025-10-03 12:20:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '', '$2y$10$soSPXF9RfaAjAti.qC6Efu0VIavjjiNkqrfVj..X7XrJKI3iJw1Cq', 'admin', '2025-10-01 06:38:18'),
(2, 'user1', 'mdjhk19@gmail.com', '$2y$10$TTvz4HVqMw4VDavFFQ9r/ululrN3gLRVbtwyEuWdwjuAV3cGt1a8m', 'user', '2025-10-03 14:10:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `upcoming_products`
--
ALTER TABLE `upcoming_products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `upcoming_products`
--
ALTER TABLE `upcoming_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
