<?php
require_once '../config/config.php';
require_once '../includes/session.php';

// Verificar que sea una petición GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

// Verificar sesión
checkSession();

// Obtener y sanitizar parámetros
$numeroFactura = sanitizeInput($_GET['numero'] ?? '');
$clienteId = intval($_GET['cliente_id'] ?? 0);

// Validaciones
if (empty($numeroFactura) || empty($clienteId)) {
    jsonResponse(false, 'Número de factura y cliente son requeridos');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consultar la factura
    $sql = "SELECT 
                f.id,
                f.numero,
                f.fecha,
                f.monto,
                f.estado,
                c.nombre as cliente,
                (SELECT COUNT(*) FROM cheques ch WHERE ch.factura_id = f.id) as tiene_cheque
            FROM 
                facturas f
                INNER JOIN clientes c ON f.cliente_id = c.id
            WHERE 
                f.numero = :numero_factura 
                AND f.cliente_id = :cliente_id
                AND f.estado = 'A'";

    $stmt = oci_parse($conn, $sql);
    
    // Bind de parámetros
    oci_bind_by_name($stmt, ":numero_factura", $numeroFactura);
    oci_bind_by_name($stmt, ":cliente_id", $clienteId);
    
    // Ejecutar consulta
    if (!oci_execute($stmt)) {
        throw new Exception("Error al consultar la factura");
    }

    // Obtener resultado
    $factura = oci_fetch_assoc($stmt);
    
    if (!$factura) {
        jsonResponse(false, 'Factura no encontrada');
    }

    // Verificar si la factura ya está asociada a un cheque
    if ($factura['TIENE_CHEQUE'] > 0) {
        jsonResponse(false, 'Esta factura ya está asociada a un cheque');
    }

    // Formatear fecha para mostrar
    $fecha = new DateTime($factura['FECHA']);
    
    // Respuesta exitosa
    jsonResponse(true, 'Factura encontrada', [
        'id' => $factura['ID'],
        'numero' => $factura['NUMERO'],
        'fecha' => $fecha->format('d/m/Y'),
        'monto' => number_format($factura['MONTO'], 2),
        'cliente' => $factura['CLIENTE'],
        'estado' => $factura['ESTADO']
    ]);

} catch (Exception $e) {
    error_log("Error en buscar_factura.php: " . $e->getMessage());
    jsonResponse(false, 'Error al buscar la factura');

} finally {
    // Liberar recursos
    if (isset($stmt)) {
        oci_free_statement($stmt);
    }
    if (isset($conn)) {
        closeOracleConnection($conn);
    }
}