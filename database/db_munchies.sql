-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 15, 2026 at 06:14 AM
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
-- Database: `db_munchies`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment`
--

CREATE TABLE `tbl_payment` (
  `payment_code` tinyint(4) NOT NULL,
  `payment_method` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_payment`
--

INSERT INTO `tbl_payment` (`payment_code`, `payment_method`) VALUES
(1, 'Cash'),
(2, 'GCash'),
(3, 'Credit Card'),
(4, 'Debit Card'),
(5, 'PayPal');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_product`
--

CREATE TABLE `tbl_product` (
  `product_code` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_desc` text NOT NULL,
  `product_image` varchar(500) NOT NULL,
  `price` decimal(11,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tbl_product`
--

INSERT INTO `tbl_product` (`product_code`, `product_name`, `product_desc`, `product_image`, `price`, `quantity`) VALUES
(1, 'Blueberry Muffin', 'A golden-domed treat featuring a tender, buttery crumb and pockets of sweet, bursting blueberries.', 'static/assets/blueberry_muffin.png', 99.00, 50),
(2, 'Chocolate Chip Muffin', 'A rich, buttery muffin packed with semi-sweet chocolate chips that create melty pockets of cocoa throughout.', 'static/assets/chocolatechip_muffin.jpg', 95.00, 50),
(3, 'Banana Muffin', 'An aromatic and incredibly moist treat made from ripened bananas that provide a dense texture.', 'static/assets/banana_muffin.jpg', 69.00, 50),
(4, 'Apple with Oatmeal Muffin', 'A hearty, wholesome muffin blending chewy oats with tender chunks of tart apple for a textured breakfast.', 'static/assets/appleoatmeal_muffins.jpg', 89.00, 50),
(5, 'Cinnamon Muffin', 'A light and airy spiced muffin featuring a warm cinnamon-sugar swirl and a crisp, fragrant topping.', 'static/assets/cinnamon_muffin.jpg', 75.00, 50),
(6, 'Cappuccino Muffin', 'A bold, sophisticated pastry infused with espresso notes that offer a deep roasted aroma.', 'static/assets/cappuccino_muffin.jpg', 75.00, 50);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sales`
--

CREATE TABLE `tbl_sales` (
  `sale_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_code` tinyint(4) NOT NULL,
  `sale_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_sales_item`
--

CREATE TABLE `tbl_sales_item` (
  `sale_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_code` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `user_id` int(11) NOT NULL,
  `lname` varchar(50) NOT NULL,
  `fname` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_payment`
--
ALTER TABLE `tbl_payment`
  ADD PRIMARY KEY (`payment_code`);

--
-- Indexes for table `tbl_product`
--
ALTER TABLE `tbl_product`
  ADD PRIMARY KEY (`product_code`);

--
-- Indexes for table `tbl_sales`
--
ALTER TABLE `tbl_sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `payment_code` (`payment_code`);

--
-- Indexes for table `tbl_sales_item`
--
ALTER TABLE `tbl_sales_item`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_code` (`product_code`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `user_id_UNIQUE` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_product`
--
ALTER TABLE `tbl_product`
  MODIFY `product_code` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_sales`
--
ALTER TABLE `tbl_sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_sales_item`
--
ALTER TABLE `tbl_sales_item`
  MODIFY `sale_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbl_sales`
--
ALTER TABLE `tbl_sales`
  ADD CONSTRAINT `tbl_sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`user_id`),
  ADD CONSTRAINT `tbl_sales_ibfk_2` FOREIGN KEY (`payment_code`) REFERENCES `tbl_payment` (`payment_code`);

--
-- Constraints for table `tbl_sales_item`
--
ALTER TABLE `tbl_sales_item`
  ADD CONSTRAINT `tbl_sales_item_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `tbl_sales` (`sale_id`),
  ADD CONSTRAINT `tbl_sales_item_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `tbl_product` (`product_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
