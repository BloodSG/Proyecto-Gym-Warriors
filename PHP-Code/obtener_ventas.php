<?php
require_once("libreria.php");

header('Content-Type: application/json; charset=utf-8');

try {
    $conexion=conectaDB();

    $consulta=" select 
                    venta.id_venta,
                    date(venta.fecha_venta) as fecha_venta,
                    concat_ws(' ', empleados.nombre, empleados.apellido_1, empleados.apellido_2) as empleado,
                    venta.total,
                    venta.metodo_pago
                from venta
                inner join empleados
                on venta.id_empleado=empleados.id_empleado
                order by venta.fecha_venta desc";

    $resultado=$conexion->prepare($consulta);
    $resultado->execute();

    $ventas=$resultado->fetchAll();

    echo json_encode([
        "estado" => true,
        "datos" => $ventas
    ]);

}catch(PDOException $e){
    echo json_encode([
        "estado" => false,
        "mensaje" => $e->getMessage()
    ]);
}
?>