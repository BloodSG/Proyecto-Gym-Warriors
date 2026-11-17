<?php

    require_once("libreria.php");

    function generar_IDMembresia($conexion)
    {
        $consulta = "SELECT MAX(id_membresia) as ultimo FROM membresia WHERE id_membresia LIKE 'M%'";
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
        $nombre_tipo = trim($_POST['nombre_tipo']);
        $precio = trim($_POST['precio']);
        $duracion_dias = trim($_POST['duracion_dias']);
        $incluye_rutina = isset($_POST['incluye_rutina']) ? 1 : 0;
        $incluye_dieta = isset($_POST['incluye_dieta']) ? 1 : 0;
        $caracteristicas = trim($_POST['caracteristicas']);
        $id_rol = "M";
        

        if (empty($nombre_tipo) || empty($precio) || empty($duracion_dias)|| empty($incluye_rutina)|| empty($incluye_dieta)) 
        {
            echo "<script>
                    alert('Por favor, complete todos los campos obligatorios.');
                    window.history.back();
                  </script>";
            exit();
        }

        $conexion = conectaDB();

        $id_nuevo = generar_IDMembresia($conexion);

        try
        {
            $consulta_alta = "INSERT INTO membresia (id_membresia, nombre_tipo, precio, duracion_dias, incluye_rutina, incluye_dieta, caracteristicas) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $puente_alta = $conexion->prepare($consulta_alta);
            $puente_alta->execute([$id_nuevo, $nombre_tipo, $precio, $duracion_dias, $incluye_rutina, $incluye_dieta, $caracteristicas]);

            echo "<script>
                alert('¡Membresía creada exitosamente!');
                window.location.href = '../HTML-Code/membrdispoadmin.html';
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