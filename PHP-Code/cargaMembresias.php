<?php
header('Content-Type: application/json; charset=utf-8');
require_once("libreria.php");
if($_GET['action'] == 'cargar' && $_GET['comando'] == 'membresias' )
{
    try
    {
        $conexion=conectaDB();

        $query="
        SELECT *
        FROM membresia";

        $puente=$conexion -> prepare($query);
        $puente  -> execute();
        $membresias=$puente->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([ 
                            "status" => "success",
                            "mensaje" => "listas cargadas correctamente",
                            "data" => $membresias
                        ], JSON_UNESCAPED_UNICODE);
    }
    catch (Exception $e )
    {
        echo json_encode([ 
                    "status" => "error",
                    "mensaje" => "erro al cargar las membresias disponibles".$e ,
                    ], JSON_UNESCAPED_UNICODE);
    }
}

?>