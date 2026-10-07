<?php
session_start();

// Solo los usuarios que iniciaron sesión pueden entrar al CRUD.
if (empty($_SESSION['logueado']) || empty($_SESSION['id_usuario'])) {
    http_response_code(403);
    exit('Debes iniciar sesión para administrar empleados.');
}

// Evita que los datos de la base se interpreten como código HTML.
function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Devuelve un dato del empleado listo para mostrar en un campo HTML.
function valorFormulario($empleado, $campo)
{
    return escapar($empleado[$campo] ?? '');
}

// Regresa a la lista y muestra un mensaje de resultado.
function volverALista($mensaje)
{
    header('Location: crud_empleados.php?accion=listar&mensaje=' . rawurlencode($mensaje));
    exit();
}

// Busca los datos de un empleado para llenar el formulario de edición.
function obtenerEmpleado($conexion, $id)
{
    $consulta = "SELECT u.id_usuario, u.nombre, u.apellidos, u.correo, u.telefono,
                        r.nombre_rol, d.direccion, d.dias_laborales,
                        CONVERT(varchar(5), d.hora_inicio, 108) AS hora_inicio,
                        CONVERT(varchar(5), d.hora_fin, 108) AS hora_fin
                 FROM dbo.usuariocliente u
                 INNER JOIN dbo.Rol r ON r.id_rol = u.id_rol
                 INNER JOIN dbo.Detalle_Empleado d ON d.id_usuario = u.id_usuario
                 WHERE u.id_usuario = ? AND u.id_usuario LIKE 'EMP%'";
    $sentencia = $conexion->prepare($consulta);
    $sentencia->execute([$id]);
    return $sentencia->fetch(PDO::FETCH_ASSOC);
}

// Revisa que los datos recibidos del formulario sean válidos.
function validarDatos($datos, $esActualizacion)
{
    $nombre = trim($datos['nombre'] ?? '');
    $apellidos = trim($datos['apellidos'] ?? '');
    $telefono = trim($datos['telefono'] ?? '');
    $correo = trim($datos['correo'] ?? '');
    $direccion = trim($datos['direccion'] ?? '');
    $rol = trim($datos['rol'] ?? '');
    $dias = $datos['dias'] ?? [];
    $horaInicio = trim($datos['hora_inicio'] ?? '');
    $horaFin = trim($datos['hora_fin'] ?? '');
    $password = $datos['password'] ?? '';
    $confirmar = $datos['confirmar_password'] ?? '';
    $rolesPermitidos = ['Entrenador', 'Manager', 'Recepcionista'];
    $diasPermitidos = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];

    if ($nombre === '' || mb_strlen($nombre) > 30 || $apellidos === '' || mb_strlen($apellidos) > 20) {
        throw new InvalidArgumentException('El nombre (máximo 30) y los apellidos (máximo 20) son obligatorios.');
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 50) {
        throw new InvalidArgumentException('Ingresa un correo válido de máximo 50 caracteres.');
    }
    if (!preg_match('/^[0-9]{10}$/', $telefono)) {
        throw new InvalidArgumentException('El teléfono debe contener exactamente 10 dígitos.');
    }
    if ($direccion === '' || mb_strlen($direccion) > 100) {
        throw new InvalidArgumentException('La dirección es obligatoria y admite hasta 100 caracteres.');
    }
    if (!in_array($rol, $rolesPermitidos, true)) {
        throw new InvalidArgumentException('Selecciona un rol válido.');
    }
    if (!is_array($dias)) {
        throw new InvalidArgumentException('Selecciona al menos un día laboral.');
    }
    $dias = array_values(array_unique(array_intersect($dias, $diasPermitidos)));
    if (!$dias) {
        throw new InvalidArgumentException('Selecciona al menos un día laboral.');
    }
    if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $horaInicio)
        || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $horaFin)
        || $horaInicio >= $horaFin) {
        throw new InvalidArgumentException('El horario no es válido: la hora de fin debe ser posterior a la de inicio.');
    }
    if (!$esActualizacion || $password !== '' || $confirmar !== '') {
        if (strlen($password) < 10) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 10 caracteres.');
        }
        if ($password !== $confirmar) {
            throw new InvalidArgumentException('Las contraseñas no coinciden.');
        }
    }

    return [
        'nombre' => $nombre,
        'apellidos' => $apellidos,
        'telefono' => $telefono,
        'correo' => $correo,
        'direccion' => $direccion,
        'rol' => $rol,
        'dias' => implode(',', $dias),
        'hora_inicio' => $horaInicio,
        'hora_fin' => $horaFin,
        'password' => $password,
    ];
}

