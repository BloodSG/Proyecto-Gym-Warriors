<?php
session_start();

function mostrarError(string $mensaje): void
{
    $mensajeSeguro = json_encode(
        $mensaje,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );
    echo "<script>alert($mensajeSeguro); window.history.back();</script>";
    exit();
}

if (
    $_SERVER["REQUEST_METHOD"] !== "POST" ||
    ($_POST["accion"] ?? "") !== "login"
) {
    header("Location: ../HTML-Code/loginView.html");
    exit();
}

$correo = trim($_POST["usuario"] ?? "");
$password = $_POST["password"] ?? "";
if ($correo === "" || $password === "") {
    mostrarError("Por favor ingresa tu correo y contraseña.");
}
if (strlen($correo) > 50 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    mostrarError("Ingresa un correo electrónico válido.");
}

require_once __DIR__ . "/libreria.php";

try {
    $conexion = conectaDB();
    $consulta = $conexion->prepare(
        "SELECT id_usuario, nombre, correo, password_hash
         FROM usuariocliente
         WHERE correo = ?"
    );
    $consulta->execute([$correo]);
    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($password, $usuario["password_hash"])) {
        mostrarError("Usuario o contraseña incorrectos.");
    }

    session_regenerate_id(true);
    $_SESSION["logueado"] = true;
    $_SESSION["id_usuario"] = $usuario["id_usuario"];
    $_SESSION["correo"] = $usuario["correo"];
    $_SESSION["nombre"] = $usuario["nombre"];
    $_SESSION["tipo_usuario"] = "Cliente";

    header("Location: ../HTML-Code/princi.html");
    exit();
} catch (PDOException $error) {
    error_log("Error al validar acceso de cliente: " . $error->getMessage());
    mostrarError("No fue posible validar el acceso. Inténtalo de nuevo.");
} catch (RuntimeException $error) {
    error_log("Configuración incompleta para validar acceso: " . $error->getMessage());
    mostrarError("El acceso no está configurado correctamente. Contacta al administrador.");
}
