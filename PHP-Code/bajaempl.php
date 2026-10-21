<?php
require_once("libreria.php");
$conexion = conectaDB();

$consulta = "SELECT id_empleado, curp, rfc, correo, salario, fecha_contratacion, puesto, turno, estado_laboral, contacto_emergencia_nom, contacto_emergencia_tel FROM Empleados";
$stmt = $conexion->prepare($consulta);
$stmt->execute();
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($empleados);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $accion = $_POST['accion'] ?? '';

    if ($accion === 'actualizar') {
        $id_empleado = trim($_POST['id_empleado']);
        $curp = trim($_POST['curp']);
        $rfc = !empty(trim($_POST['rfc'] ?? '')) ? trim($_POST['rfc']) : null;
        $correo = trim($_POST['correo']);
        $salario = trim($_POST['salario']);
        $fecha_contratacion = trim($_POST['fecha_contratacion']);
        $puesto = trim($_POST['puesto']);
        $turno = trim($_POST['turno']);
        $estado_laboral = trim($_POST['estado_laboral']);
        $nombre_emergencia = !empty(trim($_POST['contacto_emergencia_nom'] ?? '')) ? trim($_POST['contacto_emergencia_nom']) : null;
        $telefono_emergencia = !empty(trim($_POST['contacto_emergencia_tel'] ?? '')) ? trim($_POST['contacto_emergencia_tel']) : null;

        try {
            $sql = "UPDATE Empleados 
                    SET curp = ?, rfc = ?, correo = ?, salario = ?, fecha_contratacion = ?, puesto = ?, turno = ?, estado_laboral = ?, contacto_emergencia_nom = ?, contacto_emergencia_tel = ? 
                    WHERE id_empleado = ?";
            
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                $curp, $rfc, $correo, $salario, $fecha_contratacion, 
                $puesto, $turno, $estado_laboral, $nombre_emergencia, 
                $telefono_emergencia, $id_empleado
            ]);

            echo "<script>
                    alert('¡Empleado actualizado exitosamente!');
                    window.location.href = '../HTML-Code/bajaempl.html';
                  </script>";
            exit();

        } catch (PDOException $e) {
            echo "<script>
                    alert('Error al actualizar el empleado: " . addslashes($e->getMessage()) . "');
                    window.history.back();
                  </script>";
            exit();
        }
    } 
    else if ($accion === 'eliminar') {
        $id_empleado = trim($_POST['id_empleado']);

        try {
            $sql = "DELETE FROM Empleados WHERE id_empleado = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$id_empleado]);

            echo "<script>
                    alert('¡Empleado eliminado exitosamente!');
                    window.location.href = '../HTML-Code/bajaempl.html';
                  </script>";
            exit();

        } catch (PDOException $e) {
            echo "<script>
                    alert('Error al eliminar empleado: " . addslashes($e->getMessage()) . "');
                    window.history.back();
                  </script>";
            exit();
        }
    }
}
?>