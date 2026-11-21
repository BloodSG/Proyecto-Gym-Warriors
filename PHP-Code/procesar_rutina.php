<?php
include 'libreria.php';
date_default_timezone_set('America/Mexico_City');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre_rutina = trim($_POST['nombre_rutina']);

    if (empty($nombre_rutina)) {
        echo "<script>alert('El nombre de la rutina es obligatorio.'); window.history.back();</script>";
        exit();
    }

    $conexion = conectaDB();

    try {
        //agarro el ultimo id que existe en la base de datos y creo la siguiente si no hay una la inicio desde 1
        $stmtMax = $conexion->query("SELECT id_rutina FROM rutinas ORDER BY id_rutina DESC LIMIT 1");
        $ultimo = $stmtMax->fetch(PDO::FETCH_ASSOC);

        if ($ultimo && isset($ultimo['id_rutina'])) {
            $num = intval(str_replace('RUT', '', $ultimo['id_rutina'])) + 1;
            $id_rutina = 'RUT' . str_pad($num, 2, '0', STR_PAD_LEFT);
        } else {
            $id_rutina = 'RUT01';
        }

        //los usuaruios fijos ya que la fokin base no acepta null
        $id_usuario = 'CLI001';
        $id_coach = 'EMP002';
        $fecha_creacion = date('Y-m-d');
        $estatus = 'activa';

        //creo la rutina en la tabla
        $query = "INSERT INTO rutinas (id_rutina, id_usuario, id_coach, nombre_rutina, fecha_creacion, estatus) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($query);
        $stmt->execute([$id_rutina, $id_usuario, $id_coach, $nombre_rutina, $fecha_creacion, $estatus]);

        //redirijo a la pagina para comenzar a agregarle ejercicios
        header("Location: ../HTML-Code/nueva_rutina.html?id_rutina=" . $id_rutina . "#ejercicios");
        exit(); 

    } catch (PDOException $e) {
        echo "<script>alert('Error al crear la rutina: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
}
?>