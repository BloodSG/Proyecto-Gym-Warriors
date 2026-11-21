<?php

include 'libreria.php';
header('Content-Type: application/json');

try {
    //tomo de la base de datos el usuario que mostrare en la lista desplegable
    $conn = conectaDB();
    $stmt = $conn->query("SELECT id_usuario, nombre, apellido_1 FROM usuariocliente ORDER BY nombre ASC");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "status" => "success",
        "data" => $clientes
    ]);
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "mensaje" => $e->getMessage(),
        "data" => []
    ]);
}

?>