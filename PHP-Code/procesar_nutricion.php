<?php
session_start();
require_once __DIR__ . "/libreria.php";

function textoNutricion(string $campo): string
{
    $valor = $_POST[$campo] ?? "";
    return is_string($valor) || is_numeric($valor) ? trim((string)$valor) : "";
}

function listaNutricion(string $campo): array
{
    $valores = $_POST[$campo] ?? [];
    if (!is_array($valores)) return [];
    return array_values(array_filter($valores, "is_string"));
}

function siguienteIdNutricion(PDO $conexion, string $tabla, string $columna, string $prefijo): string
{
    // Los identificadores de tabla y columna son constantes del programa.
    $posicion = strlen($prefijo) + 1;
    $consulta = $conexion->prepare(
        "SELECT ISNULL(MAX(TRY_CONVERT(INT, SUBSTRING($columna, $posicion, 10))), 0) + 1
         FROM $tabla WITH (UPDLOCK, HOLDLOCK) WHERE $columna LIKE ?"
    );
    $consulta->execute([$prefijo . "%"]);
    return $prefijo . $consulta->fetchColumn();
}

function validarNutricion(array $datos): array
{
    $errores = [];
    $longitud = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
    $patronNombre = '/^[\p{L}\p{M}]+(?:[ \x{27}\x{2019}.\x{2D}][\p{L}\p{M}]+)*$/u';

    if ($datos["nombre"] === "" || $longitud($datos["nombre"]) > 30 || !preg_match($patronNombre, $datos["nombre"])) $errores[] = "El nombre debe contener solo letras y tener máximo 30 caracteres.";
    if ($datos["telefono"] !== "" && !preg_match('/^[0-9]{10}$/', $datos["telefono"])) $errores[] = "El teléfono debe tener exactamente 10 dígitos.";
    if (!in_array($datos["sexo"], ["femenino", "masculino"], true)) $errores[] = "Selecciona un sexo válido.";

    $fecha = DateTimeImmutable::createFromFormat("!Y-m-d", $datos["fecha"]);
    $erroresFecha = DateTimeImmutable::getLastErrors();
    if (!$fecha || ($erroresFecha !== false && ($erroresFecha["warning_count"] || $erroresFecha["error_count"])) || $fecha->format("Y-m-d") !== $datos["fecha"] || $fecha > new DateTimeImmutable("today")) {
        $errores[] = "La fecha de nacimiento no es válida.";
    }

    foreach (["peso" => [20, 999.99], "estatura" => [80, 250]] as $campo => [$minimo, $maximo]) {
        if (!is_numeric($datos[$campo]) || (float)$datos[$campo] < $minimo || (float)$datos[$campo] > $maximo) $errores[] = "Revisa el valor de " . $campo . ".";
    }
    if ($longitud($datos["ocupacion"]) > 30) $errores[] = "La ocupación admite máximo 30 caracteres.";

    $objetivos = ["Perder grasa corporal", "Aumentar masa muscular", "Tonificar", "Mejorar rendimiento deportivo", "Ganar fuerza", "Mejorar salud", "Otro"];
    if (!$datos["objetivos"] || array_diff($datos["objetivos"], $objetivos)) $errores[] = "Selecciona al menos un objetivo válido.";
    if (in_array("Otro", $datos["objetivos"], true) && $datos["objetivo_otro"] === "") $errores[] = "Especifica el otro objetivo.";
    if ($longitud($datos["objetivo_otro"]) > 140) $errores[] = "El objetivo personalizado admite máximo 140 caracteres.";
    $objetivosTexto = $datos["objetivos"];
    if (in_array("Otro", $objetivosTexto, true)) $objetivosTexto[array_search("Otro", $objetivosTexto, true)] = $datos["objetivo_otro"];
    if ($longitud(implode(", ", $objetivosTexto)) > 150) $errores[] = "La combinación de objetivos supera 150 caracteres.";
    foreach (["comidas" => [1, 10], "agua" => [0, 30], "horas_sueno" => [0, 24]] as $campo => [$minimo, $maximo]) {
        if (!filter_var($datos[$campo], FILTER_VALIDATE_INT) || (int)$datos[$campo] < $minimo || (int)$datos[$campo] > $maximo) $errores[] = "Revisa el valor de " . $campo . ".";
    }

    $listasValidas = [
        "enfermedades" => ["diabetes", "hipertension", "hipotiroidismo", "colesterol", "renales", "ninguna", "otra"],
        "suplementos" => ["Proteína", "Creatina", "Pre-entreno", "Multivitamínico", "Omega 3", "Colágeno", "Magnesio", "Ninguno"],
        "frecuencia_refresco" => ["diario", "ocasional", "nunca"],
        "frecuencia_comida" => ["crNunca", "cr1", "cr23", "crMas"],
        "prepara" => ["si", "no"],
        "fuera" => ["si", "no"],
    ];
    foreach ($listasValidas as $campo => $valores) {
        if (is_array($datos[$campo]) ? array_diff($datos[$campo], $valores) : !in_array($datos[$campo], $valores, true)) $errores[] = "Hay una opción inválida en " . $campo . ".";
    }
    if (!$datos["enfermedades"]) $errores[] = "Indica si tienes alguna condición de salud o selecciona 'ninguna'.";
    if (!$datos["suplementos"]) $errores[] = "Indica si consumes suplementos o selecciona 'ninguno'.";
    if (!in_array($datos["alcohol"], ["si", "no"], true)) $errores[] = "Selecciona una opción válida para consumo de alcohol.";
    if ($datos["alcohol"] === "si" && $datos["frecuencia_alcohol"] === "") $errores[] = "Indica la frecuencia de consumo de alcohol.";
    if ($longitud($datos["frecuencia_alcohol"]) > 15) $errores[] = "La frecuencia de alcohol admite máximo 15 caracteres.";
    if (!in_array($datos["estres"], ["bajo", "medio", "alto"], true)) $errores[] = "Selecciona un nivel de estrés válido.";
    if (!in_array($datos["calidad_descanso"], ["buena", "regular", "mala"], true)) $errores[] = "Selecciona una calidad de descanso válida.";
    if (!filter_var($datos["compromiso"], FILTER_VALIDATE_INT) || (int)$datos["compromiso"] < 1 || (int)$datos["compromiso"] > 10) $errores[] = "El compromiso debe estar entre 1 y 10.";
    if (in_array("ninguna", $datos["enfermedades"], true) && count($datos["enfermedades"]) > 1) $errores[] = "No combines 'ninguna' con otra condición de salud.";
    if (in_array("otra", $datos["enfermedades"], true) && $datos["enfermedad_otra"] === "") $errores[] = "Describe la otra condición de salud.";
    if ($longitud($datos["enfermedad_otra"]) > 100) $errores[] = "La condición de salud admite máximo 100 caracteres.";
    if (in_array("Ninguno", $datos["suplementos"], true) && count($datos["suplementos"]) > 1) $errores[] = "No combines 'ninguno' con otros suplementos.";
    if ($datos["consentimiento"] !== "si") $errores[] = "Debes aceptar el consentimiento para continuar.";
    if ($longitud($datos["suplemento_frecuencia"]) > 30) $errores[] = "La frecuencia de suplementos admite máximo 30 caracteres.";

    return $errores;
}

