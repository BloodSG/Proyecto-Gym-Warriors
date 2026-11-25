// Buscar al cliente para seleccionar sus medidas
const txtBuscar=document.querySelector("#client-search");
const clienteSeleccionado=document.querySelector("#selected-client");

let clienteActual=null;

let medidaEditando=null;

// Buscar mientras escribe
txtBuscar.addEventListener("input", function(){
    let texto=txtBuscar.value.trim();
    if(texto.length<2){
        clienteSeleccionado.hidden=true;
        clienteSeleccionado.innerHTML="";
        clienteActual=null;
        limpiarHistorial();
        return;
    }

    fetch("../PHP-Code/buscar_clientemedidas.php?buscar=" + encodeURIComponent(texto))
    .then(respuesta=>respuesta.json())
    .then(clientes=>{
        if(clientes.length==0){
            clienteSeleccionado.hidden=false;
            clienteSeleccionado.innerHTML="No se encontraron clientes.";
            return;
        }
        mostrarClientes(clientes);
    }).catch(error=>{
        console.error(error);
    });
});


// Mostrar resultados
function mostrarClientes(clientes){
    clienteSeleccionado.hidden=false;
    let contenido=`<strong>Clientes encontrados:</strong><br><br>`;
    clientes.forEach(cliente=>{
        contenido+=`<p 
                        class="cliente-opcion"
                        data-id="${cliente.id_usuario}"
                        data-nombre="${cliente.nombre} ${cliente.apellido_1} ${cliente.apellido_2}">
                        ${cliente.nombre}
                        ${cliente.apellido_1}
                        ${cliente.apellido_2}
                        <br>
                        ID: ${cliente.id_usuario}
                    </p>`;
    });
    clienteSeleccionado.innerHTML=contenido;

    document.querySelectorAll(".cliente-opcion")
    .forEach(opcion=>{
        opcion.addEventListener("click",()=>{
            clienteActual={
                id_usuario: opcion.dataset.id,
                nombre: opcion.dataset.nombre
            };

            txtBuscar.value=clienteActual.nombre;

            clienteSeleccionado.innerHTML=`<strong>Cliente seleccionado</strong>
                                           <br><br>
                                           ${clienteActual.nombre}
                                           <br>
                                           ID: ${clienteActual.id_usuario}`;
            
            cargarHistorial(clienteActual.id_usuario);
        });
    });
}





// Guardar medidas del cliente seleccionado
const formularioMedidas=document.querySelector("#form-medidas");

formularioMedidas.addEventListener("submit", function(e){
    e.preventDefault();
    if(clienteActual==null){
        alert("Debe seleccionar un cliente antes de guardar las medidas.");
        return;
    }

    /* Obtengo los datos del html, la informacion que introduce el usuario y se los paso a guardar_medidascliente.php
       para que se guarden en la base de datos*/
    let datosMedidas=new FormData();
    datosMedidas.append("id_usuario", clienteActual.id_usuario);
    if(medidaEditando==null){
        document.querySelector("#registro-fecha").value=new Date().toISOString().split("T")[0];
    }
    datosMedidas.append("fecha_registro", document.querySelector("#registro-fecha").value);
    datosMedidas.append("peso", document.querySelector("#peso").value);
    datosMedidas.append("porcentaje_grasa", document.querySelector("#porcentaje-grasa").value);
    datosMedidas.append("porcentaje_musculo", document.querySelector("#porcentaje-musculo").value);
    datosMedidas.append("cintura", document.querySelector("#cintura").value);
    datosMedidas.append("cadera", document.querySelector("#cadera").value);
    datosMedidas.append("pecho", document.querySelector("#pecho").value);
    datosMedidas.append("brazo", document.querySelector("#brazo").value);

    let archivoPHP;

    if(medidaEditando==null){
        archivoPHP="guardar_medidascliente.php";
    }else{
        archivoPHP="editar_medidascliente.php";
        datosMedidas.append("id_medida", medidaEditando);
    }

    fetch("../PHP-Code/" + archivoPHP,{method:"POST", body:datosMedidas})
    .then(respuesta=>respuesta.json())
    .then(resultado=>{
        if(resultado.estado){
            alert(resultado.mensaje);
            formularioMedidas.reset();
            medidaEditando=null;
            document.querySelector(".primary-button").textContent="Guardar medición";
            cargarHistorial(clienteActual.id_usuario);
        }else{
            alert(resultado.mensaje);
        }

    }).catch(error=>{
        console.error(error);
        alert("Error al guardar la medición");
    });
});





