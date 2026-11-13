<?php
require_once "libreria.php";

header("Content-Type: application/json"); // Establecer el tipo de contenido como JSON

try{
    $conexion=conectaDB();

    // Hacer la consulta para obtener los datos de las rutinas asignadas, incluyendo el nombre del cliente y del coach
    $consulta="
            select
                rutinas.id_rutina,
                rutinas.id_usuario,
                concat_ws(' ', usuariocliente.nombre, usuariocliente.apellido_1, usuariocliente.apellido_2) as nombre_cliente,
                rutinas.id_coach,
                concat_ws(' ', empleados.nombre, empleados.apellido_1, empleados.apellido_2) as nombre_coach,
                rutinas.nombre_rutina,
                date(rutinas.fecha_creacion) AS fecha_creacion,
                date(rutinas.fecha_fin) AS fecha_fin,
                rutinas.observaciones,
                case
                    when rutinas.fecha_fin is null then 'Sin fecha'
                    when curdate() > rutinas.fecha_fin then 'Finalizada'
                    when datediff(rutinas.fecha_fin, curdate()) <= 5 then 'Por vencer'
                    else 'Activa'
                end as estatus
            from rutinas

            left join usuariocliente
            on rutinas.id_usuario=usuariocliente.id_usuario
            
            left join empleados
            on rutinas.id_coach=empleados.id_empleado
            
            order by rutinas.fecha_fin asc";

    // Ejecutar la consulta
    $mostrar_rutina=$conexion->query($consulta);

    $rutinas=[];
    // Recorremos los registros obtenidos
    while($fila=$mostrar_rutina->fetch(PDO::FETCH_ASSOC)){
        // Agregamos cada registro al arreglo de rutinas
        $rutinas[]=$fila;
    }

    // Devolver las rutinas en formato JSON, para que puedan ser procesados por JavaScript
    echo json_encode($rutinas);
    }catch(PDOException $e){
        // En caso de error, devolver un mensaje de error en formato JSON
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"Error al obtener las rutinas: ".$e->getMessage()
        ]);
    }

?>