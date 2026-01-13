SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_rating
-- ---------------------------------------------------------------------

ALTER TABLE `guaranteed_opinion_product_review` ADD `locale` VARCHAR(5) AFTER `product_review_id`;
ALTER TABLE `guaranteed_opinion_product_review` DROP COLUMN `order_id`;

UPDATE `guaranteed_opinion_product_review`
SET `locale` = (SELECT `locale` FROM `lang` WHERE `by_default` = 1 LIMIT 1);

-- ---------------------------------------------------------------------
-- guaranteed_opinion_site_review
-- ---------------------------------------------------------------------

ALTER TABLE `guaranteed_opinion_site_review` ADD `locale` VARCHAR(5) AFTER `site_review_id`;
ALTER TABLE `guaranteed_opinion_site_review` DROP COLUMN `order_id`;

UPDATE `guaranteed_opinion_site_review`
SET `locale` = (SELECT `locale` FROM `lang` WHERE `by_default` = 1 LIMIT 1);

-- ---------------------------------------------------------------------
-- module_config
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `module_config_i18n` (`id`, `locale`, `value`)
SELECT `mc`.`id`,
       (SELECT `locale` FROM `lang` WHERE `by_default` = 1 LIMIT 1) AS `target_locale`,
       `mci`.`value`
FROM `module_config` `mc`
    JOIN `module_config_i18n` `mci` ON `mc`.`id` = `mci`.`id`
WHERE `mc`.`name` IN (
    'guaranteedopinion.api.review',
    'guaranteedopinion.api.order',
    'guaranteedopinion.show_rating_url'
);

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;