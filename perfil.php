<?php
session_start();
require_once "config/database.php";

/* Perfil público.
   Muestra los datos que cualquiera de la comunidad puede ver de un usuario,
   sin importar quién esté mirando la página. Para cambiar los datos de la
   cuenta está cuenta.php: acá no se edita nada y tampoco se consulta el
   email ni el hash de nadie. */

$meses = [
    1  => "enero",
    2  => "febrero",
    3  => "marzo",
    4  => "abril",
    5  => "mayo",
    6  => "junio",
    7  => "julio",
    8  => "agosto",
    9  => "septiembre",
    10 => "octubre",
    11 => "noviembre",
    12 => "diciembre"
];

// Avatar: si la ruta guardada no existe en el disco caemos en el default en
// vez de mostrar un ícono roto. Mismo criterio que la workshop.
function avatar_de($url) {
    $por_defecto = "uploads/avatars/default.jpg";

    if (!empty($url) && is_file(__DIR__ . "/" . $url)) {
        return $url;
    }

    return $por_defecto;
}

// El banner es la imagen de cabecera que sube el usuario desde cuenta.php. No
// tiene imagen por defecto: si no hay ninguna, el div queda con el gris de
// fondo que ya tenía. Mismo criterio que el avatar: si la ruta guardada ya no
// está en el disco, se muestra el recuadro vacío en vez de una imagen rota.
function banner_de($url) {
    if (!empty($url) && is_file(__DIR__ . "/" . $url)) {
        return $url;
    }

    return "";
}

// "8 sep 2026": en las tarjetas del perfil alcanza con día, mes y año.
function fecha_corta($fecha) {
    if (empty($fecha)) return "";

    $meses = ["ene", "feb", "mar", "abr", "may", "jun",
              "jul", "ago", "sep", "oct", "nov", "dic"];

    $f = new DateTime($fecha);

    return $f->format("j") . " " . $meses[(int)$f->format("n") - 1] . " " . $f->format("Y");
}

// `usuarios.bio` viene de una migración (ver sql/migracion_bio_usuario.sql).
// Se pregunta al esquema en vez de darla por sentada para que la página
// funcione igual en una base que todavía no la tenga.
function columna_existe($pdo, $tabla, $columna) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :tabla
          AND COLUMN_NAME = :columna
    ");

    $stmt->execute([
        ":tabla"   => $tabla,
        ":columna" => $columna
    ]);

    return ((int)$stmt->fetchColumn()) > 0;
}

// --- El id viene por GET y no se usa tal cual: primero se valida que sea un
// --- entero positivo y recién después se busca con consulta preparada.
// --- Cualquier otra cosa (vacío, "abc", "-3", "9 OR 1=1") se trata igual
// --- que un usuario inexistente.

$id_publico = null;

