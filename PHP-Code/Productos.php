<?php
header('Content-Type: application/json; charset=utf-8'); // le dice al navegador que la respuesta es json 
require_once("libreria.php");
$conexion=conectaDB();

if (isset($_GET['action'])) 
{   
    if($_GET['action'] == 'detalle')
    {
        $id= $_GET['id'] ?? 0;// ?? 0 significa que si no hay id, se asigna un 0
    
        $query= "SELECT
            prod.id_producto,
            prod.nombre,
            prod.descripcion,
            ImgP.url,
            cat.nombre_categoria,
            prod.precio_venta,
            prod.stock
            FROM Producto AS prod JOIN Categoria_Producto as cat 
            ON prod.id_categoria=cat.id_categoria
            JOIN Proveedor as prov
            ON prod.id_proveedor=prov.id_proveedor
            AND prod.id_producto=?
            LEFT JOIN ImgProductos as ImgP
            ON ImgP.id_producto=prod.id_producto";

            $Puente=$conexion->prepare($query);
            $Puente->execute([$id]);

            $Producto=$Puente->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([ // echo imprime la respuesta en formato json para que quien pide la informacion pueda leerla
                    "status" => "success",
                    "mensaje" => "id: $id",
                    "data" => $Producto
            ], JSON_UNESCAPED_UNICODE); // el JSON_UNESCAPED_UNICODE es para que no omita los caracteres especiales como acentos y ñ
    }
    
    elseif ($_GET['action'] == 'vuscar')
    {
        $vusqueda="%".$_GET['comando']."%";
    
    $query= "SELECT
        prod.id_producto,
        prod.nombre,
        ImgP.url,
        cat.nombre_categoria,
        prod.precio_venta,
        prod.stock
        FROM
        Producto AS prod JOIN Categoria_Producto as cat
        ON prod.id_categoria=cat.id_categoria AND prod.nombre like ?
        LEFT JOIN ImgProductos as ImgP
        ON ImgP.id_producto=prod.id_producto";
    
    $Puente=$conexion->prepare($query);
    $Puente->execute([$vusqueda]);

    $Productos=$Puente->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([ // echo imprime la respuesta en formato json para que quien pide la informacion pueda leerla
                "status" => "success",
                "mensaje" => "id: esta es la busqueda",
                "data" => $Productos
        ], JSON_UNESCAPED_UNICODE);
    }

    elseif ($_GET['action'] == 'catalogo')
    {
        if (isset($_GET['categoria'])&& $_GET['categoria']!=="")
        {
            $query= "SELECT
                prod.id_producto,
                prod.nombre,
                ImgP.url,
                cat.nombre_categoria,
                prod.precio_venta,
                prod.stock
                FROM
                Producto AS prod JOIN Categoria_Producto as cat
                ON prod.id_categoria=cat.id_categoria AND cat.nombre_categoria=?
                LEFT JOIN ImgProductos as ImgP
                ON ImgP.id_producto=prod.id_producto";
            
            $categoria=$_GET['categoria'];
            $Puente=$conexion->prepare($query);
            $Puente->execute([$categoria]);
            $Productos=$Puente->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([ 
            "status" => "success",
            "mensaje" => "Datos obtenidos correctamente",
            "data" => $Productos
            ], JSON_UNESCAPED_UNICODE);
        }
        elseif (isset($_GET['categoria'])&& $_GET['categoria']=="")
        {
            $query= "SELECT
                prod.id_producto,
                prod.nombre,
                ImgP.url,
                cat.nombre_categoria,
                prod.precio_venta,
                prod.stock
                FROM
                Producto AS prod JOIN Categoria_Producto as cat
                ON prod.id_categoria=cat.id_categoria
                LEFT JOIN ImgProductos as ImgP
                ON ImgP.id_producto=prod.id_producto";
            $Puente=$conexion->prepare($query);
            $Puente->execute();
            $Productos=$Puente->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([ 
            "status" => "success",
            "mensaje" => "Datos obtenidos correctamente",
            "data" => $Productos
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    elseif($_GET['action'] == 'allProdInfo')
    {
        $id= $_GET['id'] ?? 0;// ?? 0 significa que si no hay id, se asigna un 0
    
        $query= "SELECT
            prod.id_producto,
            prod.nombre,
            prod.descripcion,
            ImgP.url,
            prov.nombre_empresa,
            prov.id_proveedor,
            cat.nombre_categoria,
            prod.precio_venta,
            prod.precio_compra,
            prod.stock
            FROM Producto AS prod JOIN Categoria_Producto as cat 
            ON prod.id_categoria=cat.id_categoria
            AND prod.id_producto=?
            LEFT JOIN Proveedor as prov
            ON prod.id_proveedor=prov.id_proveedor
            AND prod.id_producto=?
            LEFT JOIN ImgProductos as ImgP
            ON ImgP.id_producto=prod.id_producto
            ";

            $Puente=$conexion->prepare($query);
            $Puente->execute([$id,$id]);

            $Producto=$Puente->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([ 
                    "status" => "success",
                    "mensaje" => "id: $id",
                    "data" => $Producto
            ], JSON_UNESCAPED_UNICODE); 
    }

    elseif ($_GET['action'] == 'cargar')
    {
        if($_GET['comando'] == 'categoria')
        {   
            $query="SELECT 
                    nombre_categoria 
                    FROM 
                    Categoria_Producto";
                    
            $Puente=$conexion->prepare($query);
            $Puente->execute();
            $Categorias=$Puente->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([ 
            "status" => "success",
            "mensaje" => "Categorias obtenidas correctamenete",
            "data" => $Categorias
            ], JSON_UNESCAPED_UNICODE);

        }

        if($_GET['comando'] == 'proveedores')
        {   
            $query="SELECT 
                    id_proveedor,nombre_empresa
                    FROM 
                    Proveedor";
                    
            $Puente=$conexion->prepare($query);
            $Puente->execute();
            $Proveedores=$Puente->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([ 
            "status" => "success",
            "mensaje" => "Proveedores obtenidos correctamente",
            "data" => $Proveedores
            ], JSON_UNESCAPED_UNICODE);

        }
    }

    elseif ($_GET['action'] == 'quitar')
    {   
        $producto=$_GET['producto'];
        if( $producto !== '')
        {   
            try 
            {
                $conexion->beginTransaction();
                
                $queryImg="SELECT url FROM ImgProductos WHERE id_producto=?";
                $Puente=$conexion->prepare($queryImg);
                $Puente->execute([$producto]);
                $imgData=$Puente->fetch(PDO::FETCH_ASSOC);

                if ($imgData && !empty($imgData['url'])) 
                {
                    $rutaFisica="../" . $imgData['url']; 
                    if (file_exists($rutaFisica)) 
                    {
                        unlink($rutaFisica); 
                    }
                }

                $query1="DELETE 
                        FROM ImgProductos
                        WHERE
                        id_producto=?";
                        
                $Puente=$conexion->prepare($query1);
                $Puente->execute([$producto]);

                $query2="DELETE 
                        FROM Producto
                        WHERE
                        id_producto=?";
                        
                $Puente=$conexion->prepare($query2);
                $Puente->execute([$producto]);

                $conexion->commit();

                echo json_encode([ 
                "status" => "success",
                "mensaje" => "Producto: ". $producto ." eliminado correctamente",
                ], JSON_UNESCAPED_UNICODE);
            }
            catch (Exception $e) 
            {

                $conexion->rollBack();
                echo json_encode([ 
                "status" => "error",
                "mensaje" => "Producto: ". $producto ." no se pudo eliminar",
                ], JSON_UNESCAPED_UNICODE);
            }

        }
    }

    elseif ($_GET['action'] == 'editar')
    {
        $producto=$_POST['id_producto'] ?? '';
        if( $producto !== '')
        {   
            try 
            {   
                $nombre_categoria=$_POST['categoria'] ?? ''; 
                $nombre=$_POST['nombre'] ?? '';
                $descripcion=$_POST['descripcion'] ?? '';
                $precio_compra=$_POST['precio_compra'] ?? 0;
                $precio_venta=$_POST['precio_venta'] ?? 0;
                $stock=$_POST['stock'] ?? 0;
                $idProvedor=trim($_POST["id_poveedor"] ?? "");

                if($precio_compra<0 || $precio_venta<0 || $stock<0)
                {
                    echo json_encode([ 
                    "status" => "error",
                    "mensaje" => "los valores no pueden ser menores a cero",
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                if(in_array("",[$nombre,$nombre_categoria,$precio_compra,$precio_venta,$stock,$idProvedor],true))
                {
                    echo json_encode([ 
                    "status" => "error",
                    "mensaje" => "los espacios con * son obligatorios",
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                if ($precio_compra>$precio_venta || $precio_compra===$precio_venta )
                {   
                    echo json_encode([ 
                    "status" => "error",
                    "mensaje" => "el precio de venta es menor al de compra",
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $carpetaDeServidor="Recursos/img_prods/";

                $conexion->beginTransaction();

                $query1="SELECT id_categoria
                        FROM Categoria_Producto
                        WHERE nombre_categoria=?";
                        
                $Puente=$conexion->prepare($query1);
                $Puente->execute([$nombre_categoria]);
                $id_cat=$Puente->fetch(PDO::FETCH_ASSOC);
                $id_categoria_real=$id_cat['id_categoria'] ?? null;

                if (!$id_categoria_real) 
                {
                    throw new Error("categoria no valida");
                }   

                $query2="UPDATE Producto 
                        SET id_categoria=?, 
                            nombre=?, 
                            descripcion=?, 
                            precio_compra=?, 
                            precio_venta=?, 
                            stock=?,
                            id_proveedor=?
                        WHERE id_producto=?";
                        
                $Puente=$conexion->prepare($query2);
                $Puente->execute([$id_categoria_real, $nombre, $descripcion, $precio_compra, $precio_venta, $stock, $idProvedor, $producto]);

                $hayUnArchivoValido=isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK;
                $urlImagen=trim($_POST["imagen_url"]);

                if ($hayUnArchivoValido) 
                {   
                    $ubicacionTemporal=$_FILES['imagen_archivo']['tmp_name'];//la ubicacion temporal del archido
                    $nombreOriginal=$_FILES['imagen_archivo']['name'];//extrae el nombre deel archivo
                    $extensionArchivo=pathinfo($nombreOriginal, PATHINFO_EXTENSION);//extrae la extencion del archivo
                    
                    $nombreSeguroUnico=uniqid('prod_', true) . "." . $extensionArchivo; //le ponemos un nombre que no se pueda repetir
                    
                    $rutaFinalGuardado="../". $carpetaDeServidor . $nombreSeguroUnico;
                    
                    if (move_uploaded_file($ubicacionTemporal, $rutaFinalGuardado)) 
                    {   
                        $queryImg="SELECT url FROM ImgProductos WHERE id_producto=?";
                        $Puente=$conexion->prepare($queryImg);
                        $Puente->execute([$producto]);
                        $imgData=$Puente->fetch(PDO::FETCH_ASSOC);

                        if ($imgData && !empty($imgData['url'])) 
                        {
                            $rutaFisica="../" . $imgData['url']; 
                            if (file_exists($rutaFisica)) 
                            {
                                unlink($rutaFisica); 
                            }
                        }

                        $rutaImagenFinal=$carpetaDeServidor . $nombreSeguroUnico;
                        $query3="UPDATE ImgProductos
                        SET url=?
                        WHERE id_producto=?";
                        
                        $Puente=$conexion->prepare($query3);
                        $Puente->execute([$rutaImagenFinal,$producto]);
                    }
                    
                }

                $conexion->commit();

                echo json_encode([ 
                "status" => "success",
                "mensaje" => "Producto: ". $producto ." modificado correctamente",
                ], JSON_UNESCAPED_UNICODE);
            }
            catch (Exception $e) 
            {

                $conexion->rollBack();
                echo json_encode([ 
                "status" => "error",
                "mensaje" => "Producto: ". $producto ." no se pudo modificar",
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

        }
    }

}

?>

