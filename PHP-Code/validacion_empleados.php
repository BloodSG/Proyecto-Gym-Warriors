<?php

session_start();
//aqui pido la conexion a base de datos que hizo bladi
require_once 'libreria.php';
//tomar del login el usuario y contraseña
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $nombre=trim($_POST["usuario"]);
    $contra=trim($_POST["password"]);

    if(empty($nombre)||empty($contra)){
        echo"<script>
                alert('Error: Por favor llene todos los campos.');
                window.location.href='loginView.html';
            </script>";
        exit();
                
    }

    try{
        // conecion a la base de datos
        $conexion = conectaDB();
        //selecciono todo lo que mi modulo ocupe de la base de datos
        $query="SELECT usuariocliente.id_usuario, usuariocliente.nombre, Rol.nombre_rol
                FROM usuariocliente
                INNER JOIN Rol ON usuariocliente.id_rol = Rol.id_rol
                WHERE usuariocliente.correo = :correo OR usuariocliente.nombre = :nombre";

        $stmt=$conexion->prepare($query);
        $stmt->bindParam(":correo",$nombre);
        $stmt->bindParam(":nombre",$nombre);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($usuario){
            //guardo todos los datos
            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["tipo_usuario"] = "Empleado";
            $_SESSION["subrol"] = $usuario["nombre_rol"];
            
            echo"<script>
                    alert('¡Bienvenido " . $_SESSION["nombre"] . "!');
                    window.location.href = 'index.html';
                </script>";
            exit();

        }
        else{
            echo"<script>
                    alert('Error: Correo o contraseña incorrectos.');
                    window.location.href='loginView.html';
                </script>";
            exit();
        }

    }
    catch(PDOException $e) {
        echo"<script>
                alert('Error en la base de datos: " . addslashes($e->getMessage()) . "');
                window.location.href = 'loginView.html';
            </script>";
        exit();
    }



}

?>