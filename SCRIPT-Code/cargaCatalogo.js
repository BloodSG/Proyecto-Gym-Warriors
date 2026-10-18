import { obtenerCatalogo, obtenerProducto, bucarProducto } from "./api.js"; //asi  se importan varias funciones de un archivo

function mostrarCatalogo(catalogo) 
{
    
    const contenedor=document.getElementById("catalogo");
    
    contenedor.innerHTML=""; // limpiar antes de poner cosas

    if (catalogo.status !== "success") //catalogo es el json que devuelve la funcion obtenerCatalogo, y status es una propiedad de ese json
    {
        contenedor.innerHTML =
        `<p>${catalogo.mensaje}</p>`;// se pueden poner mensajes de error en el json y se pueden mostrar en el front
        return;
    }

    catalogo.data.forEach(producto => 
    {
        const tarjeta=document.createElement("div");
        tarjeta.className="producto-card";

        // a partir de aqui reutilice el codigo de kenia para mostrar los productos, pero lo adate para que sea dinamico
        tarjeta.innerHTML=`
            <div>
                <img src="../${producto.url}">
                <span class="producto-categoria-tag">${producto.nombre_categoria}</span>
                <h3>${producto.nombre}</h3>
            </div>
            <div>
                <p class="precio">$${producto.precio_venta}</p>
                <span class="stock-info">Disponibles: ${producto.stock}</span>
                <a href="#modalDetalle3" class="btn-primary btn-card-accion" type="button"
                   data-id="${producto.id_producto}">Ver Detalles</a>         
            </div>
        `;
        console.log("Producto renderizado:",producto.id_producto)
        contenedor.appendChild(tarjeta);
    });
}

function vistaDetalleProducto(detalleProd) 
{
    const contenedor=document.getElementById("contenidomodaldetalle");
    contenedor.innerHTML=""; 
    console.log("Resultado detalle:", detalleProd);
    if (detalleProd.status !== "success") 
    {
        contenedor.innerHTML=
        `<p>${detalleProd.mensaje}</p>`;
        return;
    }

    const producto=detalleProd.data;
    contenedor.innerHTML=
    `
        <a href="#" class="btn-cerrar-modal">&times;</a>
        <img src="../${producto.url}" class=detalle-imagen>
            
            <div class="detalle-info">
                <span class="detalle-categoria">${producto.nombre_categoria}</span>
                <h2 class="detalle-titulo">${producto.nombre}</h2>
                <p class="detalle-descripcion">
                    ${producto.descripcion}
                </p>
                <div 
                    class="detalle-precio">$${producto.precio_venta}
                </div>

                <span class="stock-info detalle-stock">Unidades disponible: ${producto.stock}</span>
                <div class="mt-10">
                    <button type="button" class="btn-primary">Añadir al Carrito</button>
                </div>
            </div>
    `;
}

document.getElementById("Vuscar").addEventListener("input",async (e)=>
{
    let entrada_busqueda=e.target.value;
    console.log(entrada_busqueda)
    if (entrada_busqueda!=="")
    {
    bucarProducto(entrada_busqueda).then(mostrarCatalogo)
    }
    else
    {
        obtenerCatalogo(categoria).then(mostrarCatalogo)
    }
})

document.getElementById("catalogo").addEventListener("click", async (e) => 
{
// document.getElementById es para buscar x cosa en el contenedor que tenga el id catalogo
//addEventListener es para que cuando se haga click en el contenedor catalogo, se ejecute la fucnion e
// async(e) es para poder usar el await, y e es el evento 

    
    const boton=e.target.closest(".btn-card-accion");
    //e.target es para saber que elemento hizo click y closest es para buscar el elemento mas cercano que tenga la clase del boton
    //.btn-card-accion es la clase del boton que hace click, es decir, el boton de ber detalles
    if (!boton) return;
    console.log("click catalogo",e.target);//esto muestra un mensaje en consola, solo para ver si funciona al chingadera esta
    const id=boton.dataset.id;// el boton que obtenenos tiene un id, y se lo sacamos con .dataset.id
    console.log("id: ",id);
    const contenedor=document.getElementById("contenidomodaldetalle");
    contenedor.innerHTML=`<p class="text-center">Cargando producto :D </p>`; 
    try 
    {
        const detalle=await obtenerProducto(id);

        vistaDetalleProducto(detalle);
    } 
    catch (error) 
    {
        console.error("Error al cargar el detalle:", error);
    }
});

//A partir de aqui se ejecuta el codigo como tal, es decir, este es el inicio que desencadena la ejecucion de las fucniones
const selectcategoria=document.getElementById("filtro-categoria");
selectcategoria.addEventListener("change", async (e) =>
{
    let categoria=selectcategoria.value;
    console.log("categoria", categoria);
    obtenerCatalogo(categoria).then(mostrarCatalogo)
})

let categoria=selectcategoria.value;
console.log("categoria", categoria);
obtenerCatalogo(categoria).then(mostrarCatalogo)// quedamos que then es para que cuande termine la consulta, se ejecute la funcion mostar catalogo
    .catch(error => 
    {
        console.error("Error al ejecutar API: ", error);
        document.getElementById("catalogo").innerHTML =
            "<p>Hubo un error al cargar el catálogo.</p>";
    });
