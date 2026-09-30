-- ---------------------------------------------------------------------
-- 2.1.0: reviews, ratings and API keys by language (the 1.1.x line of the module, brought to Thelia 3).
--
-- Brings any earlier schema (1.0.x, 1.1.0 to 1.1.5, 2.0.0) to the one of Config/TheliaMain.sql, without losing a
-- row, and may be run again: every change is tested in information_schema first. Rows without a language get the
-- default language of the shop, as 1.1.0 did.
-- Each statement ends with ";" and a line break: Thelia splits the file there, so no stored procedure is used.
-- ---------------------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

SET @guaranteed_opinion_locale = COALESCE(
    (SELECT `locale` FROM `lang` WHERE `by_default` = 1 ORDER BY `id` LIMIT 1),
    (SELECT `locale` FROM `lang` ORDER BY `id` LIMIT 1)
);

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_review
-- ---------------------------------------------------------------------

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND column_name = 'locale') = 0,
    'ALTER TABLE `guaranteed_opinion_product_review` ADD `locale` VARCHAR(5) NULL AFTER `product_review_id`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

UPDATE `guaranteed_opinion_product_review` SET `locale` = @guaranteed_opinion_locale WHERE `locale` IS NULL OR `locale` = '';

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND column_name = 'order_id') > 0,
    'ALTER TABLE `guaranteed_opinion_product_review` DROP COLUMN `order_id`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND column_name = 'reply' AND data_type <> 'varbinary') > 0,
    'ALTER TABLE `guaranteed_opinion_product_review` MODIFY `reply` VARBINARY(10000)',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND column_name = 'id') > 0,
    'ALTER TABLE `guaranteed_opinion_product_review` MODIFY `id` INTEGER NOT NULL',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND column_name = 'id') > 0
    AND (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND index_name = 'PRIMARY') > 0,
    'ALTER TABLE `guaranteed_opinion_product_review` DROP PRIMARY KEY',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND column_name = 'id') > 0,
    'ALTER TABLE `guaranteed_opinion_product_review` DROP COLUMN `id`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND column_name = 'locale' AND is_nullable = 'YES') > 0,
    'ALTER TABLE `guaranteed_opinion_product_review` MODIFY `locale` VARCHAR(5) NOT NULL',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND index_name = 'guaranteed_opinion_product_review_id_unique') > 0,
    'ALTER TABLE `guaranteed_opinion_product_review` DROP INDEX `guaranteed_opinion_product_review_id_unique`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_primary_key = (
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND index_name = 'PRIMARY'
);
SET @guaranteed_opinion_sql = CASE
    WHEN @guaranteed_opinion_primary_key IS NULL THEN 'ALTER TABLE `guaranteed_opinion_product_review` ADD PRIMARY KEY (`product_review_id`, `locale`)'
    WHEN @guaranteed_opinion_primary_key <> 'product_review_id,locale' THEN 'ALTER TABLE `guaranteed_opinion_product_review` DROP PRIMARY KEY, ADD PRIMARY KEY (`product_review_id`, `locale`)'
    ELSE 'DO 0'
END;
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_review' AND index_name = 'idx_guaranteed_opinion_product_review_product_locale') = 0,
    'ALTER TABLE `guaranteed_opinion_product_review` ADD INDEX `idx_guaranteed_opinion_product_review_product_locale` (`product_id`, `locale`, `review_date`)',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_site_review
-- ---------------------------------------------------------------------

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND column_name = 'locale') = 0,
    'ALTER TABLE `guaranteed_opinion_site_review` ADD `locale` VARCHAR(5) NULL AFTER `site_review_id`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

UPDATE `guaranteed_opinion_site_review` SET `locale` = @guaranteed_opinion_locale WHERE `locale` IS NULL OR `locale` = '';

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND column_name = 'order_id') > 0,
    'ALTER TABLE `guaranteed_opinion_site_review` DROP COLUMN `order_id`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND column_name = 'reply' AND data_type <> 'varbinary') > 0,
    'ALTER TABLE `guaranteed_opinion_site_review` MODIFY `reply` VARBINARY(10000)',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND column_name = 'id') > 0,
    'ALTER TABLE `guaranteed_opinion_site_review` MODIFY `id` INTEGER NOT NULL',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND column_name = 'id') > 0
    AND (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND index_name = 'PRIMARY') > 0,
    'ALTER TABLE `guaranteed_opinion_site_review` DROP PRIMARY KEY',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND column_name = 'id') > 0,
    'ALTER TABLE `guaranteed_opinion_site_review` DROP COLUMN `id`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND column_name = 'locale' AND is_nullable = 'YES') > 0,
    'ALTER TABLE `guaranteed_opinion_site_review` MODIFY `locale` VARCHAR(5) NOT NULL',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND index_name = 'guaranteed_opinion_site_review_id_unique') > 0,
    'ALTER TABLE `guaranteed_opinion_site_review` DROP INDEX `guaranteed_opinion_site_review_id_unique`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_primary_key = (
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND index_name = 'PRIMARY'
);
SET @guaranteed_opinion_sql = CASE
    WHEN @guaranteed_opinion_primary_key IS NULL THEN 'ALTER TABLE `guaranteed_opinion_site_review` ADD PRIMARY KEY (`site_review_id`, `locale`)'
    WHEN @guaranteed_opinion_primary_key <> 'site_review_id,locale' THEN 'ALTER TABLE `guaranteed_opinion_site_review` DROP PRIMARY KEY, ADD PRIMARY KEY (`site_review_id`, `locale`)'
    ELSE 'DO 0'
END;
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_site_review' AND index_name = 'idx_guaranteed_opinion_site_review_locale_date') = 0,
    'ALTER TABLE `guaranteed_opinion_site_review` ADD INDEX `idx_guaranteed_opinion_site_review_locale_date` (`locale`, `review_date`)',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_rating
-- ---------------------------------------------------------------------

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_rating' AND column_name = 'locale') = 0,
    'ALTER TABLE `guaranteed_opinion_product_rating` ADD `locale` VARCHAR(5) NULL AFTER `product_id`',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

UPDATE `guaranteed_opinion_product_rating` SET `locale` = @guaranteed_opinion_locale WHERE `locale` IS NULL OR `locale` = '';

SET @guaranteed_opinion_sql = IF(
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_rating' AND column_name = 'locale' AND is_nullable = 'YES') > 0,
    'ALTER TABLE `guaranteed_opinion_product_rating` MODIFY `locale` VARCHAR(5) NOT NULL',
    'DO 0'
);
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

SET @guaranteed_opinion_primary_key = (
    SELECT GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',') FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'guaranteed_opinion_product_rating' AND index_name = 'PRIMARY'
);
SET @guaranteed_opinion_sql = CASE
    WHEN @guaranteed_opinion_primary_key IS NULL THEN 'ALTER TABLE `guaranteed_opinion_product_rating` ADD PRIMARY KEY (`product_id`, `locale`)'
    WHEN @guaranteed_opinion_primary_key <> 'product_id,locale' THEN 'ALTER TABLE `guaranteed_opinion_product_rating` DROP PRIMARY KEY, ADD PRIMARY KEY (`product_id`, `locale`)'
    ELSE 'DO 0'
END;
PREPARE guaranteed_opinion_statement FROM @guaranteed_opinion_sql;
EXECUTE guaranteed_opinion_statement;
DEALLOCATE PREPARE guaranteed_opinion_statement;

-- ---------------------------------------------------------------------
-- module_config: the keys read by language are copied to the default language, existing values kept
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `module_config_i18n` (`id`, `locale`, `value`)
SELECT `mc`.`id`, @guaranteed_opinion_locale, `mci`.`value`
FROM `module_config` `mc`
    JOIN `module` `m` ON `m`.`id` = `mc`.`module_id` AND `m`.`code` = 'GuaranteedOpinion'
    JOIN `module_config_i18n` `mci` ON `mci`.`id` = `mc`.`id` AND `mci`.`locale` = 'en_US'
WHERE `mc`.`name` IN (
    'guaranteedopinion.api.review',
    'guaranteedopinion.api.order',
    'guaranteedopinion.show_rating_url',
    'guaranteedopinion.site_rating_total',
    'guaranteedopinion.site_rating_average'
);

SET FOREIGN_KEY_CHECKS = 1;
