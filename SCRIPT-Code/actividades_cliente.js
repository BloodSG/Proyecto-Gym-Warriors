const URL_PHP = "../PHP-Code/actividades_cliente.php";
 
const tablas = document.querySelectorAll("table.tabla_membresias");
const cuerpoActividades = tablas[1].querySelector("tbody");
const cuerpoHistorial = tablas[2].querySelector("tbody");
const selectDia = document.querySelector("#filtro_dia");
const botonGuardar = document.querySelector("button.boton_buscar");

//hola
// Llena la tabla del historial general
function cargarHistorial() {
    fetch(URL_PHP + "?action=historial")
    .then(respuesta => respuesta.json())
    .then(resultado => {
        if (resultado.status !== "success") {
            cuerpoHistorial.innerHTML = "";
            return;
        }
 
        let filas = "";
 
        resultado.data.forEach(function (registro) {
            let estatus = "Parcial";
            if (Number(registro.completados) >= Number(registro.total)) {
                estatus = "Completada";
            }
 
            filas += `<tr>
                        <td>${registro.fecha}</td>
                        <td>${registro.nombre_rutina}</td>
                        <td>${registro.dia_semana}</td>
                        <td>${registro.completados} de ${registro.total}</td>
                        <td>${estatus}</td>
                      </tr>`;
        });
 
        cuerpoHistorial.innerHTML = filas;
    })
    .catch(error => console.log("Error al cargar el historial:", error));
}
 
// Quita el alert falso del boton y lo cambia por el guardado real
botonGuardar.removeAttribute("onclick");
 
botonGuardar.addEventListener("click", function () {
    const dia = selectDia.value;
 
    if (dia === "") {
        alert("Selecciona un día");
        return;
    }
 
    const datos = new FormData();
    datos.append("action", "guardar");
    datos.append("dia", dia);
 
    // Agrega cada ejercicio marcado
    cuerpoActividades.querySelectorAll("input[type=checkbox]:checked").forEach(function (casilla) {
        datos.append("realizados[]", casilla.value);
    });
 
    fetch(URL_PHP, { method: "POST", body: datos })
    .then(respuesta => respuesta.json())
    .then(resultado => {
        alert(resultado.mensaje);
 
        if (resultado.status === "success") {
            cargarHistorial();
        }
    })
    .catch(error => alert("No se pudo conectar con el servidor"));
});
 
cargarHistorial();