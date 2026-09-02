<?php

session_start();

require_once "config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Acceso no válido.");
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";


// 1. Comprobar campos

if ($email === "" || $password === "") {
    die("Completá todos los campos.");
}


// 2. Buscar el usuario por email

$sql = "SELECT id, nombre_usuario, email, password_hash, rol
        FROM usuarios
        WHERE email = :email
        LIMIT 1";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":email" => $email
]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


// 3. Comprobar usuario y contraseña

if (!$usuario || !password_verify($password, $usuario["password_hash"])) {
    die("Email o contraseña incorrectos.");
}


// 4. Crear la sesión

$_SESSION["usuario_id"] = $usuario["id"];
$_SESSION["nombre_usuario"] = $usuario["nombre_usuario"];
$_SESSION["email"] = $usuario["email"];
$_SESSION["rol"] = $usuario["rol"];


// 5. Login exitoso

header("Location: index.php");
exit;

?>