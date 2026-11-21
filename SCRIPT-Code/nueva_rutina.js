document.addEventListener("DOMContentLoaded", function () {
    //agarro la id de la rutina desde la url
    const urlParams = new URLSearchParams(window.location.search);
    const idRutina = urlParams.get('id_rutina');
    const modal = document.querySelector(".modal");

    if (idRutina) {
        
        if (modal) {
            modal.style.display = "none";
        }

        //actualizo el enlace para agregar ejercicios
        const btnAgregar = document.querySelector(".pie-lista .btn-sec");
        if (btnAgregar) {
            btnAgregar.href = `nuevo_ejercicio.html?id_rutina=${idRutina}`;
        }

        //veo que ejercicios estan en la rutina y las pongo en la tabla
        fetch(`../PHP-Code/obtener_rutina_detalles.php?id_rutina=${idRutina}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    //pongo los ejercicios
                    const labelRutina = document.getElementById("nombre_rutina_display");
                    if (labelRutina && data.rutina) {
                        labelRutina.textContent = `Rutina: ${data.rutina.nombre_rutina}`;
                    }

                    //Llenar la tabla de ejercicios
                    const tbody = document.querySelector("tbody");
                    if (tbody) {
                        tbody.innerHTML = "";
                        if (data.ejercicios.length === 0) {
                            tbody.innerHTML = `<tr class="fila-vacia"><td class="vacio" colspan="7">Aún no hay ejercicios</td></tr>`;
                        } else {
                            data.ejercicios.forEach(ex => {
                                const videoHtml = ex.video_url 
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
                                    </tr>
                                `;
                            });
                        }
                    }
                }
            })
            .catch(err => console.error("Error al conectar con la API:", err));
    }

    //opciones de video ya sea url o desde el dispositivo
    const selectEvidencia = document.getElementById("opc_evidencia");
    const grupoUrl = document.getElementById("grupo_url");
    const grupoArchivo = document.getElementById("grupo_archivo");
    const formRutina = document.getElementById("form_rutina");
    const inputUrl = document.getElementById("video_url");
    const inputArchivo = document.getElementById("video_archivo");

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
        formRutina.addEventListener("submit", function (e) {
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