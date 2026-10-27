// Esperamos a que la página cargue por completo
document.addEventListener("DOMContentLoaded", function() {
    cargarEmpleados();
});

let empleadosData = []; // Guardamos los datos para usarlos al editar

function cargarEmpleados() {
    fetch('../PHP-Code/bajaempl.php', {
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(function(respuesta) {
        return respuesta.json();
    })
    .then(function(empleados) {
        empleadosData = empleados; // Guardamos la lista completa
        let tabla = document.getElementById('employee-table');
        tabla.innerHTML = ''; // Limpiamos la tabla

        if (!empleados || empleados.length === 0) {
            return;
        }

        empleados.forEach(function(emp) {
            let fila = document.createElement('tr');

            // Pintamos las columnas + los botones de Editar y Eliminar
            fila.innerHTML = `
                <td>${emp.correo || 'N/A'}</td>
                <td>${emp.curp || ''}</td>
                <td>${emp.puesto || ''}</td>
                <td>${emp.turno || ''}</td>
                <td>$${parseFloat(emp.salario || 0).toFixed(2)}</td>
                <td>${emp.fecha_contratacion || ''}</td>
                <td><span class="badge ${emp.estado_laboral}">${emp.estado_laboral || 'activo'}</span></td>
                <td>
                    <button class="action-btn edit-btn" type="button" data-id="${emp.id_empleado}" style="margin-right: 5px;">Editar</button>
                    <form action="../PHP-Code/bajaempl.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminarlo?');">
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id_empleado" value="${emp.id_empleado}">
                        <button type="submit" class="action-btn delete-btn">Eliminar</button>
                    </form>
                </td>
            `;

            tabla.appendChild(fila);
        });

        // Activamos la funcionalidad del botón Editar para cada fila
        configurarBotonesEditar();

    })
    .catch(function(error) {
        console.log("Error al cargar los datos: ", error);
    });
}

function configurarBotonesEditar() {
    const editDialog = document.getElementById('edit-dialog');
    const editForm = document.getElementById('edit-form');
    const closeDialogBtn = document.getElementById('close-dialog');
    const cancelEditBtn = document.getElementById('cancel-edit');

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const id = e.target.getAttribute('data-id');
            const emp = empleadosData.find(item => item.id_empleado == id);
            
            if (emp && editDialog) {
                // Rellenamos el ID oculto
                document.getElementById('edit-id').value = emp.id_empleado;
                
                // Rellenamos todos los campos del formulario modal con los datos actuales
                const inputEmpleado = document.getElementById('edit-empleado');
                if(inputEmpleado) inputEmpleado.value = emp.correo || '';

                const inputCurp = document.getElementById('edit-curp');
                if(inputCurp) inputCurp.value = emp.curp || '';

                const inputRfc = document.getElementById('edit-rfc');
                if(inputRfc) inputRfc.value = emp.rfc || '';

                const inputPuesto = document.getElementById('edit-puesto');
                if(inputPuesto) inputPuesto.value = emp.puesto || '';

                const inputTurno = document.getElementById('edit-turno');
                if(inputTurno) inputTurno.value = emp.turno || '';

                const inputSalario = document.getElementById('edit-salario');
                if(inputSalario) inputSalario.value = emp.salario || '';

                const inputFecha = document.getElementById('edit-fecha');
                if(inputFecha) inputFecha.value = emp.fecha_contratacion || '';

                const selectEstado = document.getElementById('edit-estado');
                if(selectEstado) selectEstado.value = emp.estado_laboral || 'activo';

                const inputContactoNom = document.getElementById('edit-contacto-nom');
                if(inputContactoNom) inputContactoNom.value = emp.contacto_emergencia_nom || '';

                const inputContactoTel = document.getElementById('edit-contacto-tel');
                if(inputContactoTel) inputContactoTel.value = emp.contacto_emergencia_tel || '';

                // Aseguramos que la acción sea 'actualizar' para tu backend PHP
                let accionInput = document.getElementById('edit-accion');
                if (!accionInput) {
                    accionInput = document.createElement('input');
                    accionInput.type = 'hidden';
                    accionInput.id = 'edit-accion';
                    accionInput.name = 'accion';
                    editForm.appendChild(accionInput);
                }
                accionInput.value = 'actualizar';

                editForm.action = '../PHP-Code/bajaempl.php';
                editForm.method = 'POST';

                // Abrimos el modal
                editDialog.showModal();
            }
        });
    });

    if (closeDialogBtn) {
        closeDialogBtn.onclick = () => editDialog.close();
    }
    if (cancelEditBtn) {
        cancelEditBtn.onclick = () => editDialog.close();
    }
}