-- Create premium_subscriptions_json table for elderly management
CREATE TABLE IF NOT EXISTS `premium_subscriptions_json` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `elderly_keys` JSON DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  FOREIGN KEY (`user_id`) REFERENCES `user` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create premium_key column in the table (for the premium key display feature)
ALTER TABLE `premium_subscriptions_json` 
ADD COLUMN `premium_key` VARCHAR(255) DEFAULT NULL AFTER `elderly_keys`;

-- Add index for premium_key
ALTER TABLE `premium_subscriptions_json` 
ADD INDEX `premium_key` (`premium_key`);
