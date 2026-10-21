<?php
require_once "libreria.php";

$conexion=conectaDB();

// Hacer la consulta para obtener los clientes
$consulta="
        select
            id_usuario,
            nombre,
            apellido_1,
            apellido_2,
            correo
        from usuariocliente";

// Ejecutar la consulta
$mostrar_cliente=$conexion->query($consulta);

$clientes=[];
// Recorremos los registros obtenidos
while($fila=$mostrar_cliente->fetch(PDO::FETCH_ASSOC)){
    // Agregamos cada registro al arreglo de proveedores
    $clientes[]=$fila;
}

// Devolver los proveedores en formato JSON, para que puedan ser procesados por JavaScript
echo json_encode($clientes);

?>