import {subeComprobante} from "./api.js";
import {verificaSesion} from "./autorizacion.js";

const usuario= await verificaSesion();
console.log(usuario)