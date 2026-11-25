<?php
session_start();

require_once "libreria.php";

header('Content-Type: application/json');

try{
    if(!isset($_SESSION["id_usuario"])){
        echo json_encode([
            "estado"=>false,
            "mensaje"=>"Sesión no válida"
        ]);
        exit();
    }

    $id_usuario=$_SESSION["id_usuario"];

    $conexion=conectaDB();

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
            where id_usuario=?
            order by fecha_registro desc";

    $resultado=$conexion->prepare($consulta);
    $resultado->execute([$id_usuario]);
    $mismedidas=$resultado->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "estado"=>true,
        "medidas"=>$mismedidas
    ]);


}catch(PDOException $e){
    echo json_encode([
        "estado"=>false,
        "mensaje"=>$e->getMessage()
    ]);
}
?>