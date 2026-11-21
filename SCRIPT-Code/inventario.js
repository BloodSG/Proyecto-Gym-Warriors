document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector(".busqueda");
    const inputBuscar = document.getElementById("buscador");

    const escapar = (valor) => {
        const div = document.createElement("div");
        div.textContent = valor ?? "";
        return div.innerHTML;
    };

    function cargarInventario(buscar = "") {
        const url = "../PHP-Code/Inventario.php?buscar=" + encodeURIComponent(buscar);

        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error("Error HTTP: " + response.status);
                return response.json();
            })
            .then(data => {
                if (data.status === "error") throw new Error(data.mensaje);

                // 1. Actualizar Tarjetas
                document.querySelector(".tarjeta_valor_total").textContent = data.resumen.total ?? 0;
                document.querySelector(".tarjeta_valor_stock_bajo").textContent = data.resumen.stock_bajo ?? 0;
                document.querySelector(".tarjeta_valor_agotados").textContent = data.resumen.agotados ?? 0;

                // 2. Actualizar Alertas
                const listaAlertas = document.querySelector(".lista_alertas");
                listaAlertas.innerHTML = "";
                
                if (!data.alertas || data.alertas.length === 0) {
                    listaAlertas.textContent = "Sin alertas por el momento.";
                } else {
                    data.alertas.forEach(a => {
                        const p = document.createElement("p");
                        p.textContent = Number(a.stock) === 0
                            ? `${a.nombre}: AGOTADO`
                            : `${a.nombre}: quedan ${a.stock}`;
                        listaAlertas.appendChild(p);
                    });
                }

                // 3. Rellenar Tabla
                const tbody = document.querySelector(".tabla-inventario tbody");
                tbody.innerHTML = "";

                if (!data.productos || data.productos.length === 0) {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `<td colspan="8" style="text-align:center;">No se encontraron productos</td>`;
                    tbody.appendChild(tr);
                    return;
                }

                data.productos.forEach(item => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${escapar(item.id_producto)}</td>
                        <td>${escapar(item.nombre)}</td>
                        <td>${escapar(item.categoria)}</td>
                        <td>${escapar(item.descripcion)}</td>
                        <td>${escapar(item.precio_compra)}</td>
                        <td>${escapar(item.precio_venta)}</td>
                        <td>${escapar(item.proveedor)}</td>
                        <td>${escapar(item.stock)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(error => console.error("Error al cargar el inventario:", error));
    }

    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            cargarInventario(inputBuscar.value.trim());
        });
    }

    cargarInventario();
});