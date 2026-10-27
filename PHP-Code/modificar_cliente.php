<?php
require_once "libreria.php";

$conexion=conectaDB();

if(isset($_POST['id_usuario'])){
    $id_usuario=$_POST['id_usuario'];
    $nombre=$_POST['nombre'];
    $apellido_1=$_POST['apellido_1'];
    $apellido_2=$_POST['apellido_2'];
    $correo=$_POST['correo'];

    $consulta="update usuariocliente
            set
                nombre=?,
                apellido_1=?,
                apellido_2=?,
                correo=?
            where id_usuario=?";

    $resultado=$conexion->prepare($consulta);

    if($resultado->execute([$nombre,
                            $apellido_1,
                            $apellido_2,
                            $correo,
                            $id_usuario])){
        echo json_encode([
            "estado"=>"exito",
            "mensaje"=>"Cliente actualizado correctamente"
        ]);

    }
    else{
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"No se pudo actualizar el cliente"
        ]);
    }
}
?>