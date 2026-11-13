<?php
require_once "libreria.php";

$conexion=conectaDB();

if(isset($_GET['id'])){
    $id_rutina=$_GET['id'];
    try{
        $consulta="delete from rutinas
                   where id_rutina=?";

        $resultado=$conexion->prepare($consulta);
        $resultado->execute([$id_rutina]);
        
        if($resultado->rowCount()>0){
            echo json_encode([
                "estado"=>"exito",
                "mensaje"=>"Rutina eliminada correctamente"
            ]);
        }
        else{
            echo json_encode([
                "estado"=>"error",
                "mensaje"=>"No se encontró la rutina con el ID proporcionado"
            ]);
        }
    }
    catch(PDOException $e){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"No se puede eliminar esta rutina porque tiene información relacionada dentro del sistema."
        ]);
    }
}
?>