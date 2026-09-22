<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //hagarramos del html toda la informacion
    //require 'validacion.php';

    $nombre  = trim($_POST['nombre']);
    $password = trim($_POST['contraseña']);
    $confirmar    = trim($_POST['confirmar']);

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
   
    //todo esto va al txt
    $linea = "nombre: $nombre | Password: $password\n" . PHP_EOL;

    //guardado en el txt, file append es para escribir al ultimo
    file_put_contents("datos.txt", $linea, FILE_APPEND);

    echo "<script>
            alert('¡Cliente registrado correctamente!');
            window.location.href = 'alta_cliehtml.html';
          </script>";
    exit();
}
?>