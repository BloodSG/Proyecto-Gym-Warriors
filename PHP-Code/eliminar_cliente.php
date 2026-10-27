<?php

require_once "libreria.php";

$conexion=conectaDB();

if(isset($_GET['id'])){
    $id_usuario=$_GET['id'];
    try{
        $consulta="delete from usuariocliente
                   where id_usuario=?";

        $resultado=$conexion->prepare($consulta);
        $resultado->execute([$id_usuario]);
        
        if($resultado->rowCount()>0){
            echo json_encode([
                "estado"=>"exito",
                "mensaje"=>"Cliente eliminado correctamente"
            ]);
        }
        else{
            echo json_encode([
                "estado"=>"error",
                "mensaje"=>"No se encontró el cliente"
            ]);
        }
    }
    catch(PDOException $e){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"No se puede eliminar este cliente porque tiene información relacionada dentro del sistema."
        ]);
    }
}
?>