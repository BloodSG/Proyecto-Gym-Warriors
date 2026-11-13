<?php
require_once "libreria.php";

$conexion=conectaDB();

header("Content-Type: application/json");

if(isset($_GET['id'])){
    $id_rutina=$_GET['id'];

    try{
        $consulta="
                select
                    rutinas.id_rutina,
                    rutinas.id_usuario,
                    concat_ws(' ', usuariocliente.nombre, usuariocliente.apellido_1, usuariocliente.apellido_2) as nombre_cliente,
                    rutinas.id_coach,
                    concat_ws(' ', empleados.nombre, empleados.apellido_1, empleados.apellido_2) as nombre_coach,
                    rutinas.nombre_rutina,
                    date(rutinas.fecha_creacion) as fecha_creacion,
                    date(rutinas.fecha_fin) as fecha_fin,
                    rutinas.observaciones,
                    rutinas.estatus

                from rutinas

                left join usuariocliente
                on rutinas.id_usuario=usuariocliente.id_usuario

                left join empleados
                on rutinas.id_coach=empleados.id_empleado

                where rutinas.id_rutina=?";

        $resultado=$conexion->prepare($consulta);
        $resultado->execute([$id_rutina]);
        
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
                "mensaje"=>"No se encontró la rutina con el ID proporcionado"
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
        "mensaje"=>"No se recibió el ID de la rutina."
    ]);
}
?>