SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_rating
-- ---------------------------------------------------------------------

ALTER TABLE `guaranteed_opinion_product_rating` DROP PRIMARY KEY;

ALTER TABLE `guaranteed_opinion_product_rating` MODIFY `locale` VARCHAR(5) NOT NULL;

ALTER TABLE `guaranteed_opinion_product_rating` ADD PRIMARY KEY (`product_id`, `locale`);

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;