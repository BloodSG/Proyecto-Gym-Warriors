<?php
include 'libreria.php';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    //altoooooo, esto no se modifica hasta que el jodido constraint se quite
    $id_rutina = 'RUT01'; 
    
    $nombre_ejercicio = trim($_POST['nombre_ejercicio']);
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

    //veo si dio url del video en la caja de texto o sibio el video desde el dispositivo
    $video_final_url = '';

    if ($opc_evidencia === 'URL') {
        $video_final_url = $url_ingresada;
        //si hay una url en la caja se guarda la url en la base de datos

    } elseif ($opc_evidencia === 'Video_disp') {
        if (isset($_FILES['video_archivo']) && $_FILES['video_archivo']['error'] === UPLOAD_ERR_OK) {
            $nombre_temporal = $_FILES['video_archivo']['tmp_name'];
            $nombre_original = basename($_FILES['video_archivo']['name']);
            //si no checa la url de el video en el dispositivo y lo clona en la carpeta que ocupanos
            $carpeta_destino = '../Recursos/res_rutina/';
            
            if (!is_dir($carpeta_destino)) {
                mkdir($carpeta_destino, 0777, true);
            }

            $video_final_url = '../Recursos/res_rutina/' . time() . '_' . $nombre_original;
            $ruta_fisica = $video_final_url;

            move_uploaded_file($nombre_temporal, $ruta_fisica);

        }
    }

    $conexion = conectaDB();

    try {
        //veo que id esta en la base de datos para segir incrementando
        $stmtMax = $conexion->query("SELECT id_detalle_rutina FROM detalle_rutinas ORDER BY id_detalle_rutina DESC LIMIT 1");
        $ultimoRegistro = $stmtMax->fetch(PDO::FETCH_ASSOC);

        if ($ultimoRegistro && isset($ultimoRegistro['id_detalle_rutina'])) {
            $numero_actual = intval(str_replace('DET', '', $ultimoRegistro['id_detalle_rutina']));
            $siguiente_numero = $numero_actual + 1;
        } else {
            //en caso de no encontrar ids lo inicio desde 1
            $siguiente_numero = 1;
        }

        //aqui es lo mismo para el orden 
        $stmtOrden = $conexion->prepare("SELECT MAX(orden) as max_orden FROM detalle_rutinas WHERE id_rutina = ?");
        $stmtOrden->execute([$id_rutina]);
        $resOrden = $stmtOrden->fetch(PDO::FETCH_ASSOC);
        $siguiente_orden = ($resOrden && $resOrden['max_orden'] !== null) ? intval($resOrden['max_orden']) + 1 : 1;

        //guardo todos los detalles de la rutina en la base de datos
        $query = "INSERT INTO detalle_rutinas (id_detalle_rutina, id_rutina, dia_semana, nombre_ejercicio, series, repeticiones, peso_sugerido, tiempo_descanso, video_url, orden) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($query);

        //como los dias de la semana lo hicieron en chingaos checkboxes tuve que hacer que se guardaran uno por uno segun cuantos dias pusieron
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
                alert('¡Ejercicio guardado exitosamente en el detalle de la rutina!');
                window.location.href = '../HTML-Code/nueva_rutina.html';
              </script>";
              
    } catch (PDOException $e) {
        echo "<script>
                alert('Error al guardar el ejercicio: " . addslashes($e->getMessage()) . "');
                window.location.href = '../HTML-Code/nueva_rutina.html';
              </script>";
    }
}
?>