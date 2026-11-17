<?php
include 'libreria.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_rutina = $_POST['id_rutina'] ?? '';
    $id_detalle_rutina = $_POST['id_detalle_rutina'] ?? '';

    if (empty($id_detalle_rutina) || empty($id_rutina)) {
        echo "<script>alert('Error: El video que intentaste subir es demasiado pesado para el servidor. Por favor, elige un archivo más ligero.'); window.history.back();</script>";
        exit();
    }

    $nombre_ejercicio = trim($_POST['nombre_ejercicio'] ?? '');
    $series = trim($_POST['series']);
    $repeticiones = trim($_POST['repeticiones']);
    $peso_sugerido = trim($_POST['peso_sugerido']);
    $tiempo_descanso = trim($_POST['tiempo_descanso']);
    $opc_evidencia = trim($_POST['opc_evidencia']);
    $url_ingresada = trim($_POST['video_url'] ?? '');
    
    $dias_seleccionados = $_POST['dia_semana'] ?? ['Lunes'];
    $dia_semana = ucfirst(strtolower($dias_seleccionados[0]));

    try {
        $conexion = conectaDB();

        $stmtOld = $conexion->prepare("SELECT video_url FROM detalle_rutinas WHERE id_detalle_rutina = ?");
        $stmtOld->execute([$id_detalle_rutina]);
        $registroOld = $stmtOld->fetch(PDO::FETCH_ASSOC);
        $video_final_url = $registroOld['video_url'] ?? '';

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

                    $nuevo_path = '../Recursos/res_rutina/' . time() . '_' . $nombre_original;
                    if (move_uploaded_file($nombre_temporal, $nuevo_path)) {
                        $video_final_url = $nuevo_path;
                    }
                } elseif ($error_subida === UPLOAD_ERR_INI_SIZE || $error_subida === UPLOAD_ERR_FORM_SIZE) {
                    echo "<script>alert('Error: El video seleccionado es demasiado pesado y supera el límite permitido por el servidor.'); window.history.back();</script>";
                    exit();
                } elseif ($error_subida !== UPLOAD_ERR_NO_FILE) {
                    echo "<script>alert('Error al subir el archivo de video. Código de error: " . $error_subida . "'); window.history.back();</script>";
                    exit();
                }
            }
        } elseif ($opc_evidencia === 'aun_no') {
            $video_final_url = ''; 
        }

        $query = "UPDATE detalle_rutinas 
                  SET nombre_ejercicio = ?, dia_semana = ?, series = ?, repeticiones = ?, peso_sugerido = ?, tiempo_descanso = ?, video_url = ? 
                  WHERE id_detalle_rutina = ?";
        
        $stmt = $conexion->prepare($query);
        $stmt->execute([
            $nombre_ejercicio, 
            $dia_semana, 
            $series, 
            $repeticiones, 
            $peso_sugerido, 
            $tiempo_descanso, 
            $video_final_url, 
            $id_detalle_rutina
        ]);

        echo "<script>
                alert('¡Ejercicio actualizado correctamente!');
                window.location.href = '../HTML-Code/editar_rutina.html?id_rutina=" . $id_rutina . "';
              </script>";

    } catch (PDOException $e) {
        echo "<script>
                alert('Error al actualizar el ejercicio: " . addslashes($e->getMessage()) . "');
                window.location.href = '../HTML-Code/editar_rutina.html?id_rutina=" . $id_rutina . "';
              </script>";
    }
}
?>