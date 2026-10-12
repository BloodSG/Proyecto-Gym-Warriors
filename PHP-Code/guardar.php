<?php
require_once __DIR__ . "/libreria.php";

function responderRegistro(string $mensaje, bool $exitoso = false): void
{
    $destino = $exitoso ? "../HTML-Code/loginView.html" : "../HTML-Code/alta_cliehtml.html";
    $mensajeSeguro = json_encode($mensaje, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    echo "<script>alert($mensajeSeguro); window.location.href = " . json_encode($destino) . ";</script>";
    exit();
}

function siguienteIdCliente(PDO $conexion): string
{
    // Genera el siguiente ID CLI usando la tabla principal de clientes.
    $consulta = $conexion->query(
        "SELECT ISNULL(MAX(TRY_CONVERT(INT, SUBSTRING(id_usuario, 4, 7))), 0) + 1
         FROM usuariocliente WITH (UPDLOCK, HOLDLOCK)
         WHERE id_usuario LIKE 'CLI%'"
    );
    return "CLI" . str_pad((string)$consulta->fetchColumn(), 3, "0", STR_PAD_LEFT);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../HTML-Code/alta_cliehtml.html");
    exit();
}

$nombre = trim($_POST["nombre"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = trim($_POST["contraseña"] ?? "");
$confirmar = trim($_POST["confirmar"] ?? "");
$longitud = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
$patronNombre = '/^[\p{L}\p{M}]+(?:[ \x{27}\x{2019}.\x{2D}][\p{L}\p{M}]+)*$/u';

if ($nombre === "" || $correo === "" || $password === "" || $confirmar === "") {
    responderRegistro("Completa todos los campos.");
}
if ($longitud($nombre) > 30 || !preg_match($patronNombre, $nombre)) {
    responderRegistro("El nombre debe contener solo letras y tener máximo 30 caracteres.");
}
if ($longitud($correo) > 50 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responderRegistro("Ingresa un correo electrónico válido.");
}
if ($password !== $confirmar) {
    responderRegistro("Las contraseñas no coinciden.");
}
if ($longitud($password) !== 10) {
    responderRegistro("La contraseña debe tener exactamente 10 caracteres.");
}

try {
    $conexion = conectaDB();
    $conexion->beginTransaction();

    // Evita registrar el mismo correo en la tabla principal.
    $consulta = $conexion->prepare(
        "SELECT id_usuario FROM usuariocliente WITH (UPDLOCK, HOLDLOCK) WHERE correo = ?"
    );
    $consulta->execute([$correo]);
    if ($consulta->fetchColumn()) {
        $conexion->rollBack();
        responderRegistro("Este correo ya se encuentra registrado.");
    }

    // Busca el rol Cliente requerido por la relación de la base de datos.
    $consulta = $conexion->prepare("SELECT TOP 1 id_rol FROM Rol WHERE nombre_rol = ? ORDER BY id_rol");
    $consulta->execute(["Cliente"]);
    $idRol = $consulta->fetchColumn();
    if (!$idRol) {
        throw new RuntimeException("No existe el rol Cliente en la tabla Rol.");
    }

    $idUsuario = siguienteIdCliente($conexion);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Crea primero el cliente porque Loguin tiene una llave foránea hacia usuariocliente.
    $consulta = $conexion->prepare(
        "INSERT INTO usuariocliente
            (id_usuario, nombre, correo, fecha_registro, password_hash, id_rol, apellido_1)
         VALUES (?, ?, ?, GETDATE(), ?, ?, ?)"
    );
    $consulta->execute([$idUsuario, $nombre, $correo, $passwordHash, $idRol, ""]);

    // Guarda las credenciales en la tabla que consulta el inicio de sesión.
    $consulta = $conexion->prepare(
        "INSERT INTO Loguin (id_usuario, correo, contraseña) VALUES (?, ?, ?)"
    );
    $consulta->execute([$idUsuario, $correo, $passwordHash]);

    $conexion->commit();
    responderRegistro("Registro completo y exitoso.", true);
} catch (Throwable $error) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }
    error_log("Error al registrar cliente: " . $error->getMessage());
    responderRegistro("No fue posible guardar el registro. Revisa la configuración de la base de datos.");
}
?>