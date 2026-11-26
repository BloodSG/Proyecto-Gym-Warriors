document.addEventListener("DOMContentLoaded", cargarVentas);

function cargarVentas(){
    fetch("../PHP-Code/obtener_ventas.php")
    .then(respuesta=>respuesta.json())
    .then(resultado=>{
        const tabla=document.getElementById("ventas-body");
        tabla.innerHTML="";
        if(resultado.estado===false){
            tabla.innerHTML=`<tr>
                                <td colspan="5">
                                    Error al cargar ventas
                                </td>
                            </tr>`;
            console.error(resultado.mensaje);
            return;
        }

        const ventas=resultado.datos;
        if(ventas.length===0){
            tabla.innerHTML=`<tr>
                                <td colspan="5" class="vacio">
                                    No hay ventas registradas
                                </td>
                            </tr>`;
            return;
        }

        ventas.forEach(venta=>{
            tabla.innerHTML+=`<tr>
                                <td>
                                    <a href="detalleVenta.html?id_venta=${venta.id_venta}">${venta.id_venta}</a>
                                </td>
                                <td>${venta.fecha_venta}</td>
                                <td>${venta.empleado}</td>
                                <td class="num">$${parseFloat(venta.total).toFixed(2)}</td>
                                <td>
                                    <span class="pago ${venta.metodo_pago}">${venta.metodo_pago}</span>
                                </td>
                            </tr>`;
        });
    }).catch(error=>{
        console.error("Error de conexión:", error);
    });
}