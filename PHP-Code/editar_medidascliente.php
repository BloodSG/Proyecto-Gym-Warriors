<?php
require_once 'libreria.php';

header('Content-Type: application/json');

try{
    $conexion=conectaDB();

    $id_medida=$_POST["id_medida"];
    $fecha_registro=$_POST["fecha_registro"];
    $peso=$_POST["peso"];
    $porcentaje_grasa=empty($_POST["porcentaje_grasa"]) ? null : $_POST["porcentaje_grasa"];
    $porcentaje_musculo=empty($_POST["porcentaje_musculo"]) ? null : $_POST["porcentaje_musculo"];
    $cintura=$_POST["cintura"];
    $cadera=$_POST["cadera"];
    $pecho=$_POST["pecho"];
    $brazo=$_POST["brazo"];

    $consulta="
            update medida_corporal
            set
                fecha_registro=?,
                peso=?,
                porcentaje_grasa=?,
                porcentaje_musculo=?,
                cintura=?,
                cadera=?,
                pecho=?,
                brazo=?
            where id_medida=?";

    $resultado=$conexion->prepare($consulta);

    $resultado->execute([$fecha_registro,
                         $peso,
                         $porcentaje_grasa,
                         $porcentaje_musculo,
                         $cintura,
                         $cadera,
                         $pecho,
                         $brazo,
                         $id_medida]);

    echo json_encode([
        "estado"=>true,
        "mensaje"=>"Medidas actualizadas correctamente."
    ]);

}catch(PDOException $e){
    echo json_encode([
        "estado"=>false,
        "mensaje"=>$e->getMessage()
    ]);
}
?>