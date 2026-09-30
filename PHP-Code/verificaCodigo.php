<?php
require_once("libreria.php");
session_start();
// si intentan entrar a verify.php sin pasar antes por el formulario de correo, los regresamos alv
if (!isset($_SESSION['reset_email'])) // isset signica que si existe una sesiono
{ 
    header("Location: forgot-password.php"); 
    exit;
}
$error="";
function caducidadCodigo(string $codigo,string $remitente, $conexion)// no es un erroe, simplemente no tiene especificado el tipo de dato
{
    $expiracion = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $query="UPDATE Loguin 
            SET codigo_recuperacion = ?, token_expiracion = ? 
            WHERE correo = ?";

    $Puente=$conexion->prepare($query);
    $Puente->execute([$codigo, $expiracion, $remitente]);
    echo "<Se establecion una duracion de 15 minutos";
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{   
    $accion = $_POST["pulsado"] ?? '';
    $conexion=conectaDB();
    if ($accion === "verificar") 
    {
        $codigoIngresado=trim($_POST['codigo']);
        $correo=$_SESSION['reset_email'];

        
        $puente=$conexion->prepare("SELECT codigo_recuperacion, token_expiracion 
                                    FROM Loguin 
                                    WHERE correo=?");
        $puente->execute([$correo]);
        $usuario=$puente->fetch();

        if ($usuario) // si existe un usuario
        {
            $ahora=date('Y-m-d H:i:s');// obtenemos el dia y la hora

            if ($codigoIngresado !== $usuario['codigo_recuperacion']) // comparra el codigo ingresado con el que hay en la base
            {
                $error="El código es incorrecto.";//lo que dice ahi
            } 
            elseif ($ahora > $usuario['token_expiracion']) // sorprendente mente se puede comparar la fecha y hora con < >
            {
                $error="El código ha expirado.";//lo que dice ahi
            } 
            else 
            {
                // se valido y se procede a cambiar la contraseña
                $puente=$conexion->prepare("UPDATE Loguin 
                                            SET codigo_recuperacion=NULL, token_expiracion=NULL 
                                            WHERE correo=?");
                $puente->execute([$correo]);

                header("Location: ../HTML-Code/nuevaContraseña.html");
                exit;
            }
        }
    }
    elseif ($accion === "reenviar") 
    {
        if (isset($_SESSION["reset_email"])) 
        {
            $correo = $_SESSION["reset_email"];
            list($llaveapi,$cartero)=leerEnv();//agarramos los datos del .env
            $codigo=generaCodigo();
            $nombre=$_SESSION["name_user"];
            $enviar=enviaCorreoRecuperacion($correo, $nombre, $cartero, $codigo, $llaveapi, $conexion);
            if ($enviar)
                {
                    if (caducidadCodigo($codigo,$correo,$conexion))
                        {
                            $_SESSION['reset_email'] = $correo;
                            header("Location: ../HTML-Code/codigoRecuperar.html");
                            exit;
                        }
                }
        }
    }
}
?>
