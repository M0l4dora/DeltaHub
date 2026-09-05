<?php

session_start();

require_once "config/database.php";


// Comprobar que haya una sesión iniciada

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit;
}

// SEGURIDAD: comprobar que haya una sesión iniciada

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit;
}



// Obtener el ID del usuario

$usuario_id = $_SESSION["usuario_id"];


// Buscar los datos del usuario

$sql = "SELECT id, nombre_usuario, email, fecha_registro, rol, avatar_url
        FROM usuarios
        WHERE id = :id
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":id" => $usuario_id
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($usuario["avatar_url"])) {
    $usuario["avatar_url"] = "images/default-avatar.png";
}


// Si el usuario ya no existe en la base de datos

if (!$usuario) {
    session_unset();
    session_destroy();

    header("Location: login.html");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Mi cuenta - DeltaHub</title>

</head>

<body>

<h1>Mi cuenta</h1>

    <?php if (!empty($usuario["avatar_url"])): ?>

    <img
    src="<?php echo htmlspecialchars($usuario["avatar_url"]); ?>"
    alt="Avatar"
    width="150"
    >

    <form action="cambiar_pfp.php" method="POST" enctype="multipart/form-data">

        <label for="avatar">Cambiar avatar:</label>

        <input
        type="file"
        id="avatar"
        name="avatar"
        accept="image/png,image/jpeg,image/webp"
        required
        >

        <button type="submit">
        Cambiar avatar
        </button>

    </form>

    

    <?php else: ?>

    <p>Sin avatar</p>

    <?php endif; ?>


    <h2>
    <?php echo htmlspecialchars($usuario["nombre_usuario"]); ?>
    </h2>


    <p>
    <strong>Email:</strong>
    <?php echo htmlspecialchars($usuario["email"]); ?>
    </p>


    <p>
    <strong>Miembro desde:</strong>
    <?php echo htmlspecialchars($usuario["fecha_registro"]); ?>
    </p>


    <p>
    <strong>Rol:</strong>
    <?php echo htmlspecialchars($usuario["rol"]); ?>
    </p>


    <a href="logout.php">Cerrar sesión</a>

</body>

</html>