// Guarda un empleado nuevo o actualiza uno existente.
// Revisa que el correo no esté usado por otra cuenta.
function correoDisponible($conexion, $correo, $id = '')
{
    $consulta = $conexion->prepare(
        'SELECT id_usuario FROM dbo.usuariocliente WITH (UPDLOCK, HOLDLOCK)
         WHERE correo = ? AND id_usuario <> ?'
    );
    $consulta->execute([$correo, $id]);
    return !$consulta->fetchColumn();
}

// Crea el siguiente identificador con el formato EMP0000001.
function siguienteIdEmpleado($conexion)
{
    $consulta = $conexion->query(
        "SELECT COALESCE(MAX(TRY_CONVERT(int, SUBSTRING(id_usuario, 4, 7))), 0) + 1
         FROM dbo.usuariocliente WITH (UPDLOCK, HOLDLOCK) WHERE id_usuario LIKE 'EMP%'"
    );
    $numero = (int) $consulta->fetchColumn();
    if ($numero > 9999999) {
        throw new RuntimeException('Se agotaron los identificadores disponibles para empleados.');
    }
    return 'EMP' . str_pad((string) $numero, 7, '0', STR_PAD_LEFT);
}

// Inserta los datos nuevos en las tres tablas relacionadas.
function crearEmpleado($conexion, $datos, $idRol)
{
    $id = siguienteIdEmpleado($conexion);
    $hash = password_hash($datos['password'], PASSWORD_DEFAULT);

    $consulta = $conexion->prepare(
        'INSERT INTO dbo.usuariocliente (id_usuario, nombre, apellidos, correo, telefono, fecha_registro, password_hash, id_rol)
         VALUES (?, ?, ?, ?, ?, GETDATE(), ?, ?)'
    );
    $consulta->execute([$id, $datos['nombre'], $datos['apellidos'], $datos['correo'], $datos['telefono'], $hash, $idRol]);

    $consulta = $conexion->prepare(
        'INSERT INTO dbo.Loguin (id_usuario, correo, [contraseña]) VALUES (?, ?, ?)'
    );
    $consulta->execute([$id, $datos['correo'], $hash]);

    $consulta = $conexion->prepare(
        'INSERT INTO dbo.Detalle_Empleado (id_usuario, direccion, dias_laborales, hora_inicio, hora_fin)
         VALUES (?, ?, ?, ?, ?)'
    );
    $consulta->execute([$id, $datos['direccion'], $datos['dias'], $datos['hora_inicio'], $datos['hora_fin']]);
}

