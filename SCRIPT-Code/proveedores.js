// Mostrar proveedores
/* Obtiene la tabla donde se mostrarán los proveedores mediante su ID,
    provieneiene desde proveedor.html, con el id de la tabla*/
const tablaProveedores = document.querySelector("#tablaProveedores");

// Se verifica que la tabla exista
if(tablaProveedores){
    // Realiza una petición al archivo PHP encargado de consultar los proveedores
    fetch("../PHP-Code/mostrar_proveedor.php")

    // Convertir respuesta a JSON
    .then(respuesta => respuesta.json())

    // Procesar proveedores recibidos
    .then(proveedores => {
        let filas = "";
        // Recorre cada proveedor recibido desde la base de datos, forEach->para cada
        proveedores.forEach(function(proveedor){
            // Se crea una fila HTML con la información del proveedor
            filas += `
            <tr>
                <td>${proveedor.nombre_empresa}</td>
                <td>${proveedor.contacto_nombre}</td>
                <td>${proveedor.telefono}</td>
                <td>${proveedor.correo}</td>
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
        // Inserta las filas generadas dentro de la tabla HTML
        tablaProveedores.innerHTML = filas;
    })
    .catch(error => console.log("Error al cargar proveedores:", error));
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
function eliminarProveedor(id)
{
    if(confirm("¿Deseas eliminar este proveedor?"))
    {
        // Redirige al formulario de eliminación enviando el ID por URL
        window.location.href ="../PHP-Code/eliminar_proveedor.php?id=" + id;
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