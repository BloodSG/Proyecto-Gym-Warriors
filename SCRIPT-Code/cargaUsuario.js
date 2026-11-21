import {verificaSesion} from "./autorizacion.js";

const usuario= await verificaSesion();

const Perfil=document.getElementById("perfilUsuario");
Perfil.innerHTML=`<strong>AD</strong><span>${usuario.nombre}</span><b>⌄</b>`;