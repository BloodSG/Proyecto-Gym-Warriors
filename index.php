
<?php 
/*
//inicia la sesion obligatoriamente
session_start();
//Verifica si la variable de sesion del usuario no existe
if (!isset($_SESSION['usuario'])) 
{
    //si no esta logiado lo mandamos a iniciaar sesion
    header("Location: loginView.html");
    exit();
}
else
{
    header("Location: menuView.html");
    exit();
}
*/
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gym Warriors</title>
</head>
<body>
    <div class="contenedor">
        <header>
            <h1>Menu principal GYM WARRIORS</h1>
            <nav>
            <a href="index.php">Inicio</a>

            <a href="HTML-Code/alta_cliehtml.html">Registrate aqui</a>

            <a href="HTML-Code/loginView.html">Iniciar Sesión</a>
        </nav>
        </header>
        
    </div>

</body>
</html>