<?php
require_once "libreria.php";

header("Content-Type: application/json"); // Establecer el tipo de contenido como JSON

try{
    $conexion=conectaDB();

    // Hacer la consulta para obtener los datos de las rutinas asignadas, incluyendo el nombre del cliente y del coach
    $consulta="
            select
                ar.id_asignacion,
                ar.id_rutina,
                ar.id_usuario,
                concat_ws(' ', usuariocliente.nombre, usuariocliente.apellido_1, usuariocliente.apellido_2) as nombre_cliente,
                ar.id_coach,
                concat_ws(' ', empleados.nombre, empleados.apellido_1, empleados.apellido_2) as nombre_coach,
                r.nombre_rutina,
                date(ar.fecha_inicio) AS fecha_inicio,
                date(ar.fecha_fin) AS fecha_fin,
                ar.observaciones,
                case
                    when ar.fecha_fin is null then 'Sin fecha'
                    when curdate() > ar.fecha_fin then 'Finalizada'
                    when datediff(ar.fecha_fin, curdate()) <= 5 then 'Por vencer'
                    else 'Activa'
                end as estatus
            from asignacion_rutinas as ar
            inner join rutinas as r
            on r.id_rutina=ar.id_rutina

            left join usuariocliente
            on ar.id_usuario=usuariocliente.id_usuario
            
            left join empleados
            on ar.id_coach=empleados.id_empleado
            
            order by ar.fecha_fin asc";

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