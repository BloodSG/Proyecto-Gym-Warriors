<?php
include 'libreria.php';
//este archivo lo uso para crear los ejercicios que tendra la rutina

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_rutina = $_POST['id_rutina'] ?? $_GET['id_rutina'] ?? '';
    $origen = $_POST['origen'] ?? $_GET['origen'] ?? '';

    if (empty($id_rutina)) {
        echo "<script>alert('Error: El video que intentaste subir es demasiado pesado para el servidor. Por favor, elige un archivo más ligero.'); window.history.back();</script>";
        exit();
    }
    
    $id_ejercicio = trim($_POST['id_ejercicio'] ?? '');
    $nombre_nuevo = trim($_POST['nombre_ejercicio'] ?? '');
    $nombre_ejercicio = ($id_ejercicio === 'nuevo') ? $nombre_nuevo : $id_ejercicio;

    if (empty($nombre_ejercicio)) {
        echo "<script>alert('Por favor selecciona o ingresa el nombre del ejercicio.'); window.history.back();</script>";
        exit();
    }

    $series = trim($_POST['series']);
    $repeticiones = trim($_POST['repeticiones']);
    $peso_sugerido = trim($_POST['peso_sugerido']);
    $tiempo_descanso = trim($_POST['tiempo_descanso']);
    $opc_evidencia = trim($_POST['opc_evidencia']);
    $url_ingresada = trim($_POST['video_url']);
    
    $dias_seleccionados = $_POST['dia_semana'] ?? [];

    if (empty($dias_seleccionados)) {
        echo "<script>alert('Por favor selecciona al menos un día de la semana.'); window.history.back();</script>";
        exit();
    }

    $video_final_url = '';

    if ($opc_evidencia === 'URL') {
        $video_final_url = $url_ingresada;
    } elseif ($opc_evidencia === 'Video_disp') {
        if (isset($_FILES['video_archivo'])) {
            $error_subida = $_FILES['video_archivo']['error'];
            
            if ($error_subida === UPLOAD_ERR_OK) {
                $nombre_temporal = $_FILES['video_archivo']['tmp_name'];
                $nombre_original = basename($_FILES['video_archivo']['name']);
                $carpeta_destino = '../Recursos/res_rutina/';
                
                if (!is_dir($carpeta_destino)) {
                    mkdir($carpeta_destino, 0777, true);
                }

                $video_final_url = '../Recursos/res_rutina/' . time() . '_' . $nombre_original;
                move_uploaded_file($nombre_temporal, $video_final_url);
            } elseif ($error_subida === UPLOAD_ERR_INI_SIZE || $error_subida === UPLOAD_ERR_FORM_SIZE) {
                echo "<script>alert('Error: El video seleccionado es demasiado pesado y supera el límite permitido por el servidor.'); window.history.back();</script>";
                exit();
            } elseif ($error_subida !== UPLOAD_ERR_NO_FILE) {
                echo "<script>alert('Error al subir el archivo de video. Código de error: " . $error_subida . "'); window.history.back();</script>";
                exit();
            }
        }
    }

    if ($origen === 'editar') {
        $url_destino = '../HTML-Code/editar_rutina.html?id_rutina=' . $id_rutina;
    } else {
        $url_destino = '../HTML-Code/nueva_rutina.html?id_rutina=' . $id_rutina . '#ejercicios';
    }

    $conexion = conectaDB();

    try {
        $stmtMax = $conexion->query("SELECT id_detalle_rutina FROM detalle_rutinas ORDER BY id_detalle_rutina DESC LIMIT 1");
        $ultimoRegistro = $stmtMax->fetch(PDO::FETCH_ASSOC);

        $siguiente_numero = ($ultimoRegistro && isset($ultimoRegistro['id_detalle_rutina'])) 
            ? intval(str_replace('DET', '', $ultimoRegistro['id_detalle_rutina'])) + 1 
            : 1;

        $stmtOrden = $conexion->prepare("SELECT MAX(orden) as max_orden FROM detalle_rutinas WHERE id_rutina = ?");
        $stmtOrden->execute([$id_rutina]);
        $resOrden = $stmtOrden->fetch(PDO::FETCH_ASSOC);
        $siguiente_orden = ($resOrden && $resOrden['max_orden'] !== null) ? intval($resOrden['max_orden']) + 1 : 1;

        $query = "INSERT INTO detalle_rutinas (id_detalle_rutina, id_rutina, dia_semana, nombre_ejercicio, series, repeticiones, peso_sugerido, tiempo_descanso, video_url, orden) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($query);

        foreach ($dias_seleccionados as $index => $dia) {
            $id_actual = 'DET' . str_pad($siguiente_numero + $index, 2, '0', STR_PAD_LEFT);
            $orden_actual = $siguiente_orden + $index;

            $stmt->execute([
                $id_actual,
                $id_rutina, 
                ucfirst(strtolower($dia)),
                $nombre_ejercicio, 
                $series, 
                $repeticiones, 
                $peso_sugerido, 
                $tiempo_descanso, 
                $video_final_url,
                $orden_actual
            ]);
        }

        echo "<script>
                alert('¡Ejercicio guardado exitosamente!');
                window.location.href = '" . $url_destino . "';
              </script>";
              
    } catch (PDOException $e) {
        echo "<script>
                alert('Error en base de datos: " . addslashes($e->getMessage()) . "');
                window.location.href = '" . $url_destino . "';
              </script>";
    }
}
?>