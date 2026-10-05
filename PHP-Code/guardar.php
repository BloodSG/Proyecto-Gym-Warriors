<?php
require_once("libreria.php");

// Genera el ID basándose SOLO en la tabla loguin
function generarid($conexion) {
    $query = "SELECT MAX(id_usuario) as ultimo FROM loguin WHERE id_usuario LIKE 'CLI%'";
    $stmt = $conexion->prepare($query);
    $stmt->execute();
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if($res && $res['ultimo']) {
        $num = (int)substr($res['ultimo'], 3) + 1;
    } else {
        $num = 1;
    }
    return 'CLI' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Agarramos toda la información del formulario
    $nombre    = trim($_POST['nombre']); 
    $password  = trim($_POST['contraseña']);
    $confirmar = trim($_POST['confirmar']);
    $correo    = trim($_POST["correo"]); 

    // Verificamos que ningún campo esté vacío
    if (empty($nombre) || empty($password) || empty($confirmar) || empty($correo)) {
        echo "<script>
                alert('Error: Por favor completa todos los campos.');
<<<<<<< HEAD
                window.location.href = 'alta_cliehtml.html';
=======
                window.location.href = '../HTML-Code/alta_cliehtml.html';
>>>>>>> origin/main
              </script>";
        exit();
    }

    // Validamos que las contraseñas coincidan
    if ($password !== $confirmar){
        echo "<script>
                alert('Error: Las contraseñas no coinciden.');
<<<<<<< HEAD
                window.location.href = 'alta_cliehtml.html';
=======
                window.location.href = '../HTML-Code/alta_cliehtml.html';
              </script>";
        exit();
    }

    if (mb_strlen($password) != 10){
        echo "<script>
                alert('Error: La contraseña debe tener exactamente 10 caracteres.');
                window.location.href = '../HTML-Code/alta_cliehtml.html';
>>>>>>> origin/main
              </script>";
        exit();
    }
    
    $conexion = conectaDB();

    // Buscamos SOLO en loguin para evitar correos duplicados
    $query_buscar = "SELECT id_usuario FROM loguin WHERE correo = ?";
    $puente_buscar = $conexion->prepare($query_buscar);
    $puente_buscar->execute([$correo]);
    
    if (!$puente_buscar->fetch()) {
        
        // Generamos su nuevo ID (CLI001, etc.)
        $id_nuevo = generarid($conexion);
        
        // CREACIÓN DEL HASH DE SEGURIDAD
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Insertamos todo (ID, Nombre, Correo y Hash) directamente en loguin
            $query_loguin = "INSERT INTO loguin (id_usuario, nombre, correo, contraseña) VALUES (?, ?, ?, ?)";
            $puente_loguin = $conexion->prepare($query_loguin);
            
            $puente_loguin->execute([$id_nuevo, $nombre, $correo, $password_hash]);

            echo "<script>
                    alert('¡Registro completo y exitoso!');
<<<<<<< HEAD
                    window.location.href = 'alta_clienhtml.html';
=======
                    window.location.href = '../HTML-Code/loginView.html';
>>>>>>> origin/main
                </script>";
            exit();

        } catch (PDOException $e) {
            echo "<script>
                    alert('Error en BD: " . addslashes($e->getMessage()) . "');
<<<<<<< HEAD
                    window.location.href = 'alta_cliehtml.html';
=======
                    window.location.href = '../HTML-Code/alta_cliehtml.html';
>>>>>>> origin/main
                </script>";
            exit();
        }

    } else {
        echo "<script>
                alert('Error: Este correo ya se encuentra registrado.');
<<<<<<< HEAD
                window.location.href = 'alta_cliehtml.html';
=======
                window.location.href = '../HTML-Code/alta_cliehtml.html';
>>>>>>> origin/main
            </script>";
        exit();
    }
}
?>