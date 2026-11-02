<?php

include 'libreria.php';
date_default_timezone_set('America/Mexico_City');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['cliente'] ?? null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;
    $fecha_fin = $_POST['fecha_fin'] ?? null;
    $metodo_pago = $_POST['metodo_pago'] ?? null;
    $monto_pago = $_POST['monto_pago'] ?? null;
    $observaciones = $_POST['observaciones'] ?? null;
    $id_membresia = $_POST['id_membresia'] ?? null;

    if($id_usuario && $fecha_inicio && $fecha_fin && $id_membresia) {
        
        try {
            $conn = conectaDB();

            //se checa que la fecha no sea pasada
            $hoy_num = intval(date('Ymd'));

            //forzamos que la fecha sea dia mes año
            $obj_fecha = DateTime::createFromFormat('d/m/Y', $fecha_inicio);

            if ($obj_fecha) {
                $fecha_inicio_num = intval($obj_fecha->format('Ymd'));
            } else {
                //por si acaso llega en otro formato de respaldo
                $fecha_inicio_num = intval(date('Ymd', strtotime(str_replace('/', '-', $fecha_inicio))));
            }

            if ($fecha_inicio_num < $hoy_num) {
                echo "<script>alert('Error: No puedes seleccionar una fecha de inicio que ya pasó.'); window.history.back();</script>";
                exit();
            }

            //checo si el cliente ya tiene una membresía activa
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM membresia_cliente WHERE id_usuario = :id_usu AND estado = 'Activo'");
            $stmtCheck->execute([':id_usu' => $id_usuario]);
            $tieneMembresiaActiva = $stmtCheck->fetchColumn();

            if ($tieneMembresiaActiva > 0) {
                echo "<script>alert('Error: Este cliente ya cuenta con una membresía activa y no puede adquirir otra por el momento.'); window.history.back();</script>";
                exit();
            }

            // Si pasa las validaciones se generael ID y guarda
            $stmtMax = $conn->query("SELECT id_membresia_cliente FROM membresia_cliente ORDER BY id_membresia_cliente DESC LIMIT 1");
            $ultimoRegistro = $stmtMax->fetch(PDO::FETCH_ASSOC);

            if($ultimoRegistro && isset($ultimoRegistro['id_membresia_cliente'])){
                $numero_actual = intval(str_replace('MC', '', $ultimoRegistro['id_membresia_cliente']));
                $siguiente_numero = $numero_actual + 1;
            } else {
                $siguiente_numero = 1;
            }

            $nuevo_id_cliente = 'MC' . str_pad($siguiente_numero, 3, '0', STR_PAD_LEFT);

            $sql = "INSERT INTO membresia_cliente (id_membresia_cliente, fecha_inicio, fecha_vencimiento, estado, id_membresia, id_usuario) 
                    VALUES (:id_cliente, :f_inicio, :f_fin, 'Activo', :id_membres, :id_usu)";

            $stmt = $conn->prepare($sql);
            $resultado = $stmt->execute([
                ':id_cliente' => $nuevo_id_cliente,
                ':f_inicio' => $fecha_inicio,
                ':f_fin' => $fecha_fin,
                ':id_membres' => $id_membresia,
                ':id_usu' => $id_usuario
            ]);

            if($resultado) {
                header("Location: ../HTML-Code/membrdispoadmin.html");
                exit();
            }
        } 
        catch(PDOException $e) {
            echo "Error al asignar la membresía: " . $e->getMessage();
        }

    } else {
        echo "Llene todos los campos del formulario";
    }
}