CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'user', /* user, admin */
  `rank` varchar(20) DEFAULT 'bth', /* bth, seller, sellervip */
  `balance` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `type` varchar(50) DEFAULT 'Root & NoRoot',
  `badge` varchar(20) DEFAULT 'NORMAL',
  `price_bth` int(11) NOT NULL,
  `price_seller` int(11) NOT NULL,
  `price_sellervip` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `keys_store` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `key_code` varchar(255) NOT NULL,
  `status` int(11) DEFAULT 0, /* 0: chưa bán, 1: đã bán */
  `sold_to` int(11) DEFAULT NULL,
  `sold_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `history_buy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `key_code` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `buy_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `nap_tien` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `status` int(11) DEFAULT 0, /* 0: pending, 1: success, 2: cancel */
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Thêm Data Mẫu
INSERT INTO `users` (`username`, `password`, `role`, `rank`, `balance`) VALUES 
('admin', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', 'sellervip', 9999999), /* Mật khẩu: admin */
('demo', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 'bth', 100000); /* Mật khẩu: admin */

INSERT INTO `products` (`code`, `name`, `category`, `type`, `badge`, `price_bth`, `price_seller`, `price_sellervip`) VALUES
('ff_normal_1d', 'Gói 1 ngày', 'freefire', 'Root & NoRoot', 'NORMAL', 25000, 20000, 18000),
('ff_normal_7d', 'Gói 7 ngày', 'freefire', 'Root & NoRoot', 'NORMAL', 75000, 60000, 50000),
('ff_normal_30d', 'Gói 30 ngày', 'freefire', 'Root & NoRoot', 'NORMAL', 120000, 100000, 90000),
('lq_vip_1d', 'Gói 1 ngày', 'lienquan', 'Only Root', 'VIP', 30000, 25000, 20000),
('lq_vip_7d', 'Gói 7 ngày', 'lienquan', 'Only Root', 'VIP', 100000, 80000, 70000);
