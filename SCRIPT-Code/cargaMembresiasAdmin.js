import { cargaListaMembresias } from "./api.js";

const contenedor = document.getElementById("grid_membresias");

function crearTarjeta(membresia) {
    const tarjeta = document.createElement("article");
    tarjeta.className = "tarjeta";

    const rutinaIncluida = Number(membresia.incluye_rutina) === 1;
    const dietaIncluida = Number(membresia.incluye_dieta) === 1;

    tarjeta.innerHTML = `
        <h2>${membresia.nombre_tipo}</h2>

        <p class="precio">
            $${membresia.precio}
            <span>${membresia.duracion_dias} días</span>
        </p>

        <ul class="beneficios">
            <li class="${rutinaIncluida ? "si" : "no"}">
                Rutina personalizada
            </li>
            <li class="${dietaIncluida ? "si" : "no"}">
                Dieta y suplementos
            </li>
        </ul>

        <p class="caracteristicas">
            ${membresia.caracteristicas || "Sin características adicionales"}
        </p>

        <button type="button" class="btn-suscribir" data-id-membresia="${membresia.id_membresia}">
            Asignar membresía
        </button>
    `
    ;

    const botonAsignar = tarjeta.querySelector(".btn-suscribir");

    botonAsignar.addEventListener("click", () => {
        const idMembresia = botonAsignar.dataset.idMembresia;

        window.location.href = `altamembresia.html?id_membresia=${encodeURIComponent(idMembresia)}`;
    })

    return tarjeta;
}

async function cargarMembresias() {
    contenedor.innerHTML = "<p>Cargando membresías...</p>";

    const respuesta = await cargaListaMembresias();

    if (respuesta.status !== "success") {
        contenedor.innerHTML = `<p class="vacio">${respuesta.mensaje}</p>`;
        return;
    }

    if (respuesta.data.length === 0) {
        contenedor.innerHTML =
            '<p class="vacio">Aún no hay membresías registradas.</p>';
        return;
    }

    contenedor.innerHTML = "";

    respuesta.data.forEach(membresia => {
        contenedor.appendChild(crearTarjeta(membresia));
    });
}

cargarMembresias();