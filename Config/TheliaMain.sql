
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_review
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `guaranteed_opinion_product_review`;

CREATE TABLE `guaranteed_opinion_product_review`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `product_review_id` VARCHAR(55) NOT NULL,
    `name` VARCHAR(255),
    `rate` VARCHAR(255),
    `review` VARBINARY(10000),
    `review_date` DATETIME,
    `product_id` INTEGER,
    `order_id` VARCHAR(255),
    `order_date` DATETIME,
    `reply` VARCHAR(255),
    `reply_date` DATETIME,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `guaranteed_opinion_product_review_id_unique` (`product_review_id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_site_review
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `guaranteed_opinion_site_review`;

CREATE TABLE `guaranteed_opinion_site_review`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `site_review_id` INTEGER NOT NULL,
    `name` VARCHAR(255),
    `rate` VARCHAR(255),
    `review` VARBINARY(10000),
    `review_date` DATETIME,
    `order_id` VARCHAR(255),
    `order_date` DATETIME,
    `reply` VARCHAR(255),
    `reply_date` DATETIME,
    PRIMARY KEY (`id`,`site_review_id`),
    UNIQUE INDEX `guaranteed_opinion_site_review_id_unique` (`site_review_id`)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_order_queue
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `guaranteed_opinion_order_queue`;

CREATE TABLE `guaranteed_opinion_order_queue`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `order_id` INTEGER NOT NULL,
    `treated_at` DATETIME,
    `status` INTEGER,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
