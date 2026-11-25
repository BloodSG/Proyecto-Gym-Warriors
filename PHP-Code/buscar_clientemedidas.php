<?php
require_once 'libreria.php';

header('Content-Type: application/json');

try{
    $conexion=conectaDB();

    if(isset($_GET["buscar"])){
        $buscar=$_GET["buscar"];

        if($buscar===""){
            echo json_encode(["error"=>"El parámetro de búsqueda está vacío"]);
            exit;
        }

        $consulta="select
                    id_usuario,
                    nombre,
                    apellido_1,
                    apellido_2
                from usuariocliente
                where
                    id_usuario like ?
                    or nombre like ?
                    or apellido_1 like ?
                    or apellido_2 like ?";
        
        $resultado=$conexion->prepare($consulta);
        $resultado->execute(["%".$buscar."%", "%".$buscar."%", "%".$buscar."%", "%".$buscar."%"]);

        $clientes=$resultado->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($clientes);
        
    } else {
        echo json_encode(["error"=>"No se proporcionó un parámetro de búsqueda"]);
    }
    
}catch(PDOException $e){
    echo json_encode(["error"=>"Error en la base de datos: ".$e->getMessage()]);
}
?>