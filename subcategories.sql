
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS subcategories;
CREATE TABLE `subcategories` (
  `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255)      NOT NULL,
  `categoryId` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_subcategories_category` (`categoryId`),
  CONSTRAINT `fk_subcategories_category`
    FOREIGN KEY (`categoryId`) REFERENCES `categories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `articles`
  ADD COLUMN `subcategoryId` SMALLINT UNSIGNED NULL DEFAULT NULL AFTER `categoryId`,
  ADD KEY `idx_articles_subcategory` (`subcategoryId`),
  ADD CONSTRAINT `fk_articles_subcategory`
    FOREIGN KEY (`subcategoryId`) REFERENCES `subcategories` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS = 1;