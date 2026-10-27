<?php
    require_once("libreria.php");
    
    function inventario($conexion)
    {
        $busqueda = isset($_GET['buscar']) ? "%" . $_GET['buscar'] . "%" : "%";
        
        $consulta = "SELECT * FROM Inventario WHERE nombre_producto LIKE ?";
        $stmt = $conexion->prepare($consulta);
        $stmt->execute([$busqueda]);
        $productos= $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($productos);
        
        
    }
?>