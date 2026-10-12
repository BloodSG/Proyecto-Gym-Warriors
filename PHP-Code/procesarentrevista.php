<?php
// Procesa initialInterview.html usando la estructura ACTUAL de Gym_warriors.
require __DIR__ . "/conexion.php";

function siguienteId(PDO $conexion, string $tabla, string $columna, string $prefijo): string
{
    // Los nombres de tabla y columna de esta funcion se escriben en este archivo,
    // no vienen del formulario.
    $inicioNumero = strlen($prefijo) + 1;
    $sql = "SELECT ISNULL(MAX(TRY_CAST(SUBSTRING($columna, $inicioNumero, 10) AS INT)), 0) + 1
            FROM $tabla
            WHERE $columna LIKE ?";
    $consulta = $conexion->prepare($sql);
    $consulta->execute([$prefijo . "%"]);
    return $prefijo . $consulta->fetchColumn();
}

function horasSueno(string $valor): int
{
    if ($valor === "Menos de 5 horas") return 4;
    if ($valor === "5-6 horas") return 6;
    if ($valor === "7-8 horas") return 8;
    if ($valor === "Más de 8 horas") return 9;
    return 0;
}

function diasEntrenamiento(string $valor): int
{
    if ($valor === "1 sesión por semana") return 1;
    if ($valor === "2 sesiones por semana") return 2;
    if ($valor === "3 sesiones por semana") return 3;
    if ($valor === "4+ sesiones por semana") return 4;
    return 0;
}

function valorTexto(array $entrada, string $campo): string
{
    $valor = $entrada[$campo] ?? "";
    return is_string($valor) || is_numeric($valor) ? trim((string)$valor) : "";
}

function listaTexto(array $entrada, string $campo): array
{
    $valores = $entrada[$campo] ?? [];
    if (!is_array($valores)) return [];
    return array_values(array_filter($valores, "is_string"));
}

function obtenerDatosFormulario(): array
{
    return [
        "nombre" => valorTexto($_POST, "nombre"),
        "apellido" => valorTexto($_POST, "apellido"),
        "correo" => valorTexto($_POST, "correo"),
        "telefono" => valorTexto($_POST, "telefono"),
        "fecha_nacimiento" => valorTexto($_POST, "fecha_nacimiento"),
        "peso" => valorTexto($_POST, "peso"),
        "estatura" => valorTexto($_POST, "estatura"),
        "nivel" => valorTexto($_POST, "nivel"),
        "objetivos" => listaTexto($_POST, "objetivos"),
        "condiciones_salud" => valorTexto($_POST, "condiciones_salud"),
        "limitaciones_fisicas" => valorTexto($_POST, "limitaciones_fisicas"),
        "sueno" => valorTexto($_POST, "sueno"),
        "preferencias" => listaTexto($_POST, "preferencias"),
        "frecuencia" => valorTexto($_POST, "frecuencia")
    ];
}

