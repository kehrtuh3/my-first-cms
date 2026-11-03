DROP TABLE IF EXISTS users;
CREATE TABLE `users` (
  `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `login`    VARCHAR(50)  NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `active`   TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '1 - пользователь активен, 0 - заблокирован',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
