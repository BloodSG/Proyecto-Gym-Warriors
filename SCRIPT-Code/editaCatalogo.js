import {obtenerCatalogo,obtenerDataProducto} from "./api.js";

function mostrarInventario(inventario)
{   
    const contenedor=document.getElementById("tabla-contenedor");
    contenedor.innerHTML="";
    if (inventario.status !== "success")
    {
        contenedor.innerHTML =
        `<p>${inventario.mensaje}</p>`;// se pueden poner mensajes de error en el json y se pueden mostrar en el front
        return;
    }
    inventario.data.forEach(producto => 
    {
        let tupla=document.createElement("tr")
        tupla.innerHTML=`<td>${producto.id_producto}</td>
                            <td>${producto.nombre}</td>
                            <td>$${producto.precio_venta}</td>
                            <td class="text-stock">${producto.stock}</td>
                            <td class="text-center">
                                <a class="btn-accion" data-id="${producto.id_producto}" 
                                href="#modalEditarOverlay" type="button">Editar</a>
                            </td>`;
        contenedor.append(tupla)
    });
}

function mostraEditar(producto)    
{   
    const modalOverlay = document.getElementById("modalEditarOverlay");
    const contenedor=document.getElementById("modalEditar")

    if (producto.status !== "success")
    {
        contenedor.innerHTML =
        `<p>${producto.mensaje}</p>`;
        return;
    }
    const Data=producto.data;
    contenedor.innerHTML=`
            <h2 class="modal-titulo">Editar Producto</h2>
            
            <form class="form-admin form-admin-sin-margen">
                <label>
                    Nombre del Producto:
                    <input type="text" name="nombre" value="${Data.nombre}" required>
                </label>

                <label>
                    Categoría:
                    <select required>
                        <option value="${Data.nombre_categoria}"selected>${Data.nombre_categoria}</option>
                        <option value="2">Suplementos</option>
                        <option value="3">Accesorios</option>
                    </select>
                </label>

                <label>
                    Precio de compra ($):
                    <input type="number" name="precio_compra" value="${Data.precio_compra}" step="0.01" required>
                </label>

                <label>
                    Precio de Venta ($):
                    <input type="number" name="precio_venta" value="${Data.precio_venta}" step="0.01" required>
                </label>

                <label>
                    Stock:
                    <input type="number" name="stock"  value="${Data.stock}" min="0" required>
                </label>

                <div class="mt-15">
                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn-secundario" id="btnCancelar">Cancelar</button>
                </div>
            </form>`;
    modalOverlay.classList.add("abierto");
    document.getElementById("btnCancelar").addEventListener("click", () => {
    cerrarModalEditar(modalOverlay, contenedor);
    });
}
function cerrarModalEditar(modalOverlay,contenedor) {
    modalOverlay.classList.remove("abierto");
    contenedor.innerHTML = ""; // Opcional: limpia el contenido al cerrar para que no se quede guardado basura
}


function editar(id)
{
    const contenedor=document.getElementById("formulario-producto");
    const datos=new FormData(contenedor)
    const nombre=datos.get("nombre")
    console.log("nombre: ",nombre)
}


document.getElementById("tabla-contenedor").addEventListener("click", async (e) => 
{
// document.getElementById es para buscar x cosa en el contenedor que tenga el id catalogo
//addEventListener es para que cuando se haga click en el contenedor catalogo, se ejecute la fucnion e
// async(e) es para poder usar el await, y e es el evento 

    
    const boton=e.target.closest(".btn-accion");
    //e.target es para saber que elemento hizo click y closest es para buscar el elemento mas cercano que tenga la clase del boton
    //.btn-card-accion es la clase del boton que hace click, es decir, el boton de ber detalles
    if (!boton) return;
    console.log("click editar producto",e.target);//esto muestra un mensaje en consola, solo para ver si funciona al chingadera esta
    const id=boton.dataset.id;// el boton que obtenenos tiene un id, y se lo sacamos con .dataset.id
    console.log("id: ",id);
    try 
    {
        const producto=await obtenerDataProducto(id);
        mostraEditar(producto);
    } 
    catch (error) 
    {
        console.error("Error al obtener datos del producot para editar:", error);
    }
});

obtenerCatalogo("").then(mostrarInventario)
