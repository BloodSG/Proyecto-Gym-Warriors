import {obtenerCatalogo,obtenerDataProducto,cargaCategorias,eliminaProducto,editaProducto} from "./api.js";

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
        console.log(producto.id_producto)
        let tupla=document.createElement("tr")
        tupla.innerHTML=`<td>${producto.id_producto}</td>
                            <td>${producto.nombre}</td>
                            <td>$${producto.precio_venta}</td>
                            <td class="text-stock">${producto.stock}</td>
                            <td class="text-center">
                                <button class="btn-accion" id="Editar" data-id="${producto.id_producto}" 
                                href="#" type="button">Editar</button>

                                <button class="btn-accion" id="Eliminar" data-id="${producto.id_producto}" 
                                href="#" type="button">Eliminar</button>
                            </td>`;
        contenedor.append(tupla)
    });
}

async function mostraEditar(producto)    
{   
    const modalOverlay=document.getElementById("modalEditarOverlay");
    const contenedor=document.getElementById("modalEditar")

    contenedor.innerHTML=``;

    if (producto.status !== "success")
    {
        contenedor.innerHTML =
        `<p>${producto.mensaje}</p>`;
        return;
    }

    const Data=producto.data;
    const categorias=await creaListaCat(Data.nombre_categoria);


    contenedor.innerHTML=`
            <h2 class="modal-titulo">Editar Producto</h2>
            
            <form class="form-admin form-admin-sin-margen" id="form-editar">
                <label>
                    Nombre del Producto *:
                    <input type="text" name="nombre" value="${Data.nombre}" required>
                </label>

                <label>
                    Categoría *:
                    <select required name="categoria">
                        ${categorias}
                    </select>
                </label>
                
                <label>
                    Descripción del producto:
                    <input type="text" name="descripcion" value="${Data.descripcion}">
                </label>

                <label>
                    Precio de compra ($) *:
                    <input type="number" name="precio_compra" value="${Data.precio_compra}" step="0.01" required>
                </label>

                <label>
                    Precio de Venta ($) *:
                    <input type="number" name="precio_venta" value="${Data.precio_venta}" step="0.01" required>
                </label>

                <label>
                    Stock *:
                    <input type="number" name="stock"  value="${Data.stock}" min="0" required>
                </label>
                
                 <div class="fila-inputs-img">
                    <label>
                        url de la imagen
                        <input type="text" id="product-imagen-archivo" value="${Data.url}" name="imagen_url" >
                    </label>

                    <label>
                        seleccionar desde dispositivo
                        <input type="file" id="product-imagen-url" name="imagen_archivo" accept="image/*">
                    </label>
                </div>

                <div class="mt-15">
                    <button type="submit" class="btn-primary" id="btnGuardar" >Guardar Cambios</button>
                    <button type="button" class="btn-secundario" id="btnCancelar">Cancelar</button>
                </div>
            </form>`;

    
    modalOverlay.classList.add("abierto");
    document.getElementById("btnCancelar").addEventListener("click", () => 
    {
    cerrarModalEditar(modalOverlay, contenedor);
    });

    document.getElementById("form-editar").addEventListener("submit", (e) => 
    {
        e.preventDefault();
        editar(Data.id_producto);
    });
}
function cerrarModalEditar(modalOverlay,contenedor) 
{
    modalOverlay.classList.remove("abierto");
    contenedor.innerHTML=""; 
}


async function editar(id)
{   
    const formOverlay=document.getElementById('form-editar'); 
    const formData=new FormData(formOverlay);
    try 
    {
        const resultado=await editaProducto(id, formData);
        alert(resultado.mensaje);
        
        if(resultado.status === "success") 
        {
            cerrarModalEditar(document.getElementById("modalEditarOverlay"), document.getElementById("modalEditar"));
            obtenerCatalogo("").then(mostrarInventario); 
        }
    } 
    catch (error) 
    {
        console.error(error);
        alert("Ocurrió un error al procesar la petición.");
    }
}

async function eliminar(id)
{
    try 
    {
        const respuesta=await eliminaProducto(id);
        
        if (respuesta && respuesta.mensaje) 
        {
            alert(respuesta.mensaje);
        } 
        else 
        {
            alert("Producto procesado correctamente.");
        }
        obtenerCatalogo("").then(mostrarInventario);
    } 
    catch (error) 
    {
        console.error("Error en la petición:", error);
        alert("Hubo un error de conexión con el servidor.");
    }

}

async function creaListaCat(origen) 
{   
    
    const categorias=await cargaCategorias();// tenemos que llamar la funcion asi para que espere el resultado y no se rompa
    const listaCategorias=categorias.data;
    const opcionesCat=listaCategorias.map(cat => //aqui hacemos la lista de opciones de categoria, me costo un huebo y la mitad del otro hacer esto
        `<option value="${cat.nombre_categoria}" ${cat.nombre_categoria === origen ? 'selected' : ''}> 
        ${cat.nombre_categoria}
        </option>`
        ).join('');
    return opcionesCat;
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
    const idProducto=boton.dataset.id;
    const tipoAccion=boton.id;// el boton que obtenenos tiene un id, y se lo sacamos con .dataset.id
    console.log("se aplico el id: ",idProducto," ",tipoAccion);

    if(tipoAccion==="Editar")
    {   
        try
        {
        const producto=await obtenerDataProducto(idProducto);
        
        console.log("producto a mostrar: ",producto);
        mostraEditar(producto);
        }
        catch (error) 
        {
            console.error("Error al obtener datos del producot para editar:", error);
        }
    }
    else if(tipoAccion==="Eliminar")
    {
        const seguro=confirm("¿Estas seguro de que deseas eliminar este producto?");
        if (!seguro) 
        {
            return; 
        }
        
        // 3. Si confirma, procedemos con la petición para eliminar
        console.log("Procediendo a eliminar el producto con ID:", idProducto);
        eliminar(idProducto);
    }
    
});

obtenerCatalogo("").then(mostrarInventario)
const listaCat=document.getElementById("selectCategoriaRegistro")
listaCat.innerHTML=``
creaListaCat('').then(respuesta =>
    {
        listaCat.innerHTML=respuesta;
    }
);

