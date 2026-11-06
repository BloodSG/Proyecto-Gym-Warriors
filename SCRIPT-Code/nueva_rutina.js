
//debido a que el formulario tiene dos opciones de evidencia
//ya sea url o video del dispositivo
//este script se encarga de mostrar y ocultar los campos correspondientes según la opción seleccionada.
//tambien valida que se haya ingresado la información necesaria antes de enviar el formulario.
document.addEventListener("DOMContentLoaded", function () {
    const selectEvidencia = document.getElementById("opc_evidencia");
    const grupoUrl = document.getElementById("grupo_url");
    const grupoArchivo = document.getElementById("grupo_archivo");
    const formRutina = document.getElementById("form_rutina");
    const inputUrl = document.getElementById("video_url");
    const inputArchivo = document.getElementById("video_archivo");

    function actualizarCampos() {
        const valor = selectEvidencia.value;

        //oculto los campor del url y el archivo
        grupoUrl.style.display = "none";
        grupoArchivo.style.display = "none";

        //detecta cual selecciono el usuario y muestra solo ese campo
        if (valor === "URL") {
            grupoUrl.style.display = "block";
        } else if (valor === "Video_disp") {
            grupoArchivo.style.display = "block";
        }
    }

    // Ejecutar al cargar la página
    actualizarCampos();
    selectEvidencia.addEventListener("change", actualizarCampos);

    //se checa si no hay nuingin campo vacio
    formRutina.addEventListener("submit", function (e) {
        const valor = selectEvidencia.value;

        if (valor === "URL" && inputUrl.value.trim() === "") {
            e.preventDefault();
            alert("Por favor, introduce la URL del video.");
            inputUrl.focus();
        } else if (valor === "Video_disp" && inputArchivo.files.length === 0) {
            e.preventDefault();
            alert("Por favor, selecciona un archivo de video desde tu dispositivo.");
            inputArchivo.focus();
        }
    });
});