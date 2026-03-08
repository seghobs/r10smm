DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `content` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_turkish_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `api_providers`;
CREATE TABLE `api_providers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_turkish_ci NOT NULL,
  `api_key` varchar(500) COLLATE utf8mb4_turkish_ci NOT NULL,
  `balance` decimal(10,4) DEFAULT '0.0000',
  `status` enum('active','inactive') COLLATE utf8mb4_turkish_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `balance_transactions`;
CREATE TABLE `balance_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('admin_add','payment','order','refund') COLLATE utf8mb4_turkish_ci NOT NULL,
  `note` text COLLATE utf8mb4_turkish_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `token` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `live_support_messages`;
CREATE TABLE `live_support_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `admin_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_live_support_user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE `login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_turkish_ci,
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `success` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `message` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `type` enum('payment','order','ticket','system','balance') COLLATE utf8mb4_turkish_ci DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_notifications_user_id` (`user_id`),
  KEY `idx_notifications_is_read` (`is_read`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_id` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `api_order_id` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `api_service_id` int DEFAULT NULL,
  `service_id` int DEFAULT NULL,
  `service_name` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `link` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `profit_try` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','processing','inprogress','completed','refunded','cancelled','partial') COLLATE utf8mb4_turkish_ci DEFAULT 'pending',
  `start_count` int DEFAULT '0',
  `remains` int DEFAULT '0',
  `admin_note` text COLLATE utf8mb4_turkish_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_orders_user_id` (`user_id`),
  KEY `idx_orders_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `payment_id` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `transaction_id` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `method` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded','rejected','cancelled') COLLATE utf8mb4_turkish_ci DEFAULT 'pending',
  `proof_image` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `reject_reason` text COLLATE utf8mb4_turkish_ci,
  `approved_at` datetime DEFAULT NULL,
  `admin_note` text COLLATE utf8mb4_turkish_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payments_user_id` (`user_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `referrals`;
CREATE TABLE `referrals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `referrer_id` int DEFAULT NULL,
  `referred_email` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `bonus_amount` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','completed') COLLATE utf8mb4_turkish_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `referrer_id` (`referrer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `service_id` int DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `rate_per_1000` decimal(10,4) DEFAULT NULL,
  `price_per_1000` decimal(10,2) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `min` int DEFAULT NULL,
  `max` int DEFAULT NULL,
  `min_quantity` int DEFAULT '1',
  `max_quantity` int DEFAULT '999999',
  `description` text COLLATE utf8mb4_turkish_ci,
  `api_service_id` int DEFAULT NULL,
  `api_provider` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `provider_id` int DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_turkish_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1890 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `value` text COLLATE utf8mb4_turkish_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `settings` (`id`, `setting_key`, `value`, `updated_at`) VALUES ('1', 'exchange_rate', '1.0', '2026-03-08 15:17:15') ON DUPLICATE KEY UPDATE value=VALUES(value);
INSERT INTO `settings` (`id`, `setting_key`, `value`, `updated_at`) VALUES ('2', 'paytr_merchant_id', '123456789', '2026-03-08 16:08:31') ON DUPLICATE KEY UPDATE value=VALUES(value);
INSERT INTO `settings` (`id`, `setting_key`, `value`, `updated_at`) VALUES ('3', 'paytr_merchant_key', '123456789', '2026-03-08 16:08:31') ON DUPLICATE KEY UPDATE value=VALUES(value);
INSERT INTO `settings` (`id`, `setting_key`, `value`, `updated_at`) VALUES ('4', 'paytr_merchant_salt', '123456789', '2026-03-08 16:08:31') ON DUPLICATE KEY UPDATE value=VALUES(value);
INSERT INTO `settings` (`id`, `setting_key`, `value`, `updated_at`) VALUES ('5', 'site_logo_text', 'R10 Smm', '2026-03-08 17:33:03') ON DUPLICATE KEY UPDATE value=VALUES(value);
INSERT INTO `settings` (`id`, `setting_key`, `value`, `updated_at`) VALUES ('6', 'site_logo_image', '', '2026-03-08 17:29:15') ON DUPLICATE KEY UPDATE value=VALUES(value);

DROP TABLE IF EXISTS `support_messages`;
CREATE TABLE `support_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `admin_id` int DEFAULT NULL,
  `message` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `message` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_turkish_ci DEFAULT 'medium',
  `status` enum('open','in_progress','answered','closed','resolved') COLLATE utf8mb4_turkish_ci DEFAULT 'open',
  `admin_reply` text COLLATE utf8mb4_turkish_ci,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_id` (`ticket_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE `ticket_replies` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text COLLATE utf8mb4_turkish_ci NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE `user_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_turkish_ci,
  `related_id` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT '0.00',
  `api_key` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `status` enum('active','pending','suspended') COLLATE utf8mb4_turkish_ci DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT '0',
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `user_role` enum('user','reseller','admin') COLLATE utf8mb4_turkish_ci DEFAULT 'user',
  `referral_code` varchar(50) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `referred_by` int DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `kvkk_consent` tinyint(1) DEFAULT '0',
  `terms_accepted` tinyint(1) DEFAULT '0',
  `privacy_accepted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `referral_code` (`referral_code`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

