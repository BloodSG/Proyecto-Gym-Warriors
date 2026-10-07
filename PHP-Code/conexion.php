<?php

$servidor = "DESKTOP-C8INOU3\\SQLEXPRESS"; 
$baseDatos = "Gym_warriors";
$usuario = "sa";
$contrasena = "";

try {
    $conexion = new PDO(
        "sqlsrv:Server=$servidor;Database=$baseDatos;TrustServerCertificate=1",
        $usuario,
        $contrasena
    );

    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    die("Error de conexion: " . $error->getMessage());
}
?>
