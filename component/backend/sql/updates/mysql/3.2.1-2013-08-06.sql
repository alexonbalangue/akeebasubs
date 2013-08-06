CREATE TABLE IF NOT EXISTS `#__akeebasubs_couponsapis` (
  `akeebasubs_couponsapi_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(30) NOT NULL,
  `key` varchar(32) NOT NULL,
  `password` varchar(15) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`akeebasubs_couponsapi_id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `#__akeebasubs_coupons` ADD `akeebasubs_couponsapi_id` INT NOT NULL AFTER `akeebasubs_coupon_id`;
