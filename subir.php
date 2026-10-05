<?php
session_start();
require_once "config/database.php";
require_once "config/workshop.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit;
}

$errores = [];
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();

// El capítulo es sólo de los saves: se busca por el slug de cada categoría para
// no dejar el id clavado en el código.
$categorias_con_capitulo = [];
foreach ($categorias as $cat) {
    $slug = slugify($cat["nombre"]);
    if (usa_capitulos($slug)) $categorias_con_capitulo[$cat["id"]] = $slug;
}
$capitulos = !empty($categorias_con_capitulo) ? capitulos_disponibles($pdo, "saves") : [];
$hay_capitulos = !empty($capitulos);

// --- Config de subida (ajustá acá si cambian los límites) ---
$extensiones_archivo = ["zip"];
$tamano_maximo_mb = 50;
$extensiones_portada = ["jpg", "jpeg", "png", "gif", "webp"];
$portada_maxima_mb = 5;

$carpeta_archivos = __DIR__ . "/uploads/items/";
$carpeta_portadas = __DIR__ . "/uploads/portadas/";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria_id = $_POST["categoria_id"] ?? "";
    $version = trim($_POST["version"] ?? "") ?: "1.0";

    if ($titulo === "" || $descripcion === "" || $categoria_id === "") {
        $errores[] = "Título, descripción y categoría son obligatorios.";
    }

    $categoria_valida = false;
    foreach ($categorias as $cat) {
        if ($cat["id"] == $categoria_id) $categoria_valida = true;
    }
    if (!$categoria_valida) {
        $errores[] = "Categoría no válida.";
    }

    // Capítulo: obligatorio sólo para las categorías que lo usan (los saves) y
    // descartado en las demás, así un save no queda con un capítulo inventado.
    $categoria_con_capitulo = $categorias_con_capitulo[$categoria_id] ?? null;
    $capitulo = null;

    if ($categoria_con_capitulo !== null && $hay_capitulos) {
        $capitulo = (int)($_POST["capitulo"] ?? 0);

        if ($capitulo <= 0) {
            $errores[] = "Elegí el capítulo del save.";
        } elseif (!in_array($capitulo, $capitulos, true)) {
            $errores[] = "El capítulo elegido no existe.";
        }
    }

    // Validar archivo principal (obligatorio)
    if (!isset($_FILES["archivo"]) || $_FILES["archivo"]["error"] === UPLOAD_ERR_NO_FILE) {
        $errores[] = "Tenés que adjuntar un archivo .zip.";
    } elseif ($_FILES["archivo"]["error"] !== UPLOAD_ERR_OK) {
        $errores[] = "Hubo un error al subir el archivo.";
    } else {
        $ext_archivo = strtolower(pathinfo($_FILES["archivo"]["name"], PATHINFO_EXTENSION));
        $mb_archivo = $_FILES["archivo"]["size"] / (1024 * 1024);
        if (!in_array($ext_archivo, $extensiones_archivo)) {
            $errores[] = "El archivo principal debe ser: " . implode(", ", $extensiones_archivo);
        }
        if ($mb_archivo > $tamano_maximo_mb) {
            $errores[] = "El archivo supera el máximo de {$tamano_maximo_mb}MB.";
        }
    }

    // Validar portada (opcional)
    $hay_portada = isset($_FILES["portada"]) && $_FILES["portada"]["error"] !== UPLOAD_ERR_NO_FILE;
    if ($hay_portada) {
        if ($_FILES["portada"]["error"] !== UPLOAD_ERR_OK) {
            $errores[] = "Hubo un error al subir la portada.";
        } else {
            $ext_portada = strtolower(pathinfo($_FILES["portada"]["name"], PATHINFO_EXTENSION));
            $mb_portada = $_FILES["portada"]["size"] / (1024 * 1024);
            if (!in_array($ext_portada, $extensiones_portada)) {
                $errores[] = "La portada debe ser: " . implode(", ", $extensiones_portada);
            }
            if ($mb_portada > $portada_maxima_mb) {
                $errores[] = "La portada supera el máximo de {$portada_maxima_mb}MB.";
            }
        }
    }

    if (empty($errores)) {
        $pdo->beginTransaction();

        try {
            // 1. Insertar el item (sin portada todavía, no conocemos el item_id).
            // La columna del capítulo sólo se escribe si la base la tiene.
            $columnas = ["usuario_id", "categoria_id", "titulo", "descripcion"];
            $valores = [
                ":usuario_id" => $_SESSION["usuario_id"],
                ":categoria_id" => $categoria_id,
                ":titulo" => $titulo,
                ":descripcion" => $descripcion,
            ];

            if ($capitulo !== null && items_tienen_capitulo($pdo)) {
                $columnas[] = "capitulo";
                $valores[":capitulo"] = $capitulo;
            }

            $stmt = $pdo->prepare(
                "INSERT INTO items (" . implode(", ", $columnas) . ")
                 VALUES (" . implode(", ", array_keys($valores)) . ")"
            );
            $stmt->execute($valores);

            $item_id = $pdo->lastInsertId();

            // 2. Mover el archivo principal
            if (!is_dir($carpeta_archivos)) mkdir($carpeta_archivos, 0755, true);

            $nombre_archivo = $item_id . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", $_FILES["archivo"]["name"]);
            move_uploaded_file($_FILES["archivo"]["tmp_name"], $carpeta_archivos . $nombre_archivo);
            $tamano_mb_archivo = round($_FILES["archivo"]["size"] / (1024 * 1024), 2);

            $stmt = $pdo->prepare("
                INSERT INTO archivos (item_id, version, url_archivo, tamano_mb)
                VALUES (:item_id, :version, :url_archivo, :tamano_mb)
            ");
            $stmt->execute([
                ":item_id" => $item_id,
                ":version" => $version,
                ":url_archivo" => "uploads/items/" . $nombre_archivo,
                ":tamano_mb" => $tamano_mb_archivo,
            ]);

            // 3. Mover la portada, si la hay, y actualizar el item
            if ($hay_portada) {
                if (!is_dir($carpeta_portadas)) mkdir($carpeta_portadas, 0755, true);

                $nombre_portada = $item_id . "_" . preg_replace("/[^A-Za-z0-9._-]/", "_", $_FILES["portada"]["name"]);
                move_uploaded_file($_FILES["portada"]["tmp_name"], $carpeta_portadas . $nombre_portada);

                $stmt = $pdo->prepare("UPDATE items SET imagen_portada = :ruta WHERE id = :id");
                $stmt->execute([
                    ":ruta" => "uploads/portadas/" . $nombre_portada,
                    ":id" => $item_id,
                ]);
            }

            $pdo->commit();
            header("Location: workshop.php");
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = "Error al guardar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Subir - Deltahub</title>
</head>
<body class="cuenta">
    <header>
        <a href="index.php" class="header-logo-LoginRegister">
            <img src="imagenes/DeltahubLogo3px.png" alt="logo deltahub">
        </a>
        <a href="index.php" id="shadow">Principal</a>
        <a href="workshop.php" id="shadow">Workshop</a>
        <a href="community.html" id="shadow">Comunidad</a>
        <div class="auth">
            <a href="perfil.php?id=<?php echo (int)$_SESSION["usuario_id"]; ?>" id="shadow"><?php echo htmlspecialchars($_SESSION["nombre_usuario"]); ?></a>
        </div>
    </header>

    <main class="cuenta-layout">
        <div class="profile-card">
            <h2 class="profile-name">Subir al workshop</h2>
            <div class="profile-divider"></div>

            <?php if (!empty($errores)): ?>
                <div class="profile-row" style="border-color:#a82a3e; margin-bottom:20px;">
                    <?php foreach ($errores as $error): ?>
                        <p class="profile-value"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="avatar-form" style="text-align:left;">

                <label class="profile-label">Título</label>
                <input type="text" name="titulo" maxlength="100" required
                       value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>">

                <label class="profile-label">Descripción</label>
                <textarea name="descripcion" rows="4" required
                          style="width:100%; padding:12px; background-color:rgba(10,8,50,0.9); border:2px solid rgb(52,45,181); border-radius:8px; color:rgba(255,255,255,0.85); font-family:'Delta2';"
                ><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>

                <label class="profile-label">Categoría</label>
                <select name="categoria_id" id="subir-categoria" required
                        style="width:100%; padding:12px; background-color:rgba(10,8,50,0.9); border:2px solid rgb(52,45,181); border-radius:8px; color:rgba(255,255,255,0.85); font-family:'Delta2';">
                    <option value="">-- Elegí una categoría --</option>
                    <?php foreach ($categorias as $cat):
                        // data-cap le dice al script qué categorías abren el capítulo.
                        $con_capitulo = isset($categorias_con_capitulo[$cat['id']]);
                    ?>
                        <option value="<?php echo $cat['id']; ?>"
                                <?php if ($con_capitulo): ?>data-cap="1"<?php endif; ?>
                                <?php echo ($_POST['categoria_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Capítulo: sólo tiene sentido en los saves, así que el script lo
                     muestra cuando la categoría elegida es "Saves" y lo esconde en
                     cualquier otra. -->
                <?php if ($hay_capitulos): ?>
                    <div id="subir-capitulo" hidden>
                        <label class="profile-label">Capítulo</label>
                        <select name="capitulo"
                                style="width:100%; padding:12px; background-color:rgba(10,8,50,0.9); border:2px solid rgb(52,45,181); border-radius:8px; color:rgba(255,255,255,0.85); font-family:'Delta2';">
                            <option value="">-- Elegí un capítulo --</option>
                            <?php foreach ($capitulos as $cap): ?>
                                <option value="<?php echo $cap; ?>"
                                        <?php echo ($_POST['capitulo'] ?? '') === (string)$cap ? 'selected' : ''; ?>>
                                    <?php echo etiqueta_capitulo($cap); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="avatar-hint">Sólo para saves. Dice en qué capítulo del juego vas.</p>
                    </div>
                <?php endif; ?>

                <label class="profile-label">Versión (opcional, ej: 1.0)</label>
                <input type="text" name="version" maxlength="20"
                       value="<?php echo htmlspecialchars($_POST['version'] ?? ''); ?>">

                <label class="profile-label">Archivo (.zip, máx. <?php echo $tamano_maximo_mb; ?>MB)</label>
                <input type="file" name="archivo" accept=".zip" required>

                <label class="profile-label">Portada (opcional, imagen, máx. <?php echo $portada_maxima_mb; ?>MB)</label>
                <input type="file" name="portada" accept="image/*">

                <button type="submit" class="boton2" style="margin-top:10px;">Publicar</button>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2026 Deltahub. Todos los derechos reservados.</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>
