<?php
require_once 'libreria.php';

header('Content-Type: application/json');

try{
    $conexion=conectaDB();
    if(isset($_GET["id_usuario"])){
        $id_usuario=$_GET["id_usuario"];

        $consulta="
                select
                    id_medida,
                    fecha_registro,
                    peso,
                    porcentaje_grasa,
                    porcentaje_musculo,
                    cintura,
                    cadera,
                    pecho,
                    brazo
                from medida_corporal
                where id_usuario = ?
                order by fecha_registro desc";

        $resultado=$conexion->prepare($consulta);
        $resultado->execute([$id_usuario]);

        $medidas=$resultado->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($medidas);

    }else{
        echo json_encode([
            "error"=>"No se recibió el cliente"
        ]);
    }
}catch(PDOException $e){
    echo json_encode([
        "error"=>"Error en la base de datos: ".$e->getMessage()
    ]);
}
?>