<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

const STOCK_MINIMO = 5;

try {
    require_once("libreria.php");
    ob_clean(); // Limpia cualquier warning o espacio en blanco previo de la librería

    $conexion = conectaDB();
    if (!$conexion) {
        throw new Exception("No se pudo establecer la conexión a la base de datos.");
    }

    $buscar = "%" . ($_GET['buscar'] ?? "") . "%";

    // 1. Consulta principal con JOINs para mostrar el nombre de categoría y proveedor
    $query = "SELECT 
                prod.id_producto,
                prod.nombre,
                cat.nombre_categoria AS categoria,
                prod.descripcion,
                prod.precio_compra,
                prod.precio_venta,
                prov.nombre_empresa AS proveedor,
                prod.stock
              FROM producto AS prod 
              JOIN Categoria_Producto AS cat ON prod.id_categoria = cat.id_categoria
              JOIN Proveedor AS prov ON prod.id_proveedor = prov.id_proveedor
              WHERE prod.nombre LIKE ?
              ORDER BY prod.nombre";

    $stmt = $conexion->prepare($query);
    $stmt->execute([$buscar]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Tarjetas de resumen del inventario total
    $stmtResumen = $conexion->prepare(
        "SELECT COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END), 0) AS agotados,
                COALESCE(SUM(CASE WHEN stock > 0 AND stock <= ? THEN 1 ELSE 0 END), 0) AS stock_bajo
         FROM producto"
    );
    $stmtResumen->execute([STOCK_MINIMO]);
    $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

    // 3. Alertas de stock bajo o agotado
    $stmtAlertas = $conexion->prepare(
        "SELECT nombre, stock FROM producto WHERE stock <= ? ORDER BY stock ASC"
    );
    $stmtAlertas->execute([STOCK_MINIMO]);
    $alertas = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status"    => "success",
        "productos" => $productos,
        "resumen"   => $resumen,
        "alertas"   => $alertas
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    http_response_code(500);
    echo json_encode([
        "status"  => "error",
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
exit();
?>