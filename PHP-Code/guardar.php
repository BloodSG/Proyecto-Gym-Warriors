<?php
require_once("libreria.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // agarro los datos del formulario
    $password  = trim($_POST['contraseña']);
    $confirmar = trim($_POST['confirmar']);
    $correo    = trim($_POST["correo"]); 

    // checo que los campos no estén vacíos
    if (empty($password) || empty($confirmar) || empty($correo)) {
        echo "<script>
                alert('Error: Por favor completa todos los campos.');
                window.location.href = '../HTML-Code/alta_cliehtml.html';
              </script>";
        exit();
    }

    // checo que la contraseña y la confirmación coincidan y que la contraseña tenga exactamente 10 caracteres
    if ($password !== $confirmar){
        echo "<script>
                alert('Error: Las contraseñas no coinciden.');
                window.location.href = '../HTML-Code/alta_cliehtml.html';
              </script>";
        exit();
    }
    // checo que la contraseña tenga exactamente 10 caracteres
    if (mb_strlen($password) != 10){
        echo "<script>
                alert('Error: La contraseña debe tener exactamente 10 caracteres.');
                window.location.href = '../HTML-Code/alta_cliehtml.html';
              </script>";
        exit();
    }
    
    $conexion = conectaDB();

   
    try {
        // checo si el correo ya está registrado en la tabla usuariocliente y si tiene_cuenta es true
        $query_verificar = "SELECT id_usuario, tiene_cuenta, correo FROM usuariocliente WHERE correo = ?";
        $stmt_verificar = $conexion->prepare($query_verificar);
        $stmt_verificar->execute([$correo]);
        $cliente = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {
            echo "<script>
                    alert('Error: El correo ingresado no está registrado en el sistema del gimnasio.');
                    window.location.href = '../HTML-Code/alta_cliehtml.html';
                  </script>";
            exit();
        }

        // checo si el cliente ya tiene una cuenta activa
        if ($cliente['tiene_cuenta'] == 1 || $cliente['tiene_cuenta'] === true || strtolower($cliente['tiene_cuenta']) === 'true') {
            echo "<script>
                    alert('Error: Este usuario ya cuenta con un perfil registrado y acceso activo.');
                    window.location.href = '../HTML-Code/alta_cliehtml.html';
                  </script>";
            exit();
        }

        // agarro el id_usuario del cliente y genero el hash de la contraseña
        $id_usuario = $cliente['id_usuario'];
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $conexion->beginTransaction();

        // guardamos la información en la tabla loguin
        $query_loguin = "INSERT INTO loguin (id_usuario, correo, contraseña) VALUES (?, ?, ?)";
        $stmt_loguin = $conexion->prepare($query_loguin);
        $stmt_loguin->execute([$id_usuario, $correo, $password_hash]);

        // actualizo la tabla usuariocliente para indicar que el cliente ahora tiene una cuenta activa y actualizo su correo
        $query_update = "UPDATE usuariocliente SET tiene_cuenta = 1, correo = ? WHERE id_usuario = ?";
        $stmt_update = $conexion->prepare($query_update);
        $stmt_update->execute([$correo, $id_usuario]);

        // Confirmamos los cambios en ambas tablas
        $conexion->commit();

        echo "<script>
                alert('¡Registro y vinculación de cuenta exitosos!');
                window.location.href = '../HTML-Code/loginView.html';
              </script>";
        exit();

    } catch (PDOException $e) {
        // si no jala la transacción, hacemos rollback para revertir los cambios
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        
        echo "<script>
                alert('Error en BD: " . addslashes($e->getMessage()) . "');
                window.location.href = '../HTML-Code/alta_cliehtml.html';
              </script>";
        exit();
    }
}
?>