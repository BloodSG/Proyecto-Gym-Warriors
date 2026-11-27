import {historialPagos} from "./api.js";
import {verificaSesion} from "./autorizacion.js";

const usuario= await verificaSesion();
console.log(usuario.datos);

const contenedorHistorial=document.getElementById("pagos-tbody");
contenedorHistorial.innerHTML=`Cargando tu historial de pagos :D`;
const historial=await historialPagos();
if(historial && historial.status=="success")
{   
    if(!Array.isArray(historial.data) || historial.data.length == 0)
    {
        contenedorHistorial.innerHTML=`Aun no has realizado ninguna compra`;
    }
    let historialHTML=``;
    let estadoPago="";
    let fechaPago="";
    const datos=historial.data;
    
    datos.forEach(compra => {
        if(compra["estado_pago"])
        {
            if(compra["estado_pago"]=="completada"){estadoPago=`<span class="badge badge-aprobado">Aprovado</span>`;}
            else if(compra["estado_pago"]=="cancelada"){estadoPago=`<span class="badge badge-rechazado">Rechazado</span>`;}
            else {estadoPago=`<span class="badge badge-pendiente">Pendiente</span>`;}
        }
        else{estadoPago=`<span class="badge badge-pendiente">Pendiente</span>`;}
        
        fechaPago=compra["fecha_pago"];
        const [anio, mesNumero, dia]=fechaPago.split(" ")[0].split("-");
        const fecha=new Date(Number(anio), Number(mesNumero) - 1, Number(dia));
        const mesTexto=fecha.toLocaleString("es-MX", { month: "long" });
        const mes=mesTexto.charAt(0).toUpperCase() + mesTexto.slice(1);

        
        historialHTML+=`
        <tr>
            <td><time class="fecha-pago" datetime="${fechaPago}">${dia} de ${mes} del ${anio}</time></td>
            <td class="cantidad-pago">$${compra["monto"]} mxn</td>
            <td class="metodo-pago">${compra["metodo_pago"]}</td>
            <td>
                ${estadoPago}
            </td>
        </tr>`
        
    });
    contenedorHistorial.innerHTML=historialHTML;
    
}
else
{
    contenedorHistorial.innerHTML=`No hay nada que mostrar aqui `;
}