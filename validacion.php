<?php 


if (isset($_POST['email'])) {

    $password_normal = $_POST['password'];

    $_POST['password'] = password_hash($password_normal, PASSWORD_DEFAULT);

    //aqui se llama lo de nolasco
    require_once 'guardar.php';
}


//si se intenta logear 
if (isset($_POST['accion']) && $_POST['accion'] == 'login') {
    
    $usuario_ingresado = trim($_POST['usuario']);
    $password_ingresada = trim($_POST['password']);
    
    $entramos = false; 

    $lineas = file("datos.txt");
    
    foreach ($lineas as $linea) {
        
        $pedazos = explode(" | ", $linea);
        
        if (count($pedazos) == 3) {
            
            $usuario_guardado = trim(str_replace("Usuario: ", "", $pedazos[0]));
            $password_guardada = trim(str_replace("Password: ", "", $pedazos[2]));

            if ($usuario_ingresado == $usuario_guardado) {
                
                
            }[
                if (password_verify($password_ingresada, $password_guardada)) {
                    $entramos = true; 
                    echo "Bienvenido al sisterma ";
                    break; 
                }
            }
        }
    }
    
    if ($entramos == false) {
        echo "Usuario o contraseña incorrectos.";
    }
} 
?>