//Este archivo controla el ticket de ventas y conserva los datos separados por tipo
//los datos reales se consultan y se vuelven a calcular en PHP al guardar

const catalogoUrl = "../PHP-Code/catalogo_ventas.php";
const clientesUrl = "../PHP-Code/obtener_clientes.php";
const ventaUrl = "../PHP-Code/registrar_venta.php";

const gridProductos = document.getElementById("grid-productos");
const cuerpoTicket = document.getElementById("cuerpo-ticket");
const totalArticulos = document.getElementById("total-articulos");
const totalVenta = document.getElementById("total-venta");
const botonCobrar = document.getElementById("btn-cobrar");
const botonVaciar = document.getElementById("btn-vaciar");
const buscador = document.getElementById("input-buscador");
const botonBuscar = document.getElementById("btn-buscar");
const filtroTipo = document.getElementById("filtro-tipo");
const selectorExistente = document.getElementById("select-item-existente");
const selectorCliente = document.getElementById("select-cliente");
const botonAgregarSelect = document.getElementById("btn-agregar-select");
const metodoPago = document.getElementById("metodo-pago");

let catalogo = [];
let ticket = new Map();

function formatoDinero(valor) {
    return "$" + Number(valor).toFixed(2);
}

//Lee respuestas JSON y muestra un mensaje claro si PHP devuelve HTML o texto
async function leerJson(respuesta) {
    const texto = await respuesta.text();
    try {
        return JSON.parse(texto);
    } catch (error) {
        throw new Error(
            "El servidor no devolvio JSON. Revisa Apache, PHP y la conexion MySQL."
        );
    }
}

//Carga productos y membresias segun el filtro elegido y el texto buscado (tengo sueñito)
async function cargarCatalogo(busqueda = "") {
    const parametros = new URLSearchParams();
    parametros.set("tipo", filtroTipo.value);
    if (busqueda !== "") {
        parametros.set("buscar", busqueda);
    }

    const resultado = await leerJson(
        await fetch(catalogoUrl + "?" + parametros.toString())
    );
    if (resultado.status !== "success") {
        throw new Error(resultado.mensaje);
    }

    catalogo = resultado.data;
    mostrarOpciones();
}

//Llena el desplegable con los productos y membresías disponibles para agregar al ticket.
function mostrarOpciones() {
    gridProductos.innerHTML = "";
    selectorExistente.innerHTML =
        '<option value="">-- Elige un producto o membresía --</option>';

    catalogo.forEach((item) => {
        const opcion = document.createElement("option");
        opcion.value = item.tipo + ":" + item.id;
        opcion.textContent =
            (item.tipo === "membresia" ? "Membresía: " : "Producto: ") +
            item.nombre +
            " - " +
            formatoDinero(item.precio);
        selectorExistente.appendChild(opcion);
    });
}

//Carga los clientes registrados para el selector que necesitan las membresias
async function cargarClientes() {
    const resultado = await leerJson(await fetch(clientesUrl));
    if (resultado.status !== "success") {
        throw new Error(resultado.mensaje || "No se pudieron cargar los clientes.");
    }

    resultado.data.forEach((cliente) => {
        const opcion = document.createElement("option");
        opcion.value = cliente.id_usuario;
        opcion.textContent =
            cliente.nombre +
            (cliente.apellido_1 ? " " + cliente.apellido_1 : "") +
            " - " +
            cliente.id_usuario;
        selectorCliente.appendChild(opcion);
    });
}

//Agrega un concepto al ticket sin permitir sobrepasar el stock de productos
function agregarItem(valorSeleccionado) {
    if (valorSeleccionado === "") return;

    const separador = valorSeleccionado.indexOf(":");
    const tipo = valorSeleccionado.substring(0, separador);
    const id = valorSeleccionado.substring(separador + 1);
    const item = catalogo.find((concepto) =>
        concepto.tipo === tipo && concepto.id === id
    );
    if (!item) return;

    if (selectorCliente.value === "" && tipo === "membresia") {
        alert("Selecciona un cliente registrado antes de agregar una membresía.");
        return;
    }

    const clave = tipo + ":" + id;
    const cantidadActual = ticket.get(clave)?.cantidad ?? 0;

    if (tipo === "producto" && cantidadActual + 1 > Number(item.stock)) {
        alert("No hay existencias suficientes.");
        return;
    }

    if (tipo === "membresia" && ticket.has(clave)) {
        alert("Esta membresía ya fue agregada al ticket.");
        return;
    }

    if (
        tipo === "membresia" &&
        Array.from(ticket.values()).some((concepto) => concepto.tipo === "membresia")
    ) {
        alert("Solo puedes agregar una membresía por venta.");
        return;
    }

    ticket.set(clave, {
        tipo: tipo,
        id: id,
        nombre: item.nombre,
        precio: Number(item.precio),
        cantidad: tipo === "membresia" ? 1 : cantidadActual + 1
    });

    actualizarTicket();
}

