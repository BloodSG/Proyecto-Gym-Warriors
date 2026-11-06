<?php
header('Content-Type: application/json; charset=utf-8');
require_once ("libreria.php");

function salidaForzosa(string $status, string $mensaje)
{
    echo json_encode([
        "status" => $status,
        "mensaje" => $mensaje
        ], JSON_UNESCAPED_UNICODE);
        exit;
}

try 
{
    $conexion=conectaDB();
}
catch (Exception $e)
{
    echo json_encode([
        "status" => "error",
        "mensaje" => "error al conectar a base de datos"
        ], JSON_UNESCAPED_UNICODE);
        exit;
}

$mensaje="No se ha validado nada";
$status="error";
$data="";

if($_GET["action"]== "cargar" && $_GET["comando"]=="semana")
{
        if(isset($_GET["dia"]))
        {   
            $lista_dias=["Lunes","Martes","Miercoles","Jueves","Viernes","Sabado","Domingo"];
            $dia=$_GET["dia"];
            $usuario="USER01";
            if(!in_array($dia,$lista_dias,false))
                {
                    $mensaje="EL dia no es valido";
                    $status="error";
                    salidaForzosa($status,$mensaje);
                }

            $query="SELECT DISTINCT rutina.id_rutina,rutina.nombre_rutina
                    FROM rutinas as rutina
                    JOIN detalle_rutinas as detalle
                    ON rutina.id_rutina=detalle.id_rutina
                    AND detalle.dia_semana=?
                    AND rutina.id_usuario=?";

            //aqui deberia de completarlo una vez que haya un usuario asignado, WHERE id_usuario=""
            $Puente=$conexion->prepare($query);
            $Puente->execute([$dia,$usuario]);
            $data=$Puente->fetch(PDO::FETCH_ASSOC);
            if(empty($data))
            {
                $status="Not Data";
                $mensaje="No hay rutinas para el día seleccionado";
                salidaForzosa($status,$mensaje);
            }
            $status="success";
            $mensaje="rutina del dia encontrada";
            
        }
}
elseif($_GET["action"]== "cargar" && $_GET["comando"]=="lista")
{
    if(isset($_GET["id"]))
    {
        $id_rutina=$_GET["id"];
        $query="SELECT id_detalle_rutina AS id_ejercicio,
                        nombre_ejercicio,
                        series,
                        repeticiones,
                        peso_sugerido,
                        tiempo_descanso,
                        video_url,
                        imagen_url,
                        orden
                FROM detalle_rutinas
                WHERE id_rutina=?";
        $Puente=$conexion->prepare($query);
        $Puente->execute([$id_rutina]);
        $data=$Puente->fetchAll(PDO::FETCH_ASSOC);

        if(empty($data))
        {
            $status="Not Data";
            $mensaje="No hay ejercicios para esta rutina";
            salidaForzosa($status,$mensaje);
        }
        $status="success";
        $mensaje="ejercicios listados corrctamente";
    }
}

echo json_encode([
    "status" => $status,
    "mensaje" => $mensaje,
    "data" => $data
    ], JSON_UNESCAPED_UNICODE);
exit;
?>