// Actualiza los datos personales, de acceso y de horario.
function actualizarEmpleado($conexion, $datos, $id, $idRol)
{
    $consulta = $conexion->prepare(
        "UPDATE dbo.usuariocliente
         SET nombre = ?, apellidos = ?, correo = ?, telefono = ?, id_rol = ?
         WHERE id_usuario = ? AND id_usuario LIKE 'EMP%'"
    );
    $consulta->execute([$datos['nombre'], $datos['apellidos'], $datos['correo'], $datos['telefono'], $idRol, $id]);
    if ($consulta->rowCount() === 0 && !obtenerEmpleado($conexion, $id)) {
        throw new RuntimeException('No se encontró el empleado solicitado.');
    }

    if ($datos['password'] !== '') {
        $hash = password_hash($datos['password'], PASSWORD_DEFAULT);
        $consulta = $conexion->prepare(
            'UPDATE dbo.usuariocliente SET password_hash = ? WHERE id_usuario = ?'
        );
        $consulta->execute([$hash, $id]);

        $consulta = $conexion->prepare(
            'UPDATE dbo.Loguin SET correo = ?, [contraseña] = ? WHERE id_usuario = ?'
        );
        $consulta->execute([$datos['correo'], $hash, $id]);
    } else {
        // Si se deja vacía la contraseña, solo se actualiza el correo.
        $consulta = $conexion->prepare(
            'UPDATE dbo.Loguin SET correo = ? WHERE id_usuario = ?'
        );
        $consulta->execute([$datos['correo'], $id]);
    }

    $consulta = $conexion->prepare(
        'UPDATE dbo.Detalle_Empleado
         SET direccion = ?, dias_laborales = ?, hora_inicio = ?, hora_fin = ?
         WHERE id_usuario = ?'
    );
    $consulta->execute([$datos['direccion'], $datos['dias'], $datos['hora_inicio'], $datos['hora_fin'], $id]);
}

// Comprueba el rol y guarda todos los cambios juntos.
function guardarEmpleado($conexion, $datos, $id = null)
{
    $consulta = $conexion->prepare('SELECT id_rol FROM dbo.Rol WHERE nombre_rol = ?');
    $consulta->execute([$datos['rol']]);
    $idRol = $consulta->fetchColumn();
    if (!$idRol) {
        throw new RuntimeException('No existe ese rol. Ejecuta primero esquema_empleados.sql.');
    }
    $conexion->beginTransaction();
    try {
        // El bloqueo evita que dos altas usen el mismo correo o identificador.
        if (!correoDisponible($conexion, $datos['correo'], $id ?? '')) {
            throw new InvalidArgumentException('Ya existe una cuenta con ese correo.');
        }

        if ($id === null) {
            crearEmpleado($conexion, $datos, $idRol);
        } else {
            actualizarEmpleado($conexion, $datos, $id, $idRol);
        }
        $conexion->commit();
    } catch (Exception $error) {
        $conexion->rollBack();
        throw $error;
    }
}

// Comprueba que el usuario actual tenga el rol necesario para administrar empleados.
function esManager($conexion, $idUsuario)
{
    $consulta = $conexion->prepare(
        'SELECT r.nombre_rol
         FROM dbo.usuariocliente u
         INNER JOIN dbo.Rol r ON r.id_rol = u.id_rol
         WHERE u.id_usuario = ?'
    );
    $consulta->execute([$idUsuario]);
    return $consulta->fetchColumn() === 'Manager';
}

