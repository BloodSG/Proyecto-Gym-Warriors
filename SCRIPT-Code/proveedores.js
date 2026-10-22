// Función encargada de cargar los proveedores, para mostrarlos en el sitio web
const tablaProveedores = document.querySelector("#tablaProveedores");

function cargarProveedores(){
    fetch("../PHP-Code/mostrar_proveedor.php")
    .then(respuesta=>respuesta.json())
    .then(proveedores=>{

        let filas="";

        proveedores.forEach(function(proveedor){

            filas+=`
            <tr>
                <td>${proveedor.nombre_empresa}</td>
                <td>${proveedor.contacto_nombre}</td>
                <td>${proveedor.telefono}</td>
                <td>${proveedor.correo}</td>
                <td>${proveedor.productos ?? "Sin productos asignados"}</td>
                <td>
                    <button type="button" onclick="modificarProveedor('${proveedor.id_proveedor}')">
                        Editar
                    </button>

                    <button type="button" onclick="eliminarProveedor('${proveedor.id_proveedor}')">
                        Eliminar
                    </button>
                </td>
            </tr>
            `;
        });

        tablaProveedores.innerHTML=filas;

    })
    .catch(error=>console.log("Error al cargar proveedores:", error));
}

// Verifica que la tabla exista
if(tablaProveedores){
    cargarProveedores();
}



// CARGAR DATOS DEL PROVEEDOR PARA MODIFICAR
// Busca el campo oculto donde se almacenará el ID del proveedor
const campoId=document.getElementById("id_proveedor");

// Verifica si el formulario de modificación existe en la página actual
if(campoId){
    // Obtiene los parámetros enviados en la URL
    const parametros=new URLSearchParams(window.location.search);
    // Obtiene el ID del proveedor desde la URL
    const idProveedor=parametros.get("id");
    // Guarda el ID obtenido dentro del campo oculto del formulario
    campoId.value=idProveedor;

    // Realiza una petición al PHP encargado de buscar un proveedor específico
    fetch("../PHP-Code/buscar_proveedor.php?id=" + idProveedor)
    .then(respuesta => respuesta.json())
    .then(proveedor => {

        // Coloca los datos en los campos correspondientes
        document.getElementById("nombreemp").value = proveedor.nombre_empresa;
        document.getElementById("nombre").value = proveedor.contacto_nombre;
        document.getElementById("telefono").value = proveedor.telefono;
        document.getElementById("correo").value = proveedor.correo;
    })

    .catch(error => console.log("Error al buscar proveedor:", error));
}





// Función encargada de eliminar un proveedor
// Recibe como parámetro el ID del proveedor seleccionado
function eliminarProveedor(id){
    if(confirm("¿Deseas eliminar este proveedor?")){
        fetch("../PHP-Code/eliminar_proveedor.php?id=" + id)
        .then(respuesta=>respuesta.json())
        .then(datos=>{
            mostrarMensaje(datos.mensaje, datos.estado);
            if(datos.estado==="ok"){
                cargarProveedores();
            }
        })
        .catch(error=>{
            mostrarMensaje("Error al eliminar proveedor", "error");
            console.error(error);
        });
    }
}





// Función encargada de enviar al usuario al formulario de modificación
// Recibe como parámetro el ID del proveedor seleccionado
function modificarProveedor(id)
{
    if(confirm("¿Deseas modificar este proveedor?"))
    {
        // Redirige al formulario de modificación enviando el ID por URL
        window.location.href="../HTML-Code/modificar_proveedor.html?id=" + id;
    }
}




// Mensaje al guardar nuevo proveedor
const formularioGuardarProveedor=document.getElementById("formProveedor");

// Verifica si estamos en la página de nuevo proveedor
if(formularioGuardarProveedor){
    formularioGuardarProveedor.addEventListener("submit", async function(e){
        e.preventDefault();
        const datos=new FormData(formularioGuardarProveedor);

        try{
            const respuesta=await fetch("../PHP-Code/guardar_proveedor.php", {
                method:"POST",
                body:datos
            });

            const resultado=await respuesta.json();

            mostrarMensaje(resultado.mensaje, resultado.estado);
            if(resultado.estado==="ok"){
                formularioGuardarProveedor.reset();
                setTimeout(() => {
                    window.location.href = "../HTML-Code/proveedores.html";
                }, 3000);
            }

        }catch(error){
            mostrarMensaje("Error al guardar proveedor", "error");
            console.log(error);
        }
    });
}





// Mensaje al modificar proveedor
const formularioEditarProveedor=document.getElementById("formEditarProveedor");

// Verifica si estamos en la página de nuevo proveedor
if(formularioEditarProveedor){
    formularioEditarProveedor.addEventListener("submit", async function(e){
        e.preventDefault();
        const datos=new FormData(formularioEditarProveedor);

        try{
            const respuesta=await fetch("../PHP-Code/modificar_proveedor.php", {
                method: "POST",
                body: datos
            });
            const resultado=await respuesta.json();
            mostrarMensaje(resultado.mensaje, resultado.estado);
            if(resultado.estado==="ok"){
                formularioEditarProveedor.reset();
                setTimeout(() => {
                    window.location.href = "../HTML-Code/proveedores.html";
                }, 3000);
            }

        }catch(error){
            mostrarMensaje("Error al modificar proveedor", "error");
            console.log(error);
        }
    });
}




function mostrarMensaje(texto, tipo){
    const mensaje = document.getElementById("mensaje");
    if(mensaje){
        if(tipo==="ok"){
            tipo="exito";
        }
        mensaje.innerHTML = texto;
        mensaje.className = "mensaje " + tipo;
        mensaje.style.display = "flex";
        mensaje.style.opacity = "1";
        mensaje.style.visibility = "visible";
        setTimeout(()=>{
            mensaje.style.display = "none";
            mensaje.style.opacity = "0";
            mensaje.style.visibility = "hidden";
        },3000);
    }
}