// Cargar y mostrar las medidas guardadas del clinte que se selecciono
function cargarHistorial(id_usuario){
    const tabla=document.querySelector(".measure-table tbody");
    tabla.innerHTML=`<tr>
                        <td colspan="9">
                            Cargando historial...
                        </td>
                    </tr>`;

    fetch("../PHP-Code/obtener_medidascliente.php?id_usuario=" + id_usuario)
    .then(respuesta=>respuesta.json())
    .then(medidas=>{

        tabla.innerHTML="";
        if(medidas.length==0){
            tabla.innerHTML=`<tr>
                                <td colspan="9">
                                    No hay mediciones registradas.
                                </td>
                            </tr>`;
            return;
        }

        medidas.forEach(medida=>{
            tabla.innerHTML+=`<tr data-id="${medida.id_medida}">
                                <td>${medida.fecha_registro}</td>
                                <td>${medida.peso}</td>
                                <td>${medida.porcentaje_grasa ?? "Sin medida"}</td>
                                <td>${medida.porcentaje_musculo ?? "Sin medida"}</td>
                                <td>${medida.cintura}</td>
                                <td>${medida.cadera}</td>
                                <td>${medida.pecho}</td>
                                <td>${medida.brazo}</td>
                                <td>
                                    <button class="btn-editar">Editar</button>
                                    <button class="btn-eliminar">Eliminar</button>
                                </td>
                              </tr>`;
        });

    }).catch(error=>{
        console.error(error);
        tabla.innerHTML=`<tr>
                            <td colspan="9">
                                Error al cargar historial.
                            </td>
                        </tr>`;
    });
}

function limpiarHistorial(){
    const tabla=document.querySelector(".measure-table tbody");
    tabla.innerHTML="";
}



// Parte encargada de editar medidas de un cliente
document.addEventListener("click", function(e){
    if(e.target.classList.contains("btn-editar")){
        const fila=e.target.closest("tr");
        medidaEditando=fila.dataset.id;

        document.querySelector("#registro-fecha").value=fila.children[0].textContent;
        document.querySelector("#peso").value=fila.children[1].textContent;
        document.querySelector("#porcentaje-grasa").value=fila.children[2].textContent === "Sin medida" ? "" : fila.children[2].textContent;
        document.querySelector("#porcentaje-musculo").value=fila.children[3].textContent === "Sin medida" ? "" : fila.children[3].textContent;
        document.querySelector("#cintura").value=fila.children[4].textContent;
        document.querySelector("#cadera").value=fila.children[5].textContent;
        document.querySelector("#pecho").value=fila.children[6].textContent;
        document.querySelector("#brazo").value=fila.children[7].textContent;

        document.querySelector(".primary-button").textContent="Actualizar medición";

        window.scrollTo({
            top:0,
            behavior:"smooth"
        });
    }

     // ELIMINAR MEDIDA
    if(e.target.classList.contains("btn-eliminar")){
        const fila=e.target.closest("tr");
        const id_medida=fila.dataset.id;

        let confirmar=confirm(
            "¿Desea eliminar esta medición?"
        );

        if(confirmar){
            let datosEliminar=new FormData();

            datosEliminar.append("id_medida", id_medida);

            fetch("../PHP-Code/eliminar_medidascliente.php",{method:"POST", body:datosEliminar})
            .then(respuesta=>respuesta.json())
            .then(resultado=>{
                if(resultado.estado){
                    alert(resultado.mensaje);
                    // Actualiza la tabla después de eliminar
                    cargarHistorial(clienteActual.id_usuario);
                }else{
                    alert(resultado.mensaje);
                }
            }).catch(error=>{
                console.error(error);
                alert("Error al eliminar la medición");
            });
        }
    }
});




formularioMedidas.addEventListener("reset", function(){
    medidaEditando=null;
    document.querySelector(".primary-button").textContent="Guardar medición";
});