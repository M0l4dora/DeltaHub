<?php

require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no válido.");
}

$nombre_usuario = trim($_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirm = $_POST["confirm"] ?? "";


// 1. Comprobar que los campos estén completos

if ($nombre_usuario === "" || $email === "" || $password === "" || $confirm === "") {
    die("Todos los campos son obligatorios.");
}


// 2. Comprobar que las contraseñas coincidan

if ($password !== $confirm) {
    die("Las contraseñas no coinciden.");
}


// 3. Comprobar longitud de contraseña

if (strlen($password) < 4) {
    die("La contraseña debe tener al menos 4 caracteres.");
}


// 4. Comprobar si el usuario o email ya existen

$sql = "SELECT id FROM usuarios
        WHERE nombre_usuario = :nombre_usuario
        OR email = :email
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":nombre_usuario" => $nombre_usuario,
    ":email" => $email
]);

if ($stmt->fetch()) {
    die("El usuario o email ya están registrados.");
}


// 5. Crear el hash de la contraseña

$password_hash = password_hash($password, PASSWORD_DEFAULT);


// 6. Guardar el usuario

$sql = "INSERT INTO usuarios
        (nombre_usuario, email, password_hash, avatar_url)
        VALUES
        (:nombre_usuario, :email, :password_hash, :avatar_url)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":nombre_usuario" => $nombre_usuario,
    ":email" => $email,
    ":password_hash" => $password_hash,
    ":avatar_url" => "Uploads/Avatars/default.jpg"
]);


// 7. Registro exitoso

header("Location: login.html");
exit;

?>