# DeltaHub

DeltaHub es una plataforma web tipo workshop pensada para la comunidad de *Deltarune*, donde la gente puede subir y descargar mods, sprites, saves, música, herramientas y traducciones.
Construido con **PHP Y MySQL**, con un frontend ambientado con la estética de Deltarune. 
Este proyecto esta en desarrollo activo. Algunas funciones (como el apartado de comunidad) están incompletas.


## Características

- *Cuentas de usuario*: registro e inicio de sesión para almacenar tus mods descargados.
- *Workshop*: listado paginado de todo el contenido subido, con categorías, etiquetas y un contador de descargas.
- *Subir contenido*: los usuarios con cuenta pueden subir archivos .zip (hasta 50 MB) con portada, título, descripción, categoría y versión.
- *Página de ítem*: vista con detalles de cada publicación, con datos del autor y descarga del archivo.
- *Perfil público* (`perfil.php?id=ID`): la ficha que ve el resto de la comunidad, con avatar, rol, fecha de registro, descripción, estadísticas y las publicaciones de esa persona. Se puede abrir sin iniciar sesión y los nombres de autor de la workshop y de los comentarios llevan ahí.
- *Perfil de cuenta* (`cuenta.php`): datos del usuario, fecha de registro, cambio de foto de perfil (máximo 2 MB) y edición de la descripción pública.
- *Categorías*: Mods, Sprites, Saves, Música, Herramientas, Traducciones.
- *Comunidad*: (proximamente).

La descripción pública vive en la columna `usuarios.bio`. Si venís de una base instalada antes que esa columna, corré `sql/migracion_bio_usuario.sql`; el sitio funciona igual sin ella, sólo sin descripción.


## Próximos pasos

- Búsqueda en tiempo real dentro de la Workshop.
- La sección de Comunidad.
- Roles de moderador/admin con panel de gestión.

## Licencia

Este es un proyecto fan-made, inspirado en la estética de *Deltarune* (hecho por Toby Fox). No está afiliado con los creadores originales del juego.
