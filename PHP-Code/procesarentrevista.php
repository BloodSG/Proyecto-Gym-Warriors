<?php
// Procesa rutinaInterview.html con la conexión configurada para la base de Angie.
require_once __DIR__ . "/libreria.php";
$conexion = conectaDB();

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

function obtenerDatosRutina(): array
{
    return [
        "nombre" => valorTexto($_POST, "nombre"), "correo" => valorTexto($_POST, "correo"),
        "telefono" => valorTexto($_POST, "telefono"),
        "sexo" => valorTexto($_POST, "sexo"), "fecha" => valorTexto($_POST, "fecha"),
        "estatura" => valorTexto($_POST, "estatura"), "peso" => valorTexto($_POST, "peso"),
        "ocupacion" => valorTexto($_POST, "ocupacion"), "objetivos" => listaTexto($_POST, "objetivos"),
        "objetivo_otro" => valorTexto($_POST, "objetivoOtro"), "objetivo_especifico" => valorTexto($_POST, "objetivoEspecifico"),
        "experiencia" => valorTexto($_POST, "experiencia"), "tiempo_entrenado" => valorTexto($_POST, "tiempoEntrenando"),
        "unidad_tiempo_entrenado" => valorTexto($_POST, "unidadTiempo"),
        "nivel" => valorTexto($_POST, "nivelExp"), "entrenador" => valorTexto($_POST, "expEntrenador"),
        "actividad_actual" => valorTexto($_POST, "actividadActual"), "dias_actuales" => valorTexto($_POST, "diasActuales"),
        "tiempo_sesion_actual" => valorTexto($_POST, "tiempoSesion"), "unidad_tiempo_sesion" => valorTexto($_POST, "unidadTiempoSesion"),
        "tipo_entrenamiento" => valorTexto($_POST, "tipoEntrenamiento"),
        "deporte" => valorTexto($_POST, "deporte"), "nombre_deporte" => valorTexto($_POST, "nombreDeporte"),
        "condicion" => valorTexto($_POST, "condicionMedica"), "condicion_descripcion" => valorTexto($_POST, "condicionMedicaEspecifica"),
        "lesion" => valorTexto($_POST, "lesion"), "lesion_descripcion" => valorTexto($_POST, "lesionEspecifica"),
        "limitacion" => valorTexto($_POST, "limitacionEjercicios"), "limitacion_descripcion" => valorTexto($_POST, "limitacionEjerciciosTxt"),
        "dias_disponibles" => valorTexto($_POST, "disponibilidad"), "tiempo_disponible" => valorTexto($_POST, "disponibilidadTiempo"),
        "horario" => valorTexto($_POST, "horarios"), "lugares" => listaTexto($_POST, "lugar"),
        "lugar_otro" => valorTexto($_POST, "lugarOtro"), "equipo" => listaTexto($_POST, "equipo"),
        "equipo_otro" => valorTexto($_POST, "equipoOtro"), "preferencias" => listaTexto($_POST, "preferencias"),
        "preferencia_otra" => valorTexto($_POST, "preferenciasOtro"), "favoritos" => implode(", ", listaTexto($_POST, "disfruta")),
        "evitar" => valorTexto($_POST, "noDisfruta"), "estres" => valorTexto($_POST, "estres"),
        "sueno" => valorTexto($_POST, "sueño"), "horas_sueno" => valorTexto($_POST, "horasSueño"),
        "dieta" => strtolower(valorTexto($_POST, "dieta")), "dieta_descripcion" => valorTexto($_POST, "dietatxt"),
        "fuerza" => valorTexto($_POST, "fuerza"), "resistencia" => valorTexto($_POST, "resistencia"),
        "flexibilidad" => valorTexto($_POST, "flexibilidad"), "energia" => valorTexto($_POST, "energia"),
        "disciplina" => valorTexto($_POST, "disciplina"), "motivacion" => valorTexto($_POST, "motivacion"),
        "comentarios" => valorTexto($_POST, "comentarios"), "acepto_reglamento" => valorTexto($_POST, "acepto_reglamento"),
        "compromiso" => valorTexto($_POST, "compromiso"),
    ];
}

