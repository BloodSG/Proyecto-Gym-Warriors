<?php
require_once "libreria.php";

$conexion=conectaDB();

// Hacer la consulta para obtener los proveedores
$consulta="
        select
            Proveedor.id_proveedor,
            Proveedor.nombre_empresa,
            Proveedor.contacto_nombre,
            Proveedor.telefono,
            Proveedor.correo,

            GROUP_CONCAT(Producto.nombre SEPARATOR ', ') as productos

        from Proveedor

        left join Producto
        on Proveedor.id_proveedor = Producto.id_proveedor

        group by
            Proveedor.id_proveedor,
            Proveedor.nombre_empresa,
            Proveedor.contacto_nombre,
            Proveedor.telefono,
            Proveedor.correo";

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