
import { cargaListaClientes } from "./api.js";

document.addEventListener("DOMContentLoaded", async () => {
    
    const urlParams = new URLSearchParams(window.location.search);
    const idMembresia = urlParams.get('id_membresia');
    if (idMembresia) {
        const inputMembresia = document.getElementById('id_membresia');
        if (inputMembresia) {
            inputMembresia.value = idMembresia;
        }
    }


    const selectCliente = document.getElementById('cliente');
    if (!selectCliente) return;

    selectCliente.innerHTML = '<option value="">Cargando clientes...</option>';

    try {
        //muestro los clientes en base de datos en la lista desplegable
        const resultado = await cargaListaClientes();
        
        if (resultado.data.length === 0) {
            selectCliente.innerHTML = '<option value="">No hay clientes registrados</option>';
        } else if (resultado.status === "success" || resultado.data.length > 0) {
            selectCliente.innerHTML = '<option value="">Selecciona un cliente</option>';
            
            resultado.data.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.id_usuario;
                option.textContent = `${cliente.nombre} ${cliente.apellido_1}`;
                selectCliente.appendChild(option);
            });
        } else {
            alert("Hubo un error al cargar los clientes: " + resultado.mensaje);
        }
    } catch (error) {
        console.error(error);
        selectCliente.innerHTML = '<option value="">Error al conectar con la base de datos</option>';
        alert("Ocurrió un error al momento de cargar la lista de clientes");
    }
});
