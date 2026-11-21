<?php
// procesarNutricion.php
require_once __DIR__ . "/libreria.php";
$conexion = conectaDB();

function siguienteId(PDO $conexion, string $tabla, string $columna, string $prefijo): string {
    $inicioNumero = strlen($prefijo) + 1;
    $sql = "SELECT IFNULL(MAX(CAST(SUBSTRING($columna, $inicioNumero) AS UNSIGNED)), 0) + 1
            FROM $tabla
            WHERE $columna LIKE ?";
    $consulta = $conexion->prepare($sql);
    $consulta->execute([$prefijo . "%"]);
    return $prefijo . $consulta->fetchColumn();
}

function valorTexto(array $entrada, string $campo): string {
    $valor = $entrada[$campo] ?? "";
    return is_string($valor) || is_numeric($valor) ? trim((string)$valor) : "";
}

function listaTexto(array $entrada, string $campo): array {
    $valores = $entrada[$campo] ?? [];
    if (!is_array($valores)) return [];
    return array_values(array_filter($valores, "is_string"));
}

function obtenerDatosNutricion(): array {
    return [
        "nombre" => valorTexto($_POST, "nombre"),
        "correo" => valorTexto($_POST, "correo"), 
        "sexo" => valorTexto($_POST, "sexo"),
        "telefono" => valorTexto($_POST, "telefono"),
        "fechaNac" => valorTexto($_POST, "fechaNac"),
        "peso" => valorTexto($_POST, "peso"),
        "estatura" => valorTexto($_POST, "estatura"),
        "ocupacion" => valorTexto($_POST, "ocupacion"),
        "objetivos" => listaTexto($_POST, "objetivo"),
        "tiempoInvertir" => valorTexto($_POST, "tiempoInvertir"),
        "enfermedades" => listaTexto($_POST, "enfermedad"),
        "enfermedadOtra" => valorTexto($_POST, "otratext"),
        "numComidas" => valorTexto($_POST, "numComidas"),
        "desayuno" => valorTexto($_POST, "desayuno"),
        "agua" => valorTexto($_POST, "agua"), 
        "refresco" => valorTexto($_POST, "refresco"),
        "alcohol" => valorTexto($_POST, "alcohol"),
        "frecuenciaAlcohol" => valorTexto($_POST, "frecuenciaAlcohol"),
        "comidaRapida" => valorTexto($_POST, "comidaRapida"),
        "horasSueno" => valorTexto($_POST, "horasSueno"), 
        "nivelEstres" => valorTexto($_POST, "nivelEstres"), 
        "suplementos" => listaTexto($_POST, "suplementos"), 
        "cualesSuplementos" => valorTexto($_POST, "cualesSuplementos"), 
        "favoritos" => valorTexto($_POST, "alimentosFavoritos"), 
        "noGustan" => valorTexto($_POST, "alimentosDisgusto"), 
        "restringidos" => valorTexto($_POST, "alimentosRestriccion"), 
        "comidasDia" => valorTexto($_POST, "comidasDia"), 
        "preparaAlimentos" => valorTexto($_POST, "preparaAlimentos"),
        "comesFuera" => valorTexto($_POST, "comesFuera"),
        "compromiso" => valorTexto($_POST, "compromiso"),
        "aceptoConsentimiento" => valorTexto($_POST, "aceptoConsentimiento")
    ];
}

