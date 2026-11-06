import {cargaRutinaDia,cargaListaEjercicios} from "./api.js";

async function cambiaDia()
{
    const botones=document.getElementsByClassName("tab-btn")
    for(const boton of botones)
    {
        boton.addEventListener("click", async (e)=>
        {
            for(const otroBoton of botones)
            {
                if(otroBoton!==e.target && otroBoton.classList.contains("active"))
                {
                    otroBoton.classList.remove("active");
                }
            }
            const dia=e.target.dataset.dia;
            e.target.classList.add("active");
            await cargaDia();
        })
    }
}

async function cargaDia()
{
    const contenedorTitulo=document.getElementById("Nombre_rutina");
    const contenedorEjercicios=document.getElementById("contenedor_ejercicios");
  
    contenedorTitulo.innerHTML="Animacion de carga 💫";
    contenedorEjercicios.innerHTML="<p> Animacion de carga 💫</p>";

    const dia=document.getElementById("semanita").querySelector(".active").dataset.dia;
    console.log("dia seleccionado: "+dia);

    const rutina= await cargaRutinaDia(dia);
    if(rutina.status != "success")
    {
        contenedorTitulo.innerHTML= `<h2>No hay rutina para este dia</h2>`;
        return;
    }
    const data=rutina.data;
    contenedorTitulo.innerHTML= `<h2>${data.nombre_rutina}</h2>`;

    console.log(data.id_rutina);
    const ejercicios= await cargaListaEjercicios(data.id_rutina);
    if(ejercicios.status != "success")
    {
        contenedorEjercicios.innerHTML="<p> Aun no hay ejercicios para esta rutina </p>";
    }
    let lista_html=``;
    console.log(ejercicios);
    ejercicios.data.forEach(eyersisio => {
        lista_html+=`<article class="ejercicio-card">
                            <div class="ejercicio-info">
                                <h3>${eyersisio.orden}. ${eyersisio.nombre_ejercicio}</h3>
                                <div class="ejercicio-detalle">
                                    <span><strong>Series: </strong> ${eyersisio.series}</span>
                                    <span><strong>Reps: </strong> ${eyersisio.repeticiones}</span>
                                    <span><strong>Descanso: </strong> ${eyersisio.tiempo_descanso} seg</span>
                                </div>
                                <p class="ejercicio-tip"><strong>Peso sugeriodo: </strong> ${eyersisio.peso_sugerido}</p>
                            </div>
                            <div class="ejercicio-video" id="video_ejercicio">
                                <video class="video-local" controls>
                                    <source src="../Recursos/res_rutina/603bb672a3c13c4b1bf375ba64f747f8.mp4" type="video/mp4">
                                    Tu navegador no soporta la reproducción de video.
                                </video>
                            </div>
                        </article>`;
    });
    contenedorEjercicios.innerHTML=lista_html;
    return; 
}


cambiaDia().then(cargaDia);
