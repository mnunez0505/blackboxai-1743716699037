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
    jsonResponse(false, 'ID de cliente no válido');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Verificar si el cliente tiene cheques o facturas asociadas
    $sql_check = "SELECT 
                    (SELECT COUNT(*) FROM cheques WHERE cliente_id = :id) as total_cheques,
                    (SELECT COUNT(*) FROM facturas WHERE cliente_id = :id) as total_facturas
                  FROM dual";
    
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ":id", $id);
    
    if (!oci_execute($stmt_check)) {
        throw new Exception("Error al verificar dependencias");
    }
    
    $row = oci_fetch_assoc($stmt_check);
    
    if ($row['TOTAL_CHEQUES'] > 0 || $row['TOTAL_FACTURAS'] > 0) {
        throw new Exception(
            "No se puede eliminar el cliente porque tiene " .
            ($row['TOTAL_CHEQUES'] > 0 ? "cheques" : "facturas") .
            " asociados. Se recomienda inactivarlo en su lugar."
        );
    }

    // Iniciar transacción
    oci_execute(oci_parse($conn, "BEGIN TRANSACTION"));

    // En lugar de eliminar, marcar como inactivo
    $sql = "UPDATE clientes SET 
                estado = 'I',
                fecha_modificacion = SYSDATE,
                usuario_modificacion = :usuario_id
            WHERE id = :id";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    oci_bind_by_name($stmt, ":usuario_id", $_SESSION['user_id']);
    
    if (!oci_execute($stmt)) {
        throw new Exception("Error al eliminar el cliente");
    }

    // Registrar la eliminación en el historial
    $sql_historial = "INSERT INTO historial_clientes (
                        cliente_id,
                        accion,
                        usuario_id,
                        fecha_accion,
                        detalles
                    ) VALUES (
                        :cliente_id,
                        'ELIMINACION',
                        :usuario_id,
                        SYSDATE,
                        'Cliente marcado como inactivo'
                    )";

    $stmt_historial = oci_parse($conn, $sql_historial);
    oci_bind_by_name($stmt_historial, ":cliente_id", $id);
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
    jsonResponse(true, 'Cliente eliminado correctamente');

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn)) {
        oci_execute(oci_parse($conn, "ROLLBACK"));
    }
    
    error_log("Error en eliminar_cliente.php: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}