//Recalcula cantidades, subtotales, total y cambio
function actualizarTicket() {
    if (ticket.size === 0) {
        cuerpoTicket.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; color: var(--text-dim); padding: 25px;">
                    Aún no hay artículos en la lista
                </td>
            </tr>`;
    } else {
        cuerpoTicket.innerHTML = "";

        ticket.forEach((item) => {
            const fila = document.createElement("tr");
            const prefijo = item.tipo === "membresia" ? "M: " : "";
            fila.innerHTML = `
                <td>${prefijo}${item.nombre}</td>
                <td>
                    <div class="pos_controles_qty">
                        ${item.tipo === "producto"
                            ? `<button type="button" class="pos_btn_qty btn-menos" data-clave="${item.tipo}:${item.id}">-</button>`
                            : ""}
                        <strong>${item.cantidad}</strong>
                        ${item.tipo === "producto"
                            ? `<button type="button" class="pos_btn_qty btn-mas" data-clave="${item.tipo}:${item.id}">+</button>`
                            : ""}
                    </div>
                </td>
                <td>${formatoDinero(item.precio * item.cantidad)}</td>
                <td>
                    <button type="button" class="pos_btn_qty btn-quitar"
                            data-clave="${item.tipo}:${item.id}">×</button>
                </td>
            `;
            cuerpoTicket.appendChild(fila);
        });
    }

    let cantidad = 0;
    let total = 0;
    ticket.forEach((item) => {
        cantidad += item.cantidad;
        total += item.precio * item.cantidad;
    });

    totalArticulos.textContent = cantidad;
    totalVenta.textContent = formatoDinero(total);
    botonCobrar.disabled = ticket.size === 0;
}

botonAgregarSelect.addEventListener("click", () => {
    agregarItem(selectorExistente.value);
});

cuerpoTicket.addEventListener("click", (evento) => {
    const boton = evento.target.closest("button");
    if (!boton) return;

    const clave = boton.dataset.clave;
    const item = ticket.get(clave);
    if (!item) return;

    if (boton.classList.contains("btn-mas")) {
        agregarItem(clave);
    } else if (boton.classList.contains("btn-menos")) {
        item.cantidad--;
        if (item.cantidad <= 0) {
            ticket.delete(clave);
        }
        actualizarTicket();
    } else if (boton.classList.contains("btn-quitar")) {
        ticket.delete(clave);
        actualizarTicket();
    }
});

botonBuscar.addEventListener("click", () => {
    cargarCatalogo(buscador.value.trim()).catch((error) => alert(error.message));
});

buscador.addEventListener("input", () => {
    if (buscador.value.trim() === "") {
        cargarCatalogo().catch((error) => alert(error.message));
    }
});

filtroTipo.addEventListener("change", () => {
    cargarCatalogo(buscador.value.trim()).catch((error) => alert(error.message));
});

botonVaciar.addEventListener("click", () => {
    ticket.clear();
    actualizarTicket();
});

botonCobrar.addEventListener("click", async () => {
    const tieneMembresia = Array.from(ticket.values()).some(
        (item) => item.tipo === "membresia"
    );

    //Solo las membresías requieren un cliente registrado
    if (tieneMembresia && selectorCliente.value === "") {
        alert("Selecciona un cliente registrado antes de cobrar una membresía.");
        return;
    }

    const items = Array.from(ticket.values()).map((item) => ({
        tipo: item.tipo,
        id: item.id,
        cantidad: item.cantidad
    }));

    botonCobrar.disabled = true;

    try {
        const resultado = await leerJson(await fetch(ventaUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                items: items,
                id_usuario: selectorCliente.value || null,
                metodo_pago: metodoPago.value
            })
        }));

        if (resultado.status === "success") {
            alert(
                "Venta registrada correctamente.\n" +
                "Folio: " + resultado.data.id_venta + "\n" +
                "Total: " + formatoDinero(resultado.data.total)
            );
            ticket.clear();
            actualizarTicket();
            await cargarCatalogo();
        } else {
            alert(resultado.mensaje);
        }
    } catch (error) {
        alert(error.message);
    } finally {
        botonCobrar.disabled = ticket.size === 0;
    }
});

Promise.all([
    cargarCatalogo(),
    cargarClientes()
]).catch((error) => {
    gridProductos.innerHTML = "<p>" + error.message + "</p>";
});