function validarRutina(array $datos): array
{
    $errores = [];
    $longitud = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
    $patronNombre = '/^[\p{L}\p{M}]+(?:[ \x{27}\x{2019}.\x{2D}][\p{L}\p{M}]+)*$/u';

    if ($datos["nombre"] === "" || $longitud($datos["nombre"]) > 50 || !preg_match($patronNombre, $datos["nombre"])) $errores[] = "El nombre debe contener solo letras y tener máximo 50 caracteres.";
    if ($longitud($datos["correo"]) > 100 || !filter_var($datos["correo"], FILTER_VALIDATE_EMAIL)) $errores[] = "Ingresa un correo electrónico válido.";
    if (!preg_match('/^[0-9]{10}$/', $datos["telefono"])) $errores[] = "El teléfono debe tener exactamente 10 dígitos.";
    if (!in_array($datos["sexo"], ["femenino", "masculino"], true)) $errores[] = "Selecciona un sexo válido.";

    $fecha = DateTimeImmutable::createFromFormat("!Y-m-d", $datos["fecha"]);
    $erroresFecha = DateTimeImmutable::getLastErrors();
    if (!$fecha || ($erroresFecha !== false && ($erroresFecha["warning_count"] || $erroresFecha["error_count"])) || $fecha->format("Y-m-d") !== $datos["fecha"] || $fecha > new DateTimeImmutable("today")) $errores[] = "La fecha de nacimiento no es válida.";
    if (!is_numeric($datos["estatura"]) || (float)$datos["estatura"] < 80 || (float)$datos["estatura"] > 250) $errores[] = "La estatura debe estar entre 80 y 250 cm.";
    if (!is_numeric($datos["peso"]) || (float)$datos["peso"] < 20 || (float)$datos["peso"] > 999.99) $errores[] = "El peso debe estar entre 20 y 999.99 kg.";
    if ($longitud($datos["ocupacion"]) > 30) $errores[] = "La ocupación admite máximo 30 caracteres.";

    $objetivosValidos = ["Ganar masa muscular", "Perder grasa", "Tonificar / Definir", "Mejorar fuerza", "Mejorar resistencia", "Salud general / Bienestar", "Rehabilitación / Recuperación", "Otro"];
    if (!$datos["objetivos"] || array_diff($datos["objetivos"], $objetivosValidos)) $errores[] = "Selecciona al menos un objetivo válido.";
    if (in_array("Otro", $datos["objetivos"], true) && $datos["objetivo_otro"] === "") $errores[] = "Especifica el otro objetivo.";
    if ($longitud($datos["objetivo_otro"]) > 30 || $longitud($datos["objetivo_especifico"]) > 300) $errores[] = "Revisa la longitud de tus objetivos.";
    $objetivos = $datos["objetivos"];
    if (in_array("Otro", $objetivos, true)) $objetivos[array_search("Otro", $objetivos, true)] = $datos["objetivo_otro"];
    if ($longitud(implode(", ", $objetivos)) > 300) $errores[] = "Los objetivos combinados admiten máximo 300 caracteres.";

    foreach (["experiencia" => ["si", "no"], "nivel" => ["principiante", "intermedio", "avanzado"], "entrenador" => ["si", "no"], "actividad_actual" => ["si", "no"], "deporte" => ["si", "no"], "estres" => ["bajo", "medio", "alto"], "sueno" => ["si", "no"], "dieta" => ["si", "no"]] as $campo => $valores) {
        if (!in_array($datos[$campo], $valores, true)) $errores[] = "Selecciona una respuesta válida para " . $campo . ".";
    }
    if (!filter_var($datos["tiempo_entrenado"], FILTER_VALIDATE_INT) || (int)$datos["tiempo_entrenado"] < 0 || (int)$datos["tiempo_entrenado"] > 600) $errores[] = "Revisa el tiempo que llevas entrenando.";
    if (!in_array($datos["unidad_tiempo_entrenado"], ["días", "meses", "años"], true)) $errores[] = "Selecciona una unidad válida para el tiempo entrenado.";
    if ($datos["actividad_actual"] === "si") {
        if (!filter_var($datos["dias_actuales"], FILTER_VALIDATE_INT) || (int)$datos["dias_actuales"] < 1 || (int)$datos["dias_actuales"] > 7) $errores[] = "Selecciona entre 1 y 7 días de entrenamiento actual.";
        if (!filter_var($datos["tiempo_sesion_actual"], FILTER_VALIDATE_INT) || (int)$datos["tiempo_sesion_actual"] < 1) $errores[] = "Ingresa el tiempo por sesión actual.";
        if (!in_array($datos["unidad_tiempo_sesion"], ["minutos", "horas"], true)) $errores[] = "Selecciona la unidad del tiempo por sesión.";
        $minutosActuales = (int)$datos["tiempo_sesion_actual"] * ($datos["unidad_tiempo_sesion"] === "horas" ? 60 : 1);
        if ($minutosActuales > 240) $errores[] = "El tiempo por sesión no puede superar 240 minutos.";
    }
    $tiposEntrenamientoLargos = ["Entrenamiento funcional", "Movilidad y flexibilidad"];
    if ($longitud($datos["tipo_entrenamiento"]) > 15 && !in_array($datos["tipo_entrenamiento"], $tiposEntrenamientoLargos, true)) $errores[] = "El tipo de entrenamiento admite máximo 15 caracteres.";
    if ($datos["deporte"] === "si" && $datos["nombre_deporte"] === "") $errores[] = "Especifica cuál otro deporte realizas.";
    if ($longitud($datos["nombre_deporte"]) > 50) $errores[] = "El deporte admite máximo 50 caracteres.";

    if (!filter_var($datos["dias_disponibles"], FILTER_VALIDATE_INT) || (int)$datos["dias_disponibles"] < 1 || (int)$datos["dias_disponibles"] > 7) $errores[] = "Selecciona entre 1 y 7 días disponibles.";
    if (!in_array($datos["tiempo_disponible"], ["30", "45", "60", "90", "mas90"], true)) $errores[] = "Selecciona un tiempo disponible válido.";
    if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $datos["horario"])) $errores[] = "Selecciona un horario válido.";

    $lugaresValidos = ["gimnasio", "casa", "aire libre", "otro"];
    if (!$datos["lugares"] || array_diff($datos["lugares"], $lugaresValidos)) $errores[] = "Selecciona al menos un lugar de entrenamiento válido.";
    if (in_array("otro", $datos["lugares"], true) && $datos["lugar_otro"] === "") $errores[] = "Especifica el otro lugar.";
    if ($longitud($datos["lugar_otro"]) > 100) $errores[] = "El otro lugar admite máximo 100 caracteres.";
    $lugares = $datos["lugares"];
    if (in_array("otro", $lugares, true)) $lugares[array_search("otro", $lugares, true)] = $datos["lugar_otro"];
    if ($longitud(implode(", ", $lugares)) > 300) $errores[] = "Los lugares combinados admiten máximo 300 caracteres.";

    $equipoValido = ["Mancuernas", "Bandas elásticas", "Máquinas", "Barras", "Kettlebells", "Ninguno", "Otro"];
    if (!$datos["equipo"] || array_diff($datos["equipo"], $equipoValido)) $errores[] = "Selecciona al menos un equipo o 'ninguno'.";
    if (in_array("Ninguno", $datos["equipo"], true) && count($datos["equipo"]) > 1) $errores[] = "No combines 'ninguno' con otro equipo.";
    if (in_array("Otro", $datos["equipo"], true) && $datos["equipo_otro"] === "") $errores[] = "Especifica el otro equipo.";
    if ($longitud($datos["equipo_otro"]) > 30) $errores[] = "El otro equipo admite máximo 30 caracteres.";

    $preferenciasValidas = ["fuerza", "funcional", "cardio", "cross", "movilidad", "otro"];
    if (!$datos["preferencias"] || array_diff($datos["preferencias"], $preferenciasValidas)) $errores[] = "Selecciona al menos una preferencia válida.";
    if (in_array("otro", $datos["preferencias"], true) && $datos["preferencia_otra"] === "") $errores[] = "Especifica la otra preferencia.";
    if ($longitud($datos["preferencia_otra"]) > 15) $errores[] = "La preferencia admite máximo 15 caracteres.";
    foreach (["favoritos", "evitar"] as $campo) {
        if ($longitud($datos[$campo]) > 500) $errores[] = "La lista de ejercicios admite máximo 500 caracteres.";
        foreach (array_filter(array_map("trim", explode(",", $datos[$campo]))) as $ejercicio) {
            if ($longitud($ejercicio) > 50) $errores[] = "Cada ejercicio admite máximo 50 caracteres.";
        }
    }

    if (!filter_var($datos["horas_sueno"], FILTER_VALIDATE_INT) || (int)$datos["horas_sueno"] < 0 || (int)$datos["horas_sueno"] > 24) $errores[] = "Las horas de sueño deben estar entre 0 y 24.";
    if ($datos["dieta"] === "si" && $datos["dieta_descripcion"] === "") $errores[] = "Describe la dieta o plan alimenticio.";
    if ($longitud($datos["dieta_descripcion"]) > 255) $errores[] = "La descripción de dieta admite máximo 255 caracteres.";
    foreach (["condicion" => "condicion_descripcion", "lesion" => "lesion_descripcion", "limitacion" => "limitacion_descripcion"] as $campo => $descripcion) {
        if (!in_array($datos[$campo], ["si", "no"], true)) $errores[] = "Responde sí o no para " . $campo . ".";
        if ($datos[$campo] === "si" && $datos[$descripcion] === "") $errores[] = "Describe " . $campo . ".";
        if ($longitud($datos[$descripcion]) > 100) $errores[] = "La descripción de " . $campo . " admite máximo 100 caracteres.";
    }
    foreach (["fuerza", "resistencia", "flexibilidad", "energia", "disciplina", "motivacion"] as $campo) {
        if (!filter_var($datos[$campo], FILTER_VALIDATE_INT) || (int)$datos[$campo] < 1 || (int)$datos[$campo] > 10) $errores[] = "La evaluación de " . $campo . " debe ser del 1 al 10.";
    }
    if ($longitud($datos["comentarios"]) > 40) $errores[] = "Los comentarios admiten máximo 40 caracteres.";
    if ($datos["acepto_reglamento"] !== "si") $errores[] = "Debes aceptar el reglamento para continuar.";
    if ($datos["compromiso"] !== "si") $errores[] = "Debes aceptar el compromiso para continuar.";
    return $errores;
}

