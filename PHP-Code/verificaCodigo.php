<?php
require_once("conectarBaseDatos.php");
session_start();
// si intentan entrar a verify.php sin pasar antes por el formulario de correo, los regresamos alv
if (!isset($_SESSION['reset_email'])) // isset signica que si existe una sesiono
{ 
    header("Location: forgot-password.php"); 
    exit;
}
$error="";


if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $codigoIngresado=trim($_POST['codigo']);
    $correo=$_SESSION['reset_email'];

    $conexion=conectaDB();
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

            header("Location: cambiarContraseña.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Código</title>
</head>
<body>
    <div class="card">
        <h2>Introduce tu Código</h2>
        <p>Enviado a: <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

        <?php if (!empty($error)): ?> <!-- si hay errores, los va a printear -->
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>  <!-- como es codigo php dentro de html, se le tiene que aviar donde termina la condicion, no se porque -->

        <!-- el formulario se apunta a si mismo (action="") solo cuando el html vive donde el codigo php -->
        <form action="" method="POST">
            <input type="text" name="codigo" required placeholder="Código de 6 dígitos" value="">
            <button type="submit">Verificar</button>
        </form>
    </div>
</body>
</html>