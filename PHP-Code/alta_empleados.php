<?php
    require_once("libreria.php");

    function generar_IDEmpleado($conexion)
    {
        $consulta = "SELECT MAX(id_empleado) as ultimo FROM Empleados WHERE id_empleado LIKE 'EMP%'";
        $stmt = $conexion->prepare($consulta);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res && $res['ultimo']) {
            $num = (int)substr($res['ultimo'], 3) + 1;
        }
        else
        {
            $num = 1;
        }

        return 'EMP' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        $curp = trim($_POST['curp']);
        $rfc = !empty(trim($_POST['rfc'] ?? '')) ? trim($_POST['rfc']) : null;
        $correo = trim($_POST['correo']);
        $salario = trim($_POST['salario']);
        $fecha_contratacion = trim($_POST['fecha_contratacion']);
        $puesto = isset($_POST['puesto']) ? trim($_POST['puesto']) : '';
        $roles = [
            'Entrenador' => 'ROL_ENT',
            'Manager' => 'ROL_MAN',
            'Recepcionista' => 'ROL_REC'
        ];
        $id_rol = $roles[$puesto] ?? null;
        $contra = trim($_POST['contra']);
        $contraconfirm = trim($_POST['contraconfirm']);
        $turno = trim($_POST['turno']);
        $estado_laboral = trim($_POST['estado_laboral']);
        $nombre_emergencia = !empty(trim($_POST['contacto_emergencia_nom'])) ? trim($_POST['contacto_emergencia_nom']) : null;
        $telefono_emergencia = !empty(trim($_POST['contacto_emergencia_tel'])) ? trim($_POST['contacto_emergencia_tel']) : null;
        //hola
        if (empty($curp) || empty($correo) || empty($salario) || empty($fecha_contratacion) || empty($puesto) || empty($contra) || empty($contraconfirm) || empty($turno) || empty($estado_laboral))
        {
            echo "<script>
                    alert('Por favor, complete todos los campos obligatorios.');
                    window.history.back();
                  </script>";
            exit();
        }

        if ($contra !== $contraconfirm)
        {
            echo "<script>
                    alert('Las contraseñas no coinciden. Por favor, inténtelo de nuevo.');
                    window.history.back();
                  </script>";
            exit();
        }

        $conexion = conectaDB();

        $id_nuevo = generar_IDEmpleado($conexion);

        try
        {
            $consulta_alta_empleado = "INSERT INTO Empleados (id_empleado, curp, rfc, correo, salario, fecha_contratacion, puesto, contraseña, turno, estado_laboral, contacto_emergencia_nom, contacto_emergencia_tel, id_rol) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $puente_alta = $conexion->prepare($consulta_alta_empleado);
            $puente_alta->execute([$id_nuevo, $curp, $rfc, $correo, $salario, $fecha_contratacion, $puesto, $contra, $turno, $estado_laboral, $nombre_emergencia, $telefono_emergencia, $id_rol]);

            echo "<script>
                alert('¡Empleado registrado exitosamente!');
                window.location.href = '../HTML-Code/admin.html';
            </script>";
            exit();

        }
        catch(PDOException $e)
        {
            echo "<script>
                alert('Error al registrar el empleado: " . $e->getMessage() . "');
                window.history.back();
                </script>";
        }
    }
?>