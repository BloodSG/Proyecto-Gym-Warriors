<?php
require_once("libreria.php");

$conexion=conectaDB();

//Verifica si se recibió un ID del proveedor mediante la URL (método GET)
if(isset($_GET['id'])){
    $id_membresia=$_GET['id'];

    // Realizamos la consulta
    $consulta="select
               id_membresia,
               nombre_tipo,
               precio,
               duracion_dias,
               incluye_rutina,
               incluye_dieta,
               caracteristicas
               from Membresia
               where id_membresia=?";

    // Prepara la consulta
    $buscar_membresia=$conexion->prepare($consulta);
    // Ejecuta la consulta enviando el id_membresia como parametro
    $buscar_membresia->execute([$id_membresia]);
    // Se obtiene el registro
    $membresia=$buscar_membresia->fetch(PDO::FETCH_ASSOC);

    // Formato json para que JavaScript los pueda usar
    echo json_encode($membresia);

}
else{
    echo json_encode(["error"=>"No se recibió el ID"]);
}

?>