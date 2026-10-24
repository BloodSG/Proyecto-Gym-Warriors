<?php
require_once("libreria.php");
$conexion=conectaDB();

if ($_SERVER['REQUEST_METHOD'] == "POST")
{

    $nombre=trim($_POST['nombre'] ?? "");
    $categoria=trim($_POST['id_categoria'] ?? "");
    $descripcion=trim($_POST['descripcion'] ?? "");
    $precioCompra= trim($_POST["precio_compra"] ?? "");
    $precioVenta=trim($_POST["precio_venta"] ?? "");
    $stock= trim($_POST["stock"] ?? "");
    $idProvedor=trim($_POST["id_poveedor"] ?? "");

    if ($precioCompra < 0 || $precioVenta < 0 || $stock < 0)
    {
        echo "<script>alert('No se permiten valores negativos'); window.history.back();</script>";
        exit; 
    }
    
    if (in_array("", [$nombre, $categoria, $precioCompra, $precioVenta, $stock, $idProvedor], true))
    {
        echo "<script>alert('Alguno de los campos esta vacio, revisa de nuevo'); window.history.back();</script>";
        exit; 
    }
    
    if ($precioCompra > $precioVenta  || $precioCompra === $precioVenta)
    {   
        echo "<script>alert('El precio de venta es menor al de compra, favor de revisar'); window.history.back();</script>";
        exit;
    }

    if(!is_numeric($precioCompra) || !is_numeric($precioVenta) || !is_numeric($stock))
    {
        echo "<script>alert('Los precios y stock deben de ser valores numericos'); window.history.back();</script>";
        exit;
    }

    if(mb_strlen($nombre, 'UTF-8')>50)
    {
        echo "<script>alert('El nombre debe de ser menor o igual a 50 caracteres'); window.history.back();</script>";
        exit; 
    }
    if(mb_strlen($descripcion, 'UTF-8')>200)
    {
        echo "<script>alert('La descripcion debe de ser menor o igual a 200 caracteres'); window.history.back();</script>";
        exit; 
    }

    $caracteres='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $aleatorio=$caracteres[rand(0, strlen($caracteres) - 1)] . $caracteres[rand(0, strlen($caracteres) - 1)];
    $id_producto="PROD" . $aleatorio;

    $carpetaDeServidor="Recursos/img_prods/";
    $rutaImagenFinal="";

    $hayUnArchivoValido=isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK;
    $urlImagen=trim($_POST["imagen_url"] ?? "");

    if ($hayUnArchivoValido) 
    {   
        $ubicacionTemporal=$_FILES['imagen_archivo']['tmp_name'];
        $nombreOriginal   =$_FILES['imagen_archivo']['name'];
        $extensionArchivo =pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        
        $nombreSeguroUnico=uniqid('prod_', true) . "." . $extensionArchivo;
        $rutaFinalGuardado = __DIR__ . "/../" . $carpetaDeServidor . $nombreSeguroUnico;
        
        if (move_uploaded_file($ubicacionTemporal, $rutaFinalGuardado)) 
        {
            $rutaImagenFinal=$carpetaDeServidor . $nombreSeguroUnico;
        }
    }
    elseif ($urlImagen !== "")
    {
        $rutaImagenFinal=$urlImagen;
    }


    try 
    {
        $conexion->beginTransaction();


        $query1="SELECT id_categoria FROM Categoria_Producto WHERE nombre_categoria=?";
        $Puente=$conexion->prepare($query1);
        $Puente->execute([$categoria]);
        $id_cat_data=$Puente->fetch(PDO::FETCH_ASSOC);

        $id_cat=$id_cat_data['id_categoria'] ?? null;


        if (!$id_cat) 
        {
            throw new Exception("La categoria seleccionada no existe");
        }

        $query2="INSERT INTO Producto 
                (id_producto, 
                nombre, 
                descripcion, 
                precio_compra, 
                precio_venta, 
                stock, 
                id_categoria, 
                id_proveedor) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $Puente=$conexion->prepare($query2);
        $Puente->execute([$id_producto, $nombre, $descripcion, $precioCompra, $precioVenta, $stock, $id_cat, $idProvedor]);


        if ($rutaImagenFinal !== "") 
        {
            $query3="INSERT 
                    INTO ImgProductos (id_producto, url) 
                    VALUES (?, ?)";
            $Puente=$conexion->prepare($query3);
            $Puente->execute([$id_producto, $rutaImagenFinal]);
        }

        $conexion->commit();
        
        
        echo "<script>
            alert('Producto agregado correctamente con ID: " . $id_producto ."');
            window.location.href=document.referrer;
        </script>";
        exit;
    } 
    catch (Exception $e) 
    {
        $conexion->rollBack();
        echo "<script>
            alert('No se pudo agregar el producto: " . addslashes($e->getMessage()) . "');
            window.history.back();
        </script>";
        exit;
    }
}
?>