CREATE TABLE IF NOT EXISTS `#__akeebasubs_emailtemplates` (
  `akeebasubs_emailtemplate_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL DEFAULT '',
  `subscription_level_id` bigint(20) DEFAULT '0',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text,
  `language` varchar(10) NOT NULL DEFAULT '*',
  `enabled` tinyint(4) NOT NULL DEFAULT '1',
  `ordering` bigint(20) NOT NULL DEFAULT '0',
  `created_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created_by` bigint(20) NOT NULL DEFAULT '0',
  `modified_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modifed_by` bigint(20) NOT NULL DEFAULT '0',
  `locked_on` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `locked_by` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`akeebasubs_emailtemplate_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;