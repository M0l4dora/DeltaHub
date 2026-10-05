-- Posición del banner que eligió el usuario en la ventana de recorte.
--
-- Guarda dos porcentajes "X% Y%" que se aplican como `object-position` sobre la
-- imagen del banner. Sirve para los archivos que se suben SIN recortar (los GIF
-- animados, que el <canvas> reduciría a un fotograma fijo): el archivo entra
-- entero en uploads/banners/ y esta columna dice qué parte se ve en el recuadro.
--
-- Las dos páginas que la usan la preguntan con columna_existe(), así que la
-- aplicación sigue funcionando aunque esta migración todavía no se haya corrido.

ALTER TABLE `usuarios`
  ADD COLUMN `banner_posicion` varchar(255) DEFAULT NULL AFTER `banner_url`;