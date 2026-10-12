<?php
session_start();
require_once __DIR__ . "/libreria.php";

function textoRutina(string $campo): string
{
    $valor = $_POST[$campo] ?? "";
    return is_string($valor) || is_numeric($valor) ? trim((string)$valor) : "";
}

function listaRutina(string $campo): array
{
    $valores = $_POST[$campo] ?? [];
    if (!is_array($valores)) return [];
    return array_values(array_filter($valores, "is_string"));
}

function siguienteIdRutina(PDO $conexion, string $tabla, string $columna, string $prefijo): string
{
    // Los nombres de tabla y columna se definen aquí, nunca llegan desde el formulario.
    $posicion = strlen($prefijo) + 1;
    $consulta = $conexion->prepare(
        "SELECT ISNULL(MAX(TRY_CONVERT(INT, SUBSTRING($columna, $posicion, 10))), 0) + 1
         FROM $tabla WITH (UPDLOCK, HOLDLOCK) WHERE $columna LIKE ?"
    );
    $consulta->execute([$prefijo . "%"]);
    return $prefijo . $consulta->fetchColumn();
}

function validarRutina(array $datos): array
{
    $errores = [];
    $longitud = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
    $patronNombre = '/^[\p{L}\p{M}]+(?:[ \x{27}\x{2019}.\x{2D}][\p{L}\p{M}]+)*$/u';

    if ($datos["nombre"] === "" || $longitud($datos["nombre"]) > 30 || !preg_match($patronNombre, $datos["nombre"])) $errores[] = "El nombre debe contener solo letras y tener máximo 30 caracteres.";
    if (!filter_var($datos["correo"], FILTER_VALIDATE_EMAIL) || $datos["correo"] !== ($_SESSION["correo"] ?? "")) $errores[] = "El correo debe ser válido y coincidir con la cuenta activa.";
    if ($datos["telefono"] !== "" && !preg_match('/^[0-9]{10}$/', $datos["telefono"])) $errores[] = "El teléfono debe tener exactamente 10 dígitos.";
    if (!in_array($datos["sexo"], ["femenino", "masculino"], true)) $errores[] = "Selecciona un sexo válido.";

    $fecha = DateTimeImmutable::createFromFormat("!Y-m-d", $datos["fecha"]);
    $erroresFecha = DateTimeImmutable::getLastErrors();
    if (!$fecha || ($erroresFecha !== false && ($erroresFecha["warning_count"] || $erroresFecha["error_count"])) || $fecha->format("Y-m-d") !== $datos["fecha"] || $fecha > new DateTimeImmutable("today")) $errores[] = "La fecha de nacimiento no es válida.";
    foreach (["estatura" => [80, 250], "peso" => [20, 999.99]] as $campo => [$minimo, $maximo]) {
        if (!is_numeric($datos[$campo]) || (float)$datos[$campo] < $minimo || (float)$datos[$campo] > $maximo) $errores[] = "Revisa el valor de " . $campo . ".";
    }
    if ($longitud($datos["ocupacion"]) > 30) $errores[] = "La ocupación admite máximo 30 caracteres.";

    $objetivosValidos = ["Ganar masa muscular", "Perder grasa", "Tonificar / Definir", "Mejorar fuerza", "Mejorar resistencia", "Salud general / Bienestar", "Rehabilitación / Recuperación", "Otro"];
    if (!$datos["objetivos"] || array_diff($datos["objetivos"], $objetivosValidos)) $errores[] = "Selecciona al menos un objetivo válido.";
    if (in_array("Otro", $datos["objetivos"], true) && $datos["objetivo_otro"] === "") $errores[] = "Especifica tu otro objetivo.";
    if ($longitud($datos["objetivo_otro"]) > 30 || $longitud($datos["objetivo_especifico"]) > 30) $errores[] = "Los objetivos admiten máximo 30 caracteres.";
    $objetivosGuardados = $datos["objetivos"];
    if (in_array("Otro", $objetivosGuardados, true)) $objetivosGuardados[array_search("Otro", $objetivosGuardados, true)] = $datos["objetivo_otro"];
    if ($longitud(implode(", ", $objetivosGuardados)) > 255) $errores[] = "La selección combinada de objetivos supera los 255 caracteres permitidos por la BD.";
    if (!in_array($datos["experiencia"], ["si", "no"], true)) $errores[] = "Selecciona si tienes experiencia entrenando.";
    if (!in_array($datos["nivel"], ["principiante", "intermedio", "avanzado"], true)) $errores[] = "Selecciona un nivel de experiencia válido.";
    if (!in_array($datos["entrenador"], ["si", "no"], true)) $errores[] = "Selecciona si has trabajado con entrenador.";
    if (!filter_var($datos["tiempo_entrenado"], FILTER_VALIDATE_INT) || (int)$datos["tiempo_entrenado"] < 0 || (int)$datos["tiempo_entrenado"] > 600) $errores[] = "El tiempo entrenado debe ser de 0 a 600 meses.";
    if (!filter_var($datos["dias"], FILTER_VALIDATE_INT) || (int)$datos["dias"] < 1 || (int)$datos["dias"] > 7) $errores[] = "Selecciona entre 1 y 7 días disponibles.";
    if (!in_array($datos["tiempo_disponible"], ["30", "45", "60", "90", "mas90"], true)) $errores[] = "Selecciona un tiempo disponible válido.";
    if (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $datos["horario"])) $errores[] = "Selecciona una hora válida.";
    $lugaresValidos = ["gimnasio", "casa", "aire libre", "otro"];
    if (!$datos["lugar"] || array_diff($datos["lugar"], $lugaresValidos)) $errores[] = "Selecciona al menos un lugar de entrenamiento válido.";
    if (in_array("otro", $datos["lugar"], true) && $datos["lugar_otro"] === "") $errores[] = "Especifica el otro lugar de entrenamiento.";
    if ($longitud($datos["lugar_otro"]) > 20) $errores[] = "El lugar admite máximo 20 caracteres.";
    $lugaresGuardados = $datos["lugar"];
    if (in_array("otro", $lugaresGuardados, true)) $lugaresGuardados[array_search("otro", $lugaresGuardados, true)] = $datos["lugar_otro"];
    if ($longitud(implode(", ", $lugaresGuardados)) > 100) $errores[] = "La selección combinada de lugares supera los 100 caracteres permitidos por la BD.";

    $equiposValidos = ["Mancuernas", "Bandas elásticas", "Máquinas", "Barras", "Kettlebells", "Ninguno", "Otro"];
    $preferenciasValidas = ["fuerza", "funcional", "cardio", "cross", "movilidad", "otro"];
    if (!$datos["equipo"] || array_diff($datos["equipo"], $equiposValidos)) $errores[] = "Selecciona al menos un equipo o 'ninguno'.";
    if (in_array("Ninguno", $datos["equipo"], true) && count($datos["equipo"]) > 1) $errores[] = "No combines 'ninguno' con otro equipo.";
    if (in_array("Otro", $datos["equipo"], true) && $datos["equipo_otro"] === "") $errores[] = "Especifica el otro equipo.";
    if ($longitud($datos["equipo_otro"]) > 30) $errores[] = "El equipo admite máximo 30 caracteres.";
    if (!$datos["preferencias"] || array_diff($datos["preferencias"], $preferenciasValidas)) $errores[] = "Selecciona al menos una preferencia válida.";
    if (in_array("otro", $datos["preferencias"], true) && $datos["preferencia_otra"] === "") $errores[] = "Especifica la otra preferencia.";
    if ($longitud($datos["preferencia_otra"]) > 15) $errores[] = "La preferencia admite máximo 15 caracteres.";
    foreach (["favoritos", "evitar"] as $campo) {
        if ($longitud($datos[$campo]) > 500) $errores[] = "La lista de ejercicios admite máximo 500 caracteres.";
        foreach (array_filter(array_map("trim", explode(",", $datos[$campo]))) as $ejercicio) {
            if ($longitud($ejercicio) > 50) $errores[] = "Cada ejercicio admite máximo 50 caracteres.";
        }
    }

    foreach (["estres" => ["bajo", "medio", "alto"], "sueno" => ["si", "no"], "dieta" => ["si", "no"]] as $campo => $valores) {
        if (!in_array($datos[$campo], $valores, true)) $errores[] = "Selecciona una respuesta válida para " . $campo . ".";
    }
    if (!in_array($datos["actividad_actual"], ["si", "no"], true)) $errores[] = "Indica si entrenas actualmente.";
    if (!in_array($datos["realiza_otro_deporte"], ["si", "no"], true)) $errores[] = "Indica si realizas otro deporte.";
    if ($datos["realiza_otro_deporte"] === "si" && $datos["cual_otro_deporte"] === "") $errores[] = "Especifica cuál otro deporte realizas.";
    if ($longitud($datos["cual_otro_deporte"]) > 50) $errores[] = "El deporte adicional admite máximo 50 caracteres.";
    if ($longitud($datos["tipo_entrenamiento_actual"]) > 15) $errores[] = "El tipo de entrenamiento admite máximo 15 caracteres.";
    if ($datos["dieta"] === "si" && $datos["dieta_descripcion"] === "") $errores[] = "Describe tu dieta o plan alimenticio.";
    if ($longitud($datos["dieta_descripcion"]) > 255) $errores[] = "La descripción de dieta admite máximo 255 caracteres.";
    if (!filter_var($datos["horas_sueno"], FILTER_VALIDATE_INT) || (int)$datos["horas_sueno"] < 0 || (int)$datos["horas_sueno"] > 24) $errores[] = "Las horas de sueño deben estar entre 0 y 24.";
    foreach (["fuerza", "resistencia", "flexibilidad", "energia", "disciplina", "motivacion"] as $campo) {
        if (!filter_var($datos[$campo], FILTER_VALIDATE_INT) || (int)$datos[$campo] < 1 || (int)$datos[$campo] > 10) $errores[] = "La evaluación de " . $campo . " debe ser del 1 al 10.";
    }
    if ($datos["comentarios"] !== "" && $longitud($datos["comentarios"]) > 40) $errores[] = "Los comentarios admiten máximo 40 caracteres.";
    if ($datos["compromiso"] !== "si") $errores[] = "Debes aceptar el compromiso para continuar.";

    foreach (["condicion" => "condicion_especifica", "lesion" => "lesion_especifica", "limitacion" => "limitacion_especifica"] as $campo => $detalle) {
        if (!in_array($datos[$campo], ["si", "no"], true)) $errores[] = "Responde sí o no para " . $campo . ".";
        if ($datos[$campo] === "si" && $datos[$detalle] === "") $errores[] = "Describe " . $campo . ".";
        if ($longitud($datos[$detalle]) > 100) $errores[] = "La descripción de " . $campo . " admite máximo 100 caracteres.";
    }

    return $errores;
}

