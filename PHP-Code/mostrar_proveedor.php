<?php
require_once "libreria.php";

$conexion=conectaDB();

$consulta="select
      nombre_empresa,
      contacto_nombre,
      telefono,
      correo
      from proveedor";

$mostrar_proveedor=$conexion->query($consulta);

$proveedores=[];
while($fila=$mostrar_proveedor->fetch(PDO::FETCH_ASSOC)){
    $proveedores[]=$fila;
}

echo json_encode($proveedores);
?>