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

    //agarro de la url el id de la rutina y de que ventana vengo si la de crear o la de editar para que me pueda rederigir
    const urlParams = new URLSearchParams(window.location.search);
    const idRutina = urlParams.get('id_rutina');
    const origen = urlParams.get('origen'); 
    
    if (idRutina) {
        const inputIdRutina = document.getElementById("id_rutina_input");
        if (inputIdRutina) {
            inputIdRutina.value = idRutina;
        }

        const linkCancelar = document.querySelector(".acciones-form .btn-sec");
        if (linkCancelar) {
            if (origen === 'editar') {
                linkCancelar.href = `editar_rutina.html?id_rutina=${idRutina}`;
            } else {
                linkCancelar.href = `nueva_rutina.html?id_rutina=${idRutina}#ejercicios`;
            }
        }
    }

    //guardo el origen de la verntana de donde vengo para asi redirigirme a esa ventana
    if (formRutina) {
        let inputOrigen = document.getElementById("origen_input");
        if (!inputOrigen) {
            inputOrigen = document.createElement("input");
            inputOrigen.type = "hidden";
            inputOrigen.name = "origen";
            inputOrigen.id = "origen_input";
            formRutina.appendChild(inputOrigen);
        }
        inputOrigen.value = origen || ''; 
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

    //agarro todos los ejercicios que estan en la base de datos y los muestro en la lista desplegable
    if (selectEjercicio) {
        fetch('../PHP-Code/obtener_ejercicios.php')
            .then(res => res.json())
            .then(resultado => {
                if (resultado.status === 'success' && resultado.data) {
                    selectEjercicio.innerHTML = '<option value="" selected disabled>Selecciona el ejercicio</option>';

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
            })
            .catch(err => console.error("Error al cargar los ejercicios:", err));
    }
    //aqui es la funcion de la lista desplegable si eliges crear un nuevo ejercicio te muestra el campo para nombrar ese ejercicio si no pues lo oculta
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
    // se muestra el campo de el video segun que eligio en la lista desplegable ya sea url o desde el dispositivo
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

    if (formRutina) {
        //todo esto son las adbertencias que existe en esta ventana
        formRutina.addEventListener("submit", function (e) {
            if (selectEjercicio && selectEjercicio.value === "") {
                e.preventDefault();
                alert("Por favor, selecciona un ejercicio o elige agregar uno nuevo.");
                selectEjercicio.focus();
                return;
            }

            if (selectEjercicio && selectEjercicio.value === "nuevo" && inputNuevoEjercicio && inputNuevoEjercicio.value.trim() === "") {
                e.preventDefault();
                alert("Escribe el nombre del nuevo ejercicio.");
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