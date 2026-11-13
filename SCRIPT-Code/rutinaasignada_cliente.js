// Función encargada de cargar las rutinas asignadas a un cliente, para mostrarlos en la página web
const tablaRutinas = document.querySelector("#routine-table");

function cargarRutinas(){
    fetch("../PHP-Code/mostrar_rutinaasignada.php")
    .then(respuesta=>respuesta.json())
    .then(rutinas=>{

        if(!Array.isArray(rutinas)){
            console.log("Respuesta del servidor:", rutinas);
            alert("Error al cargar rutinas");
            return;
        }

        let filas="";

        rutinas.forEach(function(rutina){
            let fecha_fin;
            let observaciones;

            if(rutina.fecha_fin===null || rutina.fecha_fin==="0000-00-00"){
                fecha_fin="Sin fecha";
            }else{
                fecha_fin=rutina.fecha_fin;
            }

            if(rutina.observaciones===null || rutina.observaciones===""){
                observaciones="Sin observaciones";
            }else{
                observaciones=rutina.observaciones;
            }

            filas+=`<tr>
                        <td>${rutina.nombre_cliente}</td>
                        <td>${rutina.nombre_rutina}</td>
                        <td>${rutina.nombre_coach}</td>
                        <td>${rutina.fecha_creacion}</td>
                        <td>${fecha_fin}</td>
                        <td>${observaciones}</td>
                        <td>${rutina.estatus}</td>
                        <td>
                            <button type="button" onclick="modificarRutina('${rutina.id_rutina}')">
                                Editar
                            </button>

                            <button type="button" onclick="eliminarRutina('${rutina.id_rutina}')">
                                Eliminar
                            </button>
                        </td>
                    </tr>`;
        });

        tablaRutinas.innerHTML=filas;

    })
    .catch(error=>console.log("Error al cargar rutinas:", error));
}

// Verifica que la tabla exista
if(tablaRutinas){
    cargarRutinas();
}





function eliminarRutina(id_rutina){
    // Función encargada de eliminar la rutina
    if(confirm("¿Está seguro de eliminar la rutina?")){
        fetch("../PHP-Code/eliminar_rutinaasignada.php?id="+id_rutina)
        .then(respuesta=>respuesta.json())
        .then(resultado=>{
            if(resultado.estado==="exito"){
                alert(resultado.mensaje);
                cargarRutinas();
            }else{
                alert("Error: " + resultado.mensaje);
            }
        }).catch(error=>console.log("Error al eliminar rutina:", error));
    }
}





function modificarRutina(id_rutina){
    // Funciòn encargada de modificar la rutina
    fetch("../PHP-Code/buscar_rutinaasignada.php?id="+id_rutina)
    .then(respuesta=>respuesta.json())
    .then(rutina=>{
        if(rutina.estado==="error"){
            alert(rutina.mensaje);
            return;
        }
        cargarCoachesEditar(rutina.datos.id_coach);

        let observaciones;
        if(rutina.datos.observaciones===null || rutina.datos.observaciones===""){
            observaciones="Sin observaciones";
        }else{
            observaciones=rutina.datos.observaciones;
        }

        document.querySelector("#edit-id").value=rutina.datos.id_rutina;
        document.querySelector("#edit-client").value=rutina.datos.nombre_cliente;
        document.querySelector("#edit-name").value=rutina.datos.nombre_rutina;
        document.querySelector("#edit-start").value=rutina.datos.fecha_creacion;
        document.querySelector("#edit-end").value=rutina.datos.fecha_fin;
        document.querySelector("#edit-notes").value=observaciones;
        
        document.querySelector("#edit-dialog").showModal();
    })
    .catch(error=>console.log("Error al buscar rutina:",error));
}



document.querySelector("#edit-form").addEventListener("submit", function(event){
    event.preventDefault();
    const datos = new FormData();

    datos.append("id_rutina", document.querySelector("#edit-id").value);
    datos.append("id_coach", document.querySelector("#edit-coach").value);
    datos.append("nombre_rutina", document.querySelector("#edit-name").value);
    datos.append("fecha_creacion", document.querySelector("#edit-start").value);
    datos.append("fecha_fin", document.querySelector("#edit-end").value);
    datos.append("observaciones", document.querySelector("#edit-notes").value);

    fetch("../PHP-Code/editar_rutinaasignada.php", {
        method:"POST",
        body:datos
    })
    .then(respuesta=>respuesta.json())
    .then(resultado=>{
        alert(resultado.mensaje);

        if(resultado.estado==="exito"){
            cargarRutinas();
            document.querySelector("#edit-dialog").close();
        }
    }).catch(error=>{
        console.log("Error al editar rutina:",error);
    });
});

const botonCancelar=document.querySelector("#cancel-edit");
const dialogoEditar=document.querySelector("#edit-dialog");
if(botonCancelar){
    botonCancelar.addEventListener("click", function(){
        dialogoEditar.close();
    });
}


const botonCerrar=document.querySelector("#close-dialog");
if(botonCerrar){
    botonCerrar.addEventListener("click", function(){
        dialogoEditar.close();
    });
}


const buscador=document.querySelector("#routine-search");
if(buscador){
    buscador.addEventListener("input", function(){
        let texto=this.value.toLowerCase();
        let filas="";

        rutinasRegistradas.forEach(function(rutina){
            if(rutina.nombre_cliente.toLowerCase().includes(texto)){
                filas+=`<tr>
                            <td>${rutina.nombre_cliente}</td>
                            <td>${rutina.nombre_rutina}</td>
                            <td>${rutina.nombre_coach}</td>
                            <td>${rutina.fecha_creacion}</td>
                            <td>${rutina.fecha_fin}</td>
                            <td>${rutina.estatus}</td>
                            <td>
                                <button type="button" onclick="modificarRutina('${rutina.id_rutina}')">
                                    Editar
                                </button>

                                <button type="button" onclick="eliminarRutina('${rutina.id_rutina}')">
                                    Eliminar
                                </button>
                            </td>
                        </tr>`;
            }
        });
        tablaRutinas.innerHTML=filas;
    });
}





function cargarCoachesEditar(coachActual){
    fetch("../PHP-Code/mostrar_coaches.php")
    .then(respuesta=>respuesta.json())
    .then(coaches=>{
        let combo=document.querySelector("#edit-coach");
        combo.innerHTML=
        `<option value="">Selecciona un coach</option>`;

        coaches.forEach(coach=>{
            combo.innerHTML +=
            `<option value="${coach.id_empleado}">
                ${coach.nombre}
            </option>`;
        });
        // Selecciona el coach que ya tenía asignado
        combo.value=coachActual;
    })
    .catch(error=>console.log("Error cargando coaches:",error));
}