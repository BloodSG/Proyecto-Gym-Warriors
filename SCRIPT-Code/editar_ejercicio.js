document.addEventListener("DOMContentLoaded", function () {
    const urlParams = new URLSearchParams(window.location.search);
    const idRutina = urlParams.get('id_rutina');
    const idDetalleRutina = urlParams.get('id_detalle_rutina');

    if (!idRutina || !idDetalleRutina) {
        alert("Faltan parámetros de identificación para editar el ejercicio.");
        return;
    }

    document.getElementById("input_id_rutina").value = idRutina;
    document.getElementById("input_id_detalle_rutina").value = idDetalleRutina;

    const btnCancelar = document.querySelector(".acciones-form .btn-sec");
    if (btnCancelar) {
        btnCancelar.href = `editar_rutina.html?id_rutina=${idRutina}`;
    }

    const selectEjercicio = document.getElementById("id_ejercicio");
    const grupoNuevoEjercicio = document.getElementById("grupo_nuevo_ejercicio");
    const inputNombreEjercicio = document.getElementById("nombre_ejercicio");
    const selectEvidencia = document.getElementById("opc_evidencia");
    const grupoUrl = document.getElementById("grupo_url");
    const grupoArchivo = document.getElementById("grupo_archivo");
    const inputUrl = document.getElementById("video_url");
    const formEjercicio = document.getElementById("form_ejercicio");

    function inicializarEdicion() {
        fetch('../PHP-Code/obtener_ejercicios.php')
            .then(res => res.json())
            .then(resultado => {
                if (resultado.status === 'success' && resultado.data && selectEjercicio) {
                    selectEjercicio.innerHTML = '<option value="" selected disabled>Selecciona ejercicio</option>';

                    resultado.data.forEach(item => {
                        const opt = document.createElement("option");
                        opt.value = item.nombre_ejercicio;
                        opt.textContent = item.nombre_ejercicio;
                        selectEjercicio.appendChild(opt);
                    });

                    const optNuevo = document.createElement("option");
                    optNuevo.value = "nuevo";
                    optNuevo.textContent = "--- Agregar nuevo ejercicio ---";
                    selectEjercicio.appendChild(optNuevo);
                }

                return fetch(`../PHP-Code/obtener_rutina_detalles.php?id_rutina=${idRutina}`);
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.ejercicios) {
                    if (data.rutina) {
                        const labelRutina = document.querySelector(".rutina-actual");
                        if (labelRutina) labelRutina.textContent = `Rutina: ${data.rutina.nombre_rutina}`;
                    }

                    const ejercicio = data.ejercicios.find(ex => ex.id_detalle_rutina === idDetalleRutina);
                    if (ejercicio) {
                        if (selectEjercicio) {
                            let existeEnSelect = Array.from(selectEjercicio.options).some(opt => opt.value === ejercicio.nombre_ejercicio);
                            if (existeEnSelect) {
                                selectEjercicio.value = ejercicio.nombre_ejercicio;
                            } else {
                                selectEjercicio.value = "nuevo";
                            }
                        }

                        if (inputNombreEjercicio) {
                            inputNombreEjercicio.value = ejercicio.nombre_ejercicio;
                            inputNombreEjercicio.required = true;
                        }
                        if (grupoNuevoEjercicio) {
                            grupoNuevoEjercicio.style.display = "block";
                        }

                        document.getElementById("series").value = ejercicio.series;
                        document.getElementById("repeticiones").value = ejercicio.repeticiones;
                        document.getElementById("peso_sugerido").value = ejercicio.peso_sugerido;
                        document.getElementById("tiempo_descanso").value = ejercicio.tiempo_descanso;

                        const checkboxes = document.querySelectorAll('input[name="dia_semana[]"]');
                        checkboxes.forEach(chk => {
                            chk.checked = (chk.value.toLowerCase() === ejercicio.dia_semana.toLowerCase());
                        });

                       
                        if (ejercicio.video_url && ejercicio.video_url.trim() !== "") {
                            
                            if (ejercicio.video_url.includes("Recursos/res_rutina")) {
                                selectEvidencia.value = "Video_disp";
                            } else {
                                selectEvidencia.value = "URL";
                                if (inputUrl) inputUrl.value = ejercicio.video_url;
                            }
                        } else {
                            selectEvidencia.value = "aun_no";
                        }
                        actualizarCamposEvidencia();
                    }
                }
            })
            .catch(err => console.error("Error al cargar los datos:", err));
    }

    if (selectEjercicio) {
        selectEjercicio.addEventListener("change", function () {
            if (selectEjercicio.value !== "nuevo" && selectEjercicio.value !== "") {
                if (inputNombreEjercicio) inputNombreEjercicio.value = selectEjercicio.value;
            } else if (selectEjercicio.value === "nuevo") {
                if (inputNombreEjercicio) {
                    inputNombreEjercicio.value = "";
                    inputNombreEjercicio.focus();
                }
            }
        });
    }

    function actualizarCamposEvidencia() {
        if (!selectEvidencia) return;
        const valor = selectEvidencia.value;
        if (grupoUrl) grupoUrl.style.display = "none";
        if (grupoArchivo) grupoArchivo.style.display = "none";

        if (valor === "URL" && grupoUrl) {
            grupoUrl.style.display = "block";
        } else if (valor === "Video_disp" && grupoArchivo) {
            grupoArchivo.style.display = "block";
        }
    }

    if (selectEvidencia) {
        selectEvidencia.addEventListener("change", actualizarCamposEvidencia);
    }

   
    if (formEjercicio) {
        formEjercicio.addEventListener("submit", function (e) {
            const tipoEvidencia = selectEvidencia.value;
            if (tipoEvidencia === "URL" && inputUrl && inputUrl.value.trim() === "") {
                e.preventDefault();
                alert("Por favor, introduce la URL del video.");
                inputUrl.focus();
            }
        });
    }

    inicializarEdicion();
});