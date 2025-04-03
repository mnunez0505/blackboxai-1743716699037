<?php
require_once '../config/config.php';
require_once '../includes/session.php';

// Verificar sesión
checkSession();

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consulta para obtener todas las facturas con información relacionada
    $sql = "SELECT 
                f.id,
                f.numero,
                f.fecha,
                f.monto,
                f.estado,
                f.concepto,
                c.id as cliente_id,
                c.nombre as cliente,
                ch.numero_cheque as cheque_numero
            FROM 
                facturas f
                INNER JOIN clientes c ON f.cliente_id = c.id
                LEFT JOIN cheques ch ON f.id = ch.factura_id
            ORDER BY 
                f.fecha DESC, f.numero ASC";

    $stmt = oci_parse($conn, $sql);
    if (!oci_execute($stmt)) {
        throw new Exception("Error al ejecutar la consulta");
    }

    $facturas = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Sanitizar datos para la salida
        $facturas[] = [
            'id' => $row['ID'],
            'numero' => htmlspecialchars($row['NUMERO']),
            'fecha' => $row['FECHA'],
            'monto' => floatval($row['MONTO']),
            'estado' => $row['ESTADO'],
            'concepto' => htmlspecialchars($row['CONCEPTO']),
            'cliente_id' => $row['CLIENTE_ID'],
            'cliente' => htmlspecialchars($row['CLIENTE']),
            'cheque_numero' => $row['CHEQUE_NUMERO'] ? htmlspecialchars($row['CHEQUE_NUMERO']) : null
        ];
    }

    // Obtener totales para el resumen
    $sql_totales = "SELECT 
                        COUNT(*) as total_facturas,
                        SUM(CASE WHEN estado = 'A' THEN 1 ELSE 0 END) as total_activas,
                        SUM(CASE WHEN estado = 'C' THEN 1 ELSE 0 END) as total_cruzadas,
                        SUM(CASE WHEN estado = 'N' THEN 1 ELSE 0 END) as total_anuladas,
                        SUM(CASE WHEN estado = 'A' THEN monto ELSE 0 END) as monto_activas,
                        SUM(CASE WHEN estado = 'C' THEN monto ELSE 0 END) as monto_cruzadas
                    FROM facturas";

    $stmt_totales = oci_parse($conn, $sql_totales);
    oci_execute($stmt_totales);
    $totales = oci_fetch_assoc($stmt_totales);

    // Respuesta para DataTables
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => count($facturas),
        'recordsFiltered' => count($facturas),
        'data' => $facturas,
        'resumen' => [
            'total_facturas' => $totales['TOTAL_FACTURAS'],
            'total_activas' => $totales['TOTAL_ACTIVAS'],
            'total_cruzadas' => $totales['TOTAL_CRUZADAS'],
            'total_anuladas' => $totales['TOTAL_ANULADAS'],
            'monto_activas' => floatval($totales['MONTO_ACTIVAS']),
            'monto_cruzadas' => floatval($totales['MONTO_CRUZADAS'])
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en listar_facturas.php: " . $e->getMessage());
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al obtener las facturas'
    ]);

} finally {
    if (isset($stmt)) {
        oci_free_statement($stmt);
    }
    if (isset($stmt_totales)) {
        oci_free_statement($stmt_totales);
    }
    if (isset($conn)) {
        closeOracleConnection($conn);
    }
}