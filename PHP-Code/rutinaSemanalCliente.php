<?php
require_once __DIR__ . '/libreria.php';

//Inicia la sesion para identificar al usuario
session_start();

//Verifica que el usuario haya iniciado sesion
if (empty($_SESSION['logueado']) || empty($_SESSION['id_usuario'])) {
    //Redirige al login cuando se solicita la página desde el navegador osea no lo va dejar entrar a menos de q haya iniciado sesion xd
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'text/html') !== false) {
        echo '<script>
            alert("Debes iniciar sesión para consultar tu rutina.");
            window.location.href = "../HTML-Code/loginView.html";
        </script>';
        exit;
    }

    //Devuelve un error cuando la solicitud es de tipo API
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "mensaje" => "Debes iniciar sesión para continuar."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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
            //Obtiene el usuario actual desde la sesion
            $usuario=$_SESSION['id_usuario'];
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

            //Filtra la rutina por el usuario actual
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
        //Consulta ejercicios unicamente de la rutina del usuario actual
        $query="SELECT detalle_rutinas.id_detalle_rutina AS id_ejercicio,
                        nombre_ejercicio,
                        series,
                        repeticiones,
                        peso_sugerido,
                        tiempo_descanso,
                        video_url,
                        imagen_url,
                        orden
                FROM detalle_rutinas
                INNER JOIN rutinas ON rutinas.id_rutina=detalle_rutinas.id_rutina
                WHERE detalle_rutinas.id_rutina=? AND rutinas.id_usuario=?";
        $Puente=$conexion->prepare($query);
        //Envia el ID de la rutina y el usuario como parámetros :v
        $Puente->execute([$id_rutina, $_SESSION['id_usuario']]);
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