function validarNutricion(array $datos): array {
    $errores = [];
    $patronNombre = '/^[\p{L}\p{M}][\p{L}\p{M}\.\' \-]*$/u';

    if ($datos["nombre"] === "" || !preg_match($patronNombre, $datos["nombre"])) $errores[] = "El nombre es inválido. Solo usa letras y espacios.";
    if (!filter_var($datos["correo"], FILTER_VALIDATE_EMAIL)) $errores[] = "Ingresa un correo electrónico válido (esto nos ayuda a ligarte a tu cuenta).";
    if (!preg_match('/^[0-9]{10}$/', $datos["telefono"])) $errores[] = "El teléfono debe tener exactamente 10 números.";
    if (!in_array(strtolower($datos["sexo"]), ["femenino", "masculino"], true)) $errores[] = "Selecciona un sexo válido.";
    if (!is_numeric($datos["peso"]) || (float)$datos["peso"] < 20 || (float)$datos["peso"] > 300) $errores[] = "El peso debe ser un número real entre 20 y 300 kg.";
    if (!is_numeric($datos["estatura"]) || (float)$datos["estatura"] < 80 || (float)$datos["estatura"] > 250) $errores[] = "La estatura debe ser un número real en centímetros (ej. 170).";
    
    $fecha = DateTimeImmutable::createFromFormat("Y-m-d", $datos["fechaNac"]);
    if (!$fecha || $fecha > new DateTimeImmutable("today")) $errores[] = "La fecha de nacimiento no es válida.";

    if ($datos["numComidas"] !== "" && (!filter_var($datos["numComidas"], FILTER_VALIDATE_INT) || (int)$datos["numComidas"] < 1)) $errores[] = "El número de comidas debe ser un número entero.";
    if ($datos["agua"] !== "" && (!is_numeric($datos["agua"]) || (float)$datos["agua"] < 0)) $errores[] = "Los litros de agua deben ser un número válido.";
    if ($datos["horasSueno"] !== "" && (!filter_var($datos["horasSueno"], FILTER_VALIDATE_INT) || (int)$datos["horasSueno"] < 0 || (int)$datos["horasSueno"] > 24)) $errores[] = "Las horas de sueño deben ser un número entre 0 y 24.";
    
    if (!filter_var($datos["compromiso"], FILTER_VALIDATE_INT) || (int)$datos["compromiso"] < 1 || (int)$datos["compromiso"] > 10) $errores[] = "El nivel de compromiso debe ser un número del 1 al 10.";
    if ($datos["aceptoConsentimiento"] !== "on") $errores[] = "Debes aceptar el consentimiento para enviar el formulario.";

    return $errores;
}

