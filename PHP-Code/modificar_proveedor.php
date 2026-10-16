<?php
require_once('libreria.php');

$conexion=conectaDB();

// Verifica que el formulario haya enviado correctamente el ID del proveedor
if(isset($_POST['id_proveedor'])) {
    // Obtiene el ID del proveedor que será modificado
    $id_proveedor=$_POST['id_proveedor'];

    // Obtiene los nuevos valores enviados desde el formulario de modificar_proveedor.html
    $nombre_empresa=$_POST['nombreemp'];
    $nombre_contacto=$_POST['nombre'];
    $telefono=$_POST['telefono'];
    $correo=$_POST['correo'];

    // Crear la consulta
    $consulta="update Proveedor 
               set 
               nombre_empresa=?,
               contacto_nombre=?,
               telefono=?,
               correo=?
               where id_proveedor=?";
    
    // Preparar la consulta modificar
    $modificar_proveedor=$conexion->prepare($consulta);

    // Ejecutar la consulta modificar
    $modificar_proveedor->execute([$nombre_empresa,
                                   $nombre_contacto,
                                   $telefono,
                                   $correo,
                                   $id_proveedor]);
}

else {
    echo "No se proporcionó un ID de proveedor válido.";
}

header("Location: ../HTML-Code/proveedores.html");
exit();
?>