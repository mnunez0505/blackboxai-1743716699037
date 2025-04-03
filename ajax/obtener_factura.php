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
    jsonResponse(false, 'ID de factura no válido');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consultar factura
    $sql = "SELECT 
                f.id,
                f.numero,
                f.cliente_id,
                f.fecha,
                f.monto,
                f.concepto,
                f.estado,
                c.nombre as cliente,
                (SELECT COUNT(*) FROM cheques WHERE factura_id = f.id) as tiene_cheque
            FROM 
                facturas f
                INNER JOIN clientes c ON f.cliente_id = c.id
            WHERE 
                f.id = :id";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    
    if (!oci_execute($stmt)) {
        throw new Exception("Error al consultar la factura");
    }

    $factura = oci_fetch_assoc($stmt);
    
    if (!$factura) {
        jsonResponse(false, 'Factura no encontrada');
    }

    // Verificar si la factura puede ser editada
    if ($factura['TIENE_CHEQUE'] > 0) {
        jsonResponse(false, 'La factura no puede ser editada porque tiene un cheque asociado');
    }

    // Sanitizar datos para la respuesta
    $factura_data = [
        'id' => $factura['ID'],
        'numero' => htmlspecialchars($factura['NUMERO']),
        'cliente_id' => $factura['CLIENTE_ID'],
        'cliente' => htmlspecialchars($factura['CLIENTE']),
        'fecha' => $factura['FECHA'],
        'monto' => floatval($factura['MONTO']),
        'concepto' => htmlspecialchars($factura['CONCEPTO']),
        'estado' => $factura['ESTADO']
    ];

    // Liberar recursos
    oci_free_statement($stmt);
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Factura encontrada', $factura_data);

} catch (Exception $e) {
    error_log("Error en obtener_factura.php: " . $e->getMessage());
    jsonResponse(false, 'Error al obtener la factura');
}