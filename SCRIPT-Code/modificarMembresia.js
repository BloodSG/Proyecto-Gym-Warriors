const campoId = document.getElementById("id_membresia");

// Verifica si el formulario de modificación existe en la página actual
if (campoId) {
    // Obtiene los parámetros enviados en la URL
    const parametros = new URLSearchParams(window.location.search);
    // Obtiene el ID de la membresía desde la URL
    const idMembresia = parametros.get("id_membresia");
    // Guarda el ID obtenido dentro del campo oculto del formulario
    campoId.value = idMembresia;

    // Realiza una petición al PHP encargado de buscar una membresía específica
    fetch("../PHP-Code/buscarMembresia.php?id=" + idMembresia)
        .then(respuesta => respuesta.json())
        .then(membresia => {
            // Coloca los datos en los campos correspondientes
            document.getElementById("nombre_tipo").value = membresia.nombre_tipo;
            document.getElementById("precio").value = membresia.precio;
            document.getElementById("duracion_dias").value = membresia.duracion_dias;
            document.getElementById("incluye_rutina").value = membresia.incluye_rutina === 1 ? 1 :0;
            document.getElementById("incluye_dieta").value = membresia.incluye_dieta === 1 ? 1 :0;
            document.getElementById("caracteristicas").value = membresia.caracteristicas;
            
            // Si realmente necesitas el correo, asegúrate de que esté dentro de este bloque
            // y que el elemento con id="correo" exista en tu HTML:
            // document.getElementById("correo").value = membresia.correo;
        })
        .catch(error => console.log("Error al buscar membresia:", error));
}
const formulario = document.getElementById("form-modificarMembresia");

formulario.addEventListener("submit", async (evento) => {
    evento.preventDefault();

    const datos = new FormData(formulario);

    const respuesta = await fetch("../PHP-Code/modificarMembresia.php", {
        method: "POST",
        body: datos
    });

    const resultado = await respuesta.json();
    alert(resultado.mensaje);
});