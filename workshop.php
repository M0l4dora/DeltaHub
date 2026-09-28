<?php
session_start();
require_once "config/database.php";

// Tu tabla `categorias` solo tiene id y nombre (sin slug ni icono),
// así que los generamos acá para no tocar el esquema.
function slugify($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $texto);
    return preg_replace('/[^a-z0-9]+/', '', $texto);
}


// Avatar del autor. Si la ruta guardada no existe (subida a mano, cambio de
// carpeta, mayúsculas distintas...) cae en el default en vez de mostrar un ícono roto.
function avatar_de($url) {
    $por_defecto = "uploads/avatars/default.jpg";
    if (!empty($url) && is_file(__DIR__ . "/" . $url)) {
        return $url;
    }
    return $por_defecto;
}

// Fechas cortas y naturales para las tarjetas: "hoy", "ayer", "hace 3 días".
// Si el contenido es muy viejo vuelve a la fecha normal, que sigue siendo real.
function fecha_relativa($fecha) {
    if (empty($fecha)) return "";

    $dias = (int)(new DateTime($fecha))->diff(new DateTime())->format("%r%a");

    if ($dias <= 0) return "hoy";
    if ($dias === 1) return "ayer";
    if ($dias < 7)   return "hace $dias días";
    if ($dias < 31)  return ($dias < 14) ? "hace 1 semana" : "hace " . (int)ceil($dias / 7) . " semanas";
    if ($dias < 365) return ($dias < 60) ? "hace 1 mes" : "hace " . (int)floor($dias / 30) . " meses";

    $anios = (int)floor($dias / 365);
    return ($anios === 1) ? "hace 1 año" : "hace $anios años";
}