if (isset($_GET["id"]) && is_string($_GET["id"])) {

    $validado = filter_var(
        trim($_GET["id"]),
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($validado !== false) {
        $id_publico = (int)$validado;
    }

}

$hay_bio = columna_existe($pdo, "usuarios", "bio");
$hay_banner = columna_existe($pdo, "usuarios", "banner_url");

$usuario = null;
$publicaciones = [];

$stats = [
    "publicaciones" => 0,
    "descargas"     => 0,
    "comentarios"   => 0,
    "valoracion"    => null
];

if ($id_publico !== null) {

    // Sólo columnas públicas: el email y el hash no entran ni en la consulta.
    $columnas = "id, nombre_usuario, avatar_url, rol, fecha_registro";

    if ($hay_bio) {
        $columnas .= ", bio";
    }

    if ($hay_banner) {
        $columnas .= ", banner_url";
    }

    $stmt = $pdo->prepare("
        SELECT $columnas
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->bindValue(":id", $id_publico, PDO::PARAM_INT);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

}

if ($usuario) {

    $stats_stmt = $pdo->prepare("
        SELECT
            (SELECT COUNT(*)
               FROM items
              WHERE items.usuario_id = u.id) AS publicaciones,
            (SELECT COALESCE(SUM(i.descargas), 0)
               FROM items i
              WHERE i.usuario_id = u.id) AS descargas,
            (SELECT COUNT(*)
               FROM comentarios c
               JOIN items i ON i.id = c.item_id
              WHERE i.usuario_id = u.id) AS comentarios,
            (SELECT AVG(c.puntuacion)
               FROM comentarios c
               JOIN items i ON i.id = c.item_id
              WHERE i.usuario_id = u.id
                AND c.puntuacion IS NOT NULL) AS valoracion
        FROM usuarios u
        WHERE u.id = :id
    ");

    $stats_stmt->bindValue(":id", (int)$usuario["id"], PDO::PARAM_INT);
    $stats_stmt->execute();

    $fila_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila_stats) {
        $stats = $fila_stats + $stats;
    }

    $items_stmt = $pdo->prepare("
        SELECT items.id,
               items.titulo,
               items.imagen_portada,
               items.descargas,
               items.fecha_publicacion,
               categorias.nombre AS categoria_nombre,
               (SELECT COUNT(*)
                  FROM comentarios
                 WHERE comentarios.item_id = items.id) AS comentarios,
               (SELECT AVG(puntuacion)
                  FROM comentarios
                 WHERE comentarios.item_id = items.id
                   AND comentarios.puntuacion IS NOT NULL) AS valoracion
        FROM items
        JOIN categorias ON categorias.id = items.categoria_id
        WHERE items.usuario_id = :id
        ORDER BY items.fecha_publicacion DESC
    ");

    $items_stmt->bindValue(":id", (int)$usuario["id"], PDO::PARAM_INT);
    $items_stmt->execute();

    $publicaciones = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

}

// Un id inválido o un usuario que no está en la tabla son el mismo caso para
// quien está mirando: la página no existe.
if (!$usuario) {
    http_response_code(404);
}

$avatar = $usuario ? avatar_de($usuario["avatar_url"]) : "";

$banner = "";
if ($usuario && $hay_banner && !empty($usuario["banner_url"])) {
    $banner = banner_de($usuario["banner_url"]);
}

$bio = "";
if ($usuario && $hay_bio && !empty($usuario["bio"])) {
    $bio = trim($usuario["bio"]);
}

// El rol viene de un enum, pero igual se limita a los tres valores conocidos
// antes de convertirlo en clase de CSS.
$es_propio = $usuario
    && isset($_SESSION["usuario_id"])
    && (int)$_SESSION["usuario_id"] === (int)$usuario["id"];

$mes_registro = "";

if ($usuario) {
    $fecha_registro = new DateTime($usuario["fecha_registro"]);
    $mes_registro = $meses[(int)$fecha_registro->format("n")] . " de " . $fecha_registro->format("Y");
}

$valoracion_txt = $stats["valoracion"] === null
    ? "—"
    : number_format((float)$stats["valoracion"], 1, ",", ".");

$titulo = $usuario
    ? htmlspecialchars($usuario["nombre_usuario"]) . " - DeltaHub"
    : "Perfil no encontrado - DeltaHub";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title><?php echo $titulo; ?></title>
    <link rel="icon" type="image/png" href="favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg" />
    <link rel="shortcut icon" href="favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Deltahub" />
    <link rel="manifest" href="favicon/site.webmanifest" />
</head>
<body class="perfil">
    <header>
        <a href="index.php" class="header-logo-LoginRegister">
            <img src="imagenes/DeltahubLogo3px.png" alt="logo deltahub">
        </a>
        <a href="index.php" id="shadow">Principal</a>
        <a href="workshop.php" id="shadow">Workshop</a>
        <a href="community.html" id="shadow">Comunidad</a>

        <div class="auth">

            <?php if (isset($_SESSION["usuario_id"])): ?>

                <a href="perfil.php?id=<?php echo (int)$_SESSION["usuario_id"]; ?>" id="shadow">
                    <?php echo htmlspecialchars($_SESSION["nombre_usuario"]); ?>
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

    <main class="pf-layout">

        <?php if (!$usuario): ?>

            <div class="pf-missing">
                <span class="pf-missing-soul" aria-hidden="true">★</span>
                <p class="pf-missing-code">404</p>
                <h1 class="pf-missing-title">Perfil no encontrado</h1>
                <p class="pf-missing-text">
                    No hay ningún héroe con ese identificador en la FUENTE.
                    Puede que el enlace esté incompleto o que la cuenta ya no exista.
                </p>
                <div class="pf-missing-actions">
                    <a href="workshop.php" class="pf-back">Volver a la workshop</a>
                    <a href="index.php" class="pf-ghost">Ir al inicio</a>
                </div>
            </div>

        <?php else: ?>

            <nav class="item-crumbs pf-crumbs" aria-label="Navegación">
                <a class="item-crumb" href="workshop.php">Workshop</a>
                <span class="item-crumb-sep">/</span>
                <span class="item-crumb-here">@<?php echo htmlspecialchars($usuario["nombre_usuario"]); ?></span>
            </nav>

            <!-- IDENTIDAD -->
            <section class="pf-sheet" aria-label="Perfil de <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>">

                <?php if ($banner !== ""): ?>
                    <div class="pf-banner">
                        <img
                        class="pf-banner-img"
                        src="<?php echo htmlspecialchars($banner); ?>"
                        alt="Banner de <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>"
                        >
                    </div>
                <?php else: ?>
                    <div class="pf-banner" aria-hidden="true"></div>
                <?php endif; ?>

                <header class="pf-head">

                    <div class="pf-avatar">
                        <img
                        class="pf-avatar-img"
                        src="<?php echo htmlspecialchars($avatar); ?>"
                        alt="Avatar de <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>"
                        >
                    </div>

                    <div class="pf-side">

                        <div class="pf-id">
                            <h1 class="pf-name">
                                <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>
                            </h1>

                            <p class="pf-meta">
                                <span>Miembro desde <?php echo htmlspecialchars($mes_registro); ?></span>
                                <span class="pf-meta-dot" aria-hidden="true"></span>
                                <span>#<?php echo (int)$usuario["id"]; ?></span>
                            </p>
                        </div>

                        <div class="pf-stats">
                            <span class="pf-stat">
                                <strong class="pf-stat-num"><?php echo (int)$stats["publicaciones"]; ?></strong>
                                <span class="pf-stat-label">Publicaciones</span>
                            </span>
                            <span class="pf-stat">
                                <strong class="pf-stat-num"><?php echo (int)$stats["descargas"]; ?></strong>
                                <span class="pf-stat-label">Descargas</span>
                            </span>
                            <span class="pf-stat">
                                <strong class="pf-stat-num is-rate"><?php echo htmlspecialchars($valoracion_txt); ?></strong>
                                <span class="pf-stat-label">Valoración</span>
                            </span>
                            <span class="pf-stat">
                                <strong class="pf-stat-num"><?php echo (int)$stats["comentarios"]; ?></strong>
                                <span class="pf-stat-label">Comentarios</span>
                            </span>
                        </div>

                        <?php if ($es_propio): ?>
                            <div class="pf-actions">
                                <a href="cuenta.php" class="pf-edit">Editar perfil</a>
                            </div>
                        <?php endif; ?>

                        <?php if ($bio !== ""): ?>
                            <div class="pf-bio-box" role="note" aria-label="Presentación">
                                <p class="pf-bio-text"><?php echo nl2br(htmlspecialchars($bio)); ?></p>
                            </div>
                        <?php endif; ?>

                    </div>

                </header>

            </section>

            <!-- WORKSHOP -->
            <section class="pf-works" aria-label="Publicaciones">

                <div class="pf-works-head">
                    <h2 class="pf-section-title">
                        Workshop
                        <span class="pf-section-count"><?php echo count($publicaciones); ?></span>
                    </h2>
                    <a class="pf-works-link" href="workshop.php">Abrir workshop →</a>
                </div>

                <?php if (empty($publicaciones)): ?>

                    <div class="pf-empty-box">
                        <span class="pf-empty-ico" aria-hidden="true">◊</span>
                        <p class="pf-empty-title">La FUENTE aún está en silencio…</p>
                        <p class="pf-empty">
                            <?php echo $es_propio
                                ? "Todavía no publicaste nada. Tu primera creación puede ser la que encienda el hub."
                                : "Este usuario todavía no ha publicado nada."; ?>
                        </p>
                        <?php if ($es_propio): ?>
                            <a href="subir.php" class="pf-edit">+ Subir mi primera creación</a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>

                    <div class="pf-grid">

                        <?php foreach ($publicaciones as $item): ?>
                            <div class="ws-item-link" data-href="item.php?id=<?php echo (int)$item["id"]; ?>">
                                <article class="ws-item">

                                    <div class="ws-item-thumb">
                                        <?php if (!empty($item["imagen_portada"])): ?>
                                            <img src="<?php echo htmlspecialchars($item["imagen_portada"]); ?>" alt="">
                                        <?php else: ?>
                                            <div class="ws-item-nocover">
                                                <span class="ws-item-nocover-icon">★</span>
                                                <span class="ws-item-nocover-label">
                                                    <?php echo htmlspecialchars($item["categoria_nombre"]); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="ws-item-info">

                                        <p class="ws-item-kicker">
                                            <span class="ws-item-cat">
                                                <?php echo htmlspecialchars($item["categoria_nombre"]); ?>
                                            </span>
                                            <span class="ws-item-when">
                                                <?php echo htmlspecialchars(fecha_corta($item["fecha_publicacion"])); ?>
                                            </span>
                                        </p>

                                        <h3 class="ws-item-title">
                                            <a class="ws-item-title-link"
                                               href="item.php?id=<?php echo (int)$item["id"]; ?>">
                                                <?php echo htmlspecialchars($item["titulo"]); ?>
                                            </a>
                                        </h3>

                                        <div class="ws-item-stats">
                                            <span class="ws-stat ws-stat-dl">
                                                ↓ <?php echo (int)$item["descargas"]; ?>
                                            </span>
                                            <span class="ws-item-stats-tail">
                                                <?php if ($item["valoracion"] !== null): ?>
                                                    <span class="ws-stat ws-stat-rate" title="Valoración promedio de la comunidad">
                                                        ★ <?php echo htmlspecialchars(number_format((float)$item["valoracion"], 1, ",", ".")); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ((int)$item["comentarios"] > 0): ?>
                                                    <span class="ws-stat">
                                                        <?php echo (int)$item["comentarios"]; ?> coment.
                                                    </span>
                                                <?php endif; ?>
                                            </span>
                                        </div>

                                    </div>

                                </article>
                            </div>
                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>

    </main>

    <footer>
        <p>&copy; 2026 Deltahub. Todos los derechos reservados.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>
