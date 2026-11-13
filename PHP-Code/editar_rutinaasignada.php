<?php
require_once "libreria.php";

header("Content-Type: application/json");

$conexion = conectaDB();

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $id_rutina=$_POST["id_rutina"];
    $id_coach=$_POST["id_coach"];
    $nombre_rutina=$_POST["nombre_rutina"];
    $fecha_creacion=$_POST["fecha_creacion"];
    $fecha_fin=$_POST["fecha_fin"];
    $observaciones=$_POST["observaciones"];

    try{
        $consulta="
            update rutinas
            set
                id_coach=?,
                nombre_rutina=?,
                fecha_creacion=?,
                fecha_fin=?,
                observaciones=?
            where id_rutina=?";

        $resultado=$conexion->prepare($consulta);

        $resultado->execute([$id_coach,
                             $nombre_rutina,
                             $fecha_creacion,
                             $fecha_fin,
                             $observaciones,
                             $id_rutina]);

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