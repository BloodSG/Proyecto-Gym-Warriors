
const tarjeta = document.querySelector(".membresia-card");

function mostrarFecha(valor) {
    const fecha = valor.split(/[ T]/)[0];
    const [anio, mes, dia] = fecha.split("-").map(Number);

    return new Intl.DateTimeFormat("es-MX").format(
        new Date(anio, mes - 1, dia)
    );
}

async function cargarMembresia() {
    const mensaje = document.querySelector("#msg-status p");

    try {
        const respuesta = await fetch("../PHP-Code/miMembresia.php", {
            credentials: "same-origin"
        });
        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.success) {
            throw new Error(resultado.mensaje || "No se pudo consultar la membresía.");
        }

        const membresia = resultado.data;

        if (!membresia) {
            document.querySelector("#nombrePlanActual").textContent = "Sin membresía";
            document.querySelector("#badgeEstado").textContent = "NO REGISTRADA";
            mensaje.textContent = "No encontramos una membresía asociada a tu cuenta.";
            document.querySelector("#fechaActivacion").textContent = "—";
            document.querySelector("#fechaVencimiento").textContent = "—";
            document.querySelector("#diasRestantes").textContent = "—";
            document.querySelector("#montoCobro").textContent = "—";
            return;
        }

        const dias = Number(membresia.dias_restantes);
        const activa = membresia.estado.toLowerCase() === "activo";
        let claseAlerta;
        let textoEstado;

        if (!activa || dias < 0) {
            claseAlerta = "alerta-roja";
            textoEstado = activa ? "VENCIDA" : "INACTIVA";
            mensaje.textContent = "Tu membresía está vencida o inactiva. Consulta en recepción para renovarla.";
        } else if (dias <= 7) {
            claseAlerta = "alerta-amarilla";
            textoEstado = "ACTIVA";
            mensaje.textContent = `Tu membresía está próxima a vencer (vence en ${dias} días).`;
        } else {
            claseAlerta = "alerta-verde";
            textoEstado = "ACTIVA";
            mensaje.textContent = "Tu membresía está activa y al corriente.";
        }

        tarjeta.classList.remove("alerta-verde", "alerta-amarilla", "alerta-roja");
        tarjeta.classList.add(claseAlerta);

        document.querySelector("#nombrePlanActual").textContent = membresia.nombre_tipo;
        document.querySelector("#badgeEstado").textContent = textoEstado;
        document.querySelector("#badgeEstado").classList.remove("badge-activo", "badge-inactivo");
        document.querySelector("#badgeEstado").classList.add(activa && dias >= 0 ? "badge-activo" : "badge-inactivo");
        const ocultarEstadoInactivo = !activa && dias >= 0;
        document.querySelector("#badgeEstado").style.display = ocultarEstadoInactivo ? "none" : "";
        document.querySelector("#msg-status").style.display = ocultarEstadoInactivo ? "none" : "";

        document.querySelector("#fechaActivacion").textContent = mostrarFecha(membresia.fecha_inicio);
        document.querySelector("#fechaVencimiento").textContent = mostrarFecha(membresia.fecha_vencimiento);
        document.querySelector("#diasRestantes").textContent =
            dias < 0 ? `Vencida hace ${Math.abs(dias)} días` : `${dias} días`;

        document.querySelector("#montoCobro").textContent =
            `${new Intl.NumberFormat("es-MX", {
                style: "currency",
                currency: "MXN"
            }).format(Number(membresia.precio))} MXN`;
    } catch (error) {
        mensaje.textContent = error.message || "Ocurrió un error al cargar tu membresía.";
    }
}

cargarMembresia();