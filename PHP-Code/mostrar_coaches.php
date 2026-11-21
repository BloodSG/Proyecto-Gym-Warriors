<?php
require_once "libreria.php";

$conexion=conectaDB();

header("Content-Type: application/json");

try{
$consulta="
    select
        id_empleado,
        concat_ws(' ',nombre,apellido_1,apellido_2) as nombre
    from empleados
    where id_rol='ROL_ENT'";

$resultado=$conexion->query($consulta);

if(!$resultado){
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"Error al obtener los coaches"
    ]);
    exit;
}

$coaches=[];

while($fila=$resultado->fetch(PDO::FETCH_ASSOC)){
    $coaches[]=$fila;
}
echo json_encode($coaches);
}catch(PDOException $e){
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"Error al obtener los coaches: ".$e->getMessage()
    ]);
}
?>