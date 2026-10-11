-- Zoom del banner que eligió el usuario en la ventana de recorte.
--
-- Va aparte de banner_posicion porque un `object-position` no agranda la imagen:
-- sólo elige qué pedazo se ve de una imagen que ya está arrancada con cover. El
-- zoom es un multiplicador entre 1 (la imagen apenas tapa el marco) y 4, y las
-- páginas lo aplican como `transform: scale()` anclado al mismo punto que el
-- object-position, que es el centro desde el que el usuario movió el encuadre.
--
-- Sólo importa para los archivos que se suben SIN recortar (los GIF animados):
-- el archivo entra entero en uploads/banners/ y estas dos columnas deciden el
-- encuadre. Un banner recortado con canvas ya trae el zoom cocido en el archivo
-- y se guarda con zoom 1.
--
-- El default es 1, que es un scale() que no hace nada: los banners que ya
-- estaban guardados siguen viéndose exactamente igual que antes de migrar.

ALTER TABLE `usuarios`
  ADD COLUMN `banner_zoom` decimal(3,2) NOT NULL DEFAULT 1.00 AFTER `banner_posicion`;
