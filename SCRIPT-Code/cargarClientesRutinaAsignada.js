const selectorCliente = document.getElementById('client');
const selectorCoach = document.getElementById('coach');
const selectorRutina = document.getElementById('routine-name');
const mensaje = document.getElementById('form-message');

async function cargarOpciones(select, url, textoInicial, crearOpcion) {
    select.replaceChildren(new Option(textoInicial, ""));

    const respuesta = await fetch(url);
    if (!respuesta.ok) {
        throw new Error(`Error HTTP ${respuesta.status}`);
    }

    const registros = await respuesta.json();
    if (!Array.isArray(registros)) {
        throw new Error(registros.mensaje || "Respuesta inválida del servidor");
    }

    registros.forEach(registro => {
        const { id, nombre } = crearOpcion(registro);
        select.add(new Option(nombre, id));
    });
}

if (selectorCliente && selectorCoach) {
    Promise.all([
        cargarOpciones(
            selectorCliente,
            "../PHP-Code/mostrar_cliente.php",
            "Selecciona un cliente",
            cliente => ({
                id: cliente.id_usuario,
                nombre: [cliente.nombre, cliente.apellido_1, cliente.apellido_2]
                    .filter(Boolean)
                    .join(" ")
            })
        ),
        cargarOpciones(
            selectorCoach,
            "../PHP-Code/mostrar_coaches.php",
            "Selecciona un coach",
            coach => ({
                id: coach.id_empleado,
                nombre: coach.nombre
            })
        ),

        cargarOpciones(
            selectorRutina,
            "../PHP-Code/mostrarNombresRutina.php",
            "Selecciona una rutina",
            rutina => ({
                id: rutina,
                nombre: rutina
            })
        )
    ]).catch(error => {
        console.error("Error al cargar clientes o coaches:", error);

        if (mensaje) {
            mensaje.textContent =
                "No se pudieron cargar los clientes o coaches. Recarga la página e inténtalo de nuevo.";
        }
    });
}