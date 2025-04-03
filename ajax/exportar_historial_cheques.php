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

    // Consulta para obtener los datos
    $sql = "SELECT 
                ch.numero_cheque,
                ch.beneficiario,
                ch.monto,
                ch.estado,
                TO_CHAR(ch.fecha_emision, 'DD/MM/YYYY') as fecha_emision,
                TO_CHAR(ch.fecha_deposito, 'DD/MM/YYYY') as fecha_deposito,
                f.numero as numero_factura,
                c.nombre as cliente,
                u.nombre as usuario_registro,
                TO_CHAR(ch.fecha_registro, 'DD/MM/YYYY HH24:MI:SS') as fecha_registro,
                CASE 
                    WHEN ch.estado = 'DEPOSITADO' THEN 
                        (SELECT observacion 
                         FROM historial_cheques 
                         WHERE cheque_id = ch.id 
                         AND estado_nuevo = 'DEPOSITADO' 
                         ORDER BY fecha_cambio DESC 
                         FETCH FIRST 1 ROW ONLY)
                    ELSE NULL
                END as observacion_deposito
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

    // Ordenar por fecha de emisión
    $sql .= " ORDER BY ch.fecha_emision DESC, ch.numero_cheque ASC";

    $stmt = oci_parse($conn, $sql);
    
    // Bind de parámetros
    foreach ($params as $key => $value) {
        oci_bind_by_name($stmt, $key, $params[$key]);
    }

    if (!oci_execute($stmt)) {
        throw new Exception("Error al ejecutar la consulta");
    }

    // Preparar datos para el archivo Excel
    $filename = "historial_cheques_" . date('Y-m-d_His') . ".csv";
    
    // Headers para forzar la descarga
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Crear el archivo CSV
    $output = fopen('php://output', 'w');
    
    // BOM para Excel (para caracteres especiales)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados de columnas
    fputcsv($output, [
        'Número de Cheque',
        'Beneficiario',
        'Monto',
        'Estado',
        'Fecha Emisión',
        'Fecha Depósito',
        'Número Factura',
        'Cliente',
        'Usuario Registro',
        'Fecha Registro',
        'Observación'
    ]);

    // Escribir los datos
    while ($row = oci_fetch_assoc($stmt)) {
        // Formatear estado para mejor legibilidad
        $estados = [
            'INGRESADO' => 'Ingresado',
            'CRUZADO' => 'Cruzado',
            'DEPOSITADO' => 'Depositado',
            'ANULADO' => 'Anulado'
        ];

        fputcsv($output, [
            $row['NUMERO_CHEQUE'],
            $row['BENEFICIARIO'],
            number_format($row['MONTO'], 2, '.', ','),
            $estados[$row['ESTADO']],
            $row['FECHA_EMISION'],
            $row['FECHA_DEPOSITO'] ?: 'N/A',
            $row['NUMERO_FACTURA'],
            $row['CLIENTE'],
            $row['USUARIO_REGISTRO'],
            $row['FECHA_REGISTRO'],
            $row['OBSERVACION_DEPOSITO'] ?: ''
        ]);
    }

    // Cerrar recursos
    fclose($output);
    oci_free_statement($stmt);
    closeOracleConnection($conn);

} catch (Exception $e) {
    error_log("Error en exportar_historial_cheques.php: " . $e->getMessage());
    
    // En caso de error, redirigir con mensaje de error
    header("Location: /public/historial_cheques.php?error=export");
    exit;
}