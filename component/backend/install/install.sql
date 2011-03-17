CREATE TABLE IF NOT EXISTS `#__akeebasubs_levels` (
	`akeebasubs_level_id` bigint(20) unsigned NOT NULL auto_increment,
	`title` varchar(255) NOT NULL,
	`slug` varchar(255) NOT NULL,
	`image` varchar(25) NOT NULL,
	`description` text COMMENT '@Filter("html, tidy")',
	`duration` INT(10) UNSIGNED NOT NULL DEFAULT 365,
	`price` FLOAT NOT NULL,
	`ordertext` text COMMENT '@Filter("html, tidy")',
	`canceltext` text COMMENT '@Filter("html, tidy")',
	
	`enabled` tinyint(1) NOT NULL DEFAULT '1',
	`ordering` bigint(20) unsigned NOT NULL,
	`created_on` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) NOT NULL DEFAULT 0,
	`modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT 0,
	`locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`locked_by` int(11) NOT NULL DEFAULT 0,
	`uuid` char(36) NOT NULL,
  PRIMARY KEY ( `akeebasubs_level_id` ),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__akeebasubs_subscriptions` (
	`akeebasubs_subscription_id` bigint(20) unsigned NOT NULL auto_increment,
	`user_id` bigint(20) unsigned NOT NULL,
	`akeebasubs_level_id` bigint(20) unsigned NOT NULL,
	`publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`notes` TEXT,
	`enabled` tinyint(1) NOT NULL DEFAULT '1',
	
	`processor` varchar(255) NOT NULL,
	`processor_key` varchar(255) NOT NULL,
	`state` ENUM('N','P','C','X') not null default 'X',
	`net_amount` FLOAT NOT NULL,
	`tax_amount` FLOAT NOT NULL,
	`gross_amount` FLOAT NOT NULL,
	`created_on` datetime NOT NULL default '0000-00-00 00:00:00',
	`params` TEXT,

	`contact_flag` tinyint(1) NOT NULL DEFAULT '1',
	`first_contact` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`second_contact` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY ( `akeebasubs_subscription_id` )
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__akeebasubs_taxrules` (
	`akeebasubs_taxrule_id` bigint(20) unsigned NOT NULL auto_increment,
	`country` CHAR(2) NOT NULL DEFAULT 'US',
	`state` VARCHAR(100) NULL,
	`city` VARCHAR(100) NULL,
	`vies` TINYINT(1) NOT NULL DEFAULT '1',
	`taxrate` FLOAT NOT NULL DEFAULT '23.0',
	
	`enabled` tinyint(1) NOT NULL DEFAULT '1',
	`ordering` bigint(20) unsigned NOT NULL,
	`created_on` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) NOT NULL DEFAULT 0,
	`modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT 0,
	`locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`locked_by` int(11) NOT NULL DEFAULT 0,
	PRIMARY KEY ( `akeebasubs_taxrule_id` )
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__akeebasubs_coupons` (
	`akeebasubs_coupon_id` bigint(20) unsigned NOT NULL auto_increment,
	`title` varchar(255) NOT NULL,
	`coupon` varchar(255) NOT NULL,
	`publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`subscriptions` VARCHAR(255) NULL,
	`user` int(10) DEFAULT NULL,
	`params` TEXT,
	`hitslimit` BIGINT(20) unsigned NULL,
	`type` ENUM('value','percent') NOT NULL DEFAULT 'value',
	`value` FLOAT NOT NULL DEFAULT 0.0,
	
	`enabled` tinyint(1) NOT NULL DEFAULT '1',
	`ordering` bigint(20) unsigned NOT NULL,
	`created_on` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) NOT NULL DEFAULT 0,
	`modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT 0,
	`locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`locked_by` int(11) NOT NULL DEFAULT 0,
	`hits` BIGINT(20) unsigned NOT NULL default 0,
	PRIMARY KEY ( `akeebasubs_coupon_id` ),
	UNIQUE KEY `coupon` (`coupon`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__akeebasubs_upgrades` (
	`akeebasubs_upgrade_id` bigint(20) unsigned NOT NULL auto_increment,
	`title` varchar(255) NOT NULL,
	`from_id` bigint(20) unsigned NOT NULL,
	`to_id` bigint(20) unsigned NOT NULL,
	`min_presence` int(5) unsigned NOT NULL,
	`max_presence` int(5) unsigned NOT NULL,
	`type` ENUM('value','percent') NOT NULL DEFAULT 'value',
	`value` FLOAT NOT NULL DEFAULT '0.0',
	
	`enabled` tinyint(1) NOT NULL DEFAULT '1',
	`ordering` bigint(20) unsigned NOT NULL,
	`created_on` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) NOT NULL DEFAULT 0,
	`modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT 0,
	`locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`locked_by` int(11) NOT NULL DEFAULT 0,
	`hits` BIGINT(20) unsigned NOT NULL default '0',
	PRIMARY KEY ( `akeebasubs_upgrade_id` )
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__akeebasubs_users` (
	`akeebasubs_user_id` bigint(20) unsigned NOT NULL auto_increment,
	`user_id` bigint(20) unsigned NOT NULL,
	`isbusiness` TINYINT(1) NOT NULL DEFAULT '0',
	`businessname` VARCHAR(255) NULL,
	`occupation` VARCHAR(255) NULL,
	`vatnumber` VARCHAR(255) NULL,
	`viesregistered` TINYINT(1) NOT NULL DEFAULT '0',
	`taxauthority` VARCHAR(255) NULL,
	`address1` VARCHAR(255) NULL,
	`address2` VARCHAR(255) NULL,
	`city` VARCHAR(255) NULL,
	`state` VARCHAR(255) NULL,
	`zip` VARCHAR(255) NULL,
	`country` CHAR(2) NOT NULL DEFAULT 'XX',
	`params` TEXT,
	`notes` TEXT,	
	PRIMARY KEY ( `akeebasubs_user_id` ),
	UNIQUE KEY `joomlauser` (`user_id`)
)  ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `#__akeebasubs_configurations` (
	`akeebasubs_configuration_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`params` TEXT COMMENT '@Filter("json")',
	PRIMARY KEY (`akeebasubs_configuration_id`)
)  ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE OR REPLACE VIEW `#__akeebasubs_view_subscriptions` AS
SELECT
   `s`.*,
   `l`.`title` AS `title`,
   `l`.`image` as `image`,
   `u`.`name` AS `name`,
   `u`.`username` AS `username`,
   `u`.`email` AS `email`,
   `u`.`block` AS `block`,
   `a`.`isbusiness` AS `isbusiness`,
   `a`.`businessname` AS `businessname`,
   `a`.`occupation` AS `occupation`,
   `a`.`vatnumber` AS `vatnumber`,
   `a`.`viesregistered` AS `viesregistered`,
   `a`.`taxauthority` AS `taxauthority`,
   `a`.`address1` AS `address1`,
   `a`.`address2` AS `address2`,
   `a`.`city` AS `city`,
   `a`.`state` AS `geostate`,
   `a`.`zip` AS `zip`,
   `a`.`country` AS `country`,
   `a`.`params` AS `userparams`,
   `a`.`notes` AS `usernotes`
FROM
	`#__akeebasubs_subscriptions` `s`
	inner join `#__akeebasubs_levels` `l` ON(`l`.`akeebasubs_level_id` = `s`.`akeebasubs_level_id`)
	inner join `#__users` `u` on(`u`.`id` = `s`.`user_id`)
	left outer join `#__akeebasubs_users` `a` on(`a`.`user_id` = `s`.`user_id`);