import {subeComprobante} from "./api.js";
import {verificaSesion} from "./autorizacion.js";
function cargaVistaPrevia(respuesta,inputComprobante,placeholder,contenedorImagen,contenedorPdf)
{
    if(respuesta.status=="success")
    {   
        console.log("hola qu ehace")
        placeholder.hidden=false;
        contenedorImagen.hidden=true;
        contenedorPdf.hidden=true;
        contenedorImagen.src="";
        inputComprobante.value="";
        return;
    }
}

const usuario= await verificaSesion();
console.log(usuario)

const inputComprobante=document.getElementById("comprobante");
const contenedorVistaprevia=document.getElementById("previe-pdf");
const contenedorImagen=document.getElementById("preview-image");
const contenedorPdf=document.getElementById("previe-pdf");
const nombrePdf=document.getElementById("pdf-name");
const placeholder=document.querySelector(".preview-placeholder")

document.getElementById("form-pago").addEventListener("submit", async (e) =>
{   
    e.preventDefault();
    e.target.disabled=true;
    try 
    {
        const respuesta= await subeComprobante(inputComprobante);
        alert(respuesta.mensaje)

        if(respuesta.status=="success")
        {   
            cargaVistaPrevia(respuesta,inputComprobante,placeholder,contenedorImagen,contenedorPdf)
        }
    } 
    catch (error) 
    {
        alert("Ha ocurrido un error inesperado")
    }
    finally
    {
        e.target.disabled=false;
    }
    
})
inputComprobante.addEventListener("change", async (e) => //change permite detectar un cambio, en este caso para ver si se subio un archivo
{
    
    const archivo=e.target.files[0];// file[0] es para que solo tome un archivo en caso de que suba varios
    
    if (!archivo) {
        placeholder.hidden=false;
        contenedorImagen.hidden=true;
        contenedorPdf.hidden=true;
        contenedorImagen.src="";
        return;
    }
    placeholder.hidden=true;

    if(archivo.type.startsWith("image/"))
    {
        const urlTemporal=URL.createObjectURL(archivo);
        contenedorImagen.src=urlTemporal;
        contenedorImagen.hidden=false;
        contenedorPdf.hidden=true;
    }
    else if(archivo.type == "application/pdf")
    {   
        const urlTemporal=URL.createObjectURL(archivo) + "#toolbar=0&navpanes=0&scrollbar=0";
        /*
        URL.createObjectURL(archivo) recibe un objeto File o Blob (archivo) almacenado en la memoria del cliente 
        y genera una URL temporal local (con formato blob:http://...), esto permite leer el archivo como si estuviera 
        alojado en una ruta web, sin necesidad de enviarlo al back

        + "#toolbar=0&navpanes=0&scrollbar=0" añade parametros de configuración (PDF Open Parameters) al final de la URL para limpiar la interfaz del visor:

        toolbar=0: Oculta la barra superior (descargar, imprimir, zoom, rotar)
        navpanes=0: Oculta los paneles laterales (miniaturas de páginas e índice)
        scrollbar=0: Solicita ocultar las barras de desplazamiento
        */
        document.getElementById("pdf-embed").src=urlTemporal;

        /*
        document.getElementById("pdf-embed") obtiene la referencia al nodo <embed> en el DOM
        .src = urlTemporal asigna la URL temporal al atributo src, provocando que el navegador renderice el PDF al instante
        */
        nombrePdf.textContent=archivo.name;
        contenedorImagen.src="";
        contenedorImagen.hidden=true;
        contenedorPdf.hidden=false;
    }
})
