import {estadoPago} from "./api.js";
import {verificaSesion} from "./autorizacion.js";

const usuario= await verificaSesion();
console.log(usuario.datos);

const estado=await estadoPago();
if(estado)
{
    const contenedorEstadoPago=document.getElementById("estadopago");
    const contenedorEstadoComp=document.getElementById("estadocomprobante");
    
    contenedorEstadoPago.innerHTML=``;
    if (estado.status=="success" && estado.data)
    {   
        console.log("entro aqui");
        let pagoshtml=``;
        const data=estado.data;
        data.forEach(pago => 
        {   
            console.log(pago["estado_pago"])
            let concepto="Pago a realizar de ";
            if(pago["id_membresia"]){concepto+="| memebresia "}
            if(pago["id_producto"]){concepto+="| prducto."}
            pagoshtml+=`<article class="payment-row">
                        <div class="payment-main">
                            <strong>Pago pendiente</strong>
                            <span>${concepto}</span>
                        </div>
                        <div class="payment-field">
                            <span>Monto</span>
                            <strong>$${pago["monto"]} MXN</strong>
                        </div>
                        <div class="payment-field">
                            <span>Estado</span>
                            <strong>${pago["estado_pago"]}</strong>
                        </div>
                        <button
                            class="upload-payment-button"
                            type="button"
                            data-url="comprobantePago.html"
                            data-id="${pago['id_pago']}">
                            Subir comprobante
                        </button>
                        </article>`
        });
        contenedorEstadoPago.innerHTML=pagoshtml;
        
        document.addEventListener("click", (e)=>
        {
            const boton=e.target.closest('.upload-payment-button');
            if (!boton)
            {
                return;
            }
            const idPago=boton.dataset.id;
            const url=boton.dataset.url; 

            window.location.href=`${url}?id=${encodeURIComponent(idPago)}`;
        })
        
    }
    else if(estado.status=="success" )
    {   
        contenedorEstadoPago.innerHTML=`<div class="payment-main">
                                            <strong>${estado.mensaje}</strong>
                                        </div>`;
    }
    else
    {
        contenedorEstadoPago.innerHTML=`<div class="payment-main">
                                            <strong>${estado.mensaje}</strong>
                                        </div>`;
    }
}