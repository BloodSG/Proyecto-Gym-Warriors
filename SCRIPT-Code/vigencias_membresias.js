// Este archivo se encarga de obtener las vigencias de membresia de los clientes

// Almacena los registros recibidos desde la base de datos.
let clientesConMembresia = [];

const cuerpoTabla = document.getElementById('vigencias-body');
const mensajeTabla = document.getElementById('estado-tabla');
const campoBusqueda = document.getElementById('buscar-cliente');

// Cargar información
/* Se realiza una petición al archivo PHP (obtener_vigencias.php) encargado de consultar
   la base de datos y devolver la información en formato JSON*/
fetch("../PHP-Code/obtener_vigencias.php")
    .then(respuesta => respuesta.json())
    .then(respuesta => {
        // Validar errores enviados desde PHP
        if (respuesta.estado==="error") {
            mensajeTabla.textContent = respuesta.mensaje;
            console.error("Error del servidor:", respuesta.mensaje);
            return;
        }
        // Se almacenan los registros recibidos
        clientesConMembresia=respuesta.datos;

        // Se muestran los registros en la tabla de la interfaz
        mostrarRegistros(clientesConMembresia);
    })
    .catch(error => {
        console.error("Error de comunicación:", error);
        mensajeTabla.textContent="No fue posible conectar con el servidor.";});

// Convertir fecha a date
function fechaLocal(fecha) {
    if (!fecha) {
        return null;
    }
    return new Date(fecha.replace(' ', 'T'));
}

// Mostrar fecha
// Convierte una fecha al formato dd/mm/aaaa
function fechaVisible(fecha) {
    if (!fecha) {
        return "Sin fecha";
    }

    return fechaLocal(fecha).toLocaleDateString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
}

// Calcular estado de la membresia Activa, Por vencer o Vencida
function estadoMembresia(fechaVencimiento) {
    const hoy = new Date();
    hoy.setHours(0,0,0,0);

    const vencimiento=fechaLocal(fechaVencimiento);

    const dias=Math.ceil((vencimiento-hoy)/86400000);

    if (dias < 0) {
        return {
            nombre: "Vencida",
            clase: "expired",
            dias
        };
    }

    if (dias <= 7) {
        return {
            nombre: "Por vencer",
            clase: "warning",
            dias
        };
    }


    return {
        nombre: "Activa",
        clase: "active",
        dias
    };
}


// Genera el texto que indica los días restantes
function textoDias(dias) {
    if (dias < 0) {
        return `Venció hace ${Math.abs(dias)}
        ${Math.abs(dias) === 1 ? "día" : "días"}`;
    }

    if (dias === 0) {
        return "Vence hoy";
    }

    return `${dias}
    ${dias===1 ? "día restante" : "días restantes"}`;
}


/* Actualizar tarjetas de resumen de clientes con membresia, membresias activas, por vencer y vencidas
   se obtienen los valores de cada uno de ellos*/
function actualizarResumen(registros) {
    const estados=
        registros.map(
            registro=>
            estadoMembresia(
                registro.fechaVencimiento
            )
        );
    
    // se obtiene el total de clientes con membresia
    document.getElementById('total-clientes')
        .textContent=registros.length;
    
    // Obtenemos membresias activas
    document.getElementById('total-activas')
        .textContent=estados.filter(estado=>estado.clase==="active").length;
    
    // Obtenemos membresias proximas a vencer
    document.getElementById('total-por-vencer')
        .textContent=estados.filter(estado=>estado.clase=== "warning").length;
    
    // Obtenemos membresias vencidas
    document.getElementById('total-vencidas')
        .textContent=estados.filter(estado=>estado.clase=== "expired").length;
}


// Función encargada de mostrar los registros dentro de la tabla HTML
function mostrarRegistros(registros) {
    // Elimina registros anteriores
    cuerpoTabla.replaceChildren();
    // Actualiza las tarjetas
    actualizarResumen(registros);

    // Si no existen registros se muestra un mensaje
    if (!registros.length) {
        mensajeTabla.textContent =
            clientesConMembresia.length
            ?
            "No se encontraron coincidencias."
            :
            "No hay clientes con membresía registrada.";
        return;
    }

    // Ordena todos los registros obtenidos por fecha de vencimiento.
    mensajeTabla.textContent = "";
    registros
        .slice()
        .sort(
            (a,b)=>
            a.fechaVencimiento.localeCompare(
                b.fechaVencimiento
            )
        )
        .forEach(registro=>{
            const estado=
                estadoMembresia(
                    registro.fechaVencimiento
                );
            const fila=
                document.createElement("tr");

            fila.innerHTML=`<td><strong>${registro.nombre}</strong></td>

                            <td><span class="membership-name">${registro.membresia}</span></td>

                            <td>${fechaVisible(registro.fechaInicio)}</td>

                            <td>${fechaVisible(registro.fechaVencimiento)}</td>

                            <td>${textoDias(estado.dias)}</td>

                            <td><span class="status-badge ${estado.clase}">${estado.nombre}</span></td>`;
            cuerpoTabla.appendChild(fila);
        });
}

// Buscador de clientes con membresias
campoBusqueda.addEventListener(
    "input",
    evento => {
        const termino =
            evento.target.value
            .trim()
            .toLowerCase();

        const filtrados =
            clientesConMembresia.filter(
                registro => {
                    return [
                        registro.nombre,
                        registro.membresia
                    ]
                    .some(valor =>
                        String(valor)
                        .toLowerCase()
                        .includes(termino)
                    );
                }
            );
        
        // Actualiza la tabla con los registros filtrados.
        mostrarRegistros(filtrados);
    }
);