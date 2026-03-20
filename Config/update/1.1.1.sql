SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_rating
-- ---------------------------------------------------------------------

ALTER TABLE `guaranteed_opinion_product_rating` ADD `locale` VARCHAR(5) AFTER `product_id`;

UPDATE `guaranteed_opinion_product_rating`
SET `locale` = (SELECT `locale` FROM `lang` WHERE `by_default` = 1 LIMIT 1);

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;