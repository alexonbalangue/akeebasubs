ALTER TABLE `#__akeebasubs_invoicetemplates`
	ADD COLUMN `isbusiness` TINYINT(3) NOT NULL DEFAULT -1 AFTER `levels`,
	ADD COLUMN `country` VARCHAR(3) NOT NULL DEFAULT '' AFTER `levels`,
	ADD COLUMN `number_reset` BIGINT(20) NOT NULL DEFAULT 0 AFTER `levels`;

ALTER TABLE `#__akeebasubs_invoicetemplates`
	ADD COLUMN `globalnumbering` TINYINT(3) NOT NULL DEFAULT 1 AFTER `levels`,
	ADD COLUMN `globalformat` TINYINT(3) NOT NULL DEFAULT 1 AFTER `levels`,
	ADD COLUMN `format` VARCHAR(100) NOT NULL DEFAULT '' AFTER `country`;

ALTER TABLE `#__akeebasubs_invoices`
	ADD COLUMN `akeebasubs_invoicetemplate_id` BIGINT(20) NOT NULL DEFAULT 0 AFTER `extension`;