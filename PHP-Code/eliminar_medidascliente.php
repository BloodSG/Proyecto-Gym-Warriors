<?php
require_once 'libreria.php';

header('Content-Type: application/json');

try{
    $conexion=conectaDB();

    $id_medida=$_POST["id_medida"];
    $consulta="
            delete from medida_corporal
            where id_medida=?";

    $resultado=$conexion->prepare($consulta);
    $resultado->execute([$id_medida]);

    echo json_encode([
        "estado"=>true,
        "mensaje"=>"Medidas eliminadas correctamente."
    ]);

}catch(PDOException $e){
    echo json_encode([
        "estado"=>false,
        "mensaje"=>$e->getMessage()
    ]);
}
?>