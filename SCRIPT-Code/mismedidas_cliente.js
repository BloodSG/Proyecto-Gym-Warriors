document.addEventListener("DOMContentLoaded", function(){
    cargarMedidas();
});

function cargarMedidas(){
    fetch("../PHP-Code/mismedidas_cliente.php")
    .then(respuesta=>respuesta.json())
    .then(resultado=>{
        if(!resultado.estado){
            alert(resultado.mensaje);
            return;
        }

        let medidas=resultado.medidas;
        if(medidas.length==0){
            mostrarSinRegistros();
            return;
        }

        // La primera medida es la más reciente
        let ultima=medidas[0];

        // Tarjetas superiores
        document.querySelector("#peso-actual").textContent=ultima.peso + " kg";
        document.querySelector("#grasa-actual").textContent=ultima.porcentaje_grasa ?? "--";
        document.querySelector("#musculo-actual").textContent=ultima.porcentaje_musculo ?? "--";

        // Fecha última medición
        document.querySelector("#fecha-ultima").textContent=ultima.fecha_registro;

        // Medidas corporales
        document.querySelector("#cintura-actual").textContent=ultima.cintura + " cm";
        document.querySelector("#cadera-actual").textContent=ultima.cadera + " cm";
        document.querySelector("#pecho-actual").textContent=ultima.pecho + " cm";
        document.querySelector("#brazo-actual").textContent=ultima.brazo + " cm";

        cargarTabla(medidas);
    }).catch(error=>{
        console.error(error);
        alert("Error al cargar las medidas");
    });
}




function cargarTabla(medidas){
    const tabla=document.querySelector(".measure-table tbody");

    tabla.innerHTML="";

    medidas.forEach(medida=>{
        tabla.innerHTML+=`<tr>
                            <td>${medida.fecha_registro}</td>
                            <td>${medida.peso} kg</td>
                            <td>
                                ${medida.porcentaje_grasa ?? "Sin medida"}
                            </td>
                            <td>
                                ${medida.porcentaje_musculo ?? "Sin medida"}
                            </td>
                            <td>${medida.cintura} cm</td>
                            <td>${medida.cadera} cm</td>
                            <td>${medida.pecho} cm</td>
                            <td>${medida.brazo} cm</td>
                        </tr>`;
    });
}




function mostrarSinRegistros(){
    document.querySelector("#peso-actual").textContent="-- kg";
    document.querySelector("#grasa-actual").textContent="-- %";
    document.querySelector("#musculo-actual").textContent="-- %";
    document.querySelector("#fecha-ultima").textContent="Sin registros";
    document.querySelector("#cintura-actual").textContent="-- cm";
    document.querySelector("#cadera-actual").textContent="-- cm";
    document.querySelector("#pecho-actual").textContent="-- cm";
    document.querySelector("#brazo-actual").textContent="-- cm";

    document.querySelector(".measure-table tbody").innerHTML=`
                                                            <tr>
                                                                <td colspan="8">
                                                                    No tienes mediciones registradas.
                                                                </td>
                                                            </tr>`;
}