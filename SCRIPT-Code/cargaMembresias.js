import {cargaListaMembresias} from "./api.js"

function mostrarMembresias(membresias,contenedor)
{   
    contenedor.innerHTML=``;
    let htmlcontenedor=``;
    const lista=membresias.data;
    lista.forEach(membre => {
        let htmltarjeta=``;
       if (membre.incluye_rutina)
       {
            htmltarjeta+=`
            <li class="no">Rutina personalizada</li>`
       }
       if (membre.incluye_dieta)
       {
            htmltarjeta+=`
            <li class="no">Dieta y suplementos</li>`
       }
       htmlcontenedor+=`
       <article class="plan-targeta">
        <div class="plan-titulo"><h2>${membre.nombre_tipo}</h2></div>
            <div class="plan-precio"><strong>$${membre.precio}</strong><span> ${membre.duracion_dias} dias.</span></div>
            <ul class="plan-entrevis">
                ${htmltarjeta}
            </ul>
            <p class="plan-desc">${membre.caracteristicas}</p>
        </article>
       `;
    });
    contenedor.innerHTML=htmlcontenedor;
    
}       

const contenedor=document.getElementById("grid_membresias")
contenedor.innerHTML=`
<h1>Cargando membresias :D </h1>
`
try
{
    cargaListaMembresias().then(lista => 
{           
    if (lista.data.length===0)
    {
        contenedor.innerHTML=`<h1>Ninguna membresia disponible por el momento </h1>`
    }
    else if (lista.status === "success" || lista.data.length>0)
    {
        mostrarMembresias(lista,contenedor)
    }
    else
    {
        alert("Hubo un error al cargar las membresias: "+lista.mensaje);
        window.location.href=document.referrer;
    }
    
});
}
catch(error)
{
    console.error(error);
    alert("Ocurrió un error al momento de cargar la lista de membresias");
}