function mostrarRespuestaRutina(string $mensaje): void
{
    echo "<h2>" . htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") . "</h2>";
    echo '<p><a href="../HTML-Code/rutinaInterview.html">Volver al formulario</a></p>';
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Envía el formulario para continuar.");
}
if (empty($_SESSION["logueado"]) || empty($_SESSION["id_usuario"])) {
    http_response_code(401);
    exit("Inicia sesión antes de enviar la entrevista de rutina.");
}

$datos = [
    "nombre" => textoRutina("nombre"), "correo" => textoRutina("correo"), "telefono" => textoRutina("telefono"),
    "fecha" => textoRutina("fecha"), "sexo" => textoRutina("sexo"), "estatura" => textoRutina("estatura"),
    "peso" => textoRutina("peso"), "ocupacion" => textoRutina("ocupacion"),
    "objetivos" => listaRutina("objetivos"), "objetivo_otro" => textoRutina("objetivoOtro"),
    "objetivo_especifico" => textoRutina("objetivoEspecifico"), "experiencia" => textoRutina("experiencia"),
    "nivel" => textoRutina("nivelExp"), "entrenador" => textoRutina("expEntrenador"),
    "tiempo_entrenado" => textoRutina("tiempoEntrenando"), "dias" => textoRutina("disponibilidad"),
    "tiempo_disponible" => textoRutina("disponibilidadTiempo"), "horario" => textoRutina("horarios"),
    "lugar" => listaRutina("lugar"), "lugar_otro" => textoRutina("lugarOtro"),
    "equipo" => listaRutina("equipo"), "equipo_otro" => textoRutina("equipoOtro"),
    "preferencias" => listaRutina("preferencias"), "preferencia_otra" => textoRutina("preferenciasOtro"),
    "favoritos" => textoRutina("disfruta"), "evitar" => textoRutina("noDisfruta"),
    "estres" => textoRutina("estres"), "sueno" => textoRutina("sueño"), "horas_sueno" => textoRutina("horasSueño"),
    "dieta" => strtolower(textoRutina("dieta")), "dieta_descripcion" => textoRutina("dietatxt"),
    "actividad_actual" => textoRutina("actividadActual"),
    "realiza_otro_deporte" => textoRutina("deporte"), "cual_otro_deporte" => textoRutina("nombreDeporte"),
    "tipo_entrenamiento_actual" => textoRutina("tipoEntrenamiento"),
    "comentarios" => textoRutina("comentarios"),
    "fuerza" => textoRutina("fuerza"), "resistencia" => textoRutina("resistencia"),
    "flexibilidad" => textoRutina("flexibilidad"), "energia" => textoRutina("energia"),
    "disciplina" => textoRutina("disciplina"), "motivacion" => textoRutina("motivacion"),
    "condicion" => textoRutina("condicionMedica"), "condicion_especifica" => textoRutina("condicionMedicaEspecifica"),
    "lesion" => textoRutina("lesion"), "lesion_especifica" => textoRutina("lesionEspecifica"),
    "limitacion" => textoRutina("limitacionEjercicios"), "limitacion_especifica" => textoRutina("limitacionEjerciciosTxt"),
    "compromiso" => textoRutina("compromiso"),
];

