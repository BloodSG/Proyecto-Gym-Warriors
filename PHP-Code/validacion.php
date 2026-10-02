<?php 

    if (isset($_POST['correo'])) 
    {
        // saca la contraseña 
        $password_normal = $_POST['contraseña'];

        // Encripta esa contraseña 
        $_POST['contraseña'] = password_hash($password_normal, PASSWORD_DEFAULT);

        // Manda llamar al archivo de Nolasco
        require_once 'guardar.php';

        // Detiene la ejecución de este archivo por completo 
        exit();
    }

    
    $entramos = false; 

    // Verifica si el formulario mandó la orden de iniciar sesión (accion = login)
    if (isset($_POST['accion']) && $_POST['accion'] == 'login') 
    {
        //caprura datos con trim pa borrar espacios al inicio y al final
        $usuario_ingresado = trim($_POST['usuario']);
        $password_ingresada = trim($_POST['password']);
    
        //llama el archivo de Bladi que tiene las configuraciones de la base de datos
        require_once 'libreria.php';

        //corre la función  para abrir la conexión a SQL Server y la guarda en la variable $pdo
        $pdo = conectaDB();
        
        // Escribe la consulta SQL
        $sql = "SELECT * FROM usuariocliente WHERE nombre = :usuario";

        // Prepara la consulta en el motor de la base de datos 
        $stmt = $pdo->prepare($sql);
        // ingresamos el nombre q puisimos en en el nombre del usuarfio
        $stmt->execute(['usuario' => $usuario_ingresado]);

        $usuario_guardado = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($usuario_guardado)
        {
            //compara lo escrito con el hash de la bd  
            if (password_verify($password_ingresada, rtrim($usuario_guardado['password_hash'])))
            {
                $entramos = true;

                //aquin nos llevaria a la pagina principal
                header("Location: index.html");
            }
        }
    }
    

    if ($entramos == false) {


        echo "Usuario o contraseña incorrectos.";
    }
?>