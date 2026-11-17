const endpointRutinas = "../PHP-Code/rutinaSemanalCliente.php";

async function consultarEndpoint(comando, parametros = {}) {
    // Construye la petición al endpoint compartido de rutinas
    const query = new URLSearchParams({ action: "cargar", comando, ...parametros });
    const respuesta = await fetch(`${endpointRutinas}?${query.toString()}`);

    if (respuesta.status === 401) {
        alert("Debes iniciar sesión para consultar tu rutina.");
        window.location.href = "../HTML-Code/loginView.html";
        return { status: "error", data: [] };
    }

    if (!respuesta.ok) {
        throw new Error(`Error HTTP: ${respuesta.status}`);
    }

    return respuesta.json();
}

function formatearFecha(fecha) {
    if (!fecha) {
        return "Sin fecha";
    }

    return new Date(fecha).toLocaleDateString("es-MX");
}

function mostrarRutinas(rutinas) {
    // Pinta las rutinas asignadas en la primera tabla
    const cuerpo = document.querySelector(".consulta:first-of-type tbody");

    if (!rutinas.length) {
        cuerpo.innerHTML = '<tr><td colspan="3">No tienes rutinas asignadas.</td></tr>';
        return;
    }

    cuerpo.innerHTML = rutinas.map(rutina => `
        <tr>
            <td>${rutina.nombre_rutina}</td>
            <td>${formatearFecha(rutina.fecha_creacion)}</td>
            <td>${rutina.estatus}</td>
        </tr>
    `).join("");
}

function mostrarActividades(actividades) {
    // Pinta los ejercicios correspondientes al día seleccionado
    const cuerpo = document.querySelector(".consulta:nth-of-type(2) tbody");

    if (!actividades.length) {
        cuerpo.innerHTML = '<tr><td colspan="4">No hay actividades para este día.</td></tr>';
        return;
    }

    cuerpo.innerHTML = actividades.map(actividad => `
        <tr>
            <td style="text-align: center;"><input type="checkbox" value="${actividad.id_ejercicio}" ${Number(actividad.realizado) === 1 ? "checked" : ""} aria-label="Marcar ${actividad.nombre_ejercicio} como realizado"></td>
            <td>${actividad.dia_semana}</td>
            <td>${actividad.nombre_ejercicio}</td>
            <td>${actividad.peso_sugerido}</td>
        </tr>
    `).join("");
}


async function cargarRutinas() {
    // Carga las rutinas al abrir la vista
    try {
        const respuesta = await consultarEndpoint("rutinas");
        mostrarRutinas(respuesta.data || []);
    } catch (error) {
        console.error("No se pudieron cargar las rutinas:", error);
    }
}

async function cargarActividades(dia) {
    // Consulta nuevamente cuando el usuario cambia de día
    if (!dia) {
        document.querySelector(".consulta:nth-of-type(2) tbody").innerHTML = "";
        return;
    }

    try {
        const respuesta = await consultarEndpoint("actividades", { dia });
        mostrarActividades(respuesta.data || []);
    } catch (error) {
        console.error("No se pudieron cargar las actividades:", error);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const filtroDia = document.getElementById("filtro_dia");

    cargarRutinas();
    filtroDia.addEventListener("change", () => cargarActividades(filtroDia.value));
});
