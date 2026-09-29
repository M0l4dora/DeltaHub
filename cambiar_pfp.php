<?php

session_start();

require_once "config/database.php";


// Volver a la cuenta con un aviso: es el mismo canal que usa cambiar_banner.php,
// así que un avatar o un banner que no se puedan subir terminan en la misma
// pantalla en vez de en una página en blanco.

function volver_a_cuenta($aviso = "") {
    $destino = "cuenta.php";

    if ($aviso !== "") {
        $destino .= "?aviso=" . rawurlencode($aviso);
    }

    header("Location: " . $destino);
    exit;
}


// Comprobar sesión

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit;
}


// Comprobar que haya un archivo

if (!isset($_FILES["avatar"]) || $_FILES["avatar"]["error"] !== UPLOAD_ERR_OK) {
    volver_a_cuenta("sin_archivo");
}


$archivo = $_FILES["avatar"];


// Tamaño máximo: 2 MB

$maximo = 2 * 1024 * 1024;

if ($archivo["size"] > $maximo) {
    volver_a_cuenta("tamano");
}


// Comprobar que realmente sea una imagen

$info = getimagesize($archivo["tmp_name"]);

if ($info === false) {
    volver_a_cuenta("no_imagen");
}


// Obtener MIME real

$mime = $info["mime"];

$formatos_permitidos = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];


if (!isset($formatos_permitidos[$mime])) {
    volver_a_cuenta("formato");
}


// Crear nombre único

$extension = $formatos_permitidos[$mime];

$nombre_archivo = "avatar_" . $_SESSION["usuario_id"] . "_" . time() . "." . $extension;


// Ruta física donde se guardará

$carpeta = __DIR__ . "/uploads/avatars/";

$ruta_fisica = $carpeta . $nombre_archivo;


// La carpeta puede no existir todavía en una instalación nueva

if (!is_dir($carpeta)) {
    mkdir($carpeta, 0755, true);
}


// Mover archivo

if (!move_uploaded_file($archivo["tmp_name"], $ruta_fisica)) {
    volver_a_cuenta("guardado");
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

volver_a_cuenta("avatar_ok");

?>
