-- Capítulo del save (Ch.1, Ch.2, ...).
-- La columna la usan el selector de subir.php (que sólo aparece cuando la
-- categoría elegida es "Saves") y los filtros de la barra lateral de la
-- workshop. El sitio funciona igual sin ella, sólo sin capítulos.
ALTER TABLE `items`
  ADD COLUMN `capitulo` tinyint(3) unsigned DEFAULT NULL AFTER `categoria_id`;
