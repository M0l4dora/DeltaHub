<?php

session_start();

require_once "config/database.php";


// Comprobar sesión

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit;
}


// Comprobar que haya un archivo

if (!isset($_FILES["avatar"]) || $_FILES["avatar"]["error"] !== UPLOAD_ERR_OK) {
    die("No se pudo subir la imagen.");
}


$archivo = $_FILES["avatar"];


// Tamaño máximo: 2 MB

$maximo = 2 * 1024 * 1024;

if ($archivo["size"] > $maximo) {
    die("La imagen no puede superar los 2 MB.");
}


// Comprobar que realmente sea una imagen

$info = getimagesize($archivo["tmp_name"]);

if ($info === false) {
    die("El archivo seleccionado no es una imagen válida.");
}


// Obtener MIME real

$mime = $info["mime"];

$formatos_permitidos = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];


if (!isset($formatos_permitidos[$mime])) {
    die("Formato de imagen no permitido.");
}


// Crear nombre único

$extension = $formatos_permitidos[$mime];

$nombre_archivo = "avatar_" . $_SESSION["usuario_id"] . "_" . time() . "." . $extension;


// Ruta física donde se guardará

$carpeta = __DIR__ . "/uploads/avatars/";

$ruta_fisica = $carpeta . $nombre_archivo;


// Comprobar que exista la carpeta

if (!is_dir($carpeta)) {
    die("La carpeta de avatares no existe.");
}


// Mover archivo

if (!move_uploaded_file($archivo["tmp_name"], $ruta_fisica)) {
    die("No se pudo guardar la imagen.");
}


// Ruta que guardaremos en la base de datos

$avatar_url = "uploads/avatars/" . $nombre_archivo;


// Actualizar usuario

$sql = "UPDATE usuarios
        SET avatar_url = :avatar_url
        WHERE id = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":avatar_url" => $avatar_url,
    ":id" => $_SESSION["usuario_id"]
]);


// Volver a la cuenta

header("Location: cuenta.php");
exit;

?>