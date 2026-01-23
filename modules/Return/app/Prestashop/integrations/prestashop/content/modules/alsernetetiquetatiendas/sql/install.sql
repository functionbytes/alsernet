CREATE TABLE IF NOT EXISTS `PREFIX_alsernetetiquetatiendas_settings` (
  `id_settings` INT(11) NOT NULL AUTO_INCREMENT,

  -- Imagen base (vertical y horizontal)
  `base_image`    VARCHAR(255) DEFAULT NULL,
  `base_image_h`  VARCHAR(255) DEFAULT NULL,

  -- Colores por campo
  `color_referencia`   VARCHAR(7) DEFAULT '#000000',
  `color_descripcion`  VARCHAR(7) DEFAULT '#000000',
  `color_pvprp`        VARCHAR(7) DEFAULT '#000000',
  `color_pvp`          VARCHAR(7) DEFAULT '#000000',

  -- Fuentes por campo
  `font_referencia_family`   VARCHAR(64) DEFAULT 'helvetica',
  `font_referencia_size`     INT(11)     DEFAULT 12,
  `font_descripcion_family`  VARCHAR(64) DEFAULT 'helvetica',
  `font_descripcion_size`    INT(11)     DEFAULT 12,
  `font_pvprp_family`        VARCHAR(64) DEFAULT 'helvetica',
  `font_pvprp_size`          INT(11)     DEFAULT 12,
  `font_pvp_family`          VARCHAR(64) DEFAULT 'helvetica',
  `font_pvp_size`            INT(11)     DEFAULT 12,

  -- Texto fijo (label) "Precio recomendado:" y estilo
  `label_text`        TEXT        DEFAULT 'Precio recomendado:',
  `color_label`       VARCHAR(7)  DEFAULT '#000000',
  `font_label_family` VARCHAR(64) DEFAULT 'helvetica',
  `font_label_size`   INT(11)     DEFAULT 12,

  -- Tamaños de caja (px, canvas real 700x990)
  `box_referencia_w` INT(11) DEFAULT 100, `box_referencia_h` INT(11) DEFAULT 20,
  `box_descripcion_w` INT(11) DEFAULT 330, `box_descripcion_h` INT(11) DEFAULT 30,
  `box_pvprp_w` INT(11) DEFAULT 120, `box_pvprp_h` INT(11) DEFAULT 33,
  `box_pvp_w` INT(11) DEFAULT 200, `box_pvp_h` INT(11) DEFAULT 90,
  `box_label_w` INT(11) DEFAULT 220, `box_label_h` INT(11) DEFAULT 30,

  -- Posiciones VERTICALES (4 slots) - canvas 700x990 (px)
  `pos_referencia_x1` INT(11) DEFAULT 50,  `pos_referencia_y1` INT(11) DEFAULT 50,
  `pos_referencia_x2` INT(11) DEFAULT 50,  `pos_referencia_y2` INT(11) DEFAULT 50,
  `pos_referencia_x3` INT(11) DEFAULT 50,  `pos_referencia_y3` INT(11) DEFAULT 50,
  `pos_referencia_x4` INT(11) DEFAULT 50,  `pos_referencia_y4` INT(11) DEFAULT 50,

  `pos_descripcion_x1` INT(11) DEFAULT 50, `pos_descripcion_y1` INT(11) DEFAULT 80,
  `pos_descripcion_x2` INT(11) DEFAULT 50, `pos_descripcion_y2` INT(11) DEFAULT 80,
  `pos_descripcion_x3` INT(11) DEFAULT 50, `pos_descripcion_y3` INT(11) DEFAULT 80,
  `pos_descripcion_x4` INT(11) DEFAULT 50, `pos_descripcion_y4` INT(11) DEFAULT 80,

  `pos_pvprp_x1` INT(11) DEFAULT 50,       `pos_pvprp_y1` INT(11) DEFAULT 110,
  `pos_pvprp_x2` INT(11) DEFAULT 50,       `pos_pvprp_y2` INT(11) DEFAULT 110,
  `pos_pvprp_x3` INT(11) DEFAULT 50,       `pos_pvprp_y3` INT(11) DEFAULT 110,
  `pos_pvprp_x4` INT(11) DEFAULT 50,       `pos_pvprp_y4` INT(11) DEFAULT 110,

  `pos_pvp_x1` INT(11) DEFAULT 50,         `pos_pvp_y1` INT(11) DEFAULT 140,
  `pos_pvp_x2` INT(11) DEFAULT 50,         `pos_pvp_y2` INT(11) DEFAULT 140,
  `pos_pvp_x3` INT(11) DEFAULT 50,         `pos_pvp_y3` INT(11) DEFAULT 140,
  `pos_pvp_x4` INT(11) DEFAULT 50,         `pos_pvp_y4` INT(11) DEFAULT 140,

  `pos_label_x1` INT(11) DEFAULT 50,       `pos_label_y1` INT(11) DEFAULT 100,
  `pos_label_x2` INT(11) DEFAULT 50,       `pos_label_y2` INT(11) DEFAULT 100,
  `pos_label_x3` INT(11) DEFAULT 50,       `pos_label_y3` INT(11) DEFAULT 100,
  `pos_label_x4` INT(11) DEFAULT 50,       `pos_label_y4` INT(11) DEFAULT 100,

  -- Posiciones HORIZONTALES (8 slots) - canvas 700x990 (px)
  `pos_referencia_hx1` INT(11) DEFAULT 50, `pos_referencia_hy1` INT(11) DEFAULT 50,
  `pos_referencia_hx2` INT(11) DEFAULT 50, `pos_referencia_hy2` INT(11) DEFAULT 50,
  `pos_referencia_hx3` INT(11) DEFAULT 50, `pos_referencia_hy3` INT(11) DEFAULT 50,
  `pos_referencia_hx4` INT(11) DEFAULT 50, `pos_referencia_hy4` INT(11) DEFAULT 50,
  `pos_referencia_hx5` INT(11) DEFAULT 50, `pos_referencia_hy5` INT(11) DEFAULT 140,
  `pos_referencia_hx6` INT(11) DEFAULT 50, `pos_referencia_hy6` INT(11) DEFAULT 140,
  `pos_referencia_hx7` INT(11) DEFAULT 50, `pos_referencia_hy7` INT(11) DEFAULT 140,
  `pos_referencia_hx8` INT(11) DEFAULT 50, `pos_referencia_hy8` INT(11) DEFAULT 140,

  `pos_descripcion_hx1` INT(11) DEFAULT 50, `pos_descripcion_hy1` INT(11) DEFAULT 80,
  `pos_descripcion_hx2` INT(11) DEFAULT 50, `pos_descripcion_hy2` INT(11) DEFAULT 80,
  `pos_descripcion_hx3` INT(11) DEFAULT 50, `pos_descripcion_hy3` INT(11) DEFAULT 80,
  `pos_descripcion_hx4` INT(11) DEFAULT 50, `pos_descripcion_hy4` INT(11) DEFAULT 80,
  `pos_descripcion_hx5` INT(11) DEFAULT 50, `pos_descripcion_hy5` INT(11) DEFAULT 170,
  `pos_descripcion_hx6` INT(11) DEFAULT 50, `pos_descripcion_hy6` INT(11) DEFAULT 170,
  `pos_descripcion_hx7` INT(11) DEFAULT 50, `pos_descripcion_hy7` INT(11) DEFAULT 170,
  `pos_descripcion_hx8` INT(11) DEFAULT 50, `pos_descripcion_hy8` INT(11) DEFAULT 170,

  `pos_pvprp_hx1` INT(11) DEFAULT 50, `pos_pvprp_hy1` INT(11) DEFAULT 110,
  `pos_pvprp_hx2` INT(11) DEFAULT 50, `pos_pvprp_hy2` INT(11) DEFAULT 110,
  `pos_pvprp_hx3` INT(11) DEFAULT 50, `pos_pvprp_hy3` INT(11) DEFAULT 110,
  `pos_pvprp_hx4` INT(11) DEFAULT 50, `pos_pvprp_hy4` INT(11) DEFAULT 110,
  `pos_pvprp_hx5` INT(11) DEFAULT 50, `pos_pvprp_hy5` INT(11) DEFAULT 200,
  `pos_pvprp_hx6` INT(11) DEFAULT 50, `pos_pvprp_hy6` INT(11) DEFAULT 200,
  `pos_pvprp_hx7` INT(11) DEFAULT 50, `pos_pvprp_hy7` INT(11) DEFAULT 200,
  `pos_pvprp_hx8` INT(11) DEFAULT 50, `pos_pvprp_hy8` INT(11) DEFAULT 200,

  `pos_pvp_hx1` INT(11) DEFAULT 50, `pos_pvp_hy1` INT(11) DEFAULT 140,
  `pos_pvp_hx2` INT(11) DEFAULT 50, `pos_pvp_hy2` INT(11) DEFAULT 140,
  `pos_pvp_hx3` INT(11) DEFAULT 50, `pos_pvp_hy3` INT(11) DEFAULT 140,
  `pos_pvp_hx4` INT(11) DEFAULT 50, `pos_pvp_hy4` INT(11) DEFAULT 140,
  `pos_pvp_hx5` INT(11) DEFAULT 50, `pos_pvp_hy5` INT(11) DEFAULT 230,
  `pos_pvp_hx6` INT(11) DEFAULT 50, `pos_pvp_hy6` INT(11) DEFAULT 230,
  `pos_pvp_hx7` INT(11) DEFAULT 50, `pos_pvp_hy7` INT(11) DEFAULT 230,
  `pos_pvp_hx8` INT(11) DEFAULT 50, `pos_pvp_hy8` INT(11) DEFAULT 230,

  `pos_label_hx1` INT(11) DEFAULT 50, `pos_label_hy1` INT(11) DEFAULT 100,
  `pos_label_hx2` INT(11) DEFAULT 50, `pos_label_hy2` INT(11) DEFAULT 100,
  `pos_label_hx3` INT(11) DEFAULT 50, `pos_label_hy3` INT(11) DEFAULT 100,
  `pos_label_hx4` INT(11) DEFAULT 50, `pos_label_hy4` INT(11) DEFAULT 100,
  `pos_label_hx5` INT(11) DEFAULT 50, `pos_label_hy5` INT(11) DEFAULT 190,
  `pos_label_hx6` INT(11) DEFAULT 50, `pos_label_hy6` INT(11) DEFAULT 190,
  `pos_label_hx7` INT(11) DEFAULT 50, `pos_label_hy7` INT(11) DEFAULT 190,
  `pos_label_hx8` INT(11) DEFAULT 50, `pos_label_hy8` INT(11) DEFAULT 190,

  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id_settings`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
