<?php
require_once '../config/config.php';
require_once '../includes/session.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

// Verificar sesión
checkSession();

// Obtener y validar ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    jsonResponse(false, 'ID de cheque no válido');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consultar los cambios del cheque
    $sql = "SELECT 
                hc.id,
                hc.estado_anterior,
                hc.estado_nuevo,
                hc.fecha_cambio,
                hc.observacion,
                u.nombre as usuario,
                CASE 
                    WHEN hc.estado_nuevo = 'DEPOSITADO' THEN 
                        (SELECT fecha_deposito 
                         FROM cheques 
                         WHERE id = hc.cheque_id)
                    ELSE NULL
                END as fecha_deposito
            FROM 
                historial_cheques hc
                INNER JOIN usuarios u ON hc.usuario_id = u.id
            WHERE 
                hc.cheque_id = :id
            ORDER BY 
                hc.fecha_cambio DESC";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    
    if (!oci_execute($stmt)) {
        throw new Exception("Error al consultar el historial");
    }

    $cambios = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Formatear estados para mejor legibilidad
        $estados = [
            'INGRESADO' => 'Ingresado',
            'CRUZADO' => 'Cruzado',
            'DEPOSITADO' => 'Depositado',
            'ANULADO' => 'Anulado'
        ];

        // Sanitizar y formatear datos para la respuesta
        $cambio = [
            'id' => $row['ID'],
            'estado_anterior' => $row['ESTADO_ANTERIOR'] ? 
                               $estados[$row['ESTADO_ANTERIOR']] : null,
            'estado_nuevo' => $estados[$row['ESTADO_NUEVO']],
            'fecha' => $row['FECHA_CAMBIO'],
            'observacion' => htmlspecialchars($row['OBSERVACION']),
            'usuario' => htmlspecialchars($row['USUARIO'])
        ];

        // Agregar fecha de depósito si el cambio fue a estado DEPOSITADO
        if ($row['ESTADO_NUEVO'] === 'DEPOSITADO' && $row['FECHA_DEPOSITO']) {
            $cambio['observacion'] .= "\nFecha de depósito: " . 
                                    date('d/m/Y', strtotime($row['FECHA_DEPOSITO']));
        }

        $cambios[] = $cambio;
    }

    // Obtener información del cheque
    $sql_cheque = "SELECT 
                    ch.numero_cheque,
                    ch.beneficiario,
                    ch.monto,
                    ch.estado,
                    ch.fecha_emision,
                    f.numero as numero_factura,
                    c.nombre as cliente
                FROM 
                    cheques ch
                    INNER JOIN facturas f ON ch.factura_id = f.id
                    INNER JOIN clientes c ON ch.cliente_id = c.id
                WHERE 
                    ch.id = :id";

    $stmt_cheque = oci_parse($conn, $sql_cheque);
    oci_bind_by_name($stmt_cheque, ":id", $id);
    oci_execute($stmt_cheque);
    
    $cheque = oci_fetch_assoc($stmt_cheque);

    if (!$cheque) {
        throw new Exception("Cheque no encontrado");
    }

    // Liberar recursos
    oci_free_statement($stmt);
    oci_free_statement($stmt_cheque);
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Historial obtenido correctamente', [
        'cheque' => [
            'numero' => htmlspecialchars($cheque['NUMERO_CHEQUE']),
            'beneficiario' => htmlspecialchars($cheque['BENEFICIARIO']),
            'monto' => floatval($cheque['MONTO']),
            'estado' => $cheque['ESTADO'],
            'fecha_emision' => $cheque['FECHA_EMISION'],
            'factura' => htmlspecialchars($cheque['NUMERO_FACTURA']),
            'cliente' => htmlspecialchars($cheque['CLIENTE'])
        ],
        'cambios' => $cambios
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_cambios_cheque.php: " . $e->getMessage());
    jsonResponse(false, 'Error al obtener el historial de cambios');
}