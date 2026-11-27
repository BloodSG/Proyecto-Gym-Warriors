SELECT  p.id_pago,
                        c.id_comprobante,
                        c.ruta_archivo
                FROM pago AS p
                INNER JOIN venta AS v
                ON p.id_venta=v.id_venta
                AND v.estado='pendiente'
                AND p.metodo_pago IN ('tarjeta', 'transferencia')
                AND v.id_usuario='CLI006'
                LEFT JOIN comprobante_pago as c
                ON c.id_pago= p.id_pago
                AND c.estado='rechazado'
                WHERE p.estado=v.estado
                ANd p.id_pago='P000000002'