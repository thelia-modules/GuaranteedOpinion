SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_rating
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `guaranteed_opinion_product_rating`;

CREATE TABLE `guaranteed_opinion_product_rating`
(
    `product_id` INTEGER NOT NULL,
    `total` INTEGER,
    `average` VARCHAR(255),
    PRIMARY KEY (`product_id`)
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;