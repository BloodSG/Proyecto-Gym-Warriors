<?php
require_once('libreria.php');

$conexion=conectaDB();

header("Content-Type: application/json");

// Verifica que el formulario haya enviado correctamente el ID del proveedor
if(isset($_POST['id_proveedor'])) {
    // Obtiene el ID del proveedor que será modificado
    $id_proveedor=$_POST['id_proveedor'];

    // Obtiene los nuevos valores enviados desde el formulario de modificar_proveedor.html
    $nombre_empresa=$_POST['nombreemp'];
    $nombre_contacto=$_POST['nombre'];
    $telefono=$_POST['telefono'];
    $correo=$_POST['correo'];

    // Verificar si los datos ya pertenecen a otro proveedor
    $consultaExiste="select *
                     from Proveedor
                     where (nombre_empresa=? or correo=? or telefono=?) and id_proveedor<>?";

    $verificar=$conexion->prepare($consultaExiste);

    $verificar->execute([$nombre_empresa,
                         $correo,
                         $telefono,
                         $id_proveedor]);

    if($verificar->fetch(PDO::FETCH_ASSOC)){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"La empresa, el correo o telefono ya pertenecen a un proveedor"
        ]);
        exit();
    }

    // Crear la consulta
    $consulta="update Proveedor
               set
               nombre_empresa=?,
               contacto_nombre=?,
               telefono=?,
               correo=?
               where id_proveedor=?";
    
    try{
        // Preparar la consulta modificar
        $modificar_proveedor=$conexion->prepare($consulta);

        // Ejecutar la consulta modificar
        $modificar_proveedor->execute([$nombre_empresa,
                                       $nombre_contacto,
                                       $telefono,
                                       $correo,
                                       $id_proveedor]);

        echo json_encode([
            "estado"=>"ok",
            "mensaje"=>"Proveedor modificado correctamente."
        ]);

    }catch(PDOException $error){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"No se pudo modificar el proveedor."
        ]);
    }
}

else{
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"No se recibió el ID del proveedor."
    ]);
}