// Cada categoría con la cantidad de publicaciones que tiene.
$categorias = $pdo->query("
    SELECT c.id, c.nombre, COUNT(i.id) AS total
    FROM categorias c
    LEFT JOIN items i ON i.categoria_id = c.id
    GROUP BY c.id, c.nombre
    ORDER BY c.id
")->fetchAll();

// --- Traemos todo el contenido para filtrarlo del lado del cliente (JS) ---
$total_items = (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn();

$stmt = $pdo->query("
    SELECT items.*,
           usuarios.nombre_usuario,
           usuarios.avatar_url,
           categorias.nombre AS categoria_nombre,
           (SELECT COUNT(*) FROM comentarios WHERE comentarios.item_id = items.id) AS comentarios,
           (SELECT AVG(puntuacion) FROM comentarios
             WHERE comentarios.item_id = items.id AND comentarios.puntuacion IS NOT NULL) AS valoracion
    FROM items
    JOIN usuarios ON items.usuario_id = usuarios.id
    JOIN categorias ON items.categoria_id = categorias.id
    ORDER BY items.fecha_publicacion DESC
");
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Workshop - DeltaHub</title>
    <link rel="icon" type="image/png" href="favicon\favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon\favicon.svg" />
    <link rel="shortcut icon" href="favicon\favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon\apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Deltahub" />
    <link rel="manifest" href="favicon\site.webmanifest" />
</head>
<body class="workshop">
    <header>
        <a href="index.php" class="header-logo-LoginRegister">
            <img src="imagenes/DeltahubLogo3px.png" alt="logo deltahub">
        </a>
        <a href="index.php" id="shadow">Principal</a>
        <a href="workshop.php" id="shadow">Workshop</a>
        <a href="community.html" id="shadow">Comunidad</a>

        <div class="auth">

            <?php if (isset($_SESSION["usuario_id"])): ?>

                <a href="cuenta.php" id="shadow">
                    <?php echo htmlspecialchars($_SESSION["nombre_usuario"]); ?>
                </a>

                <a href="logout.php" id="shadow">
                    Logout
                </a>

            <?php else: ?>

                <a href="login.html" id="shadow">
                    Login
                </a>

                <a href="register.html" id="shadow">
                    Signup
                </a>

            <?php endif; ?>

        </div>

    </header>

    <main class="workshop-layout">

        <!-- BARRA LATERAL IZQUIERDA -->
        <aside class="ws-sidebar">
            <div class="ws-sidebar-section">
                <h3 class="ws-sidebar-title">Categorías</h3>
                <ul class="ws-category-list">
                    <li class="ws-category active" data-category="all">
                        <span class="ws-cat-icon">★</span>
                        <span class="ws-cat-name">Todos</span>
                        <span class="ws-cat-total"><?php echo $total_items; ?></span>
                    </li>
                    <?php foreach ($categorias as $cat):
                        $slug = slugify($cat['nombre']);
                        $icono = $iconos_categoria[$slug] ?? '★';
                    ?>
                    <li class="ws-category" data-category="<?php echo $slug; ?>">
                        <span class="ws-cat-icon"><?php echo $icono; ?></span>
                        <span class="ws-cat-name"><?php echo htmlspecialchars($cat['nombre']); ?></span>
                        <span class="ws-cat-total"><?php echo (int)$cat['total']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="ws-sidebar-section">
                <h3 class="ws-sidebar-title">Ordenar por</h3>
                <ul class="ws-sort-list">
                    <li class="ws-sort active" data-sort="popular">Popular</li>
                    <li class="ws-sort" data-sort="reciente">Reciente</li>
                    <li class="ws-sort" data-sort="valorado">Mejor valorado</li>
                    <li class="ws-sort" data-sort="descargas">Más descargado</li>
                </ul>
            </div>

            <div class="ws-sidebar-section">
                <h3 class="ws-sidebar-title">Filtros</h3>
                <div class="ws-filter-tags">
                    <button class="ws-tag active">Todos</button>
                    <button data-category="capitulo1" class="ws-tag">Ch.1</button>
                    <button class="ws-tag">Ch.2</button>
                    <button class="ws-tag">Ch.3</button>
                    <button class="ws-tag">Ch.4</button>
                    <button class="ws-tag">Ch.5</button>
                    <button class="ws-tag">Lorem Ipsum</button>
                    <button class="ws-tag">Lorem Ipsum</button>
                </div>
            </div>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <section class="ws-content">

            <!-- ENCABEZADO DE SECCIÓN -->
            <div class="ws-head">
                <h1 class="ws-head-title">Workshop</h1>
                <p class="ws-head-sub">Todo lo que la comunidad sube al hub.</p>
            </div>

            <!-- BARRA DE BÚSQUEDA -->
            <div class="ws-searchbar">
                <div class="ws-search-input-wrap">
                    <span class="ws-search-icon" aria-hidden="true"></span>
                    <input type="text" id="ws-search" placeholder="Buscar por título, autor o categoría..." autocomplete="off">
                </div>
                <?php if (isset($_SESSION["usuario_id"])): ?>
                    <a href="subir.php"><button class="ws-upload-btn">+ Subir</button></a>
                <?php else: ?>
                    <a href="login.html"><button class="ws-upload-btn">+ Subir (iniciá sesión)</button></a>
                <?php endif; ?>
            </div>

            <!-- FILTROS ACTIVOS / RESULTS INFO -->
            <div class="ws-results-bar">
                <span class="ws-results-count">Mostrando <strong id="ws-count-show"><?php echo count($items); ?></strong> de <strong id="ws-count-total"><?php echo $total_items; ?></strong> resultados</span>
                <div class="ws-view-toggle">
                    <button class="ws-view-btn active" data-view="grid" title="Vista cuadrícula">▦</button>
                    <button class="ws-view-btn" data-view="list" title="Vista lista">☰</button>
                </div>
            </div>

            <!-- GRID DE ITEMS -->
            <div class="ws-grid">

                <?php if (empty($items)): ?>
                    <p class="ws-nothing">
                        Todavía no hay nada publicado. ¡Sé el primero en subir algo!
                    </p>
                <?php endif; ?>

                <?php foreach ($items as $item):
                    $slug_item = slugify($item['categoria_nombre']);
                    $es_nuevo = (strtotime($item['fecha_publicacion']) >= strtotime('-7 days'));
                    $tiene_valoracion = ($item['valoracion'] !== null);
                    $valoracion_txt = $tiene_valoracion
                        ? number_format((float)$item['valoracion'], 1, ',', '.')
                        : '';
                    $comentarios = (int)$item['comentarios'];
                ?>
                    <a class="ws-item-link" href="item.php?id=<?php echo (int)$item['id']; ?>">
                        <article class="ws-item"
                                 data-category="<?php echo $slug_item; ?>"
                                 data-fecha="<?php echo htmlspecialchars($item['fecha_publicacion']); ?>"
                                 data-descargas="<?php echo (int)$item['descargas']; ?>"
                                 data-valoracion="<?php echo $tiene_valoracion ? (float)$item['valoracion'] : ''; ?>"
                                 data-comentarios="<?php echo $comentarios; ?>">

                            <div class="ws-item-thumb">
                                <?php if (!empty($item['imagen_portada'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['imagen_portada']); ?>" alt="">
                                <?php else: ?>
                                    <div class="ws-item-nocover">
                                        <span class="ws-item-nocover-icon"><?php echo $iconos_categoria[$slug_item] ?? '★'; ?></span>
                                        <span class="ws-item-nocover-label"><?php echo htmlspecialchars($item['categoria_nombre']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($es_nuevo): ?>
                                    <span class="ws-item-badge new">Nuevo</span>
                                <?php endif; ?>
                            </div>

                            <div class="ws-item-info">

                                <p class="ws-item-kicker">
                                    <span class="ws-item-cat"><?php echo htmlspecialchars($item['categoria_nombre']); ?></span>
                                    <span class="ws-item-when"><?php echo htmlspecialchars(fecha_relativa($item['fecha_publicacion'])); ?></span>
                                </p>

                                <h4 class="ws-item-title"><?php echo htmlspecialchars($item['titulo']); ?></h4>

                                <p class="ws-item-by">
                                    <img class="ws-item-avatar"
                                         src="<?php echo htmlspecialchars(avatar_de($item['avatar_url'])); ?>"
                                         alt="">
                                    <span class="ws-item-author"><?php echo htmlspecialchars($item['nombre_usuario']); ?></span>
                                </p>

                                <div class="ws-item-stats">
                                    <span class="ws-stat ws-stat-dl">↓ <?php echo (int)$item['descargas']; ?></span>
                                    <span class="ws-item-stats-tail">
                                        <?php if ($tiene_valoracion): ?>
                                            <span class="ws-stat ws-stat-rate" title="Valoración promedio de la comunidad">★ <?php echo $valoracion_txt; ?></span>
                                        <?php endif; ?>
                                        <?php if ($comentarios > 0): ?>
                                            <span class="ws-stat"><?php echo $comentarios; ?> coment.</span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                            </div>
                        </article>
                    </a>
                <?php endforeach; ?>

            </div>

            <p class="ws-empty" id="ws-empty" hidden>
                No se encontraron resultados para los filtros elegidos.
            </p>

        </section>

    </main>

    <footer>
        <p>&copy; 2026 Deltahub. Todos los derechos reservados.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>