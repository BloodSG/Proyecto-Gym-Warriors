<?php
require_once("libreria.php");
date_default_timezone_set('America/Mexico_City');
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(string $status, string $mensaje, array $datosExtra = [])
{
    echo json_encode(
        array_merge(["status" => $status, "mensaje" => $mensaje], $datosExtra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

if (empty($_SESSION["logueado"]) || ($_SESSION["tipo_usuario"] ?? "") !== "empleado")
{
    http_response_code(401);
    echo json_encode([
        "logueado" => false,
        "datos" => null,
        "mensaje" => "No se encuentra logueado, favor de iniciar sesion",
        "redirect" => "../HTML-Code/loginView.html"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try
{
    $conexion = conectaDB();
}
catch (Exception $errorConexion)
{
    responder("error", "error al conectar a base de datos");
}

$accion = $_GET["action"] ?? "";
$fechaHoy = date('Y-m-d');

if ($accion === "estado")
{
    $consulta = "SELECT c.id_caja, c.fecha, c.hora_apertura, c.fondo_inicial, c.estado,
                        CONCAT(u.nombre, ' ', u.apellidos) AS responsable
                 FROM caja AS c
                 JOIN usuariocliente AS u ON u.id_usuario = c.id_usuario_apertura
                 WHERE c.fecha = ?";
    $puente = $conexion->prepare($consulta);
    $puente->execute([$fechaHoy]);
    $cajaDelDia = $puente->fetch(PDO::FETCH_ASSOC);

    responder("success", "", ["caja" => $cajaDelDia ?: null]);
}

if ($accion === "abrir")
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST")
    {
        responder("error", "Metodo no permitido");
    }

    $fondoInicial = trim($_POST["fondo_inicial"] ?? "");
    if (!is_numeric($fondoInicial) || $fondoInicial < 0)
    {
        responder("error", "El fondo inicial debe ser un numero mayor o igual a 0");
    }

    $consultaExistente = "SELECT estado FROM caja WHERE fecha = ?";
    $puente = $conexion->prepare($consultaExistente);
    $puente->execute([$fechaHoy]);
    $cajaExistente = $puente->fetch(PDO::FETCH_ASSOC);

    if ($cajaExistente)
    {
        if ($cajaExistente["estado"] === "abierta")
        {
            responder("error", "Ya hay una caja abierta el dia de hoy");
        }
        responder("error", "La caja de hoy ya fue cerrada");
    }

    $idCaja = "CAJ" . date('Ymd');
    $horaApertura = date('H:i:s');

    $consultaInsertar = "INSERT INTO caja (id_caja, fecha, hora_apertura, fondo_inicial, id_usuario_apertura)
                         VALUES (?, ?, ?, ?, ?)";
    try
    {
        $puente = $conexion->prepare($consultaInsertar);
        $puente->execute([$idCaja, $fechaHoy, $horaApertura, $fondoInicial, $_SESSION["id_usuario"]]);
    }
    catch (PDOException $errorInsertar)
    {
        responder("error", "Ya hay una caja abierta el dia de hoy");
    }

    responder("success", "Caja abierta correctamente", [
        "caja" => [
            "id_caja" => $idCaja,
            "fecha" => $fechaHoy,
            "hora_apertura" => $horaApertura,
            "fondo_inicial" => $fondoInicial
        ]
    ]);
}

responder("error", "Accion no valida");