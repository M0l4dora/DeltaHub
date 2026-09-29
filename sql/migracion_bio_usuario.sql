-- Descripción pública del usuario: es lo que muestra perfil.php.
--
-- La columna es opcional a propósito. Si todavía no la aplicaste, tanto
-- perfil.php como cuenta.php la detectan y funcionan igual, sólo sin
-- descripción: no hace falta correr esto para que el sitio no falle.
--
-- Si instalás la base desde sql/deltahub.sql en una base nueva, la columna
-- ya viene en el CREATE TABLE y este archivo no hace falta.

ALTER TABLE `usuarios`
  ADD COLUMN `bio` text DEFAULT NULL;
