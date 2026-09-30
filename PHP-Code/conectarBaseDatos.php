<?php
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
?>