document.addEventListener("DOMContentLoaded", () => {
    // Hacemos la petición al archivo PHP que creamos
    fetch('Inventario.php')
        .then(response => response.json())
        .then(data => {

            // Buscamos el cuerpo de la tabla en el archivo HTML
            const tbody = document.querySelector("table tbody");
            if (!tbody) return;

            tbody.innerHTML = ""; // Limpiamos la tabla

            // Recorremos los productos y armamos las filas de la tabla
            data.forEach(item => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${item.id}</td>
                    <td>${item.nombre_producto}</td>
                    <td>${item.categoria}</td>
                    <td>${item.precio_venta}</td>
                    <td>${item.stock}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(error => console.error("Error al cargar el inventario:", error));
});