function mostrarRespuestaNutricion(string $mensaje): void
{
    echo "<h2>" . htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") . "</h2>";
    echo '<p><a href="../HTML-Code/nutricionInterview.html">Volver al formulario</a></p>';
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Envía el formulario para continuar.");
}
if (empty($_SESSION["logueado"]) || empty($_SESSION["id_usuario"])) {
    http_response_code(401);
    exit("Inicia sesión antes de enviar la entrevista nutricional.");
}

$datos = [
    "nombre" => textoNutricion("nombre"), "telefono" => textoNutricion("telefono"),
    "fecha" => textoNutricion("fecha"), "sexo" => textoNutricion("sexo"),
    "peso" => textoNutricion("peso"), "estatura" => textoNutricion("estatura"),
    "ocupacion" => textoNutricion("ocupacion"), "objetivos" => listaNutricion("objetivos"),
    "objetivo_otro" => textoNutricion("objetivo_otro"),
    "comidas" => textoNutricion("comidas"), "agua" => textoNutricion("agua"),
    "horas_sueno" => textoNutricion("horas_sueno"), "estres" => textoNutricion("estres"),
    "calidad_descanso" => textoNutricion("calidad_descanso"), "frecuencia_refresco" => textoNutricion("refresco"),
    "alcohol" => textoNutricion("alcohol"), "frecuencia_alcohol" => textoNutricion("frecuenciaAlcohol"),
    "frecuencia_comida" => textoNutricion("comidaRapida"), "compromiso" => textoNutricion("compromiso"),
    "prepara" => textoNutricion("preparaAlimentos"), "fuera" => textoNutricion("comesFuera"),
    "enfermedades" => listaNutricion("enfermedades"), "enfermedad_otra" => textoNutricion("enfermedad_otra"),
    "suplementos" => listaNutricion("suplementos"),
    "suplemento_frecuencia" => textoNutricion("suplemento_frecuencia"),
    "favoritos" => textoNutricion("alimentos_favoritos"), "no_gusta" => textoNutricion("alimentos_no_gustan"),
    "restringidos" => textoNutricion("alimentos_restringidos"), "consentimiento" => textoNutricion("aceptoConsentimiento"),
];

