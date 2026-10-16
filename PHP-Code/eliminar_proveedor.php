<?php
require_once "libreria.php";

$conexion=conectaDB();

// Verifica si se ha recibido un id de proveedor a eliminar
// isset() es una función que verifica si una variable está definida y no es null
if(isset($_GET["id"])){
    // Obtiene el id del proveedor a eliminar
    $id=$_GET["id"];

    // Consulta para eliminar el proveedor
    $consulta="delete from Proveedor
               where id_proveedor=?";

    // Prepara y ejecuta la consulta
    $eliminar=$conexion->prepare($consulta);
    // Elimina el proveedor con el id proporcionado
    $eliminar->execute([$id]);
}

// Regresa a la pagina para ver los cambios
header("Location: ../HTML-Code/proveedores.html");
exit();

?>