$errores = validarRutina($datos);
if ($errores) {
    http_response_code(422);
    mostrarRespuestaRutina(implode(" ", $errores));
    exit;
}

try {
    $conexion = conectaDB();
    $conexion->beginTransaction();
    $idUsuario = $_SESSION["id_usuario"];

    // Solo se actualiza un perfil ya creado por el formulario inicial.
    $consulta = $conexion->prepare("SELECT 1 FROM Detalle_Cliente WHERE id_usuario = ?");
    $consulta->execute([$idUsuario]);
    if (!$consulta->fetchColumn()) throw new RuntimeException("Completa primero la entrevista inicial para crear tu perfil.");
    $consulta = $conexion->prepare("UPDATE usuariocliente SET nombre = ?, telefono = ? WHERE id_usuario = ?");
    $consulta->execute([$datos["nombre"], $datos["telefono"] ?: null, $idUsuario]);
    $fechaNacimiento = new DateTimeImmutable($datos["fecha"]);
    $edad = (new DateTimeImmutable("today"))->diff($fechaNacimiento)->y;
    $consulta = $conexion->prepare("UPDATE Detalle_Cliente SET fecha_nacimiento = ?, edad = ?, sexo = ?, estatura = ?, peso_actual = ?, ocupacion = ? WHERE id_usuario = ?");
    $consulta->execute([$datos["fecha"], $edad, $datos["sexo"], $datos["estatura"], $datos["peso"], $datos["ocupacion"] ?: null, $idUsuario]);

    $consulta = $conexion->prepare(
        "SELECT TOP 1 e.id_usuario FROM Empleados e
         INNER JOIN usuariocliente u ON u.id_usuario = e.id_usuario
         INNER JOIN Rol r ON r.id_rol = u.id_rol
         WHERE e.estado_laboral = 'activo'
           AND (LOWER(r.nombre_rol) LIKE '%coach%' OR LOWER(r.nombre_rol) LIKE '%entrenador%')
         ORDER BY e.id_usuario"
    );
    $consulta->execute();
    $idCoach = $consulta->fetchColumn();
    if (!$idCoach) throw new RuntimeException("No hay un entrenador activo con rol Coach/Entrenador para asignar la evaluación.");

    $objetivosGuardados = $datos["objetivos"];
    if (in_array("Otro", $objetivosGuardados, true)) $objetivosGuardados[array_search("Otro", $objetivosGuardados, true)] = $datos["objetivo_otro"];
    $objetivo = implode(", ", $objetivosGuardados);
    $nivelCompromiso = (int)round(array_sum(array_map(fn($campo) => (int)$datos[$campo], ["fuerza", "resistencia", "flexibilidad", "energia", "disciplina", "motivacion"])) / 6);
    $idEntrevista = siguienteIdRutina($conexion, "Entrevista", "id_entrevista", "E");
    $consulta = $conexion->prepare("INSERT INTO Entrevista (id_entrevista, id_usuario, fecha_registro, tipo_plan, nivel_compromiso) VALUES (?, ?, GETDATE(), ?, ?)");
    $consulta->execute([$idEntrevista, $idUsuario, "Entrenamiento", $nivelCompromiso]);

    $idEntrevistaEnt = siguienteIdRutina($conexion, "entrevista_entrenamiento", "id_entrevista_ent", "EE");
    $minutos = $datos["tiempo_disponible"] === "mas90" ? 120 : (int)$datos["tiempo_disponible"];
    $lugaresGuardados = $datos["lugar"];
    if (in_array("otro", $lugaresGuardados, true)) $lugaresGuardados[array_search("otro", $lugaresGuardados, true)] = $datos["lugar_otro"];
    $lugar = implode(", ", $lugaresGuardados);
    $comentarios = $datos["comentarios"];
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
        $idEntrevistaEnt, $objetivo, $datos["objetivo_especifico"], $datos["experiencia"],
        $datos["nivel"], $datos["entrenador"], $lugar, (int)$datos["dias"], $minutos,
        $datos["horario"], $datos["estres"], $datos["sueno"] === "si" ? "buena" : "mala",
        (int)$datos["horas_sueno"], $datos["dieta"], $comentarios ?: null, $idUsuario,
        (int)$datos["tiempo_entrenado"], $datos["actividad_actual"], $datos["realiza_otro_deporte"],
        $datos["realiza_otro_deporte"] === "si" ? $datos["cual_otro_deporte"] : null,
    ]);

    // La BD vincula condiciones de salud a una entrevista nutricional; se crea el vínculo vacío requerido.
    $condiciones = [];
    foreach ([
        "condicion" => ["condicion_especifica", "enfermedad"],
        "lesion" => ["lesion_especifica", "lesion"],
        "limitacion" => ["limitacion_especifica", "lesion"],
    ] as $campo => [$detalle, $tipo]) {
        if ($datos[$campo] === "si") $condiciones[] = [$datos[$detalle], $tipo];
    }
    $idEntrevistaNutri = null;
    if ($condiciones || $datos["dieta"] === "si") {
        $idEntrevistaNutri = siguienteIdRutina($conexion, "Entrevista_Nutri_Sup", "id_entrevista_nutricional", "N");
        $consulta = $conexion->prepare("INSERT INTO Entrevista_Nutri_Sup (id_entrevista_nutricional, id_entrevista, descripcion_dieta) VALUES (?, ?, ?)");
        $consulta->execute([$idEntrevistaNutri, $idEntrevista, $datos["dieta"] === "si" ? $datos["dieta_descripcion"] : null]);
    }
    if ($condiciones) {
        $idDetalleSalud = (int)$conexion->query(
            "SELECT ISNULL(MAX(id_detalle_salud), 0) + 1 FROM Detalle_Salud_Cliente WITH (UPDLOCK, HOLDLOCK)"
        )->fetchColumn();
        $consulta = $conexion->prepare("INSERT INTO Detalle_Salud_Cliente (id_detalle_salud, id_entrevista_nutricional) VALUES (?, ?)");
        $consulta->execute([$idDetalleSalud, $idEntrevistaNutri]);
        foreach ($condiciones as [$descripcion, $tipo]) {
            $consulta = $conexion->prepare(
                "INSERT INTO Detalle_Condiciones_Salud (id_detalle_salud, descripcion_condicion, tipo_condicion) VALUES (?, ?, ?)"
            );
            $consulta->execute([$idDetalleSalud, $descripcion, $tipo]);
        }
    }

    $equipo = $datos["equipo"];
    if (in_array("Otro", $equipo, true)) $equipo[array_search("Otro", $equipo, true)] = $datos["equipo_otro"];
    foreach ($equipo as $nombreEquipo) {
        if ($nombreEquipo === "Ninguno") continue;
        $idEquipo = siguienteIdRutina($conexion, "detalle_equipamiento_cliente", "id_detalle_equipo", "EQ");
        $consulta = $conexion->prepare("INSERT INTO detalle_equipamiento_cliente (id_detalle_equipo, equipo_disponible, id_entrevista_ent) VALUES (?, ?, ?)");
        $consulta->execute([$idEquipo, $nombreEquipo, $idEntrevistaEnt]);
    }

    $nombresPreferencia = ["fuerza" => "Fuerza", "funcional" => "Funcional", "cardio" => "Cardio", "cross" => "Cross", "movilidad" => "Movilidad"];
    foreach ($datos["preferencias"] as $preferencia) {
        $nombrePreferencia = $preferencia === "otro" ? $datos["preferencia_otra"] : $nombresPreferencia[$preferencia];
        $idPreferencia = siguienteIdRutina($conexion, "detalle_preferecias_ejercicio", "id_detalle_preferencia", "P");
        $consulta = $conexion->prepare("INSERT INTO detalle_preferecias_ejercicio (id_detalle_preferencia, tipo_entrenamiento_disfruta, id_entrevista_ent) VALUES (?, ?, ?)");
        $consulta->execute([$idPreferencia, $nombrePreferencia, $idEntrevistaEnt]);
    }

    if ($datos["tipo_entrenamiento_actual"] !== "") {
        $idPreferencia = siguienteIdRutina($conexion, "detalle_preferecias_ejercicio", "id_detalle_preferencia", "P");
        $consulta = $conexion->prepare(
            "INSERT INTO detalle_preferecias_ejercicio
                (id_detalle_preferencia, tipo_entrenamiento_disfruta, id_entrevista_ent)
             VALUES (?, ?, ?)"
        );
        $consulta->execute([$idPreferencia, $datos["tipo_entrenamiento_actual"], $idEntrevistaEnt]);
    }

    // Guarda ejercicios favoritos y a evitar en el detalle que admite esas dos categorías.
    foreach (["favoritos" => "favorito", "evitar" => "evitar"] as $campo => $tipo) {
        $ejercicios = array_filter(array_map("trim", explode(",", $datos[$campo])));
        if (!$ejercicios) continue;
        $idPreferencia = siguienteIdRutina($conexion, "detalle_preferecias_ejercicio", "id_detalle_preferencia", "P");
        $consulta = $conexion->prepare(
            "INSERT INTO detalle_preferecias_ejercicio (id_detalle_preferencia, tipo_entrenamiento_disfruta, id_entrevista_ent)
             VALUES (?, ?, ?)"
        );
        $consulta->execute([$idPreferencia, "Ejercicios", $idEntrevistaEnt]);
        foreach ($ejercicios as $ejercicio) {
            $consulta = $conexion->prepare(
                "INSERT INTO Detalle_Ejercicios_Preferencia (id_detalle_preferencia, nombre_ejercicio, tipo_preferencia)
                 VALUES (?, ?, ?)"
            );
            $consulta->execute([$idPreferencia, $ejercicio, $tipo]);
        }
    }

    // Guarda autoevaluaciones en la tabla que exige asignación de un coach activo.
    $idEvaluacion = siguienteIdRutina($conexion, "fisica_rutina", "id_evaluacion_fisica", "EF");
    $consulta = $conexion->prepare(
        "INSERT INTO fisica_rutina
            (id_evaluacion_fisica, nivel_fuerza, nivel_resistencia, nivel_flexibilidad,
             nivel_energia, nivel_disciplina, nivel_motivacion, id_entrevista_ent, id_coach)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $consulta->execute([$idEvaluacion, $datos["fuerza"], $datos["resistencia"], $datos["flexibilidad"], $datos["energia"], $datos["disciplina"], $datos["motivacion"], $idEntrevistaEnt, $idCoach]);

    $conexion->commit();
    mostrarRespuestaRutina("Entrevista de rutina guardada correctamente.");
} catch (Throwable $error) {
    if (isset($conexion) && $conexion->inTransaction()) $conexion->rollBack();
    error_log("Error al guardar entrevista de rutina: " . $error->getMessage());
    http_response_code(500);
    mostrarRespuestaRutina($error instanceof RuntimeException ? $error->getMessage() : "No fue posible guardar la entrevista. Revisa la configuración de la base de datos.");
}
?>