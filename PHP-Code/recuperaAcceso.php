<?php
require_once("libreria.php");
session_start();




function caducidadCodigo(string $codigo,string $remitente, $conexion)// no es un erroe, simplemente no tiene especificado el tipo de dato
{
    $expiracion = date('Y-m-d H:i:s', strtotime('+5 minutes'));
    $query="UPDATE Loguin 
            SET codigo_recuperacion = ?, token_expiracion = ? 
            WHERE correo = ?";

    $Puente=$conexion->prepare($query);
    $Puente->execute([$codigo, $expiracion, $remitente]);
    echo "<Se establecion una duracion de 5 minutos";
    return true;
}



$conexion=conectaDB();

if ($_SERVER['REQUEST_METHOD']=="POST")
{
    //agarrary limpiar los datos del formulario
    $correo= trim($_POST['correo'] ?? '');
}

if (empty($correo))
{
    echo "<script>alert('Ingrese un correo electronico'); window.history.back();</script>";
    exit;
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) 
{
    echo "<script>alert('El correo electronico no es valido.'); window.history.back();</script>";
    exit;
}
try
{
    $query="SELECT nombre
            FROM Loguin
            WHERE correo=?";

    $Puente=$conexion->prepare($query); //sirve para preparar la consulta con el servidor antes de pasarle datos realies

    $Puente->execute((array)$correo);
    $usuario=$Puente->fetch(PDO::FETCH_ASSOC);

    if ($usuario)
        {   
            $nombre=$usuario["nombre"];
            echo "<script>alert('Usuario encontrado: $nombre');</script>";
            list($llaveapi,$cartero)=leerEnv();//agarramos los datos del .env
            $codigo=generaCodigo();

            $puente=$conexion->prepare("SELECT codigo_recuperacion, token_expiracion 
                                    FROM Loguin 
                                    WHERE correo=?");
            $puente->execute([$correo]);
            $usuario=$puente->fetch();

            $ahora=date('Y-m-d H:i:s');// obtenemos el dia y la hora

            if ($ahora < $usuario['token_expiracion']) // sorprendente mente se puede comparar la fecha y hora con < >
            {   
                $_SESSION['reset_email'] = $correo;
                $_SESSION['name_user'] = $nombre;
                header("Location: ../HTML-Code/codigoRecuperar.html");
                exit;
            } 
            else 
            {
                $enviar=enviaCorreoRecuperacion($correo, $nombre, $cartero, $codigo, $llaveapi, $conexion);
                if ($enviar)
                    {
                        if (caducidadCodigo($codigo,$correo,$conexion))
                            {
                                $_SESSION['reset_email'] = $correo;
                                $_SESSION['name_user'] = $nombre;
                                header("Location: ../HTML-Code/codigoRecuperar.html");
                                exit;
                            }
                    }
            }

        }
    else
        {
            echo "<script>alert('Usuario no encontrado'); window.history.back();</script>";
            exit;
        }
}
catch(Exception $e)
{
    die("". $e->getMessage());
}
?>