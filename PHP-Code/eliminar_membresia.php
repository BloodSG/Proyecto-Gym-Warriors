<?php
require_once("libreria.php");

header("Content-Type: application/json; charset=utf-8");

$idMembresia = trim($_POST["id_membresia"] ?? "");

if ($idMembresia === "") {
    echo json_encode([
        "status" => "error",
        "mensaje" => "No se recibió el ID de la membresía."
    ]);
    exit();
}

try {
    $conexion = conectaDB();

    $consulta = $conexion->prepare(
        "DELETE FROM membresia WHERE id_membresia = ?"
    );
    $consulta->execute([$idMembresia]);

    if ($consulta->rowCount() === 0) {
        echo json_encode([
            "status" => "error",
            "mensaje" => "La membresía ya no existe."
        ]);
        exit();
    }

    echo json_encode([
        "status" => "success",
        "mensaje" => "Membresía eliminada correctamente."
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $error) {
    echo json_encode([
        "status" => "error",
        "mensaje" => "No se pudo eliminar la membresía."
    ], JSON_UNESCAPED_UNICODE);
}