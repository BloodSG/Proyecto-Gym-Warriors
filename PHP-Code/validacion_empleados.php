<?php

require_once 'libreria.php';


function cargar_roles($correo) {
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    try {
        $conexion = conectaDB();
        
        // Consultamos directamente la tabla Loguin y Rol, descartando usuariocliente
        $query="SELECT Loguin.id_usuario, Loguin.nombre, Rol.nombre_rol
                FROM Loguin
                INNER JOIN Rol ON Loguin.id_rol = Rol.id_rol
                WHERE Loguin.correo = :correo";

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
            
            return true; // Éxito en la asignación
        } else {
            return false; // No se encontró el usuario o rol
        }
    }
    catch(PDOException $e) {
        
        return false;
    }
}
?>