import {estadoPago} from "./api.js";
import {verificaSesion} from "./autorizacion.js";

const usuario= await verificaSesion();
console.log(usuario.datos);

const estado=await estadoPago();
if(estado)
{
    const semaforo=document.getElementById("jugaremosmueveteluzverde");
    const mensajeEstado=document.getElementById("statusmessage");
    const mensajeUltimoPago=document.getElementById("ultimoPago");
    const mensajeDiasRestante=document.getElementById("restantes");
    const mensajeVencimiento=document.getElementById("vencimiento");

    semaforo.innerHTML=``;
    mensajeEstado.innerHTML=``;
    mensajeDiasRestante.innerHTML=``;
    mensajeUltimoPago.innerHTML=``;
    mensajeVencimiento.innerHTML=``;

    const fechaUltimoPago=estado.data["fecha_inicio"];
    const fechaVigencia=estado.data["fecha_vencimiento"];

    const [anio1, mesNumero1, dia1]=fechaUltimoPago.split(" ")[0].split("-");
    const [anio2, mesNumero2, dia2]=fechaVigencia.split(" ")[0].split("-");

    const fecha1 = new Date(Number(anio1), Number(mesNumero1) - 1, Number(dia1));
    const fecha2 = new Date(Number(anio2), Number(mesNumero2) - 1, Number(dia2));

    const mesTexto1 = fecha1.toLocaleString("es-MX", { month: "long" });
    const mesTexto2 = fecha2.toLocaleString("es-MX", { month: "long" });

    const mes1 = mesTexto1.charAt(0).toUpperCase() + mesTexto1.slice(1);
    const mes2 = mesTexto2.charAt(0).toUpperCase() + mesTexto2.slice(1);

    const diferenciaMs = Math.round((fecha2 - new Date()) / (1000 * 60 * 60 * 24));

    mensajeUltimoPago.innerHTML=`${dia1} de ${mes1} del ${anio1}`;
    mensajeVencimiento.innerHTML=`${dia2} de ${mes2} del ${anio2}`;
    mensajeDiasRestante.innerHTML=`${diferenciaMs} días`;
    if(estado.status=="vencida")
    {
        semaforo.innerHTML=`<span class="legend-dot red"></span>
                                <span></span>
                                <span></span>`;

        mensajeEstado.innerHTML=`${estado.mensaje}`;
    }
    if(estado.status=="proxima")
    {
        semaforo.innerHTML=`<span></span>
                                <span class="legend-dot. yellow"></span>
                                <span></span>`;

        mensajeEstado.innerHTML=`${estado.mensaje}`;
    }
    if(estado.status=="vigente")
    {
        semaforo.innerHTML=`<span></span>
                                <span></span>
                                <span class="legend-dot green"></span>`;

        mensajeEstado.innerHTML=`${estado.mensaje}`;
    }

    
}