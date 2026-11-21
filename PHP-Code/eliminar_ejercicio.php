<?php
include 'libreria.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_detalle_rutina = $_POST['id_detalle_rutina'] ?? '';
    $id_rutina = $_POST['id_rutina'] ?? '';

    if (!empty($id_detalle_rutina)) {
        try {
            $conexion = conectaDB();
            
            //elimino el ejercicio de la rutina
            $stmt = $conexion->prepare("DELETE FROM detalle_rutinas WHERE id_detalle_rutina = ?");
            $stmt->execute([$id_detalle_rutina]);

        } catch (PDOException $e) {
            
        }
    }

    
    if (!empty($id_rutina)) {
        header("Location: ../HTML-Code/editar_rutina.html?id_rutina=" . $id_rutina);
    } else {
        header("Location: ../HTML-Code/vista.html#rutinas");
    }
    exit();
}
?>