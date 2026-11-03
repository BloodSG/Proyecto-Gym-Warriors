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

        <button type="button" class="btn-eliminar" data-id-membresia="${membresia.id_membresia}">
            Eliminar membresía
        </button>
    `
    ;

    const botonAsignar = tarjeta.querySelector(".btn-suscribir");

    botonAsignar.addEventListener("click", () => {
        const idMembresia = botonAsignar.dataset.idMembresia;

        window.location.href = `altamembresia.html?id_membresia=${encodeURIComponent(idMembresia)}`;
    })

    const botonEliminar = tarjeta.querySelector(".btn-eliminar");

    botonEliminar.addEventListener("click", async () => {
        const idMembresia = botonEliminar.dataset.idMembresia;
        const seguro = confirm("¿Estás seguro de eliminar esta membresía?");

        if (!seguro) {
            return;
        }

        try {
            const respuesta = await fetch("../PHP-Code/eliminar_membresia.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `id_membresia=${encodeURIComponent(idMembresia)}`
            });

            const resultado = await respuesta.json();
            alert(resultado.mensaje);

            if (resultado.status === "success") {
                cargarMembresias();
            }
        } catch (error) {
            alert("No se pudo conectar con el servidor.");
        }
    });

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