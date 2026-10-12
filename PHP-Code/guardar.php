<?php
require_once __DIR__ . "/libreria.php";

function responder(string $mensaje, bool $exitoso): void
{
    $destino = $exitoso
        ? "../HTML-Code/loginView.html"
        : "../HTML-Code/alta_cliehtml.html";
    $mensajeSeguro = json_encode(
        $mensaje,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );

    echo "<script>alert($mensajeSeguro); window.location.href = "
        . json_encode($destino)
        . ";</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../HTML-Code/alta_cliehtml.html");
    exit();
}

$nombre = trim($_POST["nombre"] ?? "");
$apellido1 = trim($_POST["apellido_1"] ?? "");
$apellido2 = trim($_POST["apellido_2"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = $_POST["contraseña"] ?? "";
$confirmar = $_POST["confirmar"] ?? "";
$longitud = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
$patronNombre = '/^[\p{L}\p{M}]+(?:[ \x{27}\x{2019}.\x{2D}][\p{L}\p{M}]+)*$/u';

if (
    $nombre === "" ||
    $apellido1 === "" ||
    $correo === "" ||
    $password === "" ||
    $confirmar === ""
) {
    responder("Error: completa todos los campos obligatorios.", false);
}

if (
    !$nombre || !$apellido1 || !$correo || !$password || !$confirmar ||
    $longitud($nombre) > 30 || !preg_match($patronNombre, $nombre) ||
    $longitud($apellido1) > 10 || !preg_match($patronNombre, $apellido1) ||
    ($apellido2 !== "" && ($longitud($apellido2) > 10 || !preg_match($patronNombre, $apellido2))) ||
    $longitud($correo) > 50 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL)
) {
    responder("Error: revisa el formato y la longitud de los datos ingresados.", false);
}

if ($password !== $confirmar) {
    responder("Error: las contraseñas no coinciden.", false);
}

$longitudPassword = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
if ($longitudPassword($password) !== 10) {
    responder("Error: la contraseña debe tener exactamente 10 caracteres.", false);
}

try {
    $conexion = conectaDB();
    $conexion->beginTransaction();

    $consulta = $conexion->prepare(
        "SELECT id_usuario
         FROM usuariocliente WITH (UPDLOCK, HOLDLOCK)
         WHERE correo = ?"
    );
    $consulta->execute([$correo]);
    if ($consulta->fetchColumn()) {
        $conexion->rollBack();
        responder("Error: este correo ya se encuentra registrado.", false);
    }

    $consulta = $conexion->prepare(
        "SELECT ISNULL(MAX(TRY_CONVERT(INT, SUBSTRING(id_usuario, 4, 7))), 0) + 1
         FROM usuariocliente WITH (UPDLOCK, HOLDLOCK)
         WHERE id_usuario LIKE 'CLI%'"
    );
    $consulta->execute();
    $idNuevo = "CLI" . str_pad((string)$consulta->fetchColumn(), 3, "0", STR_PAD_LEFT);

    $consulta = $conexion->prepare(
        "SELECT TOP 1 id_rol
         FROM Rol
         WHERE nombre_rol = ?
         ORDER BY id_rol"
    );
    $consulta->execute(["Cliente"]);
    $idRol = $consulta->fetchColumn();
    if (!$idRol) {
        throw new RuntimeException("No existe el rol Cliente en la tabla Rol.");
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $consulta = $conexion->prepare(
        "INSERT INTO usuariocliente
            (id_usuario, nombre, correo, fecha_registro, password_hash, id_rol, apellido_1, apellido_2)
         VALUES (?, ?, ?, GETDATE(), ?, ?, ?, ?)"
    );
    $consulta->execute([
        $idNuevo,
        $nombre,
        $correo,
        $passwordHash,
        $idRol,
        $apellido1,
        $apellido2 !== "" ? $apellido2 : null,
    ]);

    // Mantiene la tabla usada por recuperación con el mismo usuario y hash que usa el login.
    $consulta = $conexion->prepare(
        "INSERT INTO Loguin (id_usuario, correo, contraseña) VALUES (?, ?, ?)"
    );
    $consulta->execute([$idNuevo, $correo, $passwordHash]);

    $conexion->commit();
    responder("¡Registro completo y exitoso! Ya puedes iniciar sesión.", true);
} catch (PDOException $error) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error al registrar cliente: " . $error->getMessage());
    responder("No fue posible guardar el registro. Inténtalo de nuevo.", false);
} catch (RuntimeException $error) {
    if (isset($conexion) && $conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log("Error de configuración al registrar cliente: " . $error->getMessage());
    responder("No se puede completar el registro por un problema de configuración.", false);
}
