<?php 
    
    session_start();

    $entramos = false; 

    if (isset($_POST['accion']) && $_POST['accion'] == 'login') 
    {
        $correo_ingresado = trim($_POST['usuario']);
        $password_ingresada = trim($_POST['password']);
    
        require_once 'libreria.php';
        $pdo = conectaDB();
        
        // BUSCAMOS LA CONTRASEÑA EN AMBAS TABLAS PARA QUE PUEDAN ENTRAR EMPLEADOS Y CLIENTES
        $sql = "SELECT id_usuario, correo, contraseña FROM loguin WHERE correo = :correo1
                UNION
                SELECT id_empleado AS id_usuario, correo, contraseña FROM Empleados WHERE correo = :correo2";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'correo1' => $correo_ingresado,
            'correo2' => $correo_ingresado
        ]);

        $usuario_guardado = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($usuario_guardado)
        {
            if (password_verify($password_ingresada, rtrim($usuario_guardado['contraseña'])))
            {
                $entramos = true;

                // GUARDAMOS LOS DATOS EN LA SESIÓN ANTES DE REDIRIGIR
                $_SESSION['logueado'] = true;
                $_SESSION['correo'] = $usuario_guardado['correo'];
                
                require_once 'validacion_empleados.php';

               
                $variableRol = cargar_roles($correo_ingresado);
                
             
                $_SESSION['id_usuario'] = $usuario_guardado['id_usuario'];
                
                // si es true significa que es empleado entonces entra a ver que tipo de empleado es 
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
                    else 
                    {
                        // Por si entra un Entrenador u otro rol
                        header("Location: ../HTML-Code/admin.html");
                    }
                }
                // si no es true asume que es un cliente
                else
                {
                    $_SESSION['tipo_usuario'] = "Cliente";
                    header("Location: ../HTML-Code/princi.html");
                }
                
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