<?php
//este archivo es para editar el nombre de la rutina
include 'libreria.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_rutina = $_POST['id_rutina'] ?? '';
    $nombre_rutina = trim($_POST['nombre_rutina'] ?? '');

    if (empty($id_rutina) || empty($nombre_rutina)) {
        echo "<script>alert('Error: Faltan datos para actualizar el nombre.'); window.history.back();</script>";
        exit();
    }

    try {
        $conexion = conectaDB();

        //edito el nombre de la rutina en la base de datos
        $stmt = $conexion->prepare("UPDATE rutinas SET nombre_rutina = ? WHERE id_rutina = ?");
        $stmt->execute([$nombre_rutina, $id_rutina]);

        echo "<script>
                alert('¡Nombre de la rutina actualizado exitosamente!');
                window.location.href = '../HTML-Code/editar_rutina.html?id_rutina=" . $id_rutina . "';
              </script>";

    } catch (PDOException $e) {
        echo "<script>
                alert('Error al actualizar el nombre: " . addslashes($e->getMessage()) . "');
                window.location.href = '../HTML-Code/editar_rutina.html?id_rutina=" . $id_rutina . "';
              </script>";
    }
}
?>