<?php
function verificar_permisos($roles_permitidos = []) {
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    // Verificamos que haya iniciado sesión
    if(!isset($_SESSION['tipo_usuario'])){
        echo"<script>
                alert('Acceso no autorizado.');
                window.location.href = 'loginView.html';
            </script>";
        exit();
    }

    // Obtenemos el rol o subrol actual del usuario
    $subrol_actual = $_SESSION['subrol'] ?? '';

    // Comprobamos si su rol está dentro de los permitidos para esta vista
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