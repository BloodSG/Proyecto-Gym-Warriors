<?php
//este archivo lo utilizo para agarrar las rutinas de la base de datos y las muestro en la miniventana para eligir cual editar
include 'libreria.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $conexion = conectaDB();
    $stmt = $conexion->query("SELECT id_rutina, nombre_rutina FROM rutinas ORDER BY nombre_rutina ASC");
    $rutinas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $rutinas
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>