-- Add private_key column to user table
-- Run this SQL to add the private_key field to the existing viegrand.user table

ALTER TABLE `user` 
ADD COLUMN `private_key` VARCHAR(255) DEFAULT NULL COMMENT 'Private key for user authentication' 
AFTER `phone`;

-- Add index for private_key for faster lookups
ALTER TABLE `user` 
ADD KEY `idx_user_private_key` (`private_key`);

-- Optional: Generate random private keys for existing users
-- Uncomment the following lines if you want to generate keys for existing users

-- UPDATE `user` SET `private_key` = CONCAT(
--     'pk_',
--     SUBSTRING(MD5(CONCAT(userId, userName, email, UNIX_TIMESTAMP())), 1, 32)
-- ) WHERE `private_key` IS NULL;
