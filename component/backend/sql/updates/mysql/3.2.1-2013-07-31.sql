ALTER TABLE `#__akeebasubs_levels` ADD COLUMN `renew_url` VARCHAR(2048) NULL DEFAULT '' AFTER `payment_plugins`;
ALTER TABLE `#__akeebasubs_levels` ADD COLUMN `content_url` VARCHAR(2048) NULL DEFAULT '' AFTER `renew_url`;
