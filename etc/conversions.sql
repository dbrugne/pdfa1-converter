CREATE TABLE `conversions` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`original_name` VARCHAR(255) NOT NULL,
	`original_size` INT(10) UNSIGNED NOT NULL,
	`original_type` VARCHAR(255) NOT NULL,
	`from_ip` VARCHAR(255) NOT NULL,
	`store_id` VARCHAR(255) NOT NULL,
	`current_name` VARCHAR(255) NOT NULL,
	`created_at` DATETIME NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
AUTO_INCREMENT=24
;
