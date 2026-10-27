<?php
// procesarNutricion.php
require_once __DIR__ . "/libreria.php";
$conexion = conectaDB();

// Función reutilizada de tu sistema para generar IDs alfanuméricos
function siguienteId(PDO $conexion, string $tabla, string $columna, string $prefijo): string {
    $inicioNumero = strlen($prefijo) + 1;
    $sql = "SELECT ISNULL(MAX(TRY_CAST(SUBSTRING($columna, $inicioNumero, 10) AS INT)), 0) + 1
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
        // Datos personales
        "nombre" => valorTexto($_POST, "nombre"),
        "correo" => valorTexto($_POST, "correo"), // Asegúrate de pedir el correo en tu HTML
        "sexo" => valorTexto($_POST, "sexo"),
        "telefono" => valorTexto($_POST, "telefono"),
        "fechaNac" => valorTexto($_POST, "fechaNac"),
        "peso" => valorTexto($_POST, "peso"),
        "estatura" => valorTexto($_POST, "estatura"),
        "ocupacion" => valorTexto($_POST, "ocupacion"),
        
        // Objetivos
        "objetivos" => listaTexto($_POST, "objetivo"),
        "tiempoInvertir" => valorTexto($_POST, "tiempoInvertir"), // Asegúrate de poner name="tiempoInvertir" en HTML
        
        // Salud
        "enfermedades" => listaTexto($_POST, "enfermedad"),
        "enfermedadOtra" => valorTexto($_POST, "otratext"),
        
        // Alimentación
        "numComidas" => valorTexto($_POST, "numComidas"), // name="numComidas"
        "desayuno" => valorTexto($_POST, "desayuno"),
        "agua" => valorTexto($_POST, "agua"), // name="agua"
        "refresco" => valorTexto($_POST, "refresco"),
        "alcohol" => valorTexto($_POST, "alcohol"),
        "frecuenciaAlcohol" => valorTexto($_POST, "frecuenciaAlcohol"),
        "comidaRapida" => valorTexto($_POST, "comidaRapida"),
        
        // Estilo de vida
        "horasSueno" => valorTexto($_POST, "horasSueno"), // name="horasSueno"
        "nivelEstres" => valorTexto($_POST, "nivelEstres"), // name="nivelEstres"
        
        // Suplementación
        "suplementos" => listaTexto($_POST, "suplementos"), // name="suplementos[]" en los checkbox
        "cualesSuplementos" => valorTexto($_POST, "cualesSuplementos"), // name="cualesSuplementos"
        
        // Preferencias y Disponibilidad
        "favoritos" => valorTexto($_POST, "alimentosFavoritos"), // name="alimentosFavoritos"
        "noGustan" => valorTexto($_POST, "alimentosDisgusto"), // name="alimentosDisgusto"
        "restringidos" => valorTexto($_POST, "alimentosRestriccion"), // name="alimentosRestriccion"
        "comidasDia" => valorTexto($_POST, "comidasDia"), // name="comidasDia"
        "preparaAlimentos" => valorTexto($_POST, "preparaAlimentos"),
        "comesFuera" => valorTexto($_POST, "comesFuera"),
        
        // Compromiso
        "compromiso" => valorTexto($_POST, "compromiso"),
        "aceptoConsentimiento" => valorTexto($_POST, "aceptoConsentimiento")
    ];
}

