
export async function verificaSesion() 
{   
    document.documentElement.style.visibility = 'hidden';
    const consultaAcceso="../PHP-Code/session.php"; 
    const respuesta= await fetch(consultaAcceso);
    const datos = await respuesta.json();

    if(!datos.logueado)
    {
        window.location.replace(datos.redirect);
        return null;
    }
    else
    {
        document.documentElement.style.visibility = 'visible';
        return datos;
    }
}



