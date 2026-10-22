<?php
require_once("libreria.php");

$conexion=conectaDB();

//Verifica si se recibió un ID del proveedor mediante la URL (método GET)
if(isset($_GET['id'])){
    $id_proveedor=$_GET['id'];

    // Realizamos la consulta
    $consulta="select
               id_proveedor,
               nombre_empresa,
               contacto_nombre,
               telefono,
               correo
               from Proveedor
               where id_proveedor=?";

    // Prepara la consulta
    $buscar_proveedor=$conexion->prepare($consulta);
    // Ejecuta la consulta enviando el id_proveedor como parametro
    $buscar_proveedor->execute([$id_proveedor]);
    // Se obtiene el registro
    $proveedor=$buscar_proveedor->fetch(PDO::FETCH_ASSOC);

    // Formato json para que JavaScript los pueda usar
    echo json_encode($proveedor);

}
else{
    echo json_encode(["error"=>"No se recibió el ID"]);
}

?>