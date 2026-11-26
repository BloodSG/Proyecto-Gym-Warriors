<?php
require_once "libreria.php";

header("Content-Type: application/json");

$conexion = conectaDB();

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $id_asignacion=$_POST["id_asignacion"];
    $id_coach=$_POST["id_coach"];
    $fecha_inicio=$_POST["fecha_inicio"];
    $fecha_fin=$_POST["fecha_fin"];
    $observaciones=$_POST["observaciones"];

    try{
        $consulta="
            update asignacion_rutinas
            set
                id_coach=?,
                fecha_inicio=?,
                fecha_fin=?,
                observaciones=?
            where id_asignacion=?";

        $resultado=$conexion->prepare($consulta);

        $resultado->execute([$id_coach,
                             $fecha_inicio,
                             $fecha_fin,
                             $observaciones,
                             $id_asignacion]);

        echo json_encode([
            "estado"=>"exito",
            "mensaje"=>"Rutina actualizada correctamente"
        ]);
    }catch(PDOException $e){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"Error al actualizar rutina: ".$e->getMessage()
        ]);
    }

}else{
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"Método no permitido"
    ]);
}
?>