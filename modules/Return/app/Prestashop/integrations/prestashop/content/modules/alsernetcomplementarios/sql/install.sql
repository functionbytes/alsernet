CREATE TABLE IF NOT EXISTS `PREFIX_alsernet_complementarios` (
  `id_complementario` INT(11) NOT NULL AUTO_INCREMENT,
  `type` ENUM('product','category','brand','label') NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  /* referencias en bruto que ingresó el usuario */
  `source_refs` TEXT NOT NULL,
  `complement_refs` TEXT NOT NULL,

  /* IDs resueltos (JSON de enteros) */
  `source_ids` TEXT NOT NULL,
  `source_brand_ids` TEXT DEFAULT NULL,
  `complement_ids` TEXT NOT NULL,

  /* Exclusiones (solo aplica a categoría/marca) */
  `excluded_products` TEXT DEFAULT NULL,

  /* NUEVO: orden numérico de este registro */
  `position` INT(11) NOT NULL DEFAULT 0,

  `date_add` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_complementario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
