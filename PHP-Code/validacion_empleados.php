<?php

require_once 'libreria.php';


function cargar_roles($correo) {
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    try {
        $conexion = conectaDB();
        
        //busco la informacion del correo en la tabla de usuariocliente y obtengo el rol correspondiente
        $query="SELECT usuariocliente.id_usuario, usuariocliente.nombre, usuariocliente.apellido_1, Rol.nombre_rol
                FROM usuariocliente
                INNER JOIN Rol ON usuariocliente.id_rol = Rol.id_rol
                WHERE usuariocliente.correo = :correo";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(":correo", $correo);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($usuario){
            // Guardamos todos los datos en la sesión
            $_SESSION["id_usuario"] = $usuario["id_usuario"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["tipo_usuario"] = "Empleado"; 
            $_SESSION["subrol"] = $usuario["nombre_rol"];
            
            return true; 
        } else {
            return false; 
        }
    }
    catch(PDOException $e) {
        
        return false;
    }
}
?>