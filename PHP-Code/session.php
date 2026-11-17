<?php
session_start();
require_once("./libreria.php");
header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['correo']))
{   
    session_unset();
    session_destroy();

    http_response_code(401);
    echo json_encode([
        "logueado" => false,
        "datos" => null,
        "mensaje"=> "No se encuentra logueado, favor de iniciar sesion",
        "redirect" => "../HTML-Code/loginView.html"
    ]);
    exit();
}
if(!isset($_SESSION['correo'])
    || !isset($_SESSION['id_usuario'])
    || !isset($_SESSION['nombre'])
    || !isset($_SESSION['subrol'])
    || !isset($_SESSION['tipo_usuario']))
{
    session_unset();
    session_destroy();

    http_response_code(401);
    echo json_encode([
        "logueado" => false,
        "datos" => null,
        "mensaje"=> "Hubo un error en la session, favor de volver a inicira session",
        "redirect" => "../HTML-Code/loginView.html"
    ]);
    exit();
}

$conexion=conectaDB();
try
{
    $queryEmpl="SELECT id_empleado, nombre, puesto, turno, salario, correo, id_rol
                    FROM Empleados 
                    WHERE correo =?";

    $queryClien="SELECT usuariocliente.id_usuario, usuariocliente.nombre, usuariocliente.apellido_1, usuariocliente.id_rol, Rol.nombre_rol
                    FROM usuariocliente
                    INNER JOIN Rol ON usuariocliente.id_rol = Rol.id_rol
                    WHERE usuariocliente.correo = ?";
    $puente=$conexion->prepare($queryEmpl);
    $puente->execute([$_SESSION["correo"]]);
    $empleado=$puente->fetch(PDO::FETCH_ASSOC);

    if($empleado)
    {
        echo json_encode([
            "logueado" => true,
            "datos" => $empleado["id_empleado"],
            "nombre" => $empleado["nombre"],
            "rol" => $empleado["id_rol"],
            "mensaje"=> "El empleado se encuentra logueado"
        ]);
        exit();
    }
    $puente=$conexion->prepare($queryClien);
    $puente->execute([$_SESSION["correo"]]);
    $cliente=$puente->fetch(PDO::FETCH_ASSOC);

    if($cliente)
    {
        echo json_encode([
            "logueado" => true,
            "datos" => $cliente["id_usuario"],
            "nombre" => $cliente["nombre"],
            "rol" => $cliente["id_rol"],
            "mensaje"=> "El cliente se encuentra logueado"
        ]);
        exit();
    }

}
catch(Exception $e)
{
    echo json_encode([
            "logueado" => false,
            "datos" => null,
            "mensaje"=> "Ha ocurrido un error inesperado, favor de volver a iniciar session",
            "redirect" => "../HTML-Code/loginView.html"
        ]);
        exit();
}

?>