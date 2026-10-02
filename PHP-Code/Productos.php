<?php
require_once("libreria.php");

$conexion=conectaDB();

$query= "SELECT
        id_usuario,
        nombre,
        apellidos,
        correo,
        telefono
        FROM
        usuariocliente";
$Puente=$conexion->prepare($query);
$Puente->execute();


$filas = []; // listapara guardar todo
while ($fila = $Puente->fetch(PDO::FETCH_ASSOC)) //segun la forma estandar de obtener todos los resultados es con un while, maldito php
{
    $filas[] = $fila; // hacemos el equivalente a append del resultado en las filas
}

// ahora filas es una lista de listas
print_r ($filas);
header('Content-Type: application/json; charset=utf-8');
$respuesta = [
    "status" => "success",
    "mensaje" => "Datos obtenidos correctamente",
    "data" => $filas // Aquí metes la info de la BD
];
echo json_encode($respuesta);
exit();
?>