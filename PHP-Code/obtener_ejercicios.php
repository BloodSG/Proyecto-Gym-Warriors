<?php
//este archivo me ayuda a agarrar todos los ejercicios de la base y mostrarlos en la lista desplegable
include 'libreria.php';
header('Content-Type: application/json');

try {
    $conexion = conectaDB();
    
    //Consulta para traer todos los nombres únicos de ejercicios registrados en la BD
    $stmt = $conexion->query("SELECT DISTINCT nombre_ejercicio FROM detalle_rutinas ORDER BY nombre_ejercicio ASC");
    $ejercicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $ejercicios
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
?>