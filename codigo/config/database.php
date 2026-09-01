<?php

$host = "localhost";
$dbname = "deltahub";
$username = "root";
$password = "";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Conexión a la base de datos exitosa.";

} catch (PDOException $e) {

    die("Error de conexión: " . $e->getMessage());

}

?>