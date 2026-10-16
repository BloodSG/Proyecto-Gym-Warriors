<?php

    require_once("libreria.php");

    function generar_IDCliente($conexion)
    {
        $consulta = "SELECT MAX(id_usuario) as ultimo FROM usuariocliente WHERE id_usuario LIKE 'CLI%'";
        $stmt = $conexion->prepare($consulta);
        $stmt -> execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($res && $res['ultimo']) {
            $num = (int)substr($res['ultimo'], 3  )+1;
            
        } 
        else 
        {
            $num = 1;
        }
        
        return 'CLI' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }



    if ($_SERVER["REQUEST_METHOD"] == "POST") 
    {
        $nombre = trim($_POST['nombre']);
        $apellido_1 = trim($_POST['apellido_1']);
        $apellido_2 = !empty(trim($_POST['apellido_2'])) ? trim($_POST['apellido_2']) : null;
        $correo = trim($_POST['correo']);
        $id_rol = "RCLI";
        $tiene_cuenta = 0;

        if (empty($nombre) || empty($apellido_1) || empty($correo)) 
        {
            echo "<script>
                    alert('Por favor, complete todos los campos obligatorios.');
                    window.history.back();
                  </script>";
            exit();
        }

        $conexion = conectaDB();

        $id_nuevo = generar_IDCliente($conexion);

        try
        {
            $consulta_alta = "INSERT INTO usuariocliente (id_usuario, nombre, apellido_1, apellido_2, ID_Rol,tiene_cuenta ,correo) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $puente_alta = $conexion->prepare($consulta_alta);
            $puente_alta->execute([$id_nuevo, $nombre, $apellido_1, $apellido_2, $id_rol, $tiene_cuenta, $correo]);

            echo "<script>
                alert('¡Cliente registrado exitosamente!');
                window.location.href = '../HTML-Code/admin.html';
            </script>"; 
            exit();

        }

        catch (PDOException $e) 
        { 
        echo "<script>
                alert('Error en BD: " . addslashes($e->getMessage()) . "');
                window.history.back();
            </script>"; 
        }


    }

?>