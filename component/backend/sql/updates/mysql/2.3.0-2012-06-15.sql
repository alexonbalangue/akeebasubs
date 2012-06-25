CREATE TABLE IF NOT EXISTS `#__akeebasubs_levelgroups` (
	`akeebasubs_levelgroup_id` bigint(20) NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(255) NOT NULL,

	`enabled` tinyint(1) NOT NULL DEFAULT '1',
	`created_on` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) NOT NULL DEFAULT 0,
	`modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT 0,
	`locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`locked_by` int(11) NOT NULL DEFAULT 0,

	PRIMARY KEY (`akeebasubs_levelgroup_id`)
) DEFAULT CHARSET=utf8;

ALTER TABLE `#__akeebasubs_levels` ADD COLUMN `akeebasubs_levelgroup_id` BIGINT(20) UNSIGNED NULL AFTER `recurring`;