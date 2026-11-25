<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'libreria.php';
//Responde en JSON y se separa el catalogo para que la pantalla no mezcle SQL y HTML -_-
function responderCatalogo($estado, $mensaje, $datos = null, $codigoHttp = 200)
{
    http_response_code($codigoHttp);
    $respuesta = ['status' => $estado, 'mensaje' => $mensaje];
    if ($datos !== null) {
        $respuesta['data'] = $datos;
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderCatalogo('error', 'Metodo no permitido.', null, 405);
}
try {
    $conexion = conectaDB();
    $busqueda = trim((string)($_GET['buscar'] ?? ''));
    $tipo = trim((string)($_GET['tipo'] ?? 'todos'));
    $texto = '%' . $busqueda . '%';
    $items = [];
    //Los productos tienen stock, por eso se cargan con su existencia disponible
    if ($tipo === 'todos' || $tipo === 'producto') {
        if ($busqueda !== '') {
            $consulta = $conexion->prepare(
                'SELECT id_producto, nombre, precio_venta, stock
                 FROM producto
                 WHERE nombre LIKE ? OR id_producto LIKE ?
                 ORDER BY nombre'
            );
            $consulta->execute([$texto, $texto]);
        } else {
            $consulta = $conexion->query(
                'SELECT id_producto, nombre, precio_venta, stock
                 FROM producto
                 ORDER BY nombre'
            );
        }
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $producto) {
            $items[] = [
                'tipo' => 'producto',
                'id' => $producto['id_producto'],
                'nombre' => $producto['nombre'],
                'precio' => (float)$producto['precio_venta'],
                'stock' => (int)$producto['stock']
            ];
        }
    }
    //Las membresias no tienen stock: se asignan al cliente registrado en la venta
    if ($tipo === 'todos' || $tipo === 'membresia') {
        if ($busqueda !== '') {
            $consulta = $conexion->prepare(
                'SELECT id_membresia, nombre_tipo, precio, duracion_dias
                 FROM membresia
                 WHERE nombre_tipo LIKE ? OR id_membresia LIKE ?
                 ORDER BY nombre_tipo'
            );
            $consulta->execute([$texto, $texto]);
        } else {
            $consulta = $conexion->query(
                'SELECT id_membresia, nombre_tipo, precio, duracion_dias
                 FROM membresia
                 ORDER BY nombre_tipo'
            );
        }
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $membresia) {
            $items[] = [
                'tipo' => 'membresia',
                'id' => $membresia['id_membresia'],
                'nombre' => $membresia['nombre_tipo'],
                'precio' => (float)$membresia['precio'],
                'duracion_dias' => (int)$membresia['duracion_dias']
            ];
        }
    }
    responderCatalogo(
        'success',
        'Catalogo cargado correctamente.',
        $items
    );
} catch (Throwable $error) {
    responderCatalogo(
        'error',
        'No se pudo cargar el catalogo.',
        null,
        500
    );
}
