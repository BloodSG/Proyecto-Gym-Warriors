<?php
require_once 'libreria.php';
//esto hace qye la carga de la respuesta sea en formato JSON
header('Content-Type: application/json');


function buscarMiembros(PDO $conexion, string $termino): array {
    try {
        $sql = "SELECT u.id_usuario AS id, 
                       CONCAT(u.nombre, ' ', u.apellido_1) AS nombre, 
                       m.nombre_tipo AS nombre_plan, 
                       mc.fecha_inicio, 
                       mc.fecha_vencimiento, 
                       mc.estado 
                FROM usuariocliente u 
                LEFT JOIN membresia_cliente mc ON u.id_usuario = mc.id_usuario
                LEFT JOIN membresia m ON mc.id_membresia = m.id_membresia";

        if (!empty($termino)) {
            $sql .= " WHERE CONCAT(u.nombre, ' ', u.apellido_1) LIKE :busqueda OR u.id_usuario = :id_exacto";
            $stmt = $conexion->prepare($sql);
            $stmt->bindValue(':busqueda', '%' . $termino . '%');
            $stmt->bindValue(':id_exacto', $termino); 
        } else {
            $sql .= " LIMIT 20";
            $stmt = $conexion->prepare($sql);
        }
        
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultados as &$row) {
            $row['fecha_inicio'] = $row['fecha_inicio'] ? date('d/m/Y', strtotime($row['fecha_inicio'])) : null;
            $row['fecha_vencimiento'] = $row['fecha_vencimiento'] ? date('d/m/Y', strtotime($row['fecha_vencimiento'])) : null;
        }

        return ['status' => 'success', 'resultados' => $resultados];

    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Error de BD al buscar: ' . $e->getMessage()];
    }
}

function renovarMembresia(PDO $conexion, string $id_usuario, string $nuevo_plan, $monto): array {
    if (!$id_usuario || !$nuevo_plan || !is_numeric($monto) || !is_finite((float) $monto) || (float) $monto <= 0) {
        return ['status' => 'error', 'message' => 'El monto debe ser un número mayor que cero.'];
    }

    try {
        $stmtPlan = $conexion->prepare('SELECT precio FROM membresia WHERE id_membresia = :plan');
        $stmtPlan->execute([':plan' => $nuevo_plan]);
        $precioPlan = $stmtPlan->fetchColumn();
        if ($precioPlan === false) {
            return ['status' => 'error', 'message' => 'El plan seleccionado no existe en la base de datos.'];
        }

        if (round((float) $monto, 2) !== round((float) $precioPlan, 2)) {
            return ['status' => 'error', 'message' => 'El monto debe ser exactamente igual al precio del plan seleccionado ($' . number_format((float) $precioPlan, 2) . ').'];
        }

        $stmtMembresia = $conexion->prepare('SELECT 1 FROM membresia_cliente WHERE id_usuario = :id_usuario');
        $stmtMembresia->execute([':id_usuario' => $id_usuario]);
        if (!$stmtMembresia->fetchColumn()) {
            return ['status' => 'error', 'message' => 'El usuario no tiene una membresía asignada previamente para renovar.'];
        }

        if ((float) $monto > 99999999.99) {
            return ['status' => 'error', 'message' => 'El monto excede el límite permitido.'];
        }

        $fecha_inicio = date('Y-m-d H:i:s');
        $fecha_vencimiento = date('Y-m-d H:i:s', strtotime('+1 month'));

        $sql = "UPDATE membresia_cliente 
                SET id_membresia = :plan, 
                    fecha_inicio = :inicio, 
                    fecha_vencimiento = :vencimiento, 
                    estado = 'activo' 
                WHERE id_usuario = :id_usuario";
        
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':plan' => $nuevo_plan,
            ':inicio' => $fecha_inicio,
            ':vencimiento' => $fecha_vencimiento,
            ':id_usuario' => $id_usuario
        ]);

        if ($stmt->rowCount() > 0) {
            return ['status' => 'success', 'message' => '¡Membresía renovada exitosamente por un mes!'];
        } else {
            return ['status' => 'error', 'message' => 'El usuario no tiene una membresía asignada previamente para renovar.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Error al renovar: ' . $e->getMessage()];
    }
}

function cancelarMembresia(PDO $conexion, string $id_usuario): array {
    if (!$id_usuario) {
        return ['status' => 'error', 'message' => 'ID de cliente inválido.'];
    }

    try {
        $sql = "UPDATE membresia_cliente SET estado = 'inactivo' WHERE id_usuario = :id_usuario";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        
        if ($stmt->rowCount() > 0) {
            return ['status' => 'success', 'message' => 'Membresía cancelada correctamente.'];
        } else {
            return ['status' => 'error', 'message' => 'No se encontró la membresía para cancelar.'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Error al cancelar: ' . $e->getMessage()];
    }
}

$conexion = conectaDB();
$metodo = $_SERVER["REQUEST_METHOD"];
$accion = $_REQUEST['accion'] ?? '';
$respuesta = ['status' => 'error', 'message' => 'Acción no válida.'];

if ($metodo === 'GET' && $accion === 'buscar') {
    $termino = filter_input(INPUT_GET, 'buscador', FILTER_SANITIZE_STRING) ?? '';
    $respuesta = buscarMiembros($conexion, $termino);
} 
elseif ($metodo === 'POST') {
    if ($accion === 'renovar') {
        $id_usuario = filter_input(INPUT_POST, 'id_cliente', FILTER_SANITIZE_STRING);
        $nuevo_plan = filter_input(INPUT_POST, 'tipo_membresia', FILTER_SANITIZE_STRING);
        $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
        
        $respuesta = renovarMembresia($conexion, $id_usuario, $nuevo_plan, $monto);
    } 
    elseif ($accion === 'cancelar') {
        $id_usuario = filter_input(INPUT_POST, 'id_cliente', FILTER_SANITIZE_STRING);
        $respuesta = cancelarMembresia($conexion, $id_usuario);
    }
}

echo json_encode($respuesta);
?>