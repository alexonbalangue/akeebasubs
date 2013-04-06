CREATE TABLE IF NOT EXISTS `#__akeebasubs_blockrules` (
	`akeebasubs_blockrule_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`username` VARCHAR(255) NOT NULL DEFAULT '',
	`name` VARCHAR(255) NOT NULL DEFAULT '',
	`email` VARCHAR(255) NOT NULL DEFAULT '',
	`iprange` VARCHAR(255) NOT NULL DEFAULT '',

	`enabled` tinyint(4) NOT NULL DEFAULT '1',
	`created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`created_by` bigint(20) NOT NULL DEFAULT '0',
	`modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modifed_by` bigint(20) NOT NULL DEFAULT '0',
	`locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`locked_by` bigint(20) NOT NULL DEFAULT '0',

	PRIMARY KEY (`akeebasubs_blockrule_id`)	
) DEFAULT CHARSET=utf8;