CREATE TABLE IF NOT EXISTS `#__akeebasubs_pf_projects` (
	`users_id` bigint(20) unsigned NOT NULL,
	`akeebasubs_level_id` bigint(20) unsigned NOT NULL,
	`pf_projects_id` bigint(20) unsigned NOT NULL,
	PRIMARY KEY (`users_id`, `akeebasubs_level_id`)
) DEFAULT CHARSET=utf8;