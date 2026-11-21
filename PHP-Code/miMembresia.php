<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/libreria.php';

if (
    empty($_SESSION['id_usuario']) ||
    ($_SESSION['tipo_usuario'] ?? '') !== 'Cliente'
) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Inicia sesión con una cuenta de cliente.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conexion = conectaDB();

    $sql = "SELECT
                mc.estado,
                mc.fecha_inicio,
                mc.fecha_vencimiento,
                m.nombre_tipo,
                m.precio
            FROM membresia_cliente AS mc
            INNER JOIN membresia AS m
                ON m.id_membresia = mc.id_membresia
            WHERE mc.id_usuario = ?
            ORDER BY
                (LOWER(mc.estado) = 'activo'
                 AND mc.fecha_vencimiento >= CURDATE()) DESC,
                mc.fecha_vencimiento DESC
            LIMIT 1";

    $consulta = $conexion->prepare($sql);
    $consulta->execute([$_SESSION['id_usuario']]);
    $membresia = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$membresia) {
        echo json_encode([
            'success' => true,
            'data' => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $hoy = new DateTimeImmutable('today');
    $vencimiento = new DateTimeImmutable($membresia['fecha_vencimiento']);
    $membresia['dias_restantes'] = (int) $hoy->diff($vencimiento)->format('%r%a');

    echo json_encode([
        'success' => true,
        'data' => $membresia
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'No se pudieron consultar los datos de la membresía.'
    ], JSON_UNESCAPED_UNICODE);
}