function validarDatosEntrevista(array $datos): array
{
    $errores = [];
    $longitud = function_exists("mb_strlen") ? "mb_strlen" : "strlen";

    $patronNombre = '/^[\p{L}\p{M}]+(?:[ \x{27}\x{2019}.\x{2D}][\p{L}\p{M}]+)*$/u';
    if ($datos["nombre"] === "" || $longitud($datos["nombre"]) > 30 || !preg_match($patronNombre, $datos["nombre"])) $errores[] = "El nombre es obligatorio, debe contener letras y tener máximo 30 caracteres.";
    if ($datos["apellido"] === "" || $longitud($datos["apellido"]) > 20 || !preg_match($patronNombre, $datos["apellido"])) $errores[] = "El apellido es obligatorio, debe contener letras y tener máximo 20 caracteres.";
    if (!filter_var($datos["correo"], FILTER_VALIDATE_EMAIL) || $longitud($datos["correo"]) > 50) $errores[] = "El correo no es válido o supera 50 caracteres.";
    if (!preg_match('/^[0-9]{10}$/', $datos["telefono"])) $errores[] = "El teléfono debe tener exactamente 10 dígitos.";

    $fecha = DateTimeImmutable::createFromFormat("!Y-m-d", $datos["fecha_nacimiento"]);
    $erroresFecha = DateTimeImmutable::getLastErrors();
    $fechaInvalida = !$fecha
        || ($erroresFecha !== false && ($erroresFecha["warning_count"] > 0 || $erroresFecha["error_count"] > 0))
        || $fecha->format("Y-m-d") !== $datos["fecha_nacimiento"];

    if ($fechaInvalida) {
        $errores[] = "La fecha de nacimiento no es válida.";
    } else {
        $hoy = new DateTimeImmutable("today");
        if ($fecha > $hoy) {
            $errores[] = "La fecha de nacimiento no puede ser futura.";
        } elseif ($fecha < $hoy->modify("-100 years")) {
            $errores[] = "La fecha de nacimiento no puede ser de hace más de 100 años.";
        }
    }

    if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $datos["peso"]) || (float)$datos["peso"] < 20 || (float)$datos["peso"] > 999.99) $errores[] = "El peso debe ser un número entre 20 y 999.99 kg.";
    if (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $datos["estatura"]) || (float)$datos["estatura"] < 80 || (float)$datos["estatura"] > 250) $errores[] = "La estatura debe ser un número entre 80 y 250 cm.";

    $nivelesValidos = ["Principiante", "Intermedio", "Avanzado", "Atleta"];
    $objetivosValidos = ["Pérdida de peso", "Desarrollo muscular", "Resistencia", "Flexibilidad", "Salud general", "Rehabilitación de lesiones"];
    $suenosValidos = ["Menos de 5 horas", "5-6 horas", "7-8 horas", "Más de 8 horas"];
    $preferenciasValidas = ["Entrenamiento con pesas", "Cardio", "Entrenamiento funcional", "Clases grupales"];
    $frecuenciasValidas = ["1 sesión por semana", "2 sesiones por semana", "3 sesiones por semana", "4+ sesiones por semana"];

    if (!in_array($datos["nivel"], $nivelesValidos, true)) $errores[] = "Selecciona un nivel de experiencia válido.";
    if (!$datos["objetivos"] || array_diff($datos["objetivos"], $objetivosValidos)) $errores[] = "Selecciona al menos un objetivo válido.";
    if (!in_array($datos["sueno"], $suenosValidos, true)) $errores[] = "Selecciona un promedio de sueño válido.";
    if (!$datos["preferencias"] || array_diff($datos["preferencias"], $preferenciasValidas)) $errores[] = "Selecciona al menos una preferencia válida.";
    if (!in_array($datos["frecuencia"], $frecuenciasValidas, true)) $errores[] = "Selecciona una frecuencia válida.";
    if ($longitud($datos["condiciones_salud"]) > 100 || $longitud($datos["limitaciones_fisicas"]) > 100) $errores[] = "Las condiciones y limitaciones admiten máximo 100 caracteres.";

    return $errores;
}

