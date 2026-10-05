# DeltaHub

DeltaHub es una plataforma web tipo workshop pensada para la comunidad de *Deltarune*, donde la gente puede subir y descargar mods, sprites, saves, música, herramientas y traducciones.
Construido con **PHP Y MySQL**, con un frontend ambientado con la estética de Deltarune. 
Este proyecto esta en desarrollo activo. Algunas funciones (como el apartado de comunidad) están incompletas.


## Características

- *Cuentas de usuario*: registro e inicio de sesión para almacenar tus mods descargados.
- *Workshop*: listado paginado de todo el contenido subido, con categorías, etiquetas y un contador de descargas.
- *Subir contenido*: los usuarios con cuenta pueden subir archivos .zip (hasta 50 MB) con portada, título, descripción, categoría y versión. Al elegir la categoría *Saves* aparece además el capítulo (Ch.1 a Ch.5), que es obligatorio para esa categoría y se guarda en la columna `items.capitulo`.
- *Página de ítem*: vista con detalles de cada publicación, con datos del autor y descarga del archivo.
- *Perfil público* (`perfil.php?id=ID`): la ficha que ve el resto de la comunidad, con avatar, banner, rol, fecha de registro, descripción, estadísticas y las publicaciones de esa persona. Se puede abrir sin iniciar sesión y los nombres de autor de la workshop y de los comentarios llevan ahí.
- *Perfil de cuenta* (`cuenta.php`): datos del usuario, fecha de registro, edición de la descripción pública y cambio de la foto de perfil (clic sobre el avatar, máximo 2 MB) y del banner del perfil (botón propio, máximo 4 MB).
- *Categorías*: Mods, Sprites, Saves, Música, Herramientas, Traducciones.
- *Filtros por capítulo*: en la barra lateral de la Workshop, sólo cuando se está mirando la categoría *Saves* (o todas), aparecen los capítulos que tienen saves publicados, con la cantidad de cada uno. Al cambiar a otra categoría el filtro se suelta solo.
- *Comunidad*: (proximamente).

La columna del capítulo de los saves se agrega con `sql/migracion_capitulo_item.sql`; sin ella el sitio funciona igual, sólo sin capítulos ni sin el filtro que los agrupa. La descripción pública vive en la columna `usuarios.bio` y el banner en `usuarios.banner_url`. Si venís de una base instalada antes que esas columnas, corré `sql/migracion_bio_usuario.sql` y `sql/migracion_banner_usuario.sql`; el sitio funciona igual sin ellas, sólo sin descripción ni banner. Las imágenes se guardan en `Uploads/Avatars` y `Uploads/Banners`.


## Próximos pasos

- Búsqueda en tiempo real dentro de la Workshop.
- La sección de Comunidad.
- Roles de moderador/admin con panel de gestión.

## Licencia

Este es un proyecto fan-made, inspirado en la estética de *Deltarune* (hecho por Toby Fox). No está afiliado con los creadores originales del juego.
