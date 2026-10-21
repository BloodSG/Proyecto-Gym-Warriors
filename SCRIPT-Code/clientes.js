// Función encargada de cargar los proveedores, para mostrarlos en el sitio web
const tablaclientes=document.querySelector("#employee-table");

let clientesRegistrados=[];

function cargarClientes(){
    fetch("../PHP-Code/mostrar_cliente.php")
    .then(respuesta=>respuesta.json())
    .then(clientes=>{

        clientesRegistrados=clientes;

        let filas="";

        clientes.forEach(function(cliente){

            filas+=`
            <tr>
                <td>${cliente.nombre}</td>
                <td>${cliente.apellido_1}</td>
                <td>${cliente.apellido_2}</td>
                <td>${cliente.correo}</td>
                <td>
                    <button type="button" onclick="modificarCliente('${cliente.id_usuario}')">
                        Editar
                    </button>

                    <button type="button" onclick="eliminarCliente('${cliente.id_usuario}')">
                        Eliminar
                    </button>
                </td>
            </tr>
            `;
        });

        tablaclientes.innerHTML=filas;

    })
    .catch(error=>console.log("Error al cargar Clientes:", error));
}

// Verifica que la tabla exista
if(tablaclientes){
    cargarClientes();
}




function modificarCliente(id_usuario){
    fetch("../PHP-Code/buscar_cliente.php?id="+id_usuario)
    .then(respuesta=>respuesta.json())
    .then(cliente=>{
        document.querySelector("#edit-id").value = cliente.id_usuario;
        document.querySelector("#edit-empleado").value = cliente.nombre;
        document.querySelector("#edit-apellido-1").value = cliente.apellido_1;
        document.querySelector("#edit-apellido-2").value = cliente.apellido_2 ?? "";
        document.querySelector("#edit-correo").value = cliente.correo;

        document.querySelector("#edit-dialog").showModal();
    })
    .catch(error=>console.log("Error al buscar cliente:",error));
}



function eliminarCliente(id_usuario){
    let confirmar = confirm("¿Está seguro de eliminar este cliente?");

    if(confirmar){
        fetch("../PHP-Code/eliminar_cliente.php?id="+id_usuario)
        .then(respuesta=>respuesta.json())
        .then(resultado=>{
            alert(resultado.mensaje);
            cargarClientes();
        })
        .catch(error=>console.log(
            "Error al eliminar cliente:",
            error
        ));
    }
}




document.querySelector("#edit-form").addEventListener("submit",function(e){
    e.preventDefault();
    let datos = new FormData();
    datos.append(
        "id_usuario",
        document.querySelector("#edit-id").value);
    datos.append(
        "nombre",
        document.querySelector("#edit-empleado").value);
    datos.append(
        "apellido_1",
        document.querySelector("#edit-apellido-1").value);
    datos.append(
        "apellido_2",
        document.querySelector("#edit-apellido-2").value);
    datos.append(
        "correo",
        document.querySelector("#edit-correo").value);

    fetch("../PHP-Code/modificar_cliente.php",{
        method:"POST",
        body:datos
    })
    .then(respuesta=>respuesta.json())
    .then(resultado=>{
        alert(resultado.mensaje);
        document.querySelector("#edit-dialog").close();
        cargarClientes();
    })
    .catch(error=>console.log("Error al modificar:",error));
});




// Botón cancelar edición
const botonCancelar = document.querySelector("#cancel-edit");
const dialogoEditar = document.querySelector("#edit-dialog");

if(botonCancelar){
    botonCancelar.addEventListener("click", function(){
        dialogoEditar.close();
    });
}


// Botón cerrar con la X
const botonCerrar = document.querySelector("#close-dialog");

if(botonCerrar){
    botonCerrar.addEventListener("click", function(){
        dialogoEditar.close();
    });
}




const buscador=document.querySelector("#employee-search");

if(buscador){
    buscador.addEventListener("input", function(){
        let texto=this.value.toLowerCase();
        let filas="";

        clientesRegistrados.forEach(function(cliente){
            if(cliente.nombre.toLowerCase().includes(texto)){
                filas+=`
                <tr>
                    <td>${cliente.nombre}</td>
                    <td>${cliente.apellido_1}</td>
                    <td>${cliente.apellido_2 ?? ""}</td>
                    <td>${cliente.correo ?? "Sin correo"}</td>

                    <td>
                        <button type="button" onclick="modificarCliente('${cliente.id_usuario}')">
                            Editar
                        </button>

                        <button type="button" onclick="eliminarCliente('${cliente.id_usuario}')">
                            Eliminar
                        </button>
                    </td>
                </tr>
                `;
            }
        });
        tablaclientes.innerHTML=filas;
    });
}