function respuestaRutina(string $mensaje, int $codigo = 200): void
{
    http_response_code($codigo);
    echo "<h2>" . htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") . "</h2>";
    echo '<p><a href="../HTML-Code/rutinaInterview.html">Volver al formulario</a></p>';
}

function guardarRutina(PDO $conexion, array $datos): string
{
    $conexion->beginTransaction();
    try {
        // Solo permite enviar entrevistas a clientes con una cuenta activa.
        $consulta = $conexion->prepare(
            "SELECT TOP 1 id_usuario FROM usuariocliente WITH (UPDLOCK, HOLDLOCK)
             WHERE correo = ? AND tiene_cuenta = 1 ORDER BY id_usuario"
        );
        $consulta->execute([$datos["correo"]]);
        $idUsuario = $consulta->fetchColumn();
        if (!$idUsuario) throw new DomainException("Ese correo no tiene una cuenta activa. Regístrate primero.");

                // fisica_rutina referencia usuariocliente; se enlaza el empleado por correo.
        $consulta = $conexion->prepare(
                        "SELECT TOP 1 u.id_usuario FROM Empleados e
                         INNER JOIN Rol r ON r.id_rol = e.id_rol
                         INNER JOIN usuariocliente u ON u.correo = e.correo
                         WHERE e.estado_laboral = 'activo'
               AND (LOWER(r.nombre_rol) LIKE '%coach%' OR LOWER(r.nombre_rol) LIKE '%entrenador%')
                         ORDER BY e.id_empleado"
        );
        $consulta->execute();
        $idCoach = $consulta->fetchColumn();
        if (!$idCoach) throw new DomainException("No hay un empleado activo con rol Coach/Entrenador enlazado a un usuario. Se necesita para guardar la evaluación física.");

        $objetivos = $datos["objetivos"];
        if (in_array("Otro", $objetivos, true)) $objetivos[array_search("Otro", $objetivos, true)] = $datos["objetivo_otro"];
        $objetivoPrincipal = implode(", ", $objetivos);
        $tipoEntrenamiento = [
            "Entrenamiento funcional" => "Funcional",
            "Movilidad y flexibilidad" => "Movilidad",
        ][$datos["tipo_entrenamiento"]] ?? $datos["tipo_entrenamiento"];
        $lugares = $datos["lugares"];
        if (in_array("otro", $lugares, true)) $lugares[array_search("otro", $lugares, true)] = $datos["lugar_otro"];
        $lugarTexto = implode(", ", $lugares);
        $minutos = $datos["tiempo_disponible"] === "mas90" ? 120 : (int)$datos["tiempo_disponible"];
        $tiempoEntrenado = (int)$datos["tiempo_entrenado"];
        if ($datos["unidad_tiempo_entrenado"] === "días") $tiempoEntrenado = intdiv($tiempoEntrenado, 30);
        if ($datos["unidad_tiempo_entrenado"] === "años") $tiempoEntrenado *= 12;
        $fechaNacimiento = new DateTimeImmutable($datos["fecha"]);
        $edad = (new DateTimeImmutable("today"))->diff($fechaNacimiento)->y;
        $nivelCompromiso = (int)round(array_sum(array_map(static fn(string $campo): int => (int)$datos[$campo], ["fuerza", "resistencia", "flexibilidad", "energia", "disciplina", "motivacion"])) / 6);

        // Crea o actualiza los datos físicos y registra la aceptación expresa del reglamento.
        $consulta = $conexion->prepare("SELECT 1 FROM Detalle_Cliente WHERE id_usuario = ?");
        $consulta->execute([$idUsuario]);
        if ($consulta->fetchColumn()) {
            $consulta = $conexion->prepare(
                "UPDATE Detalle_Cliente SET fecha_nacimiento = ?, edad = ?, sexo = ?, estatura = ?,
                 peso_actual = ?, ocupacion = ?, acepto_reglamento = 'si' WHERE id_usuario = ?"
            );
            $consulta->execute([$datos["fecha"], $edad, $datos["sexo"], $datos["estatura"], $datos["peso"], $datos["ocupacion"] ?: null, $idUsuario]);
        } else {
            $consulta = $conexion->prepare(
                "INSERT INTO Detalle_Cliente (id_usuario, fecha_nacimiento, edad, sexo, estatura, peso_actual, ocupacion, acepto_reglamento)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'si')"
            );
            $consulta->execute([$idUsuario, $datos["fecha"], $edad, $datos["sexo"], $datos["estatura"], $datos["peso"], $datos["ocupacion"] ?: null]);
        }

        $idEntrevista = siguienteId($conexion, "Entrevista", "id_entrevista", "E");
        $consulta = $conexion->prepare("INSERT INTO Entrevista (id_entrevista, id_usuario, fecha_registro, tipo_plan, nivel_compromiso) VALUES (?, ?, GETDATE(), ?, ?)");
        $consulta->execute([$idEntrevista, $idUsuario, "Entrenamiento", $nivelCompromiso]);

        $idEntrevistaEnt = siguienteId($conexion, "entrevista_entrenamiento", "id_entrevista_ent", "EE");
        $consulta = $conexion->prepare(
            "INSERT INTO entrevista_entrenamiento
                (id_entrevista_ent, objetivo_principal, objetivo_especifico, experiencia_previa,
                 nivel_experiencia, trabajo_entrenador, lugar_entrenamiento, dias_disponibles,
                 tiempo_disponible, horario_preferencia, nivel_estres, calidad_sueño, horas_sueño,
                 sigue_dieta, comentarios_adicionales, id_usuario, tiempo_entrenado,
                 entrena_actualmente, realiza_otro_deporte, cual_otro_deporte)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $consulta->execute([
            $idEntrevistaEnt, $objetivoPrincipal, $datos["objetivo_especifico"], $datos["experiencia"],
            $datos["nivel"], $datos["entrenador"], $lugarTexto, (int)$datos["dias_disponibles"],
            $minutos, $datos["horario"], $datos["estres"], $datos["sueno"] === "si" ? "buena" : "mala",
            (int)$datos["horas_sueno"], $datos["dieta"], $datos["comentarios"] ?: null, $idUsuario,
            $tiempoEntrenado, $datos["actividad_actual"], $datos["deporte"],
            $datos["deporte"] === "si" ? $datos["nombre_deporte"] : null,
        ]);

        // La BD relaciona salud y descripción de dieta por Entrevista_Nutri_Sup.
        $condiciones = [];
        foreach (["condicion" => ["condicion_descripcion", "enfermedad"], "lesion" => ["lesion_descripcion", "lesion"], "limitacion" => ["limitacion_descripcion", "lesion"]] as $campo => [$descripcion, $tipo]) {
            if ($datos[$campo] === "si") $condiciones[] = [$datos[$descripcion], $tipo];
        }
        $idEntrevistaNutri = null;
        if ($condiciones || $datos["dieta"] === "si") {
            $idEntrevistaNutri = siguienteId($conexion, "Entrevista_Nutri_Sup", "id_entrevista_nutricional", "EN");
            $consulta = $conexion->prepare("INSERT INTO Entrevista_Nutri_Sup (id_entrevista_nutricional, id_entrevista, descripcion_dieta) VALUES (?, ?, ?)");
            $consulta->execute([$idEntrevistaNutri, $idEntrevista, $datos["dieta"] === "si" ? $datos["dieta_descripcion"] : null]);
        }
        if ($condiciones) {
            $idDetalleSalud = (int)$conexion->query("SELECT ISNULL(MAX(id_detalle_salud), 0) + 1 FROM Detalle_Salud_Cliente WITH (UPDLOCK, HOLDLOCK)")->fetchColumn();
            $consulta = $conexion->prepare("INSERT INTO Detalle_Salud_Cliente (id_detalle_salud, id_entrevista_nutricional) VALUES (?, ?)");
            $consulta->execute([$idDetalleSalud, $idEntrevistaNutri]);
            foreach ($condiciones as [$descripcion, $tipo]) {
                $consulta = $conexion->prepare("INSERT INTO Detalle_Condiciones_Salud (id_detalle_salud, descripcion_condicion, tipo_condicion) VALUES (?, ?, ?)");
                $consulta->execute([$idDetalleSalud, $descripcion, $tipo]);
            }
        }

        $equipos = $datos["equipo"];
        if (in_array("Otro", $equipos, true)) $equipos[array_search("Otro", $equipos, true)] = $datos["equipo_otro"];
        foreach ($equipos as $equipo) {
            if ($equipo === "Ninguno") continue;
            $idEquipo = siguienteId($conexion, "detalle_equipamiento_cliente", "id_detalle_equipo", "EQ");
            $consulta = $conexion->prepare("INSERT INTO detalle_equipamiento_cliente (id_detalle_equipo, equipo_disponible, id_entrevista_ent) VALUES (?, ?, ?)");
            $consulta->execute([$idEquipo, $equipo, $idEntrevistaEnt]);
        }

        $nombresPreferencia = ["fuerza" => "Fuerza", "funcional" => "Funcional", "cardio" => "Cardio", "cross" => "Cross", "movilidad" => "Movilidad"];
        foreach ($datos["preferencias"] as $preferencia) {
            $nombre = $preferencia === "otro" ? $datos["preferencia_otra"] : $nombresPreferencia[$preferencia];
            $idPreferencia = siguienteId($conexion, "detalle_preferecias_ejercicio", "id_detalle_preferencia", "P");
            $consulta = $conexion->prepare("INSERT INTO detalle_preferecias_ejercicio (id_detalle_preferencia, tipo_entrenamiento_disfruta, id_entrevista_ent) VALUES (?, ?, ?)");
            $consulta->execute([$idPreferencia, $nombre, $idEntrevistaEnt]);
        }
        if ($datos["tipo_entrenamiento"] !== "") {
            $idPreferencia = siguienteId($conexion, "detalle_preferecias_ejercicio", "id_detalle_preferencia", "P");
            $consulta = $conexion->prepare("INSERT INTO detalle_preferecias_ejercicio (id_detalle_preferencia, tipo_entrenamiento_disfruta, id_entrevista_ent) VALUES (?, ?, ?)");
            $consulta->execute([$idPreferencia, $tipoEntrenamiento, $idEntrevistaEnt]);
        }

        foreach (["favoritos" => "favorito", "evitar" => "evitar"] as $campo => $tipo) {
            $ejercicios = array_filter(array_map("trim", explode(",", $datos[$campo])));
            if (!$ejercicios) continue;
            $idPreferencia = siguienteId($conexion, "detalle_preferecias_ejercicio", "id_detalle_preferencia", "P");
            $consulta = $conexion->prepare("INSERT INTO detalle_preferecias_ejercicio (id_detalle_preferencia, tipo_entrenamiento_disfruta, id_entrevista_ent) VALUES (?, ?, ?)");
            $consulta->execute([$idPreferencia, "Ejercicios", $idEntrevistaEnt]);
            foreach ($ejercicios as $ejercicio) {
                $consulta = $conexion->prepare("INSERT INTO Detalle_Ejercicios_Preferencia (id_detalle_preferencia, nombre_ejercicio, tipo_preferencia) VALUES (?, ?, ?)");
                $consulta->execute([$idPreferencia, $ejercicio, $tipo]);
            }
        }

        $idEvaluacion = siguienteId($conexion, "fisica_rutina", "id_evaluacion_fisica", "EF");
        $consulta = $conexion->prepare(
            "INSERT INTO fisica_rutina (id_evaluacion_fisica, nivel_fuerza, nivel_resistencia, nivel_flexibilidad,
                nivel_energia, nivel_disciplina, nivel_motivacion, id_entrevista_ent, id_coach)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $consulta->execute([$idEvaluacion, $datos["fuerza"], $datos["resistencia"], $datos["flexibilidad"], $datos["energia"], $datos["disciplina"], $datos["motivacion"], $idEntrevistaEnt, $idCoach]);

        $conexion->commit();
        return $idEntrevista;
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        throw $error;
    }
}

function procesarRutina(PDO $conexion): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        exit("Envía el formulario para continuar.");
    }
    $datos = obtenerDatosRutina();
    $errores = validarRutina($datos);
    if ($errores) {
        respuestaRutina(implode(" ", $errores), 422);
        return;
    }
    try {
        $folio = guardarRutina($conexion, $datos);
        respuestaRutina("Entrevista de rutina guardada. Folio: " . $folio);
    } catch (DomainException $error) {
        respuestaRutina($error->getMessage(), 422);
    } catch (Throwable $error) {
        error_log("Error al guardar entrevista de rutina: " . $error->getMessage());
        respuestaRutina("No fue posible guardar la entrevista. Revisa la conexión y la configuración de la BD.", 500);
    }
}

procesarRutina($conexion);
?>
