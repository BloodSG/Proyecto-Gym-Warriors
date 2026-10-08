<?php
require_once "libreria.php";

// Conexión a la BD, llamamos a la funcion que se hizo en libreria.php para hacer la conexión
$conexion=conectaDB();
echo "Conexión exitosa";

// Recibir los datos del formulario
$id_proveedor=generar_id($conexion);
$nombre_empresa=$_POST['nombreemp'];
$nombre_contacto=$_POST['nombre'];
$telefono=$_POST['telefono'];
$correo=$_POST['correo'];

// Crear la consulta
$consulta="insert into Proveedor
          (id_proveedor, nombre_empresa, contacto_nombre, telefono, correo)
           values (?, ?, ?, ?, ?)";

// Preparar la consulta para ejecutarla de forma segura
$insertar_proveedor=$conexion->prepare($consulta);

// Ejecutar la consulta, para agregar información
$insertar_proveedor->execute([$id_proveedor,
                              $nombre_empresa,
                              $nombre_contacto,
                              $telefono,
                              $correo]);

echo "<br>Proveedor agregado correctamente";


// Función para generar el id_proveedor automaticamente
function generar_id($conexion){
    // Crear la consulta
    $consulta="select top 1 id_proveedor
               from Proveedor
               order by id_proveedor desc";

    // Ejecuta la consulta, para obtener registros
    $resultado=$conexion->query($consulta);

    // Obtener el registro encontrado
    $ultimo_proveedor=$resultado->fetch(PDO::FETCH_ASSOC);

    // Verifica si existe algun proveedor
    if($ultimo_proveedor){
        // Quita la letra p del id_proveedor, convierte a int para sumarle 1 y generar el siguiente id_proveedor
        $numero=intval(substr($ultimo_proveedor['id_proveedor'], 1));

        $numero++;
    }
    else{
        $numero=0;
    }

    // Se crea el nuevo id_proveedor
    return "P".str_pad($numero, 3, "0", STR_PAD_LEFT);
}

?>