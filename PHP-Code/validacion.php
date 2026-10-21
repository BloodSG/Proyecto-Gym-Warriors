<?php 
    // 1. SIEMPRE arranca la sesión al principio del archivo si vas a manejar logins
    session_start();

    $entramos = false; 

    if (isset($_POST['accion']) && $_POST['accion'] == 'login') 
    {
        $correo_ingresado = trim($_POST['usuario']);
        $password_ingresada = trim($_POST['password']);
    
        require_once 'libreria.php';
        $pdo = conectaDB();
        
        // Buscar el correo en la tabla de clientes (loguin) y en la de Empleados
        $sql = "SELECT id_usuario, correo, contraseña, NULL AS subrol 
                FROM loguin 
                WHERE correo = :correo1
                UNION
                SELECT id_empleado AS id_usuario, correo, contraseña, puesto AS subrol 
                FROM Empleados 
                WHERE correo = :correo2";
                
        $stmt = $pdo->prepare($sql);
        
        // Se envía el correo a ambos marcadores para que busque en las dos tablas
        $stmt->execute([
            'correo1' => $correo_ingresado,
            'correo2' => $correo_ingresado
        ]);

        $usuario_guardado = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($usuario_guardado)
        {
            // Validar la contraseña ingresada contra el hash de la base de datos
            if (password_verify($password_ingresada, rtrim($usuario_guardado['contraseña'])))
            {
                $entramos = true;

                
                $_SESSION['logueado'] = true;
                $_SESSION['id_usuario'] = $usuario_guardado['id_usuario'];
                $_SESSION['correo'] = $usuario_guardado['correo'];
                
                
                if (!empty($usuario_guardado['subrol']))
                {
                    $_SESSION['subrol'] = $usuario_guardado['subrol'];
                    
                    if($_SESSION['subrol'] == 'Manager' || $_SESSION['subrol'] == 'Recepcionista')
                    {
                        header("Location: ../HTML-Code/admin.html");
                        exit();
                    }
                    else 
                    {
                        
                        header("Location: ../HTML-Code/admin.html"); 
                        exit();
                    }
                }
                
                else
                {
                    $_SESSION['tipo_usuario'] = "Cliente";
                    header("Location: ../HTML-Code/princi.html");
                    exit();
                }
            }
        }
    }

    if ($entramos == false) {
        echo "<script>
                alert('Usuario o contraseña incorrectos.');
                window.history.back(); // Lo regresa al formulario
              </script>";
    }
?>