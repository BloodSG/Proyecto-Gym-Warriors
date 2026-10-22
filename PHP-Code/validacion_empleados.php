<?php

require_once 'libreria.php';

function cargar_roles($correo) {
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    try {
        $conexion=conectaDB();
        //primero busco si el correo esta en la tabla de empleados y obtengo su subrol
        $query_empleado="SELECT id_empleado, puesto, turno, salario, correo 
                        FROM Empleados 
                        WHERE correo = :correo";

        $stmt_emp=$conexion->prepare($query_empleado);
        $stmt_emp->bindParam(":correo", $correo);
        $stmt_emp->execute();
        $empleado=$stmt_emp->fetch(PDO::FETCH_ASSOC);

        if($empleado){
            //guarfo los datos del empleado en la sesión
            $_SESSION["id_usuario"]=$empleado["id_usuario"];
            $_SESSION["subrol"]=$empleado["puesto"];
            $_SESSION["tipo_usuario"]="empleado";
            //si es empleado retorno true
            return true;
        }
        
        //si no esta en la tabla de empleados busco si esta en la tabl a de clientes y obtengo su subrol
        $query_cliente="SELECT usuariocliente.id_usuario, usuariocliente.nombre, usuariocliente.apellido_1, Rol.nombre_rol
                        FROM usuariocliente
                        INNER JOIN Rol ON usuariocliente.id_rol = Rol.id_rol
                        WHERE usuariocliente.correo = :correo";

        $stmt_cli=$conexion->prepare($query_cliente);
        $stmt_cli->bindParam(":correo", $correo);
        $stmt_cli->execute();
        $usuario=$stmt_cli->fetch(PDO::FETCH_ASSOC);

        
        
        if($usuario){
            // guardo los datos del usuario en la sesión
            $_SESSION["id_usuario"]=$usuario["id_usuario"];
            $_SESSION["nombre"]=$usuario["nombre"];
            $_SESSION["subrol"]=$usuario["nombre_rol"];
            $_SESSION["tipo_usuario"]="Cliente";
            //si es cliente retorno false
            return false; 

        } 
        else {
            //si no esta en ninguna de las tablas retorno false
            return false; 
        }
    }
    catch(PDOException $e) {
        return false;
    }
}
?>