<?php
require_once("conectarBaseDatos.php");

session_start();
if (!isset($_SESSION["reset_email"])) // isset signica que si existe una sesiono
{ 
    header("Location: forgot-password.php"); 
    exit;
}
echo $_SESSION["reset_email"];


function verificaContraseña(string $contraseña1, string $contraseña2)
{
    $caracteresNopermitidos='/[\s\'"\\\\<>]/';
    $valida=false;
    if ($contraseña1== $contraseña2)
        {
            if (strlen($contraseña1)==10)
                {
                    if (preg_match($caracteresNopermitidos,$contraseña1))
                        {
                            echo "<script>alert('Las contraseñas contiene caracteres prohibidos'); window.history.back();</script>";
                        }
                    else
                    {   
                        $valida=true;
                    }
                }
                else
                {
                    echo "<script>alert('Las contraseñas debe de ser de 10 caracteres'); window.history.back();</script>";
        exit;
                }
        }
    else
    {
        echo "<script>alert('Las contraseñas no coinciden'); window.history.back();</script>";
    }
    return $valida;
}


if ($_SERVER["REQUEST_METHOD"] === "POST") 
{
    $newPassword=trim($_POST["contraseña"]);
    $comparePassword=trim($_POST["compara"]);
    $correo=$_SESSION["reset_email"];

    if (verificaContraseña($newPassword,$comparePassword))
        {
            $conexion=conectaDB();
            $query="UPDATE Loguin 
                    SET contraseña = ?
                    WHERE correo = ?";

            $Puente=$conexion->prepare($query);
            $Puente->execute([$newPassword, $correo]);
            
            $_SESSION = array();
            // Borrar la cookie de sesion
            if (ini_get("session.use_cookies")) 
            {
                $params = session_get_cookie_params();
                setcookie
                (
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

            // destruye la session
            session_destroy();

            header("Location: ../HTML-Code/loginView.html");
            exit();
        }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar contraseña</title>
</head>
<body>
    <div class="card">
        <h2>Reestablece tu contraseña</h2>
        <form action="" method="POST">
            <div>
            <p>Ingresa la nueva contraseña</p>
            <input type="text" name="contraseña" required placeholder="Al menos 8 caracteres" value="">
            </div>

            <div>
            <p>Verifica la contraseña</p>
            <input type="password" name="compara" required placeholder="••••••••" value="">
            </div>

            <div>
            <button type="submit", name="comprobar">Cambiar</button>

            <div>
        </form>
    </div>
</body>