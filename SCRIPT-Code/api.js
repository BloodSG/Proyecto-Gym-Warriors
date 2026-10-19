const consulta="../PHP-Code/Productos.php";
// este archivo funciona de api, es decir, solo es el intermediario entre vase de datos y front


export async function obtenerCatalogo(categoria) 
//export es por asi deciro la forma de hacer que otros archivos puedan usar la funcion
// async es para que la funcion pueda usar await, es por asi decirlo como el atributo de una funcion
{   
    const url=`${consulta}?action=catalogo&categoria=${categoria}`;
    const catalogo=await fetch(url); //await es para que la funcion espere a que se responda el fetch antes de continuar
    //fetch es para hacer peticiones a un servidor, devuelve un objeto by the way
    

    if (!catalogo.ok)//.ok es una propiedad de la respuesta que indica si la respuesta salio bien 
    {
        throw new Error(`Error HTTP al cargar el catálogo: ${catalogo.status}`);
    }
    return catalogo.json(); //la respuesta (catalogo) deve de llevar .json, porque fetch devuelve un objeto detalle y lo ocupamos devolver en json
}


export async function obtenerProducto(id) {
    // valida antes de gastar una peticion
    if (!id) 
    {
        throw new Error("falto un ID de producto");
    }

    const url=`${consulta}?action=detalle&id=${encodeURIComponent(id)}`;// se hace una url para redirigir el navegador con cierta informacion
    //encodeURIComponent es para que el id no tenga caracteres especiales que puedan romper la url
    const detalle=await fetch(url);

    if (!detalle.ok) {
        throw new Error(`Error HTTP al cargar el detalle: ${detalle.status}`);
    }

    return detalle.json();
}

export async function obtenerDataProducto(id) 
{
    // valida antes de gastar una peticion
    if (!id) 
    {
        throw new Error("falto un ID de producto");
    }

    const url=`${consulta}?action=allProdInfo&id=${encodeURIComponent(id)}`;// se hace una url para redirigir el navegador con cierta informacion
    //encodeURIComponent es para que el id no tenga caracteres especiales que puedan romper la url
    const data=await fetch(url);

    if (!data.ok) {
        throw new Error(`Error HTTP al cargar data: ${data.status}`);
    }

    return data.json();
}

export async function bucarProducto(txt)
{
    if(!txt)
    {
        throw new Error("no hay busqueda");
    }
    const url=`${consulta}?action=vuscar&comando=${txt}`
    const producto=await fetch(url)

    if (!producto.ok) {
        throw new Error(`Error HTTP al cargar la busqueda: ${producto.status}`);
    }

    return producto.json();
}



export async function editaProducto(id,formDataDelFormulario) 
{
    if (!id) 
    {
        throw new Error("falto un ID de producto");
    }
    formDataDelFormulario.append('id_producto', id);
    const url=`${consulta}?action=editar`;
    const respuesta= await fetch(url, 
        {
            method: 'POST', 
            body: formDataDelFormulario 
        });

    if (!respuesta.ok) {
        throw new Error(`Error HTTP al eliminar producto: ${respuesta.status}`);
    }
    return respuesta.json();
}

export async function eliminaProducto(id) 
{   
    if (!id) 
    {
        throw new Error("falto un ID de producto");
    }
    const url=`${consulta}?action=quitar&producto=${encodeURIComponent(id)}`;
    const respuesta=await fetch(url);

    if (!respuesta.ok) {
        throw new Error(`Error HTTP al eliminar producto: ${respuesta.status}`);
    }
    return respuesta.json();
}


export async function cargaCategorias() 
{   
    
    const url=`${consulta}?action=cargar&comando=categoria`;
    const categorias=await fetch(url);
    if (!categorias.ok) {
        throw new Error(`Error HTTP al cargar las categorias: ${categorias.status}`);
        
    }
    return categorias.json();
}

export async function cargaProveedores() 
{   
    
    const url=`${consulta}?action=cargar&comando=proveedores`;
    const proveedores=await fetch(url);
    if (!proveedores.ok) {
        throw new Error(`Error HTTP al cargar los provedores ${proveedores.status}`);
        
    }
    return proveedores.json();
}