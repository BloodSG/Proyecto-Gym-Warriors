<?php

include 'libreria.php';
date_default_timezone_set('America/Mexico_City');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['cliente'] ?? null;
    $fecha_creacion = $_POST['fecha_creacion'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $coach = $_POST['coach'] ?? null;
    $nombre_rutina = $_POST['nombre'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;
    

    if(
        $id_usuario && $fecha_creacion && $fecha_fin && $coach && $nombre_rutina) {
        
        try {
            $conn = conectaDB();

            //se checa que la fecha no sea pasada
            $fechaInicio = DateTime::createFromFormat('Y-m-d', $fecha_creacion);
            $fechaFin = DateTime::createFromFormat('Y-m-d', $fecha_fin);
            $hoy = new DateTime('today');

            if (!$fechaInicio || !$fechaFin) {
                echo "<script>alert('Ingresa fechas válidas.'); window.history.back();</script>";
                exit();
            }

            if ($fechaInicio < $hoy) {
                echo "<script>alert('La fecha de inicio no puede estar en el pasado.'); window.history.back();</script>";
                exit();
            }

            if ($fechaFin <= $fechaInicio) {
                echo "<script>alert('La fecha de término debe ser posterior a la de inicio.'); window.history.back();</script>";
                exit();
            }

            //checo si el cliente ya tiene una membresía activa
            $stmtCheck = $conn->prepare("SELECT 1 FROM rutinas WHERE id_usuario = :id_usu AND LOWER(estatus) = 'activa' AND fecha_creacion <= NOW() AND (fecha_fin IS NULL OR fecha_fin >= CURDATE())LIMIT 1");
            $stmtCheck->execute([':id_usu' => $id_usuario]);
            $tieneMembresiaActiva = $stmtCheck->fetchColumn();

            if ($tieneMembresiaActiva > 0) {
                echo "<script>alert('Error: Este cliente ya cuenta con una rutina activa y vigente, no puede adquirir otra por el momento.'); window.history.back();</script>";
                exit();
            }

            // Si pasa las validaciones se generael ID y guarda
            $stmtMax = $conn->query("SELECT id_rutina FROM rutinas ORDER BY id_rutina DESC LIMIT 1");
            $ultimoRegistro = $stmtMax->fetch(PDO::FETCH_ASSOC);

            if($ultimoRegistro && isset($ultimoRegistro['id_rutina'])){
                $numero_actual = intval(str_replace('R', '', $ultimoRegistro['id_rutina']));
                $siguiente_numero = $numero_actual + 1;
            } else {
                $siguiente_numero = 1;
            }

            $nuevo_id_rutina = 'R' . str_pad($siguiente_numero, 3, '0', STR_PAD_LEFT);

            $sql = "INSERT INTO rutinas (id_rutina, fecha_creacion, fecha_fin, estatus, id_usuario, id_coach, nombre_rutina, observaciones) 
                    VALUES (:id_rutina, :f_creacion, :f_fin, 'activa', :id_usu, :id_coach, :nombre_rutina, :observaciones)";

            $stmt = $conn->prepare($sql);
            $resultado = $stmt->execute([
                ':id_rutina' => $nuevo_id_rutina,
                ':f_creacion' => $fecha_creacion,
                ':f_fin' => $fecha_fin,
                ':id_usu' => $id_usuario,
                ':id_coach' => $coach,
                ':nombre_rutina' => $nombre_rutina,
                ':observaciones' => $observaciones
            ]);

            if($resultado) {
                header("Location: ../HTML-Code/altarutina.html");
                exit();
            }
        } 
        catch(PDOException $e) {
            echo "Error al asignar la rutina: " . $e->getMessage();
        }

    } else {
        echo "Llene todos los campos del formulario";
    }
}