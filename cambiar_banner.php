<?php

session_start();

require_once "config/database.php";


// Comprobar sesión

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit;
}


// Volver a la cuenta con un aviso. cambiar_pfp.php usa el mismo canal, así
// que los dos accesos de imagen fallan siempre en la misma pantalla y no en
// una página en blanco.

function volver_a_cuenta($aviso = "") {
    $destino = "cuenta.php";

    if ($aviso !== "") {
        $destino .= "?aviso=" . rawurlencode($aviso);
    }

    header("Location: " . $destino);
    exit;
}


// `usuarios.banner_url` viene de una migración (ver
// sql/migracion_banner_usuario.sql). Se pregunta al esquema en vez de darla por
// sentada para que una base que todavía no la tenga no se rompa.

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


// `usuarios.banner_url` se agregó con sql/migracion_banner_usuario.sql. Sin la
// columna no hay dónde guardar la ruta: se corta antes de tocar el disco.

if (!columna_existe($pdo, "usuarios", "banner_url")) {
    volver_a_cuenta("columna");
}


// Comprobar que haya un archivo

if (!isset($_FILES["banner"]) || $_FILES["banner"]["error"] !== UPLOAD_ERR_OK) {
    volver_a_cuenta("sin_archivo");
}


$archivo = $_FILES["banner"];


// Tamaño máximo: 4 MB (el banner es más ancho que el avatar, entra menos
// detalle por pixel y no necesita pesar más que el resto de las imágenes)

$maximo = 4 * 1024 * 1024;

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

$nombre_archivo = "banner_" . $_SESSION["usuario_id"] . "_" . time() . "." . $extension;


// Ruta física donde se guardará

$carpeta = __DIR__ . "/uploads/banners/";

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

$banner_url = "uploads/banners/" . $nombre_archivo;


// Actualizar usuario

$sql = "UPDATE usuarios
        SET banner_url = :banner_url
        WHERE id = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":banner_url" => $banner_url,
    ":id" => $_SESSION["usuario_id"]
]);


// Volver a la cuenta

volver_a_cuenta("banner_ok");

?>
