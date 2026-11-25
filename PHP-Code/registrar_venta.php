<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'libreria.php';
//Respuesta JSON comun para validaciones y errores
function responderVenta($estado, $mensaje, $datos = null, $codigoHttp = 200)
{
    http_response_code($codigoHttp);
    $respuesta = ['status' => $estado, 'mensaje' => $mensaje];
    if ($datos !== null) {
        $respuesta['data'] = $datos;
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}
//Lee el ticket enviado por el JavaScript
function obtenerDatosVenta()
{
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) {
        responderVenta('error', 'Datos de venta invalidos.', null, 400);
    }
    return $datos;
}
//Valida que los items del ticket sean correctos

function validarItems($items)
{
    if (!is_array($items) || count($items) === 0) {
        responderVenta('error', 'La venta debe tener al menos un concepto.', null, 400);
    }
    $resultado = ['productos' => [], 'membresias' => []];
    foreach ($items as $item) {
        $tipo = trim((string)($item['tipo'] ?? ''));
        $id = trim((string)($item['id'] ?? ''));
        $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
        if ($tipo === 'producto') {
            if ($id === '' || $cantidad === false || $cantidad <= 0) {
                responderVenta('error', 'Producto o cantidad invalida.', null, 400);
            }
            $resultado['productos'][$id] =
                ($resultado['productos'][$id] ?? 0) + $cantidad;
        } elseif ($tipo === 'membresia') {
            if ($id === '' || $cantidad !== 1) {
                responderVenta(
                    'error',
                    'Cada membresia puede agregarse una sola vez por venta.',
                    null,
                    400
                );
            }
            if (isset($resultado['membresias'][$id])) {
                responderVenta(
                    'error',
                    'La membresia ya fue agregada al ticket.',
                    null,
                    400
                );
            }
            if (count($resultado['membresias']) >= 1) {
                responderVenta(
                    'error',
                    'Solo se permite una membresia por venta.',
                    null,
                    400
                );
            }
            $resultado['membresias'][$id] = 1;
        } else {
            responderVenta('error', 'Tipo de concepto invalido.', null, 400);
        }
    }
    return $resultado;
}
//Obtiene el empleado que inicia la venta y valida el metodo de pago
function obtenerEmpleadoYPago($conexion, $datos)
{
    $metodoPago = trim((string)($datos['metodo_pago'] ?? ''));
    if (!in_array($metodoPago, ['efectivo', 'tarjeta', 'transferencia'], true)) {
        responderVenta('error', 'Metodo de pago invalido.', null, 400);
    }

    if (empty($_SESSION['logueado']) || empty($_SESSION['correo'])) {
        responderVenta('error', 'Debes iniciar sesion como empleado.', null, 401);
    }
    $consulta = $conexion->prepare(
        'SELECT id_empleado FROM empleados WHERE correo = ? LIMIT 1'
    );
    $consulta->execute([$_SESSION['correo']]);
    $empleado = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        responderVenta(
            'error',
            'El empleado de la sesion no existe.',
            null,
            401
        );
    }
    return [$empleado['id_empleado'], $metodoPago];
}
//Valida el cliente solo cuando la venta contiene membresias
//Las ventas de productos pueden guardar id_usuario como NULL como habiamos quedado en la bd alv
function validarCliente($conexion, $idUsuario, $tieneMembresias)
{
    if (!$tieneMembresias) {
        return null;
    }

    $idUsuario = trim((string)$idUsuario);
    if ($idUsuario === '') {
        responderVenta(
            'error',
            'Debes seleccionar un cliente registrado para vender una membresia.',
            null,
            400
        );
    }
    $consulta = $conexion->prepare(
        'SELECT id_usuario FROM usuariocliente WHERE id_usuario = ?'
    );
    $consulta->execute([$idUsuario]);
    if (!$consulta->fetchColumn()) {
        responderVenta(
            'error',
            'El cliente seleccionado no existe.',
            null,
            400
        );
    }
    return $idUsuario;
}
//Consulta para ver productos bloqueados y verifica que tengan existencias suficientes :v
function prepararProductos($conexion, $cantidades)
{
    $consulta = $conexion->prepare(
        'SELECT id_producto, nombre, precio_venta, stock
         FROM producto
         WHERE id_producto = ?
         FOR UPDATE'
    );
    $detalle = [];
    $subtotal = 0.0;
    foreach ($cantidades as $idProducto => $cantidad) {
        $consulta->execute([$idProducto]);
        $producto = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$producto) {
            throw new RuntimeException('El producto ' . $idProducto . ' no existe.');
        }
        if ((int)$producto['stock'] < $cantidad) {
            throw new RuntimeException(
                'Existencias insuficientes para ' . $producto['nombre'] .
                '. Disponible: ' . (int)$producto['stock'] .
                ', solicitado: ' . $cantidad . '.'
            );
        }
        $precio = (float)$producto['precio_venta'];
        $subtotal += $precio * $cantidad;
        $detalle[] = [
            'tipo' => 'producto',
            'id' => $idProducto,
            'cantidad' => $cantidad,
            'precio' => $precio
        ];
    }
    return ['detalle' => $detalle, 'subtotal' => $subtotal];
}
//Consulta las membresias actuales y evita asignar otra a un cliente activo me pashe me pashe
function prepararMembresias($conexion, $ids, $idUsuario)
{
    $consulta = $conexion->prepare(
        'SELECT id_membresia, nombre_tipo, precio, duracion_dias
         FROM membresia
         WHERE id_membresia = ?'
    );
    $detalle = [];
    $subtotal = 0.0;
    foreach ($ids as $idMembresia => $cantidad) {
        $consulta->execute([$idMembresia]);
        $membresia = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$membresia) {
            throw new RuntimeException(
                'La membresia ' . $idMembresia . ' no existe.'
            );
        }
        $comprobacion = $conexion->prepare(
            'SELECT id_membresia_cliente
             FROM membresia_cliente
             WHERE id_usuario = ? AND estado = ?
             LIMIT 1'
        );
        $comprobacion->execute([$idUsuario, 'activo']);
        if ($comprobacion->fetchColumn()) {
            throw new RuntimeException(
                'El cliente ya tiene una membresia activa.'
            );
        }
        $precio = (float)$membresia['precio'];
        $subtotal += $precio;
        $detalle[] = [
            'tipo' => 'membresia',
            'id' => $idMembresia,
            'cantidad' => 1,
            'precio' => $precio,
            'duracion_dias' => (int)$membresia['duracion_dias']
        ];
    }
    return ['detalle' => $detalle, 'subtotal' => $subtotal];
}
//Genera IDs varchar consecutivos usados por las tablas de venta
function generarId($conexion, $tabla, $columna, $prefijo)
{
    $permitidas = [
        'venta' => 'id_venta',
        'detalle_venta' => 'id_detalle_venta',
        'pago' => 'id_pago',
        'membresia_cliente' => 'id_membresia_cliente'
    ];

    if (!isset($permitidas[$tabla]) || $permitidas[$tabla] !== $columna) {
        throw new RuntimeException('Identificador no permitido.');
    }
    //El punto de inicio cambia según el largo del prefijo, como MC.
    $inicioNumero = strlen($prefijo) + 1;
    $consulta = $conexion->query(
        "SELECT COALESCE(MAX(CAST(SUBSTRING($columna, $inicioNumero, 9) AS UNSIGNED)), 0) + 1
         FROM $tabla
         WHERE $columna LIKE '$prefijo%'"
    );
    $numero = (int)$consulta->fetchColumn();
    $anchoNumero = $tabla === 'membresia_cliente' ? 8 : 9;
    return $prefijo . str_pad((string)$numero, $anchoNumero, '0', STR_PAD_LEFT);
}
//Guarda venta, detalle, stock, membresia y pago dentro de una transaccion
function guardarVentaCompleta(
    $conexion,
    $idEmpleado,
    $idUsuario,
    $metodoPago,
    $detalle,
    $subtotal
) {
    $idVenta = generarId($conexion, 'venta', 'id_venta', 'V');
    $estadoPago = $metodoPago === 'efectivo' ? 'completada' : 'pendiente';
    $estadoMembresia = $estadoPago === 'completada' ? 'activo' : 'inactivo';

    $insertarVenta = $conexion->prepare(
        'INSERT INTO venta
         (id_venta, fecha_venta, subtotal, iva, total, metodo_pago,
          estado, id_usuario, id_empleado)
         VALUES (?, NOW(), ?, 0.00, ?, ?, ?, ?, ?)'
    );
    $insertarVenta->execute([
        $idVenta,
        $subtotal,
        $subtotal,
        $metodoPago,
        'pendiente',
        $idUsuario,
        $idEmpleado
    ]);
    $numeroDetalle = (int)$conexion->query(
        "SELECT COALESCE(MAX(CAST(SUBSTRING(id_detalle_venta, 2, 9) AS UNSIGNED)), 0)
         FROM detalle_venta
         WHERE id_detalle_venta LIKE 'D%'"
    )->fetchColumn();
    $insertarDetalle = $conexion->prepare(
        'INSERT INTO detalle_venta
         (id_detalle_venta, id_venta, id_producto, id_membresia,
          cantidad, precio_unitario)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $descontarStock = $conexion->prepare(
        'UPDATE producto SET stock = stock - ? WHERE id_producto = ?'
    );
    foreach ($detalle as $linea) {
        $numeroDetalle++;
        $idDetalle = 'D' . str_pad((string)$numeroDetalle, 9, '0', STR_PAD_LEFT);
        if ($linea['tipo'] === 'producto') {
            $insertarDetalle->execute([
                $idDetalle,
                $idVenta,
                $linea['id'],
                null,
                $linea['cantidad'],
                $linea['precio']
            ]);
            $descontarStock->execute([
                $linea['cantidad'],
                $linea['id']
            ]);
        } else {
            $insertarDetalle->execute([
                $idDetalle,
                $idVenta,
                null,
                $linea['id'],
                1,
                $linea['precio']
            ]);
            $fechaInicio = date('Y-m-d H:i:s');
            $fechaVencimiento = date(
                'Y-m-d H:i:s',
                strtotime('+' . $linea['duracion_dias'] . ' days')
            );
            $idMembresiaCliente = generarId(
                $conexion,
                'membresia_cliente',
                'id_membresia_cliente',
                'MC'
            );
            //MC usa dos letras y la columna permite diez caracteres en total
            if (strlen($idMembresiaCliente) > 10) {
                throw new RuntimeException(
                    'Se alcanzo el limite de identificadores de membresia cliente.'
                );
            }
            $insertarMembresia = $conexion->prepare(
                'INSERT INTO membresia_cliente
                 (id_membresia_cliente, fecha_inicio, fecha_vencimiento,
                  estado, id_membresia, id_usuario, id_venta)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $insertarMembresia->execute([
                $idMembresiaCliente,
                $fechaInicio,
                $fechaVencimiento,
                $estadoMembresia,
                $linea['id'],
                $idUsuario,
                $idVenta
            ]);
        }
    }
    $idPago = generarId($conexion, 'pago', 'id_pago', 'P');
    $insertarPago = $conexion->prepare(
        'INSERT INTO pago
         (id_pago, id_venta, monto, metodo_pago, fecha_pago, estado, id_promocion)
         VALUES (?, ?, ?, ?, CURDATE(), ?, NULL)'
    );
    $insertarPago->execute([
        $idPago,
        $idVenta,
        $subtotal,
        $metodoPago,
        $estadoPago
    ]);
    $conexion->commit();
    responderVenta(
        'success',
        'Venta registrada correctamente.',
        [
            'id_venta' => $idVenta,
            'id_pago' => $idPago,
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'iva' => '0.00',
            'total' => number_format($subtotal, 2, '.', ''),
            'estado_venta' => 'pendiente',
            'estado_pago' => $estadoPago,
            'metodo_pago' => $metodoPago
        ]
    );
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderVenta('error', 'Metodo no permitido.', null, 405);
}
$datos = obtenerDatosVenta();
$items = validarItems($datos['items'] ?? []);
try {
    $conexion = conectaDB();
    [$idEmpleado, $metodoPago] = obtenerEmpleadoYPago($conexion, $datos);

    $tieneMembresias = count($items['membresias']) > 0;
    $idUsuario = validarCliente(
        $conexion,
        $datos['id_usuario'] ?? null,
        $tieneMembresias
    );
    $conexion->beginTransaction();
    $resultadoProductos = prepararProductos($conexion, $items['productos']);
    $resultadoMembresias = $tieneMembresias
        ? prepararMembresias($conexion, $items['membresias'], $idUsuario)
        : ['detalle' => [], 'subtotal' => 0.0];

    $detalle = array_merge(
        $resultadoProductos['detalle'],
        $resultadoMembresias['detalle']
    );
    $subtotal = round(
        $resultadoProductos['subtotal'] + $resultadoMembresias['subtotal'],
        2
    );
    guardarVentaCompleta(
        $conexion,
        $idEmpleado,
        $idUsuario,
        $metodoPago,
        $detalle,
        $subtotal
    );
} catch (RuntimeException $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    responderVenta('error', $error->getMessage(), null, 409);
} catch (Throwable $error) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    responderVenta('error', 'No se pudo registrar la venta.', null, 500);
}