function guardarEntrevista(PDO $conexion, array $datos): array
{
    $fechaNacimiento = $datos["fecha_nacimiento"];
    $fecha = new DateTimeImmutable($fechaNacimiento);
    $edad = (new DateTimeImmutable("today"))->diff($fecha)->y;
    $objetivosTexto = implode(", ", $datos["objetivos"]);
    $sueno = $datos["sueno"];

    $conexion->beginTransaction();
    try {

    // Si el alta/login ya creó al usuario, reutilizamos su ID por correo.
    $consulta = $conexion->prepare("SELECT id_usuario FROM usuariocliente WHERE correo = ?");
    $consulta->execute([$datos["correo"]]);
    $idUsuario = $consulta->fetchColumn();

    if (!$idUsuario) {
        $idUsuario = siguienteId($conexion, "usuariocliente", "id_usuario", "U");

        $consulta = $conexion->prepare("SELECT TOP 1 id_rol FROM Rol WHERE nombre_rol = 'Cliente' ORDER BY id_rol");
        $consulta->execute();
        $idRol = $consulta->fetchColumn();
        if (!$idRol) {
            $idRol = $conexion->query("SELECT TOP 1 id_rol FROM Rol ORDER BY id_rol")->fetchColumn();
        }
        if (!$idRol) throw new Exception("La tabla Rol no tiene registros.");

        // Valor temporal porque la entrevista no solicita contraseña.
        // El módulo de alta/login debe administrar la contraseña real.
        $passwordTemporal = substr(hash("sha256", $idUsuario . $datos["correo"]), 0, 20);

        $sql = "INSERT INTO usuariocliente
                (id_usuario, nombre, apellidos, correo, telefono, fecha_registro, password_hash, id_rol)
                VALUES (?, ?, ?, ?, ?, GETDATE(), ?, ?)";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$idUsuario, $datos["nombre"], $datos["apellido"], $datos["correo"], $datos["telefono"], $passwordTemporal, $idRol]);
    } else {
        // Completa/actualiza los datos básicos del usuario ya registrado.
        $sql = "UPDATE usuariocliente
                SET nombre = ?, apellidos = ?, telefono = ?
                WHERE id_usuario = ?";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$datos["nombre"], $datos["apellido"], $datos["telefono"], $idUsuario]);
    }

    // Detalle físico del cliente: un registro por usuario.
    $consulta = $conexion->prepare("SELECT COUNT(*) FROM Detalle_Cliente WHERE id_usuario = ?");
    $consulta->execute([$idUsuario]);
    $existeDetalle = (int)$consulta->fetchColumn() > 0;

    if ($existeDetalle) {
        $sql = "UPDATE Detalle_Cliente
                SET fecha_nacimiento = ?, edad = ?, estatura = ?, peso_actual = ?
                WHERE id_usuario = ?";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$fechaNacimiento, $edad, $datos["estatura"], $datos["peso"], $idUsuario]);
    } else {
        $sql = "INSERT INTO Detalle_Cliente
                (id_usuario, fecha_nacimiento, edad, sexo, estatura, peso_actual, ocupacion, acepto_reglamento)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $consulta = $conexion->prepare($sql);
        $consulta->execute([$idUsuario, $fechaNacimiento, $edad, "masculino", $datos["estatura"], $datos["peso"], null, "si"]);
    }

    $idEntrevista = siguienteId($conexion, "Entrevista", "id_entrevista", "E");
    $consulta = $conexion->prepare("INSERT INTO Entrevista
        (id_entrevista, id_usuario, fecha_registro, tipo_plan, nivel_compromiso)
        VALUES (?, ?, GETDATE(), ?, ?)");
    $consulta->execute([$idEntrevista, $idUsuario, "Entrenamiento", 0]);

    $idEntrevistaEnt = siguienteId($conexion, "entrevista_entrenamiento", "id_entrevista_ent", "EE");
    $sql = "INSERT INTO entrevista_entrenamiento
        (id_entrevista_ent, objetivo_principal, objetivo_especifico,
         experiencia_previa, nivel_experiencia, trabajo_entrenador,
         lugar_entrenamiento, dias_disponibles, tiempo_disponible,
         horario_preferencia, nivel_estres, calidad_sueño, horas_sueño,
         sigue_dieta, comentarios_adicionales, id_usuario, tiempo_entrenado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $consulta = $conexion->prepare($sql);
    $consulta->execute([
        $idEntrevistaEnt,
        substr($objetivosTexto, 0, 30),
        substr($objetivosTexto, 0, 30),
        $datos["nivel"],
        $datos["nivel"],
        "Por asignar",
        null,
        diasEntrenamiento($datos["frecuencia"]),
        60,
        "08:00:00",
        null,
        substr($sueno, 0, 10),
        horasSueno($sueno),
        "no",
        null,
        $idUsuario,
        0
    ]);

    // Un registro de preferencias por cada opción seleccionada.
    foreach ($datos["preferencias"] as $preferencia) {
        $idPreferencia = siguienteId($conexion, "detalle_preferecias_ejercicio", "id_detalle_preferencia", "P");
        $consulta = $conexion->prepare("INSERT INTO detalle_preferecias_ejercicio
            (id_detalle_preferencia, tipo_entrenamiento_disfruta, id_entrevista_ent)
            VALUES (?, ?, ?)");
        $consulta->execute([$idPreferencia, substr($preferencia, 0, 15), $idEntrevistaEnt]);
    }

    // La parte de salud cuelga de Entrevista_Nutri_Sup en la base actual.
    $idEntrevistaNutri = siguienteId($conexion, "Entrevista_Nutri_Sup", "id_entrevista_nutricional", "EN");
    $consulta = $conexion->prepare("INSERT INTO Entrevista_Nutri_Sup
        (id_entrevista_nutricional, id_entrevista, objetivo_principal, horas_sueño, calidad_descanso)
        VALUES (?, ?, ?, ?, ?)");
    $consulta->execute([$idEntrevistaNutri, $idEntrevista, substr($objetivosTexto, 0, 150), horasSueno($sueno), substr($sueno, 0, 15)]);

    $idDetalleSalud = (int)$conexion->query("SELECT ISNULL(MAX(id_detalle_salud), 0) + 1 FROM Detalle_Salud_Cliente")->fetchColumn();
    $consulta = $conexion->prepare("INSERT INTO Detalle_Salud_Cliente
        (id_detalle_salud, id_entrevista_nutricional) VALUES (?, ?)");
    $consulta->execute([$idDetalleSalud, $idEntrevistaNutri]);

    if ($datos["condiciones_salud"] !== "") {
        $consulta = $conexion->prepare("INSERT INTO Detalle_Condiciones_Salud
            (id_detalle_salud, descripcion_condicion, tipo_condicion)
            VALUES (?, ?, ?)");
        $consulta->execute([$idDetalleSalud, $datos["condiciones_salud"], "enfermedad"]);
    }

    if ($datos["limitaciones_fisicas"] !== "") {
        $consulta = $conexion->prepare("INSERT INTO Detalle_Condiciones_Salud
            (id_detalle_salud, descripcion_condicion, tipo_condicion)
            VALUES (?, ?, ?)");
        $consulta->execute([$idDetalleSalud, $datos["limitaciones_fisicas"], "lesion"]);
    }

    $conexion->commit();

    return ["id_usuario" => $idUsuario, "id_entrevista" => $idEntrevista];
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        throw $error;
    }
}

function mostrarErrores(array $errores): void
{
    echo "<h2>No se pudo guardar la entrevista</h2><ul>";
    foreach ($errores as $error) echo "<li>" . htmlspecialchars($error, ENT_QUOTES, "UTF-8") . "</li>";
    echo '</ul><a href="javascript:history.back()">Volver al formulario</a>';
}

function mostrarConfirmacion(array $datos, array $resultado): void
{
    echo "<h2>Entrevista guardada correctamente</h2>";
    echo "<p>Cliente: " . htmlspecialchars($datos["nombre"] . " " . $datos["apellido"], ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>ID de usuario: " . htmlspecialchars((string)$resultado["id_usuario"], ENT_QUOTES, "UTF-8") . "</p>";
    echo "<p>ID de entrevista: " . htmlspecialchars((string)$resultado["id_entrevista"], ENT_QUOTES, "UTF-8") . "</p>";
}

function mostrarErrorGuardado(Throwable $error): void
{
    echo "<h2>No se pudo guardar la entrevista</h2>";
    echo "<p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
}

function procesarSolicitud(PDO $conexion): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo "Abre el formulario y presiona Enviar.";
        return;
    }

    $datos = obtenerDatosFormulario();
    $errores = validarDatosEntrevista($datos);
    if ($errores) {
        http_response_code(422);
        mostrarErrores($errores);
        return;
    }

    try {
        $resultado = guardarEntrevista($conexion, $datos);
        mostrarConfirmacion($datos, $resultado);
    } catch (Throwable $error) {
        mostrarErrorGuardado($error);
    }
}

procesarSolicitud($conexion);
?>
