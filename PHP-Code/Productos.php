<?php
header('Content-Type: application/json; charset=utf-8'); // le dice al navegador que la respuesta es json 
require_once("libreria.php");
$conexion=conectaDB();

if (isset($_GET['action']) && $_GET['action'] == 'detalle') 
{
    $id =  $_GET['id'] ?? 0;// ?? 0 significa que si no hay id, se asigna un 0
    
    $query= "SELECT
        prod.id_producto,
        prod.nombre,
        prod.descripcion,
        ImgP.url,
        prov.nombre_empresa,
        cat.nombre_categoria,
        prod.precio_venta,
        prod.stock
        FROM Producto AS prod JOIN Categoria_Producto as cat 
        ON prod.id_categoria=cat.id_categoria
        JOIN Proveedor as prov
        ON prod.id_proveedor=prov.id_proveedor
        AND prod.id_producto=?
        LEFT JOIN ImgProductos as ImgP
        ON ImgP.id_producto=prod.id_producto";

        $Puente=$conexion->prepare($query);
        $Puente->execute([$id]);

        $Producto=$Puente->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([ // echo imprime la respuesta en formato json para que quien pide la informacion pueda leerla
                "status" => "success",
                "mensaje" => "id: $id",
                "data" => $Producto
        ], JSON_UNESCAPED_UNICODE); // el JSON_UNESCAPED_UNICODE es para que no omita los caracteres especiales como acentos y ñ



}
elseif ((isset($_GET['action']) && $_GET['action'] == 'vuscar') )
{
    $vusqueda = "%".$_GET['comando']."%";
    
    $query= "SELECT
        prod.id_producto,
        prod.nombre,
        ImgP.url,
        cat.nombre_categoria,
        prod.precio_venta,
        prod.stock
        FROM
        Producto AS prod JOIN Categoria_Producto as cat
        ON prod.id_categoria=cat.id_categoria AND prod.nombre like ?
        LEFT JOIN ImgProductos as ImgP
        ON ImgP.id_producto=prod.id_producto";
    
    $Puente=$conexion->prepare($query);
    $Puente->execute([$vusqueda]);

    $Productos=$Puente->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([ // echo imprime la respuesta en formato json para que quien pide la informacion pueda leerla
                "status" => "success",
                "mensaje" => "id: esta es la busqueda",
                "data" => $Productos
        ], JSON_UNESCAPED_UNICODE);
}
else
{
    if (isset($_GET['categoria'])&& $_GET['categoria']=="")
{
    $query= "SELECT
        prod.id_producto,
        prod.nombre,
        ImgP.url,
        cat.nombre_categoria,
        prod.precio_venta,
        prod.stock
        FROM
        Producto AS prod JOIN Categoria_Producto as cat
        ON prod.id_categoria=cat.id_categoria
        LEFT JOIN ImgProductos as ImgP
        ON ImgP.id_producto=prod.id_producto";
    $Puente=$conexion->prepare($query);
    $Puente->execute();

}
else
{
        $query= "SELECT
        prod.id_producto,
        prod.nombre,
        ImgP.url,
        cat.nombre_categoria,
        prod.precio_venta,
        prod.stock
        FROM
        Producto AS prod JOIN Categoria_Producto as cat
        ON prod.id_categoria=cat.id_categoria AND cat.nombre_categoria=?
        LEFT JOIN ImgProductos as ImgP
        ON ImgP.id_producto=prod.id_producto";
    
    $categoria=$_GET['categoria'];
    $Puente=$conexion->prepare($query);
    $Puente->execute([$categoria]);
}




$Productos=$Puente->fetchAll(PDO::FETCH_ASSOC);
echo json_encode([ 
        "status" => "success",
        "mensaje" => "Datos obtenidos correctamente",
        "data" => $Productos
    ], JSON_UNESCAPED_UNICODE);
}
?>

