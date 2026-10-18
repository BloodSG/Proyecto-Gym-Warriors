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
        
        $sql = "SELECT * FROM loguin WHERE correo = :correo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['correo' => $correo_ingresado]);

        $usuario_guardado = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($usuario_guardado)
        {
            if (password_verify($password_ingresada, rtrim($usuario_guardado['contraseña'])))
            {
                $entramos = true;

                //GUARDAMOS LOS DATOS EN LA SESIÓN ANTES DE REDIRIGIR
                $_SESSION['logueado'] = true;
                $_SESSION['id_usuario'] = $usuario_guardado['id_usuario'];
                $_SESSION['correo'] = $usuario_guardado['correo'];
                //por si agregas nombre $_SESSION['nombre'] = $usuario_guardado['nombre'];
                require_once 'validacion_empleados.php';

                $variableRol = cargar_roles($correo_ingresado);
                
                //si es true significa que es empleado entonces entra a ver que tipo de empleado es 
                if($variableRol)
                {
                    $subrol = $_SESSION['subrol'];
                    if($subrol == 'Manager')
                    {
                        header("Location: ../HTML-Code/admin.html");
                    }
                    
                    else if ($subrol == 'Recepcionista')
                    {
                        header("Location: ../HTML-Code/admin.html");
                    }
                }

                //si no es true asume que es un cliente
                else
                {
                    $_SESSION['tipo_usuario'] = "Cliente";
                    header("Location: ../HTML-Code/princi.html");
                }
                
                
                ; 
                exit();
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