// Devuelve todos los empleados para mostrarlos en la tabla.
function listarEmpleados($conexion)
{
    $consulta = $conexion->query(
        "SELECT u.id_usuario, u.nombre, u.apellidos, u.correo, u.telefono,
                r.nombre_rol, d.direccion, d.dias_laborales,
                CONVERT(varchar(5), d.hora_inicio, 108) AS hora_inicio,
                CONVERT(varchar(5), d.hora_fin, 108) AS hora_fin
         FROM dbo.usuariocliente u
         INNER JOIN dbo.Rol r ON r.id_rol = u.id_rol
         INNER JOIN dbo.Detalle_Empleado d ON d.id_usuario = u.id_usuario
         WHERE u.id_usuario LIKE 'EMP%'
         ORDER BY u.apellidos, u.nombre"
    );
    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

// Elimina al empleado y su acceso dentro de una misma transacción.
function eliminarEmpleado($conexion, $id)
{
    $conexion->beginTransaction();
    try {
        $conexion->prepare('DELETE FROM dbo.Loguin WHERE id_usuario = ?')->execute([$id]);

        $consulta = $conexion->prepare(
            "DELETE FROM dbo.usuariocliente WHERE id_usuario = ? AND id_usuario LIKE 'EMP%'"
        );
        $consulta->execute([$id]);

        if ($consulta->rowCount() === 0) {
            throw new RuntimeException('No se encontró el empleado solicitado.');
        }

        $conexion->commit();
    } catch (Exception $error) {
        $conexion->rollBack();
        throw $error;
    }
}

// Comprueba el formato del identificador antes de consultar o borrar.
function idEmpleadoValido($id)
{
    return preg_match('/^EMP[0-9]{7}$/', $id) === 1;
}

// Imprime el formulario de alta o de edición.
function renderFormulario($empleado = null)
{
    $diasSeleccionados = $empleado ? explode(',', $empleado['dias_laborales']) : [];
    $roles = ['Entrenador', 'Manager', 'Recepcionista'];
    $dias = ['Lunes' => 'L', 'Martes' => 'Ma', 'Miercoles' => 'Mi', 'Jueves' => 'J', 'Viernes' => 'V'];
    $accion = $empleado ? 'actualizar' : 'crear';
    ?>
    <form action="crud_empleados.php" method="post">
        <input type="hidden" name="accion" value="<?= $accion ?>">
        <?php if ($empleado): ?><input type="hidden" name="id_usuario" value="<?= escapar($empleado['id_usuario']) ?>"><?php endif; ?>
        <div class="fila-doble">
            <div><label for="nombre">Nombre</label><input id="nombre" name="nombre" maxlength="30" value="<?= valorFormulario($empleado, 'nombre') ?>" required></div>
            <div><label for="apellidos">Apellidos</label><input id="apellidos" name="apellidos" maxlength="20" value="<?= valorFormulario($empleado, 'apellidos') ?>" required></div>
        </div>
        <div class="fila-doble">
            <div><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" maxlength="10" pattern="[0-9]{10}" value="<?= valorFormulario($empleado, 'telefono') ?>" required></div>
            <div><label for="correo">Correo electrónico</label><input id="correo" name="correo" type="email" maxlength="50" value="<?= valorFormulario($empleado, 'correo') ?>" required></div>
        </div>
        <label for="direccion">Dirección</label><input id="direccion" name="direccion" maxlength="100" value="<?= valorFormulario($empleado, 'direccion') ?>" required>
        <p>Rol</p>
        <div class="rol">
            <?php foreach ($roles as $rol): ?>
                <label class="roldis"><input type="radio" name="rol" value="<?= escapar($rol) ?>" <?= ($empleado['nombre_rol'] ?? '') === $rol ? 'checked' : '' ?> required> <?= escapar($rol) ?></label>
            <?php endforeach; ?>
        </div>
        <p>Días laborales</p>
        <div class="dia">
            <?php foreach ($dias as $dia => $abreviatura): ?>
                <label class="dia-check"><input type="checkbox" name="dias[]" value="<?= escapar($dia) ?>" <?= in_array($dia, $diasSeleccionados, true) ? 'checked' : '' ?>> <?= escapar($abreviatura) ?></label>
            <?php endforeach; ?>
        </div>
        <div class="fila-doble">
            <div><label for="hora_inicio">Hora de inicio</label><input id="hora_inicio" name="hora_inicio" type="time" value="<?= valorFormulario($empleado, 'hora_inicio') ?>" required></div>
            <div><label for="hora_fin">Hora de fin</label><input id="hora_fin" name="hora_fin" type="time" value="<?= valorFormulario($empleado, 'hora_fin') ?>" required></div>
        </div>
        <div class="fila-doble">
            <div><label for="password">Contraseña <?= $empleado ? '(dejar vacía para conservarla)' : '' ?></label><input id="password" name="password" type="password" minlength="10" <?= $empleado ? '' : 'required' ?>></div>
            <div><label for="confirmar_password">Confirmar contraseña</label><input id="confirmar_password" name="confirmar_password" type="password" minlength="10" <?= $empleado ? '' : 'required' ?>></div>
        </div>
        <button type="submit"><?= $empleado ? 'Guardar cambios' : 'Crear empleado' ?></button>
    </form>
    <?php
}

require_once __DIR__ . '/conexion.php';
if (!esManager($conexion, $_SESSION['id_usuario'])) {
    http_response_code(403);
    exit('Solo un Manager puede administrar empleados.');
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? 'listar';
$error = '';
$empleadoEditar = null;
$empleados = [];

try {
    // Cada opción del menú ejecuta una acción del CRUD.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($accion === 'crear') {
            $datos = validarDatos($_POST, false);
            guardarEmpleado($conexion, $datos);
            volverALista('Empleado registrado.');
        } elseif ($accion === 'actualizar') {
            $id = trim($_POST['id_usuario'] ?? '');
            if (!idEmpleadoValido($id)) {
                throw new InvalidArgumentException('Identificador de empleado no válido.');
            }
            $datos = validarDatos($_POST, true);
            guardarEmpleado($conexion, $datos, $id);
            volverALista('Empleado actualizado.');
        } elseif ($accion === 'eliminar') {
            $id = trim($_POST['id_usuario'] ?? '');
            if (!idEmpleadoValido($id)) {
                throw new InvalidArgumentException('Identificador de empleado no válido.');
            }
            eliminarEmpleado($conexion, $id);
            volverALista('Empleado eliminado.');
        }
    }

    // Si se pidió editar, busca ese registro; de lo contrario, carga la lista.
    if ($accion === 'editar') {
        $id = trim($_GET['id'] ?? '');
        $empleadoEditar = obtenerEmpleado($conexion, $id);
        if (!$empleadoEditar) {
            throw new RuntimeException('No se encontró el empleado solicitado.');
        }
    } else {
        $empleados = listarEmpleados($conexion);
    }
} catch (Throwable $excepcion) {
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }
    $error = $excepcion instanceof PDOException
        ? 'No se pudo completar la operación. Verifica que esquema_empleados.sql esté aplicado y que el empleado no tenga registros relacionados.'
        : $excepcion->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD de empleados | Fitness Warriors</title>
    <link rel="stylesheet" href="../CSS-Code/Reg_emplcss.css">
