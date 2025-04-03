<?php
require_once '../config/config.php';
require_once '../includes/session.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

// Verificar sesión
checkSession();

// Obtener y validar ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    jsonResponse(false, 'ID de factura no válido');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Verificar si la factura existe y puede ser anulada
    $sql_check = "SELECT estado, 
                         (SELECT COUNT(*) FROM cheques WHERE factura_id = f.id) as tiene_cheque
                  FROM facturas f 
                  WHERE id = :id";
    
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ":id", $id);
    
    if (!oci_execute($stmt_check)) {
        throw new Exception("Error al verificar la factura");
    }
    
    $factura = oci_fetch_assoc($stmt_check);
    
    if (!$factura) {
        throw new Exception("Factura no encontrada");
    }
    
    if ($factura['ESTADO'] !== 'A') {
        throw new Exception("La factura ya está " . 
            ($factura['ESTADO'] === 'N' ? 'anulada' : 'cruzada con un cheque'));
    }
    
    if ($factura['TIENE_CHEQUE'] > 0) {
        throw new Exception("No se puede anular la factura porque tiene un cheque asociado");
    }

    // Iniciar transacción
    oci_execute(oci_parse($conn, "BEGIN TRANSACTION"));

    // Anular la factura
    $sql = "UPDATE facturas SET 
                estado = 'N',
                fecha_modificacion = SYSDATE,
                usuario_modificacion = :usuario_id
            WHERE id = :id";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    oci_bind_by_name($stmt, ":usuario_id", $_SESSION['user_id']);
    
    if (!oci_execute($stmt)) {
        throw new Exception("Error al anular la factura");
    }

    // Registrar en el historial
    $sql_historial = "INSERT INTO historial_facturas (
                        factura_id,
                        accion,
                        usuario_id,
                        fecha_accion,
                        detalles
                    ) VALUES (
                        :factura_id,
                        'ANULACION',
                        :usuario_id,
                        SYSDATE,
                        'Factura anulada'
                    )";

    $stmt_historial = oci_parse($conn, $sql_historial);
    oci_bind_by_name($stmt_historial, ":factura_id", $id);
    oci_bind_by_name($stmt_historial, ":usuario_id", $_SESSION['user_id']);
    
    if (!oci_execute($stmt_historial)) {
        throw new Exception("Error al registrar en el historial");
    }

    // Confirmar transacción
    oci_execute(oci_parse($conn, "COMMIT"));

    // Liberar recursos
    oci_free_statement($stmt_check);
    oci_free_statement($stmt);
    oci_free_statement($stmt_historial);
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Factura anulada correctamente');

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn)) {
        oci_execute(oci_parse($conn, "ROLLBACK"));
    }
    
    error_log("Error en anular_factura.php: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}