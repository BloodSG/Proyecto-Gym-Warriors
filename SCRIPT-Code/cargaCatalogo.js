
// Hacemos la petición HTTP a tu archivo PHP (la API)
fetch("../PHP-Code/Productos.php")// funcion que practicamente hace esto: Ve a buscar información al archivo Productos.php
    .then(response => response.json()) //Esta función toma eld texto plano y lo convierte en un objeto de JavaScrip
    .then(resultado => //el .then significa: cuando el servidor PHP te responda, ejecuta esta funcion
    /* resultado: es el objeto que se formo  en PHP (["status" => "success", "mensaje" => "...", "data" => $Productos])
    ahora ya puedes usar sus propiedades */

    {
        const contenedor = document.getElementById("catalogo");
        /*const contenedor = document.getElementById("catalogo");
        buscamos en el documento HTML el div que etiquetamos con el id catalogo y 
        lo guarda en una constante llamada contenedor para poder modificarlo desde el codigo */
        
        // Verificamos que el estatus sea exitoso
        if (resultado.status === "success") {
            
            contenedor.innerHTML = ""; //limipia el cargando productos del div anterior

            // Recorremos la lista de productos que vienen en el JSON
            resultado.data.forEach(producto => 
            {
                const tarjeta = document.createElement("div");
                
                // se hace la vista html de cada tarjeta usando los datos del json
                tarjeta.innerHTML = `
                    <h3>${producto.nombre}</h3>
                    <p>${producto.nombre_categoria}</p>
                    <div class="precio">$${Number(producto.precio_venta).toFixed(2)}</div>
                    <a href="../PHP-Code/detalleProducto.php ?id=${producto.id_producto}">Ver Producto</a>
                `;
                
                contenedor.appendChild(tarjeta);
            });
        } else {
            contenedor.innerHTML = `<p>${resultado.mensaje}</p>`;
        }
    })
    .catch(error => {
        console.error("Error al consumir la API:", error);
        document.getElementById("catalogo").innerHTML = "<p>Hubo un error al cargar el catálogo.</p>";
    });
