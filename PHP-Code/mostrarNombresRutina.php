<?php
require_once "libreria.php";

header("Content-Type: application/json; charset=utf-8");

try {
    $conexion = conectaDB();
    $consulta = $conexion->query("
        SELECT DISTINCT nombre_rutina
        FROM rutinas
        WHERE nombre_rutina IS NOT NULL
          AND nombre_rutina <> ''
        ORDER BY nombre_rutina
    ");

    echo json_encode($consulta->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["mensaje" => "No se pudieron cargar los nombres de rutina."]);
}

?>