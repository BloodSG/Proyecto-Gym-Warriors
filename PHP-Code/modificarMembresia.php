<?php
require_once('libreria.php');

$conexion=conectaDB();

header("Content-Type: application/json");

// Verifica que el formulario haya enviado correctamente el ID de la membresía
if(isset($_POST['id_membresia'])) {
    // Obtiene el ID del proveedor que será modificado
    $id_membresia=$_POST['id_membresia'];

    // Obtiene los nuevos valores enviados desde el formulario de modificar_membresia.html
    $nombre_tipo=trim($_POST['nombre_tipo'] ?? '');
    $precio=$_POST['precio'] ?? '';
    $duracion_dias=$_POST['duracion_dias'] ?? '';
    $incluye_rutina=$_POST['incluye_rutina'] ?? '0';
    $incluye_dieta=$_POST['incluye_dieta'] ?? '0';
    $caracteristicas=trim($_POST['caracteristicas'] ?? '');
    

    // Verificar si los datos ya pertenecen a otra membresia
    $consultaExiste="select *
                    from membresia
                    where nombre_tipo=?  and id_membresia<>?";

    $verificar=$conexion->prepare($consultaExiste);

    $verificar->execute([$nombre_tipo,
                        $id_membresia]);

    if($verificar->fetch(PDO::FETCH_ASSOC)){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"Ya existe otra membresía con ese nombre."
        ]);
        exit();
    }

    // Crear la consulta
    $consulta="update membresia
               set
               nombre_tipo=?,
               precio=?,
               duracion_dias=?,
               incluye_rutina=?,
               incluye_dieta=?,
               caracteristicas=?
               where id_membresia=?";
    
    try{
        // Preparar la consulta modificar
        $modificar_membresia=$conexion->prepare($consulta);

        // Ejecutar la consulta modificar
        $modificar_membresia->execute([$nombre_tipo,
                                       $precio,
                                       $duracion_dias                                                                                                                     ,
                                       $incluye_rutina,
                                       $incluye_dieta,
                                       $caracteristicas,
                                       $id_membresia]);

        echo json_encode([
            "estado"=>"ok",
            "mensaje"=>"Membresía modificada correctamente."
        ]);

    }catch(PDOException $error){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"No se pudo modificar la membresía."
        ]);
    }
}

else{
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"No se recibió el ID de la membresía."
    ]);
}

?>