<?php
session_start();

function leerEnv($archivo='.env') 
{
    if (!file_exists($archivo)) 
    {
        return;
    }
    $lineas=file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) 
    {
        // Ignorar comentarios que empiezan con #
        if (strpos(trim($linea), '#') === 0) // strpos busca la posicion de un caracter en una cadena, si es 0 significa que es un comentario
        {
            continue;// trim elimina espaios en blanco al inicio y al final de una cadena
        }
        list($nombre, $valor)=explode('=', $linea, 2);// explode divide una cadena en un array, divide en nombre y valor, el 2 es para que solo divida en 2 partes
        $_ENV[trim($nombre)]=trim($valor);
        
    }
    $llaveapi=$_ENV['BREVO_API_KEY'] ?? '';#$_ENV es un array que contine las variables de entorno, el ?? es para que si no existe la variable, se le asigne un valor vacio
    $cartero=$_ENV['BREVO_SENDER_EMAIL'] ?? '';
    return [$llaveapi,$cartero];
}

function generaCodigo()
{
    $codigoRecupera=rand(100000, 999999);
    return $codigoRecupera;
}

function enviaCorreo(string $remitente, string $nombre, string $cartero,string $codigo, string $apikey)
{
    # prepara los datos par el uso de la API de brevo
    $mensaje=
    [
    'sender' => 
    [
        'name' => 'Soporte Gym Warriors',
        'email' => $cartero
    ],
    'to' => 
    [
        [
            'email' => $remitente,
            'name' => 'Gym Warriors'
        ]
    ],
    'subject' => 'Codigo de recuperacion de contraseña',
    'htmlContent' => '<html><body>'
                   . '<h2>Recuperación de Contraseña</h2>'
                   . '<p>'.$nombre.', has solicitado restablecer tu contraseña.</p>'
                   . '<p>Tu codigo de verificación es: <strong>' . $codigo . '</strong></p>'
                   . '<p>Este código es válido por 15 minutos.</p>'#falta implemetar un contador para que caduque 
                   . '</body>
                   </html>'
    ];
    //enviar la peticioon con cURL 
    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);#curlopt_returntransfer es para que curl_exec devuelva el resultado en lugar de imprimirlo
    curl_setopt($ch, CURLOPT_POST, true);#post es para que la peticcion sea de tipo post, osea, que envia datso al servidor
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mensaje));#postfields es para enviar los datos en formato yaison
    curl_setopt($ch, CURLOPT_HTTPHEADER, 
    [ #httpheader es para enviar caveceras http, para esto, la llave api y el contenido tipo yeison
        'accept: application/json',
        'api-key: ' . $apikey,
        'content-type: application/json',
        'User-Agent: PHP-Script-Proyecto'# para que brevo sepa que es un scipt de php y no un navegador, asi no te bloquesa
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);

    if ($error) 
        {
        echo "Error de conexión con cURL: " . $error;
        return False;
        } 
    else 
    {
    $resultado = json_decode($response, true);
    if (isset($resultado['messageId'])) 
        {
            echo "Correo enviado El codigo generado fue: <strong>" . $codigo . "</strong>";
            return true;
        } 
        else 
        {
            echo "Brevo rechazó el correo. Respuesta: " . $response;
            return False;
        }
}
}

function conectaDB()
{
    //estabelce la conexion con la base de datos utilizando PDO
    $servidor= "localhost"; //nombre del servidor, en este caso es localhost porque la base de datos esta en el mismo servidor que el script php
    $usuario= "sa";// nombre de usuario de la base de datos, para sql server es sa y para mysql es root
    $database= "Gym_warriors";// sin pierde, nombre de la base de datos
    $contraseña= ""; //contraseña en caso de que tenga

    try
    {
        $conexion=new PDO("sqlsrv:server=$servidor;database=$database",$usuario,$contraseña);//el oreden de los parametros es importante
        $conexion ->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);// esto es para que muestre los errores de la conexion en caso de que haya algunpñ

        return $conexion;
    }
    catch(Exception $e)
    {
        die("Error al conectar a la db". $e->getMessage()); //die es para que se detenga el script y muestre el error
    }
}

function caducidadCodigo(string $codigo,string $remitente, $conexion)// no es un erroe, simplemente no tiene especificado el tipo de dato
{
    $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $query="UPDATE Loguin 
            SET codigo_recuperacion = ?, token_expiracion = ? 
            WHERE correo = ?";

    $Puente=$conexion->prepare($query);
    $Puente->execute([$codigo, $expiracion, $remitente]);
    echo "<Se establecion una duracion de 15 minutos";
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
    $query="SELECT nombre+' '+apellidos AS Nombre
            FROM usuariocliente JOIN Loguin
            ON usuariocliente.id_usuario=Loguin.id_usuario
            WHERE Loguin.correo=?";

    $Puente=$conexion->prepare($query); //sirve para preparar la consulta con el servidor antes de pasarle datos realies

    $Puente->execute((array)$correo);
    $usuario=$Puente->fetch(PDO::FETCH_ASSOC);

    if ($usuario)
        {   
            $enviado=false;
            $nombre=$usuario["Nombre"];
            echo "<script>alert('Usuario encontrado: $nombre);</script>";
            list($llaveapi,$cartero)=leerEnv();
            $codigo=generaCodigo();
            $enviado=enviaCorreo($correo, $nombre, $cartero, $codigo, $llaveapi);

            if ($enviado)
                {
                    if (caducidadCodigo($codigo,$correo,$conexion))
                        {
                            $_SESSION['reset_email'] = $correo;
                            header("Location: verify.php");
                            exit;
                        }
                }

        }
    else
        {
            echo "<script>alert('Usuario encontrado: $usuario[Nombre]');  window.history.back();</script>";
        }
}
catch(Exception $e)
{
    die("". $e->getMessage());
}
?>