document.addEventListener("DOMContentLoaded", function () {
    //este archivo es para hacer todo el proceso de editar la rutina
    const urlParams = new URLSearchParams(window.location.search);
    const idRutina = urlParams.get('id_rutina');

    const modalSeleccion = document.getElementById("modal_seleccionar_rutina");
    const selectRutinaModal = document.getElementById("select_rutina_modal");
    const formElegirRutina = document.getElementById("form_elegir_rutina");

    //si no envian el id en la url se muestra el modal para elegir la rutina a editar
    if (!idRutina) {
        if (modalSeleccion) {
            modalSeleccion.style.display = "flex";
        }

        if (selectRutinaModal) {
            fetch('../PHP-Code/obtener_rutinas.php')
                .then(res => res.json())
                .then(resultado => {
                    if (resultado.status === 'success' && resultado.data) {
                        if (resultado.data.length === 0) {
                            selectRutinaModal.innerHTML = '<option value="" selected disabled>No hay rutinas registradas</option>';
                            return;
                        }

                        selectRutinaModal.innerHTML = '<option value="" selected disabled>Selecciona una rutina</option>';
                        resultado.data.forEach(rutina => {
                            const opt = document.createElement("option");
                            opt.value = rutina.id_rutina;
                            opt.textContent = `${rutina.nombre_rutina} (${rutina.id_rutina})`;
                            selectRutinaModal.appendChild(opt);
                        });
                    } else {
                        selectRutinaModal.innerHTML = '<option value="" selected disabled>Error al obtener datos</option>';
                    }
                })
                .catch(err => {
                    console.error("Error al cargar rutinas:", err);
                    selectRutinaModal.innerHTML = '<option value="" selected disabled>Error de conexión</option>';
                });
        }

        if (formElegirRutina) {
            formElegirRutina.addEventListener("submit", function (e) {
                e.preventDefault();
                const rutinaSeleccionada = selectRutinaModal.value;
                if (rutinaSeleccionada) {
                    window.location.href = `editar_rutina.html?id_rutina=${rutinaSeleccionada}`;
                } else {
                    alert("Por favor selecciona una rutina.");
                }
            });
        }

        return;
    }

    //si ya hay una id de rutina o se selecciono se oculta el modal y se muestra la informacion de la rutina
    if (modalSeleccion) {
        modalSeleccion.style.display = "none";
    }

    const inputIdRutina = document.getElementById("id_rutina_input");
    if (inputIdRutina) {
        inputIdRutina.value = idRutina;
    }

    const btnAgregar = document.querySelector(".pie-lista .btn-sec");
    if (btnAgregar) {
        btnAgregar.href = `nuevo_ejercicio.html?id_rutina=${idRutina}&origen=editar`;
    }

    fetch(`../PHP-Code/obtener_rutina_detalles.php?id_rutina=${idRutina}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const inputNombre = document.getElementById("nombre_rutina");
                const labelRutinaDisplay = document.getElementById("nombre_rutina_display");

                if (data.rutina) {
                    if (inputNombre) inputNombre.value = data.rutina.nombre_rutina;
                    if (labelRutinaDisplay) labelRutinaDisplay.textContent = `Rutina: ${data.rutina.nombre_rutina}`;
                }

                const tbody = document.querySelector("table tbody");
                let modalesContainer = document.getElementById("modales_eliminar_container");
                if (!modalesContainer) {
                    modalesContainer = document.createElement("div");
                    modalesContainer.id = "modales_eliminar_container";
                    document.body.appendChild(modalesContainer);
                }

                if (tbody) {
                    tbody.innerHTML = "";
                    modalesContainer.innerHTML = "";

                    if (!data.ejercicios || data.ejercicios.length === 0) {
                        tbody.innerHTML = `<tr class="fila-vacia"><td class="vacio" colspan="8">Aún no hay ejercicios</td></tr>`;
                    } else {
                        //aqui se llena la tabla con los ejercicios que tiene la rutina
                        data.ejercicios.forEach(ex => {
                            const videoHtml = ex.video_url && ex.video_url.trim() !== ""
                                ? `<a class="ver" href="${ex.video_url}" target="_blank">Ver video</a>`
                                : 'Sin video';

                            tbody.innerHTML += `
                                <tr>
                                    <td>${ex.nombre_ejercicio}</td>
                                    <td>${ex.dia_semana}</td>
                                    <td class="num">${ex.series}</td>
                                    <td class="num">${ex.repeticiones}</td>
                                    <td class="num">${ex.peso_sugerido} kg</td>
                                    <td class="num">${ex.tiempo_descanso} min</td>
                                    <td>${videoHtml}</td>
                                    <td class="acc">
                                        <a class="btn-mini" href="editar_ejercicio.html?id_detalle_rutina=${ex.id_detalle_rutina}&id_rutina=${idRutina}&origen=editar">Editar</a>
                                        <a class="btn-mini peligro" href="#eliminar_${ex.id_detalle_rutina}">Eliminar</a>
                                    </td>
                                </tr>
                            `;
                            //esta es una miniventana que me dice si quiero borrar el ejercicio o no
                            modalesContainer.innerHTML += `
                                <div class="modal confirmar" id="eliminar_${ex.id_detalle_rutina}" role="dialog" aria-modal="true">
                                    <div class="modal-caja">
                                        <h1>Eliminar ejercicio</h1>
                                        <p>¿Seguro que quieres quitar <strong>${ex.nombre_ejercicio}</strong> de esta rutina?</p>
                                        <form action="../PHP-Code/eliminar_ejercicio.php" method="post">
                                            <input type="hidden" name="id_detalle_rutina" value="${ex.id_detalle_rutina}">
                                            <input type="hidden" name="id_rutina" value="${idRutina}">
                                            <div class="acciones-form">
                                                <a class="btn-sec" href="#">Cancelar</a>
                                                <button type="submit" class="btn-peligro">Sí, eliminar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            `;
                        });
                    }
                }
            } else {
                console.error("Respuesta de error del servidor:", data.message);
            }
        })
        .catch(err => console.error("Error al cargar la rutina:", err));
});