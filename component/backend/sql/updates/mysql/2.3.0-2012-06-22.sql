CREATE TABLE IF NOT EXISTS `#__akeebasubs_invoicetemplates` (
	`akeebasubs_invoicetemplate_id` bigint(20) NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(255) NOT NULL,
	`template` TEXT,
	`levels` VARCHAR(255) NOT NULL DEFAULT '0',

	`enabled` tinyint(1) NOT NULL DEFAULT '1',
	`ordering` bigint(20) unsigned NOT NULL,
	`created_on` datetime NOT NULL default '0000-00-00 00:00:00',
	`created_by` int(11) NOT NULL DEFAULT 0,
	`modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modified_by` int(11) NOT NULL DEFAULT 0,
	`locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`locked_by` int(11) NOT NULL DEFAULT 0,

	PRIMARY KEY (`akeebasubs_invoicetemplate_id`)
) DEFAULT CHARSET=utf8;