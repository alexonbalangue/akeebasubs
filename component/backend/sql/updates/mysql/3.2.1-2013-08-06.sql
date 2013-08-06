CREATE TABLE IF NOT EXISTS `#__akeebasubs_apicoupons` (
  `akeebasubs_apicoupon_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(30) NOT NULL,
  `key` varchar(32) NOT NULL,
  `password` varchar(32) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `creation_limit` int(11) NOT NULL,
  `subscriptions` varchar(255) NOT NULL,
  `subscription_limit` int(11) NOT NULL,
  `type` enum('value','percent') NOT NULL DEFAULT 'value',
  `value` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`akeebasubs_apicoupon_id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `#__akeebasubs_coupons` ADD `akeebasubs_apicoupon_id` INT NOT NULL AFTER `akeebasubs_coupon_id`;
