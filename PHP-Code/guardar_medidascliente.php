<?php

require_once 'libreria.php';

header('Content-Type: application/json');

try{
    $conexion=conectaDB();

    // Obtener los datos enviados
    $id_usuario=$_POST["id_usuario"];
    $fecha_registro=$_POST["fecha_registro"];
    $peso=$_POST["peso"];
    $porcentaje_grasa=empty($_POST["porcentaje_grasa"]) ? null : $_POST["porcentaje_grasa"];
    $porcentaje_musculo=empty($_POST["porcentaje_musculo"]) ? null : $_POST["porcentaje_musculo"];
    $cintura=$_POST["cintura"];
    $cadera=$_POST["cadera"];
    $pecho=$_POST["pecho"];
    $brazo=$_POST["brazo"];

    // Obtener el último ID
    $consultaID="select id_medida
                 from medida_corporal
                 order by id_medida desc
                 limit 1";

    $resultado=$conexion->query($consultaID);

    $ultimoRegistro=$resultado->fetch(PDO::FETCH_ASSOC);

    if($ultimoRegistro){
        $numero=intval(substr($ultimoRegistro["id_medida"],3)) + 1;
        $id_medida="MED".str_pad($numero,3,"0",STR_PAD_LEFT);
    }else{
        $id_medida="MED001";
    }

    // Insertar las medidas del cliente en la base de datos
    $consulta="insert into medida_corporal(id_medida,
                                            fecha_registro,
                                            peso,
                                            porcentaje_grasa,
                                            porcentaje_musculo,
                                            cintura,
                                            cadera,
                                            pecho,
                                            brazo,
                                            id_usuario)
                                            values(?,?,?,?,?,?,?,?,?,?)";

    $sentencia=$conexion->prepare($consulta);

    $sentencia->execute([$id_medida,
                         $fecha_registro,
                         $peso,
                         $porcentaje_grasa,
                         $porcentaje_musculo,
                         $cintura,
                         $cadera,
                         $pecho,
                         $brazo,
                         $id_usuario]);

    echo json_encode([
        "estado"=>true,
        "mensaje" => "Medidas registradas correctamente."
    ]);

}catch(PDOException $e){
    echo json_encode([
        "estado"=>false,
        "mensaje"=>$e->getMessage()
    ]);
}