function validarNutricion(array $datos): array {
    $errores = [];
    $patronNombre = '/^[\p{L}\p{M}]+(?:[ \x{27}\x{2019}.\x{2D}][\p{L}\p{M}]+)*$/u';

    // Validaciones estrictas Letras/Números
    if ($datos["nombre"] === "" || !preg_match($patronNombre, $datos["nombre"])) $errores[] = "El nombre es inválido. Solo usa letras y espacios.";
    if (!filter_var($datos["correo"], FILTER_VALIDATE_EMAIL)) $errores[] = "Ingresa un correo electrónico válido (esto nos ayuda a ligarte a tu cuenta).";
    if (!preg_match('/^[0-9]{10}$/', $datos["telefono"])) $errores[] = "El teléfono debe tener exactamente 10 números.";
    if (!in_array(strtolower($datos["sexo"]), ["femenino", "masculino"], true)) $errores[] = "Selecciona un sexo válido.";
    
    // Validación de Peso y Estatura (Solo números coherentes)
    if (!is_numeric($datos["peso"]) || (float)$datos["peso"] < 20 || (float)$datos["peso"] > 300) $errores[] = "El peso debe ser un número real entre 20 y 300 kg.";
    if (!is_numeric($datos["estatura"]) || (float)$datos["estatura"] < 80 || (float)$datos["estatura"] > 250) $errores[] = "La estatura debe ser un número real en centímetros (ej. 170).";
    
    // Fecha de nacimiento
    $fecha = DateTimeImmutable::createFromFormat("Y-m-d", $datos["fechaNac"]);
    if (!$fecha || $fecha > new DateTimeImmutable("today")) $errores[] = "La fecha de nacimiento no es válida.";

    // Hábitos y alimentación (Solo números)
    if ($datos["numComidas"] !== "" && (!filter_var($datos["numComidas"], FILTER_VALIDATE_INT) || (int)$datos["numComidas"] < 1)) $errores[] = "El número de comidas debe ser un número entero.";
    if ($datos["agua"] !== "" && (!is_numeric($datos["agua"]) || (float)$datos["agua"] < 0)) $errores[] = "Los litros de agua deben ser un número válido.";
    if ($datos["horasSueno"] !== "" && (!filter_var($datos["horasSueno"], FILTER_VALIDATE_INT) || (int)$datos["horasSueno"] < 0 || (int)$datos["horasSueno"] > 24)) $errores[] = "Las horas de sueño deben ser un número entre 0 y 24.";
    
    // Compromiso
    if (!filter_var($datos["compromiso"], FILTER_VALIDATE_INT) || (int)$datos["compromiso"] < 1 || (int)$datos["compromiso"] > 10) $errores[] = "El nivel de compromiso debe ser un número del 1 al 10.";
    if ($datos["aceptoConsentimiento"] !== "on") $errores[] = "Debes aceptar el consentimiento para enviar el formulario.";

    return $errores;
}

