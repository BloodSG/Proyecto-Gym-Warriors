<?php
require_once "libreria.php";

// Conexión a la BD, llamamos a la funcion que se hizo en libreria.php para hacer la conexión
$conexion=conectaDB();

header("Content-Type: application/json");

// Recibir los datos del formulario
$id_proveedor=generar_id($conexion);
$nombre_empresa=$_POST['nombreemp'];
$nombre_contacto=$_POST['nombre'];
$telefono=$_POST['telefono'];
$correo=$_POST['correo'];

// Verificar si el proveedor ya existe
$consultaExiste="select *
                 from Proveedor
                 where nombre_empresa=? or correo=? or telefono=?";
$verificar=$conexion->prepare($consultaExiste);
$verificar->execute([$nombre_empresa,
                     $correo,
                     $telefono]);

if($verificar->fetch(PDO::FETCH_ASSOC)){
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"La empresa, el correo o el telefono ya se encuentra registrado"
    ]);
    exit();
}

// Crear la consulta
$consulta="insert into Proveedor
          (id_proveedor,
           nombre_empresa,
           contacto_nombre,
           telefono,
           correo)
           values (?, ?, ?, ?, ?)";

try{
    // Preparar la consulta para ejecutarla de forma segura
    $insertar_proveedor=$conexion->prepare($consulta);

    // Ejecutar la consulta, para agregar información
    $insertar_proveedor->execute([$id_proveedor,
                                $nombre_empresa,
                                $nombre_contacto,
                                $telefono,
                                $correo]);

    echo json_encode([
        "estado"=>"ok",
        "mensaje"=>"Proveedor guardado correctamente."
    ]);

}catch(PDOException $error){
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"No se pudo guardar el proveedor."
    ]);
}

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
    return "P".str_pad($numero, 2, "0", STR_PAD_LEFT);
}

?>