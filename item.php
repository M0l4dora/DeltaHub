<?php
session_start();
require_once "config/database.php";

// Formatear fecha en español
function fecha_espanol($fecha) {
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
    $f = new DateTime($fecha);
    return $f->format("d") . " de " . $meses[(int)$f->format("n")] . " de " . $f->format("Y");
}

$id = (int)($_GET["id"] ?? 0);

$stmt = $pdo->prepare("
    SELECT items.*, usuarios.nombre_usuario, usuarios.avatar_url, categorias.nombre AS categoria_nombre
    FROM items
    JOIN usuarios ON items.usuario_id = usuarios.id
    JOIN categorias ON items.categoria_id = categorias.id
    WHERE items.id = :id
    LIMIT 1
");
$stmt->execute([":id" => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

$no_encontrado = empty($item);

$archivos = [];
$comentarios = [];

// Comentario nuevo (POST)
if (isset($_POST["contenido"]) && !$no_encontrado) {
    if (!isset($_SESSION["usuario_id"])) {
        header("Location: login.html");
        exit;
    }
    $contenido = trim($_POST["contenido"] ?? "");
    $puntuacion = (int)($_POST["puntuacion"] ?? 0);
    if ($puntuacion < 1 || $puntuacion > 5) {
        $puntuacion = null;
    }
    if ($contenido !== "") {
        $stmt = $pdo->prepare("
            INSERT INTO comentarios (item_id, usuario_id, contenido, puntuacion)
            VALUES (:item_id, :usuario_id, :contenido, :puntuacion)
        ");
        $stmt->execute([
            ":item_id" => $id,
            ":usuario_id" => $_SESSION["usuario_id"],
            ":contenido" => $contenido,
            ":puntuacion" => $puntuacion,
        ]);
        header("Location: item.php?id={$id}#comentarios");
        exit;
    }
}

if (!$no_encontrado) {
    // Archivos del item
    $stmt = $pdo->prepare("SELECT * FROM archivos WHERE item_id = :id ORDER BY fecha_subida DESC");
    $stmt->execute([":id" => $id]);
    $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Comentarios
    $stmt = $pdo->prepare("
        SELECT comentarios.*, usuarios.nombre_usuario, usuarios.avatar_url
        FROM comentarios
        JOIN usuarios ON comentarios.usuario_id = usuarios.id
        WHERE comentarios.item_id = :id
        ORDER BY comentarios.fecha DESC
    ");
    $stmt->execute([":id" => $id]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Avatar del autor por defecto
    if (empty($item["avatar_url"])) {
        $item["avatar_url"] = "uploads/avatars/default.jpg";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title><?php echo $no_encontrado ? "No encontrado - DeltaHub" : htmlspecialchars($item["titulo"]) . " - DeltaHub"; ?></title>
    <link rel="icon" type="image/png" href="favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg" />
    <link rel="shortcut icon" href="favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Deltahub" />
    <link rel="manifest" href="favicon/site.webmanifest" />
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

    <main class="item-layout">

        <?php if ($no_encontrado): ?>

            <div class="profile-card">
                <h1 class="profile-name">Contenido no encontrado</h1>
                <div class="profile-divider"></div>
                <p class="profile-value" style="margin-bottom:24px;">
                    Ese contenido no existe o fue eliminado.
                </p>
                <a href="workshop.php" class="btn">Volver a la workshop</a>
            </div>

        <?php else: ?>

            <article class="item-card">

                <div class="item-cover">
                    <?php if (!empty($item["imagen_portada"])): ?>
                        <img src="<?php echo htmlspecialchars($item["imagen_portada"]); ?>" alt="Portada de <?php echo htmlspecialchars($item["titulo"]); ?>">
                    <?php else: ?>
                        <div class="ws-item-thumb-placeholder">
                            <?php echo htmlspecialchars(mb_strtoupper(mb_substr($item["categoria_nombre"], 0, 4), "UTF-8")); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="item-body">

                    <div class="item-top">
                        <h1 class="item-title"><?php echo htmlspecialchars($item["titulo"]); ?></h1>
                        <span class="role-badge role-usuario"><?php echo htmlspecialchars($item["categoria_nombre"]); ?></span>
                    </div>

                    <div class="item-meta">
                        <div class="item-author">
                            <img
                            class="item-author-avatar"
                            src="<?php echo htmlspecialchars($item["avatar_url"]); ?>"
                            alt="Avatar de <?php echo htmlspecialchars($item["nombre_usuario"]); ?>"
                            >
                            <span>por <strong><?php echo htmlspecialchars($item["nombre_usuario"]); ?></strong></span>
                        </div>
                        <span class="item-date">📅 <?php echo htmlspecialchars(fecha_espanol($item["fecha_publicacion"])); ?></span>
                        <span class="item-downloads">⬇ <?php echo (int)$item["descargas"]; ?> descargas</span>
                    </div>

                    <?php if (!empty($item["fecha_actualizacion"])): ?>
                        <p class="item-updated">
                            Actualizado: <?php echo htmlspecialchars(fecha_espanol($item["fecha_actualizacion"])); ?>
                        </p>
                    <?php endif; ?>

                    <div class="profile-divider"></div>

                    <h2 class="profile-subtitle">Descripción</h2>
                    <p class="item-desc"><?php echo nl2br(htmlspecialchars($item["descripcion"] ?: "Sin descripción.")); ?></p>

                    <div class="profile-divider"></div>

                    <h2 class="profile-subtitle">Descargas</h2>

                    <?php if (empty($archivos)): ?>
                        <p class="profile-value">Este contenido todavía no tiene archivos disponibles.</p>
                    <?php else: ?>
                        <div class="item-files">
                            <?php foreach ($archivos as $archivo): ?>
                                <div class="file-row">
                                    <div class="file-info">
                                        <span class="file-name">
                                            <?php echo htmlspecialchars(basename($archivo["url_archivo"])); ?>
                                        </span>
                                        <span class="file-detail">v<?php echo htmlspecialchars($archivo["version"] ?? "1.0"); ?> · <?php echo (float)$archivo["tamano_mb"]; ?> MB</span>
                                    </div>
                                    <a href="descargar.php?id=<?php echo (int)$item["id"]; ?>" class="btn btn-download">Descargar</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </article>

            <!-- COMENTARIOS -->
            <section class="comments-card" id="comentarios">

                <h2 class="profile-subtitle">
                    Comentarios (<?php echo count($comentarios); ?>)
                </h2>

                <?php if (empty($comentarios)): ?>
                    <p class="profile-value" style="margin-bottom:16px;">
                        Todavía no hay comentarios. ¡Sé el primero!
                    </p>
                <?php else: ?>
                    <div class="comment-list">

                        <?php foreach ($comentarios as $comentario):
                            $avatar = empty($comentario["avatar_url"]) ? "uploads/avatars/default.jpg" : $comentario["avatar_url"];
                        ?>
                            <div class="comment-item">
                                <img class="comment-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar de <?php echo htmlspecialchars($comentario["nombre_usuario"]); ?>">
                                <div class="comment-body">
                                    <div class="comment-head">
                                        <span class="comment-author"><strong><?php echo htmlspecialchars($comentario["nombre_usuario"]); ?></strong></span>
                                        <?php if (!empty($comentario["puntuacion"])): ?>
                                            <span class="comment-stars">
                                                <?php
                                                    $c = (int)$comentario["puntuacion"];
                                                    echo str_repeat("★", $c) . str_repeat("☆", 5 - $c);
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="comment-text"><?php echo nl2br(htmlspecialchars($comentario["contenido"])); ?></p>
                                    <span class="comment-date"><?php echo htmlspecialchars(fecha_espanol($comentario["fecha"])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION["usuario_id"])): ?>

                    <div class="profile-divider"></div>

                    <form class="item-form" action="item.php?id=<?php echo (int)$item["id"]; ?>#comentarios" method="POST">
                        <label class="profile-label" for="contenido">Dejá tu comentario</label>
                        <textarea id="contenido" name="contenido" rows="3" maxlength="1000" placeholder="Contá tu opinión sobre este contenido..." required></textarea>

                        <label class="profile-label" for="puntuacion">Puntuación (opcional)</label>
                        <select id="puntuacion" name="puntuacion">
                            <option value="">Sin puntuar</option>
                            <option value="5">★★★★★</option>
                            <option value="4">★★★★☆</option>
                            <option value="3">★★★☆☆</option>
                            <option value="2">★★☆☆☆</option>
                            <option value="1">★☆☆☆☆</option>
                        </select>

                        <button type="submit" class="btn">Publicar comentario</button>
                    </form>

                <?php else: ?>

                    <p class="switch" style="margin-top:16px;">
                        <a href="login.html">Iniciá sesión</a> para comentar.
                    </p>

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