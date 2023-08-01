-- This is a fix for InnoDB in MySQL >= 4.1.x
-- It "suspends judgement" for fkey relationships until all tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- brevo_newsletter
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `guaranteed_opinion_product_review`;

CREATE TABLE `guaranteed_opinion_product_review`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `product_review_id` VARCHAR(255) NOT NULL,
    `review_id` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255),
    `first_name` VARCHAR(255),
    `last_name` VARCHAR(255),
    `review_date` DATETIME,
    `message` TEXT,
    `rating` FLOAT,
    `product_id` VARCHAR(255),
    `order_id` VARCHAR(255),
    PRIMARY KEY (`id`),
    INDEX `guaranteed_opinion_product_id` (`product_id`),
    INDEX `guaranteed_opinion_order_id` (`order_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `guaranteed_opinion_site_review`;

CREATE TABLE `guaranteed_opinion_site_review`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `review_id` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(255),
    `last_name` VARCHAR(255),
    `review_date` DATETIME,
    `message` TEXT,
    `rating` FLOAT,
    `order_id` VARCHAR(255),
    PRIMARY KEY (`id`),
    INDEX `guaranteed_opinion_order_id` (`order_id`)
) ENGINE=InnoDB;

-- This restores the fkey checks after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
