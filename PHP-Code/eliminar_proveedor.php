<?php
require_once "libreria.php";

$conexion=conectaDB();

header("Content-Type: application/json");

// Verifica si se ha recibido un id de proveedor a eliminar
// isset() es una función que verifica si una variable está definida y no es null
if(isset($_GET["id"])){
    // Obtiene el id del proveedor a eliminar
    $id=$_GET["id"];

    try{
        // Consulta para eliminar el proveedor
        $consulta="delete from Proveedor
                where id_proveedor=?";

        // Prepara y ejecuta la consulta
        $eliminar=$conexion->prepare($consulta);
        // Elimina el proveedor con el id proporcionado
        $eliminar->execute([$id]);

        echo json_encode([
            "estado"=>"ok",
            "mensaje"=>"Proveedor eliminado correctamente."
        ]);

    }catch(PDOException $error){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"No se puede eliminar el proveedor porque tiene productos asociados."
        ]);
    }
}

else{
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"No se recibió el ID del proveedor."
    ]);
}
?>