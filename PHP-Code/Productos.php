<?php
header('Content-Type: application/json; charset=utf-8');

require_once("libreria.php");

$conexion=conectaDB();

$query= "SELECT
        prod.id_producto,
        prod.nombre,
        cat.nombre_categoria,
        prod.precio_venta,
        prod.stock
        FROM
        Producto AS prod JOIN Categoria_Producto as cat
        ON prod.id_categoria=cat.id_categoria";

$Puente=$conexion->prepare($query);
$Puente->execute();


$Productos=$Puente->fetchAll(PDO::FETCH_ASSOC);
echo json_encode([ // al parecer el echo es necesario, no pregunen por que
        "status" => "success",
        "mensaje" => "Datos obtenidos correctamente",
        "data" => $Productos
    ], JSON_UNESCAPED_UNICODE);
?>

