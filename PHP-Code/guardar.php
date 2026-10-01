<?php

require_once("libreria.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //hagarramos del html toda la informacion
    //require 'validacion.php';

    $nombre  = trim($_POST['nombre']);
    $password = trim($_POST['contraseña']);
    $confirmar    = trim($_POST['confirmar']);
    $correo = trim($_POST["correo"]); 

    //se verifica si no estan vacios los campos a llenar
    if (empty($nombre) || empty($password) || empty($confirmar)) {
        echo "<script>
                alert('Error: Por favor completa todos los campos del formulario.');
                window.location.href = 'alta_cliehtml.html';
              </script>";
        exit();
    }

    //comprobamos si la contraseña dada y la contraseña a confirmar son iguales
    if ($password!==$confirmar){
        echo "<script>
                alert('Error: Las contraseñas no coinciden. Intenta de nuevo.');
                window.location.href = 'alta_cliehtml.html';
              </script>";
        exit();
    }
    
    $conexion=conectaDB();
    if (!correoExistente($correo,$conexion))
    {
        $query="INSERT INTO Loguin\
                VALUES (?,?,?)";
        $Puente=$conexion->prepare($query);//aqui deberia continuar el de registrar clientes
        //ya esta la consulta, solo basta con con poner esto $Puente->execute([$id,$correo,$contraseña]);
        // se recomienda crear una funcion que vaya incrementando asi US01 US02 US03 US04
    }
    else
    {
        echo "<script>
                alert('Error: El correo ya se encuetra registrado');
                window.location.href = '../HTML-Code/alta_cliehtml.html';
              </script>";
        exit();
    }
    


    /*todo esto va al txt
    $linea = "nombre: $nombre | Password: $password\n" . PHP_EOL;

    //guardado en el txt, file append es para escribir al ultimo
    file_put_contents("datos.txt", $linea, FILE_APPEND);

    echo "<script>
            alert('¡Cliente registrado correctamente!');
            window.location.href = 'alta_cliehtml.html';
          </script>";
    exit();*/
}
?>