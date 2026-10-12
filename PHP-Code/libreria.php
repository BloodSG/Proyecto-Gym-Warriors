<?php
//es directamente mejor creear una libreria con funciones que se van a repetir en algunos archivos, me vi en la necesidad de 
//hacer este archivo para almacenar la base de datos y reenviar el codigo de recuperacion

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

function generaCodigo()
{
    $codigoRecupera=rand(100000, 999999);
    return $codigoRecupera;
}

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
function noEnviado(string $remitente, $conexion)
{   
    $codigo=NULL;
    $token=NULL;
    $query="SELECT codigo_recuperacion, token_expiracion
            FROM Loguin  
            WHERE correo = ?";
    $Puente=$conexion->prepare($query);
    $Puente->execute([$remitente]);

    $fila = $Puente->fetch(PDO::FETCH_ASSOC);
    
    if ($fila) 
    {
        $codigo = $fila['codigo_recuperacion'];
        $token = $fila['token_expiracion'];
        if ($token && $codigo)
        {
            $ahora = date('Y-m-d H:i:s');
            if ($token > $ahora) 
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    } 
    else 
    {
        return false;
    }
}
function enviaCorreoRecuperacion(string $remitente, string $nombre, string $cartero,string $codigo, string $apikey, $conexion)
{   
    $enviado=noEnviado($remitente, $conexion);
    if (!$enviado)
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
                    . '<p>Este código es válido por 5 minutos</p>'#falta implemetar un contador para que caduque 
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
    else
    {
        echo "<script>
            alert('Debes esperar 5 minutos para generar otro codigo');
            window.location.href = '../HTML-Code/codigoRecuperar.html';
        </script>";
        exit;
            }
}


function cerrarSession()
{   
    if (session_status() == PHP_SESSION_ACTIVE)
    {
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

