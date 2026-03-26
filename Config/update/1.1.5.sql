SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_review
-- ---------------------------------------------------------------------

ALTER TABLE `guaranteed_opinion_product_review` DROP `id`;
ALTER TABLE `guaranteed_opinion_product_review` MODIFY `locale` VARCHAR(5) NOT NULL;
ALTER TABLE `guaranteed_opinion_product_review` DROP INDEX guaranteed_opinion_product_review_id_unique;
ALTER TABLE `guaranteed_opinion_product_review` ADD PRIMARY KEY (`product_review_id`, `locale`);

-- ---------------------------------------------------------------------
-- guaranteed_opinion_site_review
-- ---------------------------------------------------------------------

ALTER TABLE `guaranteed_opinion_site_review` MODIFY COLUMN `id` INTEGER NOT NULL;
ALTER TABLE `guaranteed_opinion_site_review` DROP PRIMARY KEY;
ALTER TABLE `guaranteed_opinion_site_review` DROP `id`;
ALTER TABLE `guaranteed_opinion_site_review` MODIFY `locale` VARCHAR(5) NOT NULL;
ALTER TABLE `guaranteed_opinion_site_review` DROP INDEX guaranteed_opinion_site_review_id_unique;
ALTER TABLE `guaranteed_opinion_site_review` ADD PRIMARY KEY (`site_review_id`, `locale`);

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;