   ALTER TABLE `articles`
     ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1
     COMMENT '1 - статья показывается на сайте, 0 - скрыта (видна только в админке)'
     AFTER `content`;
