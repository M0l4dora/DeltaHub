<?php
session_start();
require_once "config/database.php";

$id = (int)($_GET["id"] ?? 0);

// Buscar el item y su archivo
$stmt = $pdo->prepare("
    SELECT archivos.url_archivo
    FROM archivos
    WHERE archivos.item_id = :id
    ORDER BY archivos.fecha_subida DESC
    LIMIT 1
");
$stmt->execute([":id" => $id]);
$archivo = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($archivo)) {
    die("Este contenido no tiene archivos disponibles.");
}

// Sumar una descarga
$stmt = $pdo->prepare("UPDATE items SET descargas = descargas + 1 WHERE id = :id");
$stmt->execute([":id" => $id]);

header("Location: " . htmlspecialchars($archivo["url_archivo"]));
exit;