function guardarEntrevistaNutricional(PDO $conexion, array $datos): string {
    $conexion->beginTransaction();
    try {
        // 1. Verificar si el usuario existe (basado en correo como en la rutina)
        $consulta = $conexion->prepare("SELECT id_usuario FROM usuariocliente WHERE correo = ?");
        $consulta->execute([$datos["correo"]]);
        $idUsuario = $consulta->fetchColumn();
        
        if (!$idUsuario) throw new DomainException("Ese correo no tiene una cuenta registrada en el sistema. Crea tu cuenta primero.");

        // 2. Insertar Entrevista General
        $idEntrevista = siguienteId($conexion, "Entrevista", "id_entrevista", "E");
        $consulta = $conexion->prepare("INSERT INTO Entrevista (id_entrevista, id_usuario, fecha_registro, tipo_plan, nivel_compromiso) VALUES (?, ?, GETDATE(), 'Nutricional', ?)");
        $consulta->execute([$idEntrevista, $idUsuario, (int)$datos["compromiso"]]);

        // 3. Procesar datos binarios
        $preparaAlimentos = (strtolower($datos["preparaAlimentos"]) === 'si') ? 1 : 0;
        $comeFuera = (strtolower($datos["comesFuera"]) === 'si') ? 1 : 0;
        $objetivoPrincipal = implode(", ", $datos["objetivos"]);

        // 4. Insertar Entrevista Nutricional
        $idEntrevistaNutri = siguienteId($conexion, "Entrevista_Nutri_Sup", "id_entrevista_nutricional", "EN");
        $sqlNutri = "INSERT INTO Entrevista_Nutri_Sup 
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

        // 5. Insertar Condiciones de Salud
        if (!empty($datos["enfermedades"])) {
            // Generar nuevo ID para Detalle_Salud_Cliente (es un INT, no alfanumérico en tu tabla)
            $idDetalleSalud = (int)$conexion->query("SELECT ISNULL(MAX(id_detalle_salud), 0) + 1 FROM Detalle_Salud_Cliente")->fetchColumn();
            $consulta = $conexion->prepare("INSERT INTO Detalle_Salud_Cliente (id_detalle_salud, id_entrevista_nutricional) VALUES (?, ?)");
            $consulta->execute([$idDetalleSalud, $idEntrevistaNutri]);

            $consultaEnfermedad = $conexion->prepare("INSERT INTO Detalle_Condiciones_Salud (id_detalle_salud, descripcion_condicion, tipo_condicion) VALUES (?, ?, 'enfermedad')");
            foreach ($datos["enfermedades"] as $enfermedad) {
                if ($enfermedad === "ninguna") continue;
                $desc = ($enfermedad === "otra" && !empty($datos["enfermedadOtra"])) ? $datos["enfermedadOtra"] : $enfermedad;
                $consultaEnfermedad->execute([$idDetalleSalud, substr($desc, 0, 100)]);
            }
        }

        // 6. Preferencias Alimenticias (Separar por comas e insertar)
        $categoriasAlimentos = [
            "favorito" => $datos["favoritos"],
            "no gusta" => $datos["noGustan"],
            "restringido" => $datos["restringidos"]
        ];

        $consultaAlimento = $conexion->prepare("INSERT INTO Detalle_Alimentos_Cliente (id_entrevista_nutricional, nombre_alimento, categoria) VALUES (?, ?, ?)");
        foreach ($categoriasAlimentos as $cat => $texto) {
            $alimentos = array_filter(array_map("trim", explode(",", $texto)));
            foreach ($alimentos as $alimento) {
                if (!empty($alimento)) {
                    $consultaAlimento->execute([$idEntrevistaNutri, substr($alimento, 0, 50), $cat]);
                }
            }
        }

        // 7. Suplementación Actual
        if (!empty($datos["suplementos"])) {
            $consultaSuplemento = $conexion->prepare("INSERT INTO Detalle_Suplementos_Actuales (id_suplemento_cliente, id_entrevista_nutricional, nombre_suplemento, cantidad_y_frecuencia) VALUES (?, ?, ?, ?)");
            foreach ($datos["suplementos"] as $sup) {
                if ($sup === "Ninguno") continue;
                $idSup = siguienteId($conexion, "Detalle_Suplementos_Actuales", "id_suplemento_cliente", "SU");
                $consultaSuplemento->execute([$idSup, $idEntrevistaNutri, substr($sup, 0, 30), substr($datos["cualesSuplementos"], 0, 30) ?: "No especificada"]);
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
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        exit("Por favor, envía el formulario correctamente.");
    }
    
    $datos = obtenerDatosNutricion();
    $errores = validarNutricion($datos);
    
    if ($errores) {
        http_response_code(422);
        echo "<h2>Corrija los siguientes errores:</h2><ul>";
        foreach ($errores as $error) echo "<li>" . htmlspecialchars($error, ENT_QUOTES, "UTF-8") . "</li>";
        echo '</ul><p><a href="javascript:history.back()">Volver al formulario</a></p>';
        return;
    }
    
    try {
        $folio = guardarEntrevistaNutricional($conexion, $datos);
        echo "<h2>¡Plan Nutricional en camino!</h2>";
        echo "<p>Tu entrevista ha sido guardada exitosamente. Folio de registro: <strong>" . htmlspecialchars($folio, ENT_QUOTES, "UTF-8") . "</strong></p>";
        echo '<p><a href="../HTML-Code/nutricionInterview.html">Volver al inicio</a></p>';
    } catch (DomainException $error) {
        http_response_code(422);
        echo "<h2>Error de validación</h2><p>" . htmlspecialchars($error->getMessage(), ENT_QUOTES, "UTF-8") . "</p>";
        echo '<p><a href="javascript:history.back()">Volver al formulario</a></p>';
    } catch (Throwable $error) {
    http_response_code(500);
    echo "<h2>Error técnico:</h2><p>" . $error->getMessage() . "</p>";
}
}

// Ejecutar el proceso
procesarNutricion($conexion);
?>