</head>
<body>
    <main class="formulario">
        <h1><?= $empleadoEditar ? 'Editar empleado' : 'Empleados registrados' ?></h1>
        <?php if ($error !== ''): ?><p role="alert"><?= escapar($error) ?></p><?php endif; ?>
        <?php if (isset($_GET['mensaje'])): ?><p role="status"><?= escapar($_GET['mensaje']) ?></p><?php endif; ?>
        <?php if ($empleadoEditar): ?>
            <?php renderFormulario($empleadoEditar); ?>
            <p><a href="crud_empleados.php?accion=listar">Volver a empleados</a></p>
        <?php else: ?>
            <p><a href="../HTML-Code/Reg_empl.html">Registrar empleado</a></p>
            <?php if (!$empleados): ?>
                <p>No hay empleados registrados.</p>
            <?php else: ?>
                <div style="overflow-x:auto">
                    <table>
                        <thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Rol</th><th>Días</th><th>Horario</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($empleados as $empleado): ?>
                            <tr>
                                <td><?= escapar($empleado['id_usuario']) ?></td>
                                <td><?= escapar($empleado['nombre'] . ' ' . $empleado['apellidos']) ?></td>
                                <td><?= escapar($empleado['correo']) ?></td>
                                <td><?= escapar($empleado['telefono']) ?></td>
                                <td><?= escapar($empleado['nombre_rol']) ?></td>
                                <td><?= escapar(str_replace(',', ', ', $empleado['dias_laborales'])) ?></td>
                                <td><?= escapar($empleado['hora_inicio'] . ' - ' . $empleado['hora_fin']) ?></td>
                                <td>
                                    <a href="crud_empleados.php?accion=editar&amp;id=<?= rawurlencode($empleado['id_usuario']) ?>">Editar</a>
                                    <form action="crud_empleados.php" method="post" onsubmit="return confirm('¿Eliminar este empleado? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_usuario" value="<?= escapar($empleado['id_usuario']) ?>">
                                        <button type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>