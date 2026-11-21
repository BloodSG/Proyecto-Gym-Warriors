<?php
//este archivo me ayuda a agarrar los ejercicios que estan en la base de datos y asi mostrarlos en la interfaz en la tabla de ejercicios
include 'libreria.php';
header('Content-Type: application/json');

$id_rutina = $_GET['id_rutina'] ?? '';

if (empty($id_rutina)) {
    echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado']);
    exit();
}

try {
    $conexion = conectaDB();

    //Obtener el nombre de la rutina de la tabla rutinas
    $stmtR = $conexion->prepare("SELECT nombre_rutina FROM rutinas WHERE id_rutina = ?");
    $stmtR->execute([$id_rutina]);
    $rutina = $stmtR->fetch(PDO::FETCH_ASSOC);

    //Obtener los ejercicios vinculados a esta rutina en detalle_rutinas
    $stmtE = $conexion->prepare("SELECT * FROM detalle_rutinas WHERE id_rutina = ? ORDER BY orden ASC");
    $stmtE->execute([$id_rutina]);
    $ejercicios = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    //Devolver todo en formato JSON limpio
    echo json_encode([
        'status' => 'success',
        'rutina' => $rutina,
        'ejercicios' => $ejercicios
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>