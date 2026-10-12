<?php
header('Content-Type: application/json; charset=utf-8');

require_once("libreria.php");


$conexion=conectaDB();

$query= "SELECT
        prod.id_producto,
        prod.nombre,
        prod.descripcion,
        prov.nombre_empresa,
        cat.nombre_categoria,
        prod.precio_venta,
        prod.stock
        FROM Producto AS prod JOIN Categoria_Producto as cat 
        ON prod.id_categoria=cat.id_categoria
        JOIN Proveedor as prov
        ON prod.id_proveedor=prov.id_proveedor
        AND prod.id_producto=?";
$id_producto='PROD01';
$Puente=$conexion->prepare($query);
$Puente->execute([$id_producto]);

$Producto=$Puente->fetch(PDO::FETCH_ASSOC);
echo json_encode([ // al parecer el echo es necesario, no pregunen por que
        "status" => "success",
        "mensaje" => "Datos obtenidos correctamente",
        "data" => $Producto
    ], JSON_UNESCAPED_UNICODE);
?>