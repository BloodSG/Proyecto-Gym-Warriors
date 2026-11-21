<?php
header('Content-Type: application/json; charset=utf-8');
try {
    require_once "libreria.php";
    $conexion=conectaDB();
    $sql="select uc.id_usuario, uc.nombre, m.nombre_tipo, mc.fecha_inicio, mc.fecha_vencimiento
        from membresia_cliente mc
        inner join membresia m
        on mc.id_membresia = m.id_membresia
        inner join usuariocliente uc
        on mc.id_usuario = uc.id_usuario
        order by mc.fecha_vencimiento ASC";

    $consulta=$conexion->prepare($sql);
    $consulta->execute();

    $datos=[];
    while($fila=$consulta->fetch(PDO::FETCH_ASSOC)){
        $datos[]=[
            "nombre"=>$fila["nombre"],
            "id"=>$fila["id_usuario"],
            "membresia"=>$fila["nombre_tipo"],
            "fechaInicio"=>$fila["fecha_inicio"],
            "fechaVencimiento"=>$fila["fecha_vencimiento"]];
    }

    echo json_encode([
        "estado"=>"success",
        "datos"=>$datos
    ]);
}
catch(PDOException $error){
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"Error al consultar las vigencias: "
        .$error->getMessage()]);
}
catch(Exception $error){
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"Error del sistema: "
        .$error->getMessage()]);
}
?>