$errores = validarNutricion($datos);
if ($errores) {
    http_response_code(422);
    mostrarRespuestaNutricion(implode(" ", $errores));
    exit;
}

try {
    $conexion = conectaDB();
    $conexion->beginTransaction();

    // Verifica el perfil antes de actualizarlo; rowCount puede ser cero si los datos no cambiaron.
    $consulta = $conexion->prepare("SELECT 1 FROM Detalle_Cliente WHERE id_usuario = ?");
    $consulta->execute([$_SESSION["id_usuario"]]);
    if (!$consulta->fetchColumn()) throw new RuntimeException("Completa primero la entrevista inicial para crear tu perfil.");

    // Actualiza los datos físicos del perfil existente, sin cambiar su aceptación del reglamento.
    $consulta = $conexion->prepare("UPDATE usuariocliente SET nombre = ?, telefono = ? WHERE id_usuario = ?");
    $consulta->execute([$datos["nombre"], $datos["telefono"] ?: null, $_SESSION["id_usuario"]]);
    $fechaNacimiento = new DateTimeImmutable($datos["fecha"]);
    $edad = (new DateTimeImmutable("today"))->diff($fechaNacimiento)->y;
    $consulta = $conexion->prepare(
        "UPDATE Detalle_Cliente SET fecha_nacimiento = ?, edad = ?, sexo = ?, estatura = ?, peso_actual = ?, ocupacion = ? WHERE id_usuario = ?"
    );
    $consulta->execute([$datos["fecha"], $edad, $datos["sexo"], $datos["estatura"], $datos["peso"], $datos["ocupacion"] ?: null, $_SESSION["id_usuario"]]);
    $idEntrevista = siguienteIdNutricion($conexion, "Entrevista", "id_entrevista", "E");
    $consulta = $conexion->prepare("INSERT INTO Entrevista (id_entrevista, id_usuario, fecha_registro, tipo_plan, nivel_compromiso) VALUES (?, ?, GETDATE(), ?, ?)");
    $consulta->execute([$idEntrevista, $_SESSION["id_usuario"], "Nutrición", (int)$datos["compromiso"]]);

    $idNutricion = siguienteIdNutricion($conexion, "Entrevista_Nutri_Sup", "id_entrevista_nutricional", "N");
    $objetivos = $datos["objetivos"];
    if (in_array("Otro", $objetivos, true)) $objetivos[array_search("Otro", $objetivos, true)] = $datos["objetivo_otro"];
    $objetivo = implode(", ", $objetivos);
    $alcohol = $datos["alcohol"] === "si" ? $datos["frecuencia_alcohol"] : "nunca";
    $frecuenciaComida = ["crNunca" => "nunca", "cr1" => "1 vez/semana", "cr23" => "2-3 veces", "crMas" => "mas de 3 veces"][$datos["frecuencia_comida"]];
    $consulta = $conexion->prepare(
        "INSERT INTO Entrevista_Nutri_Sup
            (id_entrevista_nutricional, id_entrevista, objetivo_principal, comidas_al_dia,
             consumo_agua_litros, horas_sueño, calidad_descanso, nivel_estres,
             frecuencia_refresco, frecuencia_alcohol, frecuencia_comida_rapida, prepara_alimentos, come_fuera)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $consulta->execute([
        $idNutricion, $idEntrevista, $objetivo, (int)$datos["comidas"], (int)$datos["agua"],
        (int)$datos["horas_sueno"], $datos["calidad_descanso"], $datos["estres"],
        $datos["frecuencia_refresco"], $alcohol, $frecuenciaComida,
        $datos["prepara"] === "si" ? 1 : 0, $datos["fuera"] === "si" ? 1 : 0,
    ]);

    $idDetalleSalud = (int)$conexion->query(
        "SELECT ISNULL(MAX(id_detalle_salud), 0) + 1 FROM Detalle_Salud_Cliente WITH (UPDLOCK, HOLDLOCK)"
    )->fetchColumn();
    $consulta = $conexion->prepare("INSERT INTO Detalle_Salud_Cliente (id_detalle_salud, id_entrevista_nutricional) VALUES (?, ?)");
    $consulta->execute([$idDetalleSalud, $idNutricion]);
    foreach ($datos["enfermedades"] as $enfermedad) {
        if ($enfermedad === "ninguna") continue;
        $descripcion = $enfermedad === "otra" ? $datos["enfermedad_otra"] : $enfermedad;
        $consulta = $conexion->prepare(
            "INSERT INTO Detalle_Condiciones_Salud (id_detalle_salud, descripcion_condicion, tipo_condicion) VALUES (?, ?, ?)"
        );
        $consulta->execute([$idDetalleSalud, $descripcion, "enfermedad"]);
    }

    // Guarda alimentos por categoría usando los valores permitidos por la restricción CHECK.
    foreach (["favoritos" => "favorito", "no_gusta" => "no gusta", "restringidos" => "restringido"] as $campo => $categoria) {
        $alimentos = array_filter(array_map("trim", explode(",", $datos[$campo])));
        foreach ($alimentos as $alimento) {
            if (strlen($alimento) > 50) throw new RuntimeException("Cada alimento admite máximo 50 caracteres.");
            $consulta = $conexion->prepare(
                "INSERT INTO Detalle_Alimentos_Cliente (id_entrevista_nutricional, nombre_alimento, categoria) VALUES (?, ?, ?)"
            );
            $consulta->execute([$idNutricion, $alimento, $categoria]);
        }
    }

    foreach ($datos["suplementos"] as $suplemento) {
        if ($suplemento === "Ninguno") continue;
        $idSuplemento = siguienteIdNutricion($conexion, "Detalle_Suplementos_Actuales", "id_suplemento_cliente", "SC");
        $consulta = $conexion->prepare(
            "INSERT INTO Detalle_Suplementos_Actuales
                (id_suplemento_cliente, id_entrevista_nutricional, nombre_suplemento, cantidad_y_frecuencia)
             VALUES (?, ?, ?, ?)"
        );
        $consulta->execute([$idSuplemento, $idNutricion, $suplemento, $datos["suplemento_frecuencia"] ?: "No especificada"]);
    }

    $conexion->commit();
    mostrarRespuestaNutricion("Entrevista nutricional guardada correctamente.");
} catch (Throwable $error) {
    if (isset($conexion) && $conexion->inTransaction()) $conexion->rollBack();
    error_log("Error al guardar entrevista nutricional: " . $error->getMessage());
    http_response_code(500);
    mostrarRespuestaNutricion($error instanceof RuntimeException ? $error->getMessage() : "No fue posible guardar la entrevista. Revisa la configuración de la base de datos.");
}
?>