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

$iconos_categoria = [
    'mods' => '⚔',
    'sprites' => '✦',
    'saves' => '♥',
    'musica' => '♪',
    'herramientas' => '⚙',
    'traducciones' => '🌐',
];

$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY id")->fetchAll();

$items = $pdo->query("
    SELECT items.*, usuarios.nombre_usuario, categorias.nombre AS categoria_nombre
    FROM items
    JOIN usuarios ON items.usuario_id = usuarios.id
    JOIN categorias ON items.categoria_id = categorias.id
    ORDER BY items.fecha_publicacion DESC
")->fetchAll();
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
                        <span class="ws-cat-icon">★</span> Todos
                    </li>
                    <?php foreach ($categorias as $cat):
                        $slug = slugify($cat['nombre']);
                        $icono = $iconos_categoria[$slug] ?? '★';
                    ?>
                    <li class="ws-category" data-category="<?php echo $slug; ?>">
                        <span class="ws-cat-icon"><?php echo $icono; ?></span> <?php echo htmlspecialchars($cat['nombre']); ?>
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

            <!-- BARRA DE BÚSQUEDA -->
            <div class="ws-searchbar">
                <div class="ws-search-input-wrap">
                    <input type="text" id="ws-search" placeholder="Buscar en la workshop..." autocomplete="off">
                </div>
                <?php if (isset($_SESSION["usuario_id"])): ?>
                    <a href="subir.php"><button class="ws-upload-btn">+ Subir</button></a>
                <?php else: ?>
                    <a href="login.html"><button class="ws-upload-btn">+ Subir (iniciá sesión)</button></a>
                <?php endif; ?>
            </div>

            <!-- FILTROS ACTIVOS / RESULTS INFO -->
            <div class="ws-results-bar">
                <span class="ws-results-count">Mostrando <strong><?php echo count($items); ?></strong> resultados</span>
                <div class="ws-view-toggle">
                    <button class="ws-view-btn active" data-view="grid" title="Vista cuadrícula">▦</button>
                    <button class="ws-view-btn" data-view="list" title="Vista lista">☰</button>
                </div>
            </div>

            <!-- GRID DE ITEMS -->
            <div class="ws-grid">

                <?php if (empty($items)): ?>
                    <p style="color:rgba(255,255,255,0.6); font-family:'Delta2';">
                        Todavía no hay nada publicado. ¡Sé el primero en subir algo!
                    </p>
                <?php endif; ?>

                <?php foreach ($items as $item): ?>
                    <?php
                        $slug_item = slugify($item['categoria_nombre']);
                        $es_nuevo = (strtotime($item['fecha_publicacion']) >= strtotime('-7 days'));
                        $tipo_label = mb_strtoupper(mb_substr($item['categoria_nombre'], 0, 4), 'UTF-8');
                    ?>
                    <article class="ws-item" data-category="<?php echo $slug_item; ?>">
                        <div class="ws-item-thumb">
                            <?php if (!empty($item['imagen_portada'])): ?>
                                <img src="<?php echo htmlspecialchars($item['imagen_portada']); ?>" alt=""
                                     style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div class="ws-item-thumb-placeholder"><?php echo htmlspecialchars($tipo_label); ?></div>
                            <?php endif; ?>
                            <?php if ($es_nuevo): ?>
                                <span class="ws-item-badge new">Nuevo</span>
                            <?php endif; ?>
                        </div>
                        <div class="ws-item-info">
                            <h4 class="ws-item-title"><?php echo htmlspecialchars($item['titulo']); ?></h4>
                            <p class="ws-item-author">por <strong><?php echo htmlspecialchars($item['nombre_usuario']); ?></strong></p>
                            <div class="ws-item-meta">
                                <span class="ws-item-downloads">⬇ <?php echo (int)$item['descargas']; ?></span>
                            </div>
                            <div class="ws-item-tags">
                                <span class="ws-item-tag"><?php echo htmlspecialchars($item['categoria_nombre']); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

            </div>

            <!-- PAGINACIÓN -->
            <div class="ws-pagination">
                <button class="ws-page-btn active">1</button>
                <button class="ws-page-btn">2</button>
                <button class="ws-page-btn">3</button>
                <span class="ws-page-dots">...</span>
                <button class="ws-page-btn">8</button>
                <button class="ws-page-btn ws-page-next">→</button>
            </div>

        </section>

    </main>

    <footer>
        <p>&copy; 2026 Deltahub. Todos los derechos reservados.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>