function guardarEntrevistaNutricional(PDO $conexion, array $datos): string {
    $conexion->beginTransaction();
    try {
        $consulta = $conexion->prepare("SELECT id_usuario FROM usuariocliente WHERE correo = ?");
        $consulta->execute([$datos["correo"]]);
        $idUsuario = $consulta->fetchColumn();
        
        if (!$idUsuario) throw new DomainException("Ese correo no tiene una cuenta registrada en el sistema. Crea tu cuenta primero.");

        $idEntrevista = siguienteId($conexion, "entrevista", "id_entrevista", "E");
        $consulta = $conexion->prepare("INSERT INTO entrevista (id_entrevista, id_usuario, fecha_registro, tipo_plan, nivel_compromiso) VALUES (?, ?, NOW(), 'Nutricional', ?)");
        $consulta->execute([$idEntrevista, $idUsuario, (int)$datos["compromiso"]]);

        $preparaAlimentos = (strtolower($datos["preparaAlimentos"]) === 'si') ? 1 : 0;
        $comeFuera = (strtolower($datos["comesFuera"]) === 'si') ? 1 : 0;
        $objetivoPrincipal = implode(", ", $datos["objetivos"]);

        $idEntrevistaNutri = siguienteId($conexion, "entrevista_nutri_sup", "id_entrevista_nutricional", "EN");
        $sqlNutri = "INSERT INTO entrevista_nutri_sup 
                    (id_entrevista_nutricional, id_entrevista, objetivo_principal, comidas_al_dia, consumo_agua_litros, horas_sueño, 
                     nivel_estres, frecuencia_refresco, frecuencia_alcohol, frecuencia_comida_rapida, prepara_alimentos, come_fuera) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $consulta = $conexion->prepare($sqlNutri);
        $consulta->execute([
            $idEntrevistaNutri, $idEntrevista, substr($objetivoPrincipal, 0, 300), 
            (int)$datos["numComidas"] ?: null, (int)$datos["agua"] ?: null, (int)$datos["horasSueno"] ?: null,
            $datos["nivelEstres"] ?: null, $datos["refresco"] ?: null, 
            ($datos["alcohol"] === "alcoholSi" ? $datos["frecuenciaAlcohol"] : "Nunca"), 
            $datos["comidaRapida"] ?: null, $preparaAlimentos, $comeFuera
        ]);

        if (!empty($datos["enfermedades"])) {
            $idDetalleSalud = (int)$conexion->query("SELECT IFNULL(MAX(id_detalle_salud), 0) + 1 FROM detalle_salud_cliente")->fetchColumn();
            $consulta = $conexion->prepare("INSERT INTO detalle_salud_cliente (id_detalle_salud, id_entrevista_nutricional) VALUES (?, ?)");
            $consulta->execute([$idDetalleSalud, $idEntrevistaNutri]);

            $consultaEnfermedad = $conexion->prepare("INSERT INTO detalle_condiciones_salud (id_detalle_salud, descripcion_condicion, tipo_condicion) VALUES (?, ?, 'enfermedad')");
            foreach ($datos["enfermedades"] as $enfermedad) {
                if ($enfermedad === "ninguna") continue;
                $desc = ($enfermedad === "otra" && !empty($datos["enfermedadOtra"])) ? $datos["enfermedadOtra"] : $enfermedad;
                $consultaEnfermedad->execute([$idDetalleSalud, substr($desc, 0, 100)]);
            }
        }

        $categoriasAlimentos = [
            "favorito" => $datos["favoritos"],
            "no gusta" => $datos["noGustan"],
            "restringido" => $datos["restringidos"]
        ];

        $consultaAlimento = $conexion->prepare("INSERT INTO detalle_alimentos_cliente (id_entrevista_nutricional, nombre_alimento, categoria) VALUES (?, ?, ?)");
        foreach ($categoriasAlimentos as $cat => $texto) {
            $alimentos = array_filter(array_map("trim", explode(",", $texto)));
            foreach ($alimentos as $alimento) {
                if (!empty($alimento)) {
                    $consultaAlimento->execute([$idEntrevistaNutri, substr($alimento, 0, 50), $cat]);
                }
            }
        }

        if (!empty($datos["suplementos"])) {
            $consultaSuplemento = $conexion->prepare("INSERT INTO detalle_suplementos_actuales (id_suplemento_cliente, id_entrevista_nutricional, nombre_suplemento, cantidad_y_frecuencia) VALUES (?, ?, ?, ?)");
            foreach ($datos["suplementos"] as $sup) {
                if ($sup === "Ninguno") continue;
                $idSup = siguienteId($conexion, "detalle_suplementos_actuales", "id_suplemento_cliente", "SU");
                // Modificado: El límite del detalle del suplemento pasa de 30 a 100 caracteres.
                $consultaSuplemento->execute([$idSup, $idEntrevistaNutri, substr($sup, 0, 30), substr($datos["cualesSuplementos"], 0, 100) ?: "No especificada"]);
            }
        }

        $conexion->commit();
        return $idEntrevista;
    } catch (Throwable $error) {
        if ($conexion->inTransaction()) $conexion->rollBack();
        throw $error;
    }
}

function procesarNutricion(PDO $conexion): void {
    header('Content-Type: application/json');

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Por favor, envía el formulario correctamente.']);
        exit;
    }
    
    $datos = obtenerDatosNutricion();
    $errores = validarNutricion($datos);
    
    if ($errores) {
        http_response_code(422);
        $mensaje = "Corrija los siguientes errores:<br><ul style='list-style: none; padding: 0; margin-top: 10px;'>";
        foreach ($errores as $error) {
            $mensaje .= "<li>" . htmlspecialchars($error, ENT_QUOTES, "UTF-8") . "</li>";
        }
        $mensaje .= "</ul>";
        echo json_encode(['status' => 'error', 'message' => $mensaje]);
        return;
    }
    
    try {
        $folio = guardarEntrevistaNutricional($conexion, $datos);
        $mensaje = "¡Plan Nutricional en camino!<br>Tu entrevista ha sido guardada exitosamente.<br>Folio de registro: <strong>" . htmlspecialchars($folio, ENT_QUOTES, "UTF-8") . "</strong>";
        echo json_encode(['status' => 'success', 'message' => $mensaje]);
    } catch (DomainException $error) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8")]);
    } catch (Throwable $error) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Error técnico: " . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8")]);
    }
}

procesarNutricion($conexion);
?>