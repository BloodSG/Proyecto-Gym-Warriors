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

                // 2. GUARDAMOS LOS DATOS EN LA SESIÓN ANTES DE REDIRIGIR
                $_SESSION['logueado'] = true;
                $_SESSION['id_usuario'] = $usuario_guardado['id_usuario'];
                $_SESSION['correo'] = $usuario_guardado['correo'];
                // Si agregas la columna 'nombre' a tu BD, también puedes guardarla aquí:
                // $_SESSION['nombre'] = $usuario_guardado['nombre'];

                // 3. Ahora sí, lo mandamos a la página principal
                header("Location: ../HTML-Code/princi.html"); // Ojo: lo ideal sería que index fuera .php para poder leer la sesión
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