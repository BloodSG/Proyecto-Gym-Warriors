let clientesConMembresia = [];

const cuerpoTabla = document.getElementById('vigencias-body');
const mensajeTabla = document.getElementById('estado-tabla');
const campoBusqueda = document.getElementById('buscar-cliente');


// Cargar información desde PHP
fetch("../PHP/obtener_vigencias.php")
    .then(respuesta => respuesta.json())
    .then(respuesta => {


        // Validar errores enviados desde PHP
        if (respuesta.estado === "error") {

            mensajeTabla.textContent = respuesta.mensaje;

            console.error(
                "Error del servidor:",
                respuesta.mensaje
            );

            return;
        }


        // Guardar registros recibidos
        clientesConMembresia = respuesta.datos;


        mostrarRegistros(clientesConMembresia);


    })
    .catch(error => {

        console.error(
            "Error de comunicación:",
            error
        );


        mensajeTabla.textContent =
            "No fue posible conectar con el servidor.";

    });



// Convertir fecha
function fechaLocal(fecha) {

    const [anio, mes, dia] =
        fecha.split('-').map(Number);


    return new Date(
        anio,
        mes - 1,
        dia
    );
}



// Mostrar fecha
function fechaVisible(fecha) {

    if (!fecha) {

        return "Sin fecha";

    }


    return fechaLocal(fecha)
        .toLocaleDateString('es-MX', {

            day: '2-digit',
            month: '2-digit',
            year: 'numeric'

        });

}



// Calcular estado
function estadoMembresia(fechaVencimiento) {


    const hoy = new Date();

    hoy.setHours(0,0,0,0);



    const vencimiento =
        fechaLocal(fechaVencimiento);



    const dias =
        Math.ceil(
            (vencimiento - hoy) / 86400000
        );



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



// Texto de días
function textoDias(dias) {


    if (dias < 0) {


        return `Venció hace ${Math.abs(dias)}
        ${Math.abs(dias) === 1 ? "día" : "días"}`;


    }



    if (dias === 0) {


        return "Vence hoy";


    }



    return `${dias}
    ${dias === 1 ? "día restante" : "días restantes"}`;

}



// Actualizar tarjetas
function actualizarResumen(registros) {


    const estados =
        registros.map(registro =>
            estadoMembresia(
                registro.fechaVencimiento
            )
        );



    document.getElementById('total-clientes')
        .textContent =
        registros.length;



    document.getElementById('total-activas')
        .textContent =
        estados.filter(
            estado =>
            estado.clase === "active"
        ).length;



    document.getElementById('total-por-vencer')
        .textContent =
        estados.filter(
            estado =>
            estado.clase === "warning"
        ).length;



    document.getElementById('total-vencidas')
        .textContent =
        estados.filter(
            estado =>
            estado.clase === "expired"
        ).length;

}



// Mostrar tabla
function mostrarRegistros(registros) {


    cuerpoTabla.replaceChildren();


    actualizarResumen(registros);



    if (!registros.length) {


        mensajeTabla.textContent =
            clientesConMembresia.length
            ?
            "No se encontraron coincidencias."
            :
            "No hay clientes con membresía registrada.";


        return;

    }



    mensajeTabla.textContent = "";



    registros
        .slice()
        .sort(
            (a,b)=>
            a.fechaVencimiento.localeCompare(
                b.fechaVencimiento
            )
        )
        .forEach(registro => {



            const estado =
                estadoMembresia(
                    registro.fechaVencimiento
                );



            const fila =
                document.createElement("tr");



            fila.innerHTML = `

                <td>
                    <strong>${registro.nombre}</strong>
                </td>

                <td>
                    <span class="membership-name">
                        ${registro.membresia}
                    </span>
                </td>

                <td>
                    ${fechaVisible(
                        registro.fechaInicio
                    )}
                </td>

                <td>
                    ${fechaVisible(
                        registro.fechaVencimiento
                    )}
                </td>

                <td>
                    ${textoDias(
                        estado.dias
                    )}
                </td>

                <td>
                    <span class="status-badge ${estado.clase}">
                        ${estado.nombre}
                    </span>
                </td>

            `;


            cuerpoTabla.appendChild(fila);


        });

}



// Buscar
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



        mostrarRegistros(filtrados);


    }
);