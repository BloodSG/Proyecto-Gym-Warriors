<?php
require_once("libreria.php");

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if (!isset($_SESSION["reset_email"])&& !isset($_SESSION["codigo_verificado"])) // isset signica que si existe una sesiono
{ 
    if ($_SESSION['codigo_verificado'] !== true) {
        echo "<script>alert('No has validado el codigo'); window.history.back();</script>";
    }
    header("Location: ../HTML-Code/loginView.html"); 
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
            $newPassword_hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $query="UPDATE Loguin 
                    SET contraseña = ?
                    WHERE correo = ?";

            $Puente=$conexion->prepare($query);
            $Puente->execute([$newPassword_hash, $correo]);
            
            cerrarSession();
        }
}
?>

