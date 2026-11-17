<?php
require_once "libreria.php";
 
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Mexico_City');
session_start();
 
// Verifica que el cliente haya iniciado sesion
if (empty($_SESSION['logueado']) || empty($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "mensaje" => "Debes iniciar sesión para continuar.",
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
 
function salidaForzosa(string $status, string $mensaje)
{
    echo json_encode([
        "status" => $status,
        "mensaje" => $mensaje,
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
 
try
{
    $conexion = conectaDB();
}
catch (Exception $e)
{
    salidaForzosa("error", "error al conectar a base de datos");
}
 
$usuario = $_SESSION['id_usuario'];
$accion = $_GET['action'] ?? $_POST['action'] ?? '';
$hoy = date('Y-m-d');
 
$status = "error";
$mensaje = "No se ha validado nada";
$data = [];
 
try
{
    if ($accion === 'guardar')
    {
        // Convierte el dia al formato de la base de datos
        $dias = [
            'lunes' => 'lunes', 'martes' => 'martes', 'miercoles' => 'miercoles',
            'miércoles' => 'miercoles', 'jueves' => 'jueves', 'viernes' => 'viernes',
            'sabado' => 'sabado', 'sábado' => 'sabado', 'domingo' => 'domingo'
        ];
        $dia_recibido = $_POST['dia'] ?? '';
        $dia = $dias[mb_strtolower(trim($dia_recibido), 'UTF-8')] ?? null;
 
        if ($dia === null)
        {
            salidaForzosa("error", "El día no es válido");
        }
 
        // Ejercicios que el cliente marco
        $marcados = $_POST['realizados'] ?? [];
        if (!is_array($marcados))
        {
            $marcados = [];
        }
 
        // Ejercicios de la rutina del cliente en ese dia
        $query = "SELECT detalle.id_detalle_rutina
                  FROM detalle_rutinas AS detalle
                  INNER JOIN rutinas AS rutina ON rutina.id_rutina = detalle.id_rutina
                  WHERE rutina.id_usuario = ? AND LOWER(detalle.dia_semana) = ?";
        $Puente = $conexion->prepare($query);
        $Puente->execute([$usuario, $dia]);
        $ejercicios = $Puente->fetchAll(PDO::FETCH_COLUMN);
 
        if (empty($ejercicios))
        {
            salidaForzosa("error", "No hay actividades para este día");
        }
 
        $guardar = $conexion->prepare("INSERT IGNORE INTO actividad_realizada
                                       (id_usuario, id_detalle_rutina, fecha)
                                       VALUES (?, ?, ?)");
        $quitar = $conexion->prepare("DELETE FROM actividad_realizada
                                      WHERE id_usuario = ? AND id_detalle_rutina = ? AND fecha = ?");
 
        $conexion->beginTransaction();
 
        $marcadosTexto = array_map('strval', $marcados);

        foreach ($ejercicios as $id_ejercicio)
        {
            if (in_array((string)$id_ejercicio, $marcadosTexto, true))
            {
                $guardar->execute([$usuario, $id_ejercicio, $hoy]);
            }
            else
            {
                $quitar->execute([$usuario, $id_ejercicio, $hoy]);
            }
        }
 
        $conexion->commit();
 
        $status = "success";
        $mensaje = "Avances guardados correctamente";
    }
    elseif ($accion === 'historial')
    {
        // completados = ejercicios marcados ese dia, total = ejercicios del dia en la rutina
        $query = "SELECT actividad.fecha,
                         rutina.nombre_rutina,
                         detalle.dia_semana,
                         COUNT(*) AS completados,
                         (SELECT COUNT(*)
                          FROM detalle_rutinas AS todos
                          WHERE todos.id_rutina = rutina.id_rutina
                            AND todos.dia_semana = detalle.dia_semana) AS total
                  FROM actividad_realizada AS actividad
                  INNER JOIN detalle_rutinas AS detalle ON detalle.id_detalle_rutina = actividad.id_detalle_rutina
                  INNER JOIN rutinas AS rutina ON rutina.id_rutina = detalle.id_rutina
                  WHERE actividad.id_usuario = ?
                  GROUP BY actividad.fecha, rutina.id_rutina, rutina.nombre_rutina, detalle.dia_semana
                  ORDER BY actividad.fecha DESC
                  LIMIT 30";
        $Puente = $conexion->prepare($query);
        $Puente->execute([$usuario]);
        $data = $Puente->fetchAll(PDO::FETCH_ASSOC);
 
        $status = "success";
        $mensaje = empty($data) ? "Todavía no tienes actividades registradas" : "Historial cargado correctamente";
    }
    else
    {
        $mensaje = "Acción no válida";
    }
}
catch (PDOException $e)
{
    if ($conexion->inTransaction())
    {
        $conexion->rollBack();
    }
    salidaForzosa("error", "No se pudo completar la operación");
}
 
echo json_encode([
    "status" => $status,
    "mensaje" => $mensaje,
    "data" => $data
], JSON_UNESCAPED_UNICODE);
exit;

