<?php
//aqui recibire los datos del usuario que tiene seccion iniciada
function verificar_permisos($roles_permitidos=[]){
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    //checo que el usuario si haya iniciado sesion
    if(!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'Empleado'){
        echo"<script>
                alert('Acceso no autorizado.');
                window.location.href = 'loginView.html';
            </script>";
        exit();
    }

    //checo que rol tiene ahorita el usuario
    $subrol_actual=$_SESSION['subrol'] ?? '';

    //compruebo si el rol de la seccion ouede acceder a la pagina o no
    if(!in_array($subrol_actual, $roles_permitidos)){
        http_response_code(403);
        echo"<script>
                alert('Acceso denegado: No tienes permiso para ver este módulo.');
                window.location.href = 'index.html';
            </script>";
        exit();
    }
}
?>