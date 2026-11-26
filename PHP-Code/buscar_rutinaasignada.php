<?php
require_once "libreria.php";

$conexion=conectaDB();

header("Content-Type: application/json");

if(isset($_GET['id'])){
    $id_asignacion=$_GET['id'];

    try{
        $consulta="
                select
                    ar.id_asignacion,
                    ar.id_rutina,
                    ar.id_usuario,
                    concat_ws(' ', usuariocliente.nombre, usuariocliente.apellido_1, usuariocliente.apellido_2) as nombre_cliente,
                    ar.id_coach,
                    concat_ws(' ', empleados.nombre, empleados.apellido_1, empleados.apellido_2) as nombre_coach,
                    rutinas.nombre_rutina,
                    date(ar.fecha_inicio) as fecha_inicio,
                    date(ar.fecha_fin) as fecha_fin,
                    ar.observaciones,
                    ar.estatus

                from asignacion_rutinas as ar
                inner join rutinas
                on rutinas.id_rutina=ar.id_rutina

                left join usuariocliente
                on ar.id_usuario=usuariocliente.id_usuario

                left join empleados
                on ar.id_coach=empleados.id_empleado

                where ar.id_asignacion=?";

        $resultado=$conexion->prepare($consulta);
        $resultado->execute([$id_asignacion]);
        
        $rutina=$resultado->fetch(PDO::FETCH_ASSOC);

        if($rutina){
            echo json_encode([
                "estado"=>"exito",
                "datos"=>$rutina
            ]);
        }
        else{
            echo json_encode([
                "estado"=>"error",
                "mensaje"=>"No se encontró la asignación con el ID proporcionado"
            ]);
        }
    }
    catch(PDOException $e){
        echo json_encode([
            "estado"=>"error",
            "mensaje"=>"Error al buscar la rutina: ".$e->getMessage()
        ]);
    }
}
else{
    echo json_encode([
        "estado"=>"error",
        "mensaje"=>"No se recibió el ID de la asignación."
    ]);
}
?>