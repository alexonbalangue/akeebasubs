ALTER TABLE `#__akeebasubs_invoices` ADD COLUMN
	`extension` VARCHAR(50) NOT NULL DEFAULT "misc" AFTER `akeebasubs_subscription_id`;

ALTER TABLE `#__akeebasubs_invoices` ADD COLUMN
	`display_number` VARCHAR(255) NULL AFTER `invoice_no`;