<?php
require_once("libreria.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

function salidaForzosa(string $status, string $mensaje)
{   
    echo json_encode([
        "status" => $status,
        "mensaje" => $mensaje
        ], JSON_UNESCAPED_UNICODE);
        exit;
}

try 
{
    $conexion=conectaDB();
}
catch (Exception $e)
{
    echo json_encode([
        "status" => "error",
        "mensaje" => "error al conectar a base de datos"
        ], JSON_UNESCAPED_UNICODE);
        exit;
}

$mensaje="No se ha validado nada";
$status="error";
$data="";

if($_GET["action"]=="subir")
{   
    $conexion=conectaDB();
    $id_usuario=$_SESSION["id_usuario"];
    $membresia=buscaMembresia($id_usuario,$conexion);

    if(!$membresia["encontrado"])
    {
        $status="error";
        $mensaje="Ocurrio un error con la sesión, vuelva a intentarlo";
        salidaForzosa($status,$mensaje);
    }
    
    $id_membresia=$membresia['id_membresia'];

    if(!isset($_FILES["comprobante"]) || $_FILES["comprobante"]["error"]!=0)
    {
        $status="error";
        $mensaje="No se ha subido nada";
        salidaForzosa($status,$mensaje);
    }

    $limiteBytes=5 * 1024 * 1024; // 5 megabites
    if ($_FILES['comprobante']['size'] > $limiteBytes) 
    {
        $status="error";
        $mensaje="El archivo cargado no puede pesar mas de 5 Mb";
        salidaForzosa($status,$mensaje);
    }

    $carpetaDeServidor="Recursos/rec_pagos/";
    $archivo=$_FILES["comprobante"];

    if($archivo["type"]!="image/png" &&
     $archivo["type"]!="image/jpeg" && 
     $archivo["type"]!="image/jpg" && 
     $archivo["type"]!="application/pdf")
    {
        $status="error";
        $mensaje="El archivo cargado no es de tipo valido";
        salidaForzosa($status,$mensaje);
    }
    
    try
    {   
        $conexion->beginTransaction();
        $id_comprobante="COMP". strtoupper(bin2hex(random_bytes(3)));
        $rutaOriginal=$archivo["tmp_name"];
        $nombreArchivo=$archivo["name"];
        $extensionArchivo=pathinfo($nombreArchivo, PATHINFO_EXTENSION);
        $nombreArchivoFinal=$id_usuario."_".$id_comprobante.".".$extensionArchivo;

        $rutaFinalGuardado="../".$carpetaDeServidor.$nombreArchivoFinal;
        $rutaComprobante=$carpetaDeServidor.$nombreArchivoFinal;
        
        $query="INSERT INTO comprobante_pago (
            id_comprobante,
            id_membresia_cliente,
            id_usuario,
            ruta_archivo)
        VALUES (?,?,?,?)";
        $Puente=$conexion->prepare($query);
        $Puente->execute([$id_comprobante,$id_membresia,$id_usuario,$rutaComprobante]);

        move_uploaded_file($rutaOriginal,$rutaFinalGuardado);
        $mensaje="Archivo cargado correctamente";
        $status="success";
        $conexion->commit();
    }
    catch(Exception $e)
    {
        $conexion->rollBack();
        $mensaje="El comprobante no se pudo subir, vuelva a intentarlo";
        $status="error";
        salidaForzosa($status,$mensaje);
    }
    
}



echo json_encode([
    "status" => $status,
    "mensaje" => $mensaje,
    "data" => $data
    ], JSON_UNESCAPED_UNICODE);
exit;
?>