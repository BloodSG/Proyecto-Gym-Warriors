<?php
    require_once("libreria.php");

    function baja_empelados($conexion)
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