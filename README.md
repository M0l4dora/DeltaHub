# DeltaHub

DeltaHub es una plataforma web tipo workshop pensada para la comunidad de *Deltarune*, donde la gente puede subir y descargar mods, sprites, saves, música, herramientas y traducciones.
Construido con **PHP Y MySQL**, con un frontend ambientado con la estética de Deltarune. 
Este proyecto esta en desarrollo activo. Algunas funciones (como el apartado de comunidad) están incompletas.


## Características

- *Cuentas de usuario*: registro e inicio de sesión para almacenar tus mods descargados.
- *Workshop*: listado paginado de todo el contenido subido, con categorías, etiquetas y un contador de descargas.
- *Subir contenido*: los usuarios con cuenta pueden subir archivos .zip (hasta 50 MB) con portada, título, descripción, categoría y versión.
- *Página de ítem*: vista con detalles de cada publicación, con datos del autor y descarga del archivo.
- *Perfil de cuenta*: datos del usuario, fecha de registro y cambio de foto de perfil, y tamaño máximo de 2 MB.
- *Categorías*: Mods, Sprites, Saves, Música, Herramientas, Traducciones.
- *Comunidad*: (proximamente).


## Capas

| Backend | PHP (PDO + MySQL) |
| Base de datos | MySQL |
| Frontend | HTML, CSS y JavaScript |
| Sesiones | Sesiones nativas de PHP ($_SESSION) |
| Assets | Fuentes propias (`deltarune.ttf` y `mercy.ttf`), mouse personalizado, íconos y GIFs. |


## Próximos pasos

- Sistema de comentarios y valoraciones (Para los mods que la gente sube).
- Filtros funcionales en la Workshop (por popularidad, fecha, valoración, descargas).
- Búsqueda en tiempo real dentro de la Workshop.
- La sección de Comunidad.
- Roles de moderador/admin con panel de gestión.


## Licencia

Este es un proyecto fan-made, inspirado en la estética de *Deltarune* (hecho por Toby Fox). No está afiliado con los creadores originales del juego.
