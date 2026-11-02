<?php
session_start();
require_once "config/database.php";

function community_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function community_avatar($url) {
    if (!empty($url) && is_file(__DIR__ . "/" . $url)) {
        return $url;
    }

    return "uploads/avatars/default.jpg";
}

function community_fecha($fecha) {
    $timestamp = (int)$fecha;
    $date = new DateTime("@" . $timestamp);
    $segundos = max(0, time() - $timestamp);

    if ($segundos < 60) return "ahora";
    if ($segundos < 3600) return "hace " . floor($segundos / 60) . " min";
    if ($segundos < 86400) return "hace " . floor($segundos / 3600) . " h";
    if ($segundos < 604800) return "hace " . floor($segundos / 86400) . " d";

    return $date->format("d/m/Y");
}

$usuario_id = isset($_SESSION["usuario_id"]) ? (int)$_SESSION["usuario_id"] : null;
$nombre_usuario = $_SESSION["nombre_usuario"] ?? "";
$token_csrf = $_SESSION["community_csrf"] ?? bin2hex(random_bytes(32));
$_SESSION["community_csrf"] = $token_csrf;
$errores = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token_recibido = $_POST["csrf_token"] ?? "";

    if (!is_string($token_recibido) || !hash_equals($token_csrf, $token_recibido)) {
        $errores[] = "La sesión del formulario venció. Actualizá la página e intentá de nuevo.";
    } elseif (!$usuario_id) {
        $errores[] = "Iniciá sesión para participar en la comunidad.";
    } else {
        $accion = $_POST["accion"] ?? "";

        if ($accion === "publicar") {
            $texto = trim((string)($_POST["contenido"] ?? ""));
            $archivo = $_FILES["medio"] ?? null;
            $ruta_medio = null;
            $tipo_medio = null;

            if (mb_strlen($texto) > 2000) {
                $errores[] = "La publicación no puede superar los 2000 caracteres.";
            }

            if ($archivo && $archivo["error"] !== UPLOAD_ERR_NO_FILE) {
                if ($archivo["error"] !== UPLOAD_ERR_OK) {
                    $errores[] = "No se pudo recibir el archivo. Revisá su tamaño e intentá de nuevo.";
                } elseif ($archivo["size"] > 8 * 1024 * 1024) {
                    $errores[] = "El archivo supera el límite de 8 MB.";
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($archivo["tmp_name"]);
                    $formatos = [
                        "image/jpeg" => ["jpg", "imagen"],
                        "image/png" => ["png", "imagen"],
                        "image/webp" => ["webp", "imagen"],
                        "image/gif" => ["gif", "imagen"],
                        "video/mp4" => ["mp4", "video"],
                        "video/webm" => ["webm", "video"]
                    ];

                    if (!isset($formatos[$mime])) {
                        $errores[] = "Formato no permitido. Usá JPG, PNG, WebP, GIF, MP4 o WebM.";
                    } else {
                        $directorio = __DIR__ . "/uploads/community/";
                        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
                            $errores[] = "No se pudo preparar el almacenamiento de imágenes.";
                        } else {
                            $nombre_archivo = bin2hex(random_bytes(16)) . "." . $formatos[$mime][0];
                            if (move_uploaded_file($archivo["tmp_name"], $directorio . $nombre_archivo)) {
                                $ruta_medio = "uploads/community/" . $nombre_archivo;
                                $tipo_medio = $formatos[$mime][1];
                            } else {
                                $errores[] = "No se pudo guardar el archivo en el servidor.";
                            }
                        }
                    }
                }
            }

            if ($texto === "" && $ruta_medio === null && !$errores) {
                $errores[] = "Escribí algo o adjuntá una imagen o un video.";
            }

            if (!$errores) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO community_posts (usuario_id, contenido, medio_url, medio_tipo) VALUES (:usuario_id, :contenido, :medio_url, :medio_tipo)");
                    $stmt->execute([
                        ":usuario_id" => $usuario_id,
                        ":contenido" => $texto,
                        ":medio_url" => $ruta_medio,
                        ":medio_tipo" => $tipo_medio
                    ]);
                    header("Location: community.php?publicado=1");
                    exit;
                } catch (PDOException $e) {
                    if ($ruta_medio && is_file(__DIR__ . "/" . $ruta_medio)) {
                        unlink(__DIR__ . "/" . $ruta_medio);
                    }
                    $errores[] = "No se pudo publicar. Comprobá que esté aplicada la migración de comunidad.";
                }
            }
        } elseif ($accion === "like") {
            $post_id = filter_var($_POST["post_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
            if ($post_id) {
                $existe = $pdo->prepare("SELECT 1 FROM community_likes WHERE post_id = :post_id AND usuario_id = :usuario_id");
                $existe->execute([":post_id" => $post_id, ":usuario_id" => $usuario_id]);
                if ($existe->fetchColumn()) {
                    $stmt = $pdo->prepare("DELETE FROM community_likes WHERE post_id = :post_id AND usuario_id = :usuario_id");
                } else {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO community_likes (post_id, usuario_id) VALUES (:post_id, :usuario_id)");
                }
                $stmt->execute([":post_id" => $post_id, ":usuario_id" => $usuario_id]);
                header("Location: community.php?orden=" . (($_GET["orden"] ?? "reciente") === "popular" ? "popular" : "reciente") . "#post-" . $post_id);
                exit;
            }
        } elseif ($accion === "comentar") {
            $post_id = filter_var($_POST["post_id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
            $comentario = trim((string)($_POST["comentario"] ?? ""));
            if (!$post_id || $comentario === "" || mb_strlen($comentario) > 500) {
                $errores[] = "El comentario debe tener entre 1 y 500 caracteres.";
            } else {
                $post_existe = $pdo->prepare("SELECT id FROM community_posts WHERE id = :id");
                $post_existe->execute([":id" => $post_id]);
                if ($post_existe->fetchColumn()) {
                    $stmt = $pdo->prepare("INSERT INTO community_comments (post_id, usuario_id, contenido) VALUES (:post_id, :usuario_id, :contenido)");
                    $stmt->execute([":post_id" => $post_id, ":usuario_id" => $usuario_id, ":contenido" => $comentario]);
                }
                header("Location: community.php?orden=" . (($_GET["orden"] ?? "reciente") === "popular" ? "popular" : "reciente") . "#post-" . $post_id);
                exit;
            }
        }
    }
}

$orden = (isset($_GET["orden"]) && $_GET["orden"] === "popular") ? "popular" : "reciente";
$orden_sql = $orden === "popular" ? "likes DESC, p.fecha DESC" : "p.fecha DESC";

$stmt = $pdo->query("SELECT p.id, p.usuario_id, p.contenido, p.medio_url, p.medio_tipo, p.fecha, UNIX_TIMESTAMP(p.fecha) AS fecha_epoch,
                            u.nombre_usuario, u.avatar_url,
                            (SELECT COUNT(*) FROM community_likes l WHERE l.post_id = p.id) AS likes,
                            (SELECT COUNT(*) FROM community_comments c WHERE c.post_id = p.id) AS comentarios" .
                    ($usuario_id ? ", EXISTS(SELECT 1 FROM community_likes l WHERE l.post_id = p.id AND l.usuario_id = " . $usuario_id . ") AS me_gusta" : ", 0 AS me_gusta") .
                    " FROM community_posts p JOIN usuarios u ON u.id = p.usuario_id
                      ORDER BY " . $orden_sql . " LIMIT 40");
$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$comentarios_por_post = [];
if ($publicaciones) {
    $ids = array_map("intval", array_column($publicaciones, "id"));
    $marcadores = implode(",", array_fill(0, count($ids), "?"));
    $stmt = $pdo->prepare("SELECT c.id, c.post_id, c.contenido, c.fecha, UNIX_TIMESTAMP(c.fecha) AS fecha_epoch, u.id AS usuario_id, u.nombre_usuario, u.avatar_url
                           FROM community_comments c JOIN usuarios u ON u.id = c.usuario_id
                           WHERE c.post_id IN ($marcadores) ORDER BY c.fecha ASC");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $comentario) {
        $comentarios_por_post[$comentario["post_id"]][] = $comentario;
    }
}

$total_publicaciones = (int)$pdo->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
$total_usuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$usuario_actual = null;
if ($usuario_id) {
    $stmt = $pdo->prepare("SELECT nombre_usuario, avatar_url FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $usuario_id]);
    $usuario_actual = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="community.css">
    <title>Comunidad - DeltaHub</title>
    <link rel="icon" type="image/png" href="favicon/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg">
    <link rel="shortcut icon" href="favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
    <link rel="manifest" href="favicon/site.webmanifest">
</head>
<body class="community">
    <video class="ws-bg-video" autoplay muted loop playsinline preload="auto" aria-hidden="true">
        <source src="imagenes/wsbackground.mp4" type="video/mp4">
    </video>
    <header>
        <a href="index.php" class="header-logo-LoginRegister"><img src="imagenes/DeltahubLogo3px.png" alt="DeltaHub"></a>
        <a href="index.php" id="shadow">Principal</a>
        <a href="workshop.php" id="shadow">Workshop</a>
        <a href="community.php" id="shadow" aria-current="page">Comunidad</a>
        <div class="auth">
            <?php if ($usuario_id): ?>
                <a href="perfil.php?id=<?php echo $usuario_id; ?>" id="shadow"><?php echo community_h($nombre_usuario); ?></a>
            <?php else: ?>
                <a href="login.html" id="shadow">Login</a>
                <a href="register.html" id="shadow">Signup</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="community-layout">
        <aside class="community-rail" aria-label="Navegación de comunidad">
            <a class="community-rail-link active" href="community.php?orden=reciente"><span aria-hidden="true">⌂</span> Inicio</a>
            <a class="community-rail-link" href="community.php?orden=popular"><span aria-hidden="true">↗</span> Lo más gustado</a>
            <div class="community-rail-divider"></div>
            <p class="community-rail-label">DELTAHUB</p>
            <p class="community-rail-note">Un rincón para compartir lo que estás creando, descubriendo y jugando.</p>
            <?php if ($usuario_id): ?>
                <a class="community-rail-profile" href="perfil.php?id=<?php echo $usuario_id; ?>">
                    <img src="<?php echo community_h(community_avatar($usuario_actual["avatar_url"] ?? "")); ?>" alt="">
                    <span><strong><?php echo community_h($usuario_actual["nombre_usuario"] ?? $nombre_usuario); ?></strong><small>Ver perfil</small></span>
                    <span aria-hidden="true">→</span>
                </a>
            <?php endif; ?>
        </aside>

        <section class="community-feed" aria-labelledby="community-title">
            <div class="community-heading">
                <div>
                    <p class="community-eyebrow">EL PUNTO DE ENCUENTRO</p>
                    <h1 id="community-title">Comunidad</h1>
                    <p>Ideas, hallazgos y creaciones de la gente de DeltaHub.</p>
                </div>
                <span class="community-heading-mark" aria-hidden="true">✳</span>
            </div>

            <?php if (isset($_GET["publicado"])): ?>
                <p class="community-alert success" role="status">Tu publicación ya está en el feed.</p>
            <?php endif; ?>
            <?php foreach ($errores as $error): ?>
                <p class="community-alert" role="alert"><?php echo community_h($error); ?></p>
            <?php endforeach; ?>

            <?php if ($usuario_id): ?>
                <form class="community-composer" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo community_h($token_csrf); ?>">
                    <input type="hidden" name="accion" value="publicar">
                    <img class="community-avatar composer-avatar" src="<?php echo community_h(community_avatar($usuario_actual["avatar_url"] ?? "")); ?>" alt="">
                    <div class="community-compose-main">
                        <label class="visually-hidden" for="community-content">Escribí una publicación</label>
                        <textarea id="community-content" name="contenido" maxlength="2000" placeholder="¿Qué está pasando en tu mundo?" rows="3"></textarea>
                        <div class="community-compose-footer">
                            <label class="community-attach" for="community-media" title="Adjuntar imagen, GIF o video"><span aria-hidden="true">＋</span> Medio</label>
                            <input class="visually-hidden" type="file" id="community-media" name="medio" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm">
                            <span class="community-file-name" data-community-file></span>
                            <span class="community-char-count"><span data-community-count>0</span>/2000</span>
                            <button class="community-submit" type="submit">Publicar <span aria-hidden="true">↗</span></button>
                        </div>
                        <small class="community-compose-help">JPG, PNG, WebP, GIF, MP4 o WebM · hasta 8 MB</small>
                    </div>
                </form>
            <?php else: ?>
                <div class="community-login-prompt">
                    <span class="community-prompt-symbol" aria-hidden="true">✦</span>
                    <div><strong>La conversación empieza con vos.</strong><p>Iniciá sesión para publicar, responder y dejar tu me gusta.</p></div>
                    <a href="login.html">Iniciar sesión <span aria-hidden="true">→</span></a>
                </div>
            <?php endif; ?>

            <div class="community-feed-toolbar">
                <h2>En el feed</h2>
                <nav aria-label="Ordenar publicaciones">
                    <a class="<?php echo $orden === "reciente" ? "active" : ""; ?>" href="community.php?orden=reciente">Recientes</a>
                    <a class="<?php echo $orden === "popular" ? "active" : ""; ?>" href="community.php?orden=popular">Populares</a>
                </nav>
            </div>

            <div class="community-post-list">
                <?php if (!$publicaciones): ?>
                    <div class="community-empty"><span aria-hidden="true">✳</span><h2>El feed está en blanco</h2><p>Cuando alguien publique, vas a encontrarlo acá.</p></div>
                <?php endif; ?>

                <?php foreach ($publicaciones as $post): ?>
                    <article class="community-post" id="post-<?php echo (int)$post["id"]; ?>">
                        <img class="community-avatar" src="<?php echo community_h(community_avatar($post["avatar_url"])); ?>" alt="">
                        <div class="community-post-body">
                            <div class="community-post-byline">
                                <a class="community-author" href="perfil.php?id=<?php echo (int)$post["usuario_id"]; ?>"><?php echo community_h($post["nombre_usuario"]); ?></a>
                                <span class="community-post-dot" aria-hidden="true">·</span>
                                <time datetime="<?php echo community_h(date(DATE_ATOM, (int)$post["fecha_epoch"])); ?>" title="<?php echo community_h(date("d/m/Y H:i", (int)$post["fecha_epoch"])); ?>"><?php echo community_h(community_fecha($post["fecha_epoch"])); ?></time>
                            </div>
                            <?php if ($post["contenido"] !== ""): ?>
                                <p class="community-post-text"><?php echo nl2br(community_h($post["contenido"])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($post["medio_url"]) && is_file(__DIR__ . "/" . $post["medio_url"])): ?>
                                <?php if ($post["medio_tipo"] === "video"): ?>
                                    <video class="community-post-media" controls preload="metadata">
                                        <source src="<?php echo community_h($post["medio_url"]); ?>">
                                        Tu navegador no puede reproducir este video.
                                    </video>
                                <?php else: ?>
                                    <a class="community-media-link" href="<?php echo community_h($post["medio_url"]); ?>" target="_blank" rel="noopener">
                                        <img class="community-post-media" src="<?php echo community_h($post["medio_url"]); ?>" alt="Imagen compartida por <?php echo community_h($post["nombre_usuario"]); ?>" loading="lazy">
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="community-post-actions">
                                <?php if ($usuario_id): ?>
                                    <form method="POST" class="community-action-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo community_h($token_csrf); ?>">
                                        <input type="hidden" name="accion" value="like">
                                        <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                                        <button class="community-action <?php echo $post["me_gusta"] ? "liked" : ""; ?>" type="submit" aria-label="<?php echo $post["me_gusta"] ? "Quitar me gusta" : "Me gusta"; ?>">
                                            <span aria-hidden="true"><?php echo $post["me_gusta"] ? "♥" : "♡"; ?></span> <?php echo (int)$post["likes"]; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a class="community-action" href="login.html" aria-label="Iniciá sesión para dar me gusta"><span aria-hidden="true">♡</span> <?php echo (int)$post["likes"]; ?></a>
                                <?php endif; ?>
                                <a class="community-action" href="#comments-<?php echo (int)$post["id"]; ?>"><span aria-hidden="true">▱</span> <?php echo (int)$post["comentarios"]; ?> <?php echo (int)$post["comentarios"] === 1 ? "respuesta" : "respuestas"; ?></a>
                            </div>
                            <div class="community-comments" id="comments-<?php echo (int)$post["id"]; ?>">
                                <?php foreach (($comentarios_por_post[$post["id"]] ?? []) as $comentario): ?>
                                    <div class="community-comment">
                                        <img class="community-avatar comment-avatar" src="<?php echo community_h(community_avatar($comentario["avatar_url"])); ?>" alt="">
                                        <div><div class="community-comment-byline"><a href="perfil.php?id=<?php echo (int)$comentario["usuario_id"]; ?>"><?php echo community_h($comentario["nombre_usuario"]); ?></a><time><?php echo community_h(community_fecha($comentario["fecha_epoch"])); ?></time></div><p><?php echo nl2br(community_h($comentario["contenido"])); ?></p></div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($usuario_id): ?>
                                    <form method="POST" class="community-reply-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo community_h($token_csrf); ?>">
                                        <input type="hidden" name="accion" value="comentar">
                                        <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                                        <label class="visually-hidden" for="reply-<?php echo (int)$post["id"]; ?>">Responder a <?php echo community_h($post["nombre_usuario"]); ?></label>
                                        <input id="reply-<?php echo (int)$post["id"]; ?>" type="text" name="comentario" maxlength="500" placeholder="Escribí una respuesta..." required>
                                        <button type="submit" aria-label="Enviar respuesta">↗</button>
                                    </form>
                                <?php else: ?>
                                    <a class="community-reply-login" href="login.html">Iniciá sesión para responder</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="community-aside">
            <section class="community-aside-block community-about">
                <p class="community-aside-kicker">EL HUB</p>
                <h2>La comunidad se mueve.</h2>
                <p>Un espacio para conversar sobre Deltarune, mostrar avances y descubrir qué están haciendo los demás.</p>
                <div class="community-stats"><span><strong><?php echo $total_publicaciones; ?></strong> publicaciones</span><span><strong><?php echo $total_usuarios; ?></strong> miembros</span></div>
            </section>
            <section class="community-aside-block community-shortcuts">
                <p class="community-aside-kicker">PARA EXPLORAR</p>
                <a href="workshop.php"><span>Workshop</span><span aria-hidden="true">↗</span></a>
                <a href="community.php?orden=popular"><span>Lo más gustado</span><span aria-hidden="true">↗</span></a>
                <?php if ($usuario_id): ?><a href="cuenta.php"><span>Editar mi cuenta</span><span aria-hidden="true">↗</span></a><?php endif; ?>
            </section>
            <p class="community-aside-foot">DeltaHub · hecho por la comunidad</p>
        </aside>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const textarea = document.getElementById('community-content');
            const count = document.querySelector('[data-community-count]');
            const media = document.getElementById('community-media');
            const filename = document.querySelector('[data-community-file]');
            if (textarea && count) textarea.addEventListener('input', () => { count.textContent = textarea.value.length; });
            if (media && filename) media.addEventListener('change', () => { filename.textContent = media.files.length ? media.files[0].name : ''; });
        });
    </script>
</body>
</html>
