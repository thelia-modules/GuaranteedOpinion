SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- guaranteed_opinion_product_rating
-- ---------------------------------------------------------------------

ALTER TABLE guaranteed_opinion_product_review MODIFY reply VARBINARY(10000);

ALTER TABLE guaranteed_opinion_site_review MODIFY reply VARBINARY(10000);

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;