<?php
require_once "libreria.php";

$conexion=conectaDB();

// Hacer la consulta para obtener los proveedores
$consulta="select
      id_proveedor,
      nombre_empresa,
      contacto_nombre,
      telefono,
      correo
      from proveedor";

// Ejecutar la consulta
$mostrar_proveedor=$conexion->query($consulta);

$proveedores=[];
// Recorremos los registros obtenidos
while($fila=$mostrar_proveedor->fetch(PDO::FETCH_ASSOC)){
    // Agregamos cada registro al arreglo de proveedores
    $proveedores[]=$fila;
}

// Devolver los proveedores en formato JSON, para que puedan ser procesados por JavaScript
echo json_encode($proveedores);

?>