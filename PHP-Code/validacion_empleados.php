<?php

require_once 'libreria.php';

function cargar_roles($correo) {
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    try {
        $conexion = conectaDB();
        
        // busco el usuario en la tabla usuariocliente y obtengo su rol
        $query = "SELECT usuariocliente.id_usuario, usuariocliente.nombre, usuariocliente.apellido_1, Rol.nombre_rol
                FROM usuariocliente
                INNER JOIN Rol ON usuariocliente.id_rol = Rol.id_rol
                WHERE usuariocliente.correo = :correo";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(":correo", $correo);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($usuario){
            // guardo los datos del usuario en la sesión
            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["subrol"] = $usuario["nombre_rol"];
            
            $subrol = $usuario["nombre_rol"];

            // checo si el subrol es cliente o cualquier otro rol de empleado
            if ($subrol === 'Cliente') {
                //si la sesion es de un cliente retorno false para que no entre a la parte de empleados
                
                $_SESSION["tipo_usuario"] = "Cliente";
                return false; 
            } else {
                //si es un empleado retorno true para que entre a la parte de empleadosS
                $_SESSION["tipo_usuario"] = "Empleado";
                return true; 
            }
            
        } else {
            return false; 
        }
    }
    catch(PDOException $e) {
        return false;
    }
}
?>