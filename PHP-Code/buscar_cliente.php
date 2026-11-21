<?php
require_once "libreria.php";

$conexion=conectaDB();

if(isset($_GET['id'])){
    $id_usuario=$_GET['id'];

    $consulta="
        select
            id_usuario,
            nombre,
            apellido_1,
            apellido_2,
            correo
        from usuariocliente
        where id_usuario=?";

    $resultado=$conexion->prepare($consulta);
    $resultado->execute([$id_usuario]);

    $cliente=$resultado->fetch(PDO::FETCH_ASSOC);

    echo json_encode($cliente);
}
?>