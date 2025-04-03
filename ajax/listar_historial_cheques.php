<?php
require_once '../config/config.php';
require_once '../includes/session.php';

// Verificar sesión
checkSession();

// Obtener parámetros de filtro
$estado = sanitizeInput($_GET['estado'] ?? '');
$fecha_desde = sanitizeInput($_GET['fecha_desde'] ?? '');
$fecha_hasta = sanitizeInput($_GET['fecha_hasta'] ?? '');
$beneficiario = sanitizeInput($_GET['beneficiario'] ?? '');
$numero = sanitizeInput($_GET['numero'] ?? '');

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Construir la consulta base
    $sql = "SELECT 
                ch.id,
                ch.numero_cheque,
                ch.beneficiario,
                ch.monto,
                ch.estado,
                ch.fecha_emision,
                ch.fecha_deposito,
                f.numero as numero_factura,
                c.nombre as cliente,
                u.nombre as usuario_registro,
                ch.fecha_registro
            FROM 
                cheques ch
                INNER JOIN facturas f ON ch.factura_id = f.id
                INNER JOIN clientes c ON ch.cliente_id = c.id
                INNER JOIN usuarios u ON ch.usuario_registro = u.id
            WHERE 1=1";

    // Array para almacenar los parámetros de bind
    $params = [];

    // Agregar condiciones según los filtros
    if (!empty($estado)) {
        $sql .= " AND ch.estado = :estado";
        $params[':estado'] = $estado;
    }

    if (!empty($fecha_desde)) {
        $sql .= " AND ch.fecha_emision >= TO_DATE(:fecha_desde, 'YYYY-MM-DD')";
        $params[':fecha_desde'] = $fecha_desde;
    }

    if (!empty($fecha_hasta)) {
        $sql .= " AND ch.fecha_emision <= TO_DATE(:fecha_hasta, 'YYYY-MM-DD')";
        $params[':fecha_hasta'] = $fecha_hasta;
    }

    if (!empty($beneficiario)) {
        $sql .= " AND UPPER(ch.beneficiario) LIKE UPPER(:beneficiario)";
        $params[':beneficiario'] = '%' . $beneficiario . '%';
    }

    if (!empty($numero)) {
        $sql .= " AND ch.numero_cheque LIKE :numero";
        $params[':numero'] = '%' . $numero . '%';
    }

    // Ordenar por fecha de emisión descendente
    $sql .= " ORDER BY ch.fecha_emision DESC, ch.numero_cheque ASC";

    $stmt = oci_parse($conn, $sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta");
    }

    // Bind de parámetros
    foreach ($params as $key => $value) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }

    if (!oci_execute($stmt)) {
        throw new Exception("Error al ejecutar la consulta");
    }

    $cheques = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Sanitizar datos para la salida
        $cheques[] = [
            'id' => $row['ID'],
            'numero_cheque' => htmlspecialchars($row['NUMERO_CHEQUE']),
            'beneficiario' => htmlspecialchars($row['BENEFICIARIO']),
            'monto' => floatval($row['MONTO']),
            'estado' => $row['ESTADO'],
            'fecha_emision' => $row['FECHA_EMISION'],
            'fecha_deposito' => $row['FECHA_DEPOSITO'],
            'numero_factura' => htmlspecialchars($row['NUMERO_FACTURA']),
            'cliente' => htmlspecialchars($row['CLIENTE']),
            'usuario_registro' => htmlspecialchars($row['USUARIO_REGISTRO']),
            'fecha_registro' => $row['FECHA_REGISTRO']
        ];
    }

    // Obtener totales para el resumen
    $sql_totales = "SELECT 
                        COUNT(*) as total_cheques,
                        SUM(CASE WHEN estado = 'INGRESADO' THEN 1 ELSE 0 END) as total_ingresados,
                        SUM(CASE WHEN estado = 'CRUZADO' THEN 1 ELSE 0 END) as total_cruzados,
                        SUM(CASE WHEN estado = 'DEPOSITADO' THEN 1 ELSE 0 END) as total_depositados,
                        SUM(CASE WHEN estado = 'ANULADO' THEN 1 ELSE 0 END) as total_anulados,
                        SUM(CASE WHEN estado != 'ANULADO' THEN monto ELSE 0 END) as monto_total
                    FROM cheques
                    WHERE 1=1";

    // Agregar las mismas condiciones de filtro
    foreach ($params as $key => $value) {
        if ($key !== ':beneficiario' && $key !== ':numero') {
            $sql_totales .= " AND " . substr($key, 1) . " = " . $key;
        }
    }

    $stmt_totales = oci_parse($conn, $sql_totales);
    
    // Bind de parámetros para totales
    foreach ($params as $key => $value) {
        if ($key !== ':beneficiario' && $key !== ':numero') {
            oci_bind_by_name($stmt_totales, $key, $params[$key]);
        }
    }

    oci_execute($stmt_totales);
    $totales = oci_fetch_assoc($stmt_totales);

    // Respuesta para DataTables
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => count($cheques),
        'recordsFiltered' => count($cheques),
        'data' => $cheques,
        'resumen' => [
            'total_cheques' => $totales['TOTAL_CHEQUES'],
            'total_ingresados' => $totales['TOTAL_INGRESADOS'],
            'total_cruzados' => $totales['TOTAL_CRUZADOS'],
            'total_depositados' => $totales['TOTAL_DEPOSITADOS'],
            'total_anulados' => $totales['TOTAL_ANULADOS'],
            'monto_total' => floatval($totales['MONTO_TOTAL'])
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en listar_historial_cheques.php: " . $e->getMessage());
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al obtener el historial de cheques'
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