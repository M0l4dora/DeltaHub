<?php

session_start();

require_once "config/database.php";

// SEGURIDAD: comprobar que haya una sesión iniciada

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit;
}

// Obtener el ID del usuario

$usuario_id = $_SESSION["usuario_id"];

// `usuarios.bio` se agregó con sql/migracion_bio_usuario.sql. Se pregunta al
// esquema en vez de darla por sentada para que la cuenta siga funcionando en
// una base que todavía no la tenga.

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

$hay_bio = columna_existe($pdo, "usuarios", "bio");

// Buscar los datos del usuario

$columnas = "id, nombre_usuario, email, fecha_registro, rol, avatar_url";

if ($hay_bio) {
    $columnas .= ", bio";
}

$sql = "SELECT $columnas
        FROM usuarios
        WHERE id = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":id" => $usuario_id
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Si el usuario ya no existe en la base de datos

if (!$usuario) {
    session_unset();
    session_destroy();

    header("Location: login.html");
    exit;
}

// Guardar la descripción pública (la que muestra perfil.php)

$errores = [];
$bio_guardada = false;
$bio_texto = $hay_bio ? trim((string)($usuario["bio"] ?? "")) : "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["bio"])) {

    $bio_texto = trim($_POST["bio"]);

    // El textarea ya limita a 300, pero el POST se recorta igual: no se fía
    // del navegador para el tamaño.

    if (mb_strlen($bio_texto) > 300) {
        $bio_texto = mb_substr($bio_texto, 0, 300);
    }

    if (!$hay_bio) {
        $errores[] = "La descripción todavía no está disponible en esta base de datos.";
    } else {
        $stmt = $pdo->prepare("UPDATE usuarios SET bio = :bio WHERE id = :id");
        $stmt->execute([
            ":bio" => $bio_texto !== "" ? $bio_texto : null,
            ":id"  => $usuario_id
        ]);

        $bio_guardada = true;
    }

}

// Avatar por defecto si el usuario no tiene uno propio

if (empty($usuario["avatar_url"])) {
    $usuario["avatar_url"] = "uploads/avatars/default.jpg";
}

// Formatear la fecha de registro en español

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

$fecha = new DateTime($usuario["fecha_registro"]);

$fecha_formateada = $fecha->format("d") . " de " .
                    $meses[(int)$fecha->format("n")] . " de " .
                    $fecha->format("Y") . ", " .
                    $fecha->format("H:i");

// Clase CSS según el rol

$rol_clase = "role-" . htmlspecialchars($usuario["rol"]);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Mi cuenta - DeltaHub</title>
    <link rel="icon" type="image/png" href="favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="favicon/favicon.svg" />
    <link rel="shortcut icon" href="favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="Deltahub" />
    <link rel="manifest" href="favicon/site.webmanifest" />
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
            <a href="perfil.php?id=<?php echo (int)$_SESSION["usuario_id"]; ?>" id="shadow">
                <?php echo htmlspecialchars($_SESSION["nombre_usuario"]); ?>
            </a>
        </div>
    </header>

    <main class="cuenta-layout">

        <section class="profile-card">

            <div class="profile-header">
                <div class="profile-avatar-wrap">
                    <img
                    src="<?php echo htmlspecialchars($usuario["avatar_url"]); ?>"
                    alt="Avatar de <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>"
                    class="profile-avatar"
                    >
                </div>

                <div class="profile-heading">
                    <h1 class="profile-name">
                        <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>
                    </h1>
                    <span class="role-badge <?php echo $rol_clase; ?>">
                        <?php echo htmlspecialchars($usuario["rol"]); ?>
                    </span>
                    <a class="cuenta-link" href="perfil.php?id=<?php echo (int)$usuario["id"]; ?>">
                        Ver mi perfil público
                    </a>
                </div>
            </div>

            <div class="profile-divider"></div>

            <h2 class="profile-subtitle">Datos de la cuenta</h2>

            <div class="profile-info">

                <div class="profile-row">
                    <span class="profile-label">Identificador</span>
                    <span class="profile-value">#<?php echo htmlspecialchars($usuario["id"]); ?></span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Nombre de usuario</span>
                    <span class="profile-value">
                        <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>
                    </span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Email</span>
                    <span class="profile-value">
                        <?php echo htmlspecialchars($usuario["email"]); ?>
                    </span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Miembro desde</span>
                    <span class="profile-value">
                        <?php echo htmlspecialchars(ucfirst($fecha_formateada)); ?>
                    </span>
                </div>

                <div class="profile-row">
                    <span class="profile-label">Rol</span>
                    <span class="profile-value">
                        <span class="role-badge <?php echo $rol_clase; ?>">
                            <?php echo htmlspecialchars($usuario["rol"]); ?>
                        </span>
                    </span>
                </div>

            </div>

            <div class="profile-divider"></div>

            <h2 class="profile-subtitle">Descripción pública</h2>

            <form class="avatar-form" method="POST" action="cuenta.php">

                <textarea
                class="cuenta-bio"
                id="bio"
                name="bio"
                rows="4"
                maxlength="300"
                placeholder="Contá de vos, qué hacés en el hub, qué mod vas a subir..."
                ><?php echo htmlspecialchars($bio_texto); ?></textarea>

                <small class="avatar-hint">
                    Hasta 300 caracteres. Se muestra en tu perfil público.
                </small>

                <?php if ($bio_guardada): ?>
                    <small class="cuenta-ok">Descripción guardada.</small>
                <?php endif; ?>

                <?php foreach ($errores as $error): ?>
                    <small class="cuenta-error"><?php echo htmlspecialchars($error); ?></small>
                <?php endforeach; ?>

                <button type="submit" class="btn">Guardar descripción</button>

            </form>

            <div class="profile-divider"></div>

            <h2 class="profile-subtitle">Cambiar avatar</h2>

            <form class="avatar-form" action="cambiar_pfp.php" method="POST" enctype="multipart/form-data">
                <input
                type="file"
                id="avatar"
                name="avatar"
                accept="image/png,image/jpeg,image/webp"
                required
                >
                <small class="avatar-hint">PNG, JPG o WebP. Tamaño máximo: 2 MB.</small>
                <button type="submit" class="btn">Cambiar avatar</button>
            </form>

            <a href="logout.php" class="btn btn-danger">Cerrar sesión</a>

        </section>

    </main>

    <footer>
        <p>&copy; 2026 Deltahub. Todos los derechos reservados.</p>
    </footer>
</body>

</html>