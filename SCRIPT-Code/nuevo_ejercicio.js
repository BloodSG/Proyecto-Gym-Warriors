document.addEventListener("DOMContentLoaded", function () {
    const selectEvidencia = document.getElementById("opc_evidencia");
    const grupoUrl = document.getElementById("grupo_url");
    const grupoArchivo = document.getElementById("grupo_archivo");
    const formRutina = document.getElementById("form_ejercicio");
    const inputUrl = document.getElementById("video_url");
    const inputArchivo = document.getElementById("video_archivo");
    const selectEjercicio = document.getElementById("id_ejercicio");
    const grupoNuevoEjercicio = document.getElementById("grupo_nuevo_ejercicio");
    const inputNuevoEjercicio = document.getElementById("nombre_ejercicio");

    //Capturar el id_rutina de la URL y asignarlo al input oculto y al botón cancelar
    const urlParams = new URLSearchParams(window.location.search);
    const idRutina = urlParams.get('id_rutina');
    
    if (idRutina) {
        const inputIdRutina = document.getElementById("id_rutina_input");
        if (inputIdRutina) {
            inputIdRutina.value = idRutina;
        }

        const linkCancelar = document.querySelector(".acciones-form .btn-sec");
        if (linkCancelar) {
            linkCancelar.href = `nueva_rutina.html?id_rutina=${idRutina}#ejercicios`;
        }
    }

    if (idRutina) {
        fetch(`../PHP-Code/obtener_rutina_detalles.php?id_rutina=${idRutina}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.rutina) {
                    const labelRutinaActual = document.getElementById("nombre_rutina_actual");
                    if (labelRutinaActual) {
                        labelRutinaActual.textContent = `Rutina: ${data.rutina.nombre_rutina}`;
                    }
                }
            })
            .catch(err => console.error("Error al cargar el nombre de la rutina:", err));
    }

    //cargo los ejercicios que esten en la base de datos
    if (selectEjercicio) {
        fetch('../PHP-Code/obtener_ejercicios.php')
            .then(res => res.json())
            .then(resultado => {
                if (resultado.status === 'success' && resultado.data) {
                    
                    selectEjercicio.innerHTML = '<option value="" selected disabled>Selecciona ejercicio</option>';

                    //Inserto cada ejercicio único de la base de datos
                    resultado.data.forEach(item => {
                        const opt = document.createElement("option");
                        opt.value = item.nombre_ejercicio;
                        opt.textContent = item.nombre_ejercicio;
                        selectEjercicio.appendChild(opt);
                    });

                    //pongo la opcion de agregar ejercicio nuevo
                    const optNuevo = document.createElement("option");
                    optNuevo.value = "nuevo";
                    optNuevo.textContent = "--- Agregar nuevo ejercicio ---";
                    selectEjercicio.appendChild(optNuevo);
                }
            })
            .catch(err => console.error("Error al cargar los ejercicios:", err));
    }

    //se muestra o oculta el campo del nombre del nuevo ejercicio segun que se selecciono en la lista desplegable
    function actualizarGrupoNuevo() {
        if (!selectEjercicio || !grupoNuevoEjercicio) return;
        if (selectEjercicio.value === "nuevo") {
            grupoNuevoEjercicio.style.display = "block";
            if (inputNuevoEjercicio) inputNuevoEjercicio.required = true;
        } else {
            grupoNuevoEjercicio.style.display = "none";
            if (inputNuevoEjercicio) {
                inputNuevoEjercicio.required = false;
                inputNuevoEjercicio.value = "";
            }
        }
    }

    if (selectEjercicio) {
        selectEjercicio.addEventListener("change", actualizarGrupoNuevo);
        actualizarGrupoNuevo();
    }

    //muestro los campos de la url o el archivo del video segun lo que se seleccione en la lista desplegable
    function actualizarCampos() {
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
        actualizarCampos();
        selectEvidencia.addEventListener("change", actualizarCampos);
    }

    //se validad los datos antes de enviar a base de datos para que no este vacios los videos
    if (formRutina) {
        formRutina.addEventListener("submit", function (e) {
            if (selectEjercicio && selectEjercicio.value === "") {
                e.preventDefault();
                alert("Por favor, selecciona un ejercicio o elige agregar uno nuevo.");
                selectEjercicio.focus();
                return;
            }

            if (selectEjercicio && selectEjercicio.value === "nuevo" && inputNuevoEjercicio && inputNuevoEjercicio.value.trim() === "") {
                e.preventDefault();
                alert("Por favor, escribe el nombre del nuevo ejercicio.");
                inputNuevoEjercicio.focus();
                return;
            }

            if (!selectEvidencia) return;
            const valor = selectEvidencia.value;

            if (valor === "URL" && inputUrl && inputUrl.value.trim() === "") {
                e.preventDefault();
                alert("Por favor, introduce la URL del video.");
                inputUrl.focus();
            } else if (valor === "Video_disp" && inputArchivo && inputArchivo.files.length === 0) {
                e.preventDefault();
                alert("Por favor, selecciona un archivo de video desde tu dispositivo.");
                inputArchivo.focus();
            }
        });
    }
});