const consulta="../PHP-Code/Productos.php";
// este archivo funciona de api, es decir, solo es el intermediario entre vase de datos y front


export async function obtenerCatalogo(categoria) 
//export es por asi deciro la forma de hacer que otros archivos puedan usar la funcion
// async es para que la funcion pueda usar await, es por asi decirlo como el atributo de una funcion
{   
    const url=`${consulta}?categoria=${categoria}`;
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