<?php
require_once '../config/config.php';
require_once '../includes/session.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

// Verificar sesión
checkSession();

// Obtener y sanitizar datos
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$estado = sanitizeInput($_POST['estado'] ?? '');
$observacion = sanitizeInput($_POST['observacion'] ?? '');
$fecha_deposito = sanitizeInput($_POST['fecha_deposito'] ?? '');

// Validaciones
if ($id <= 0) {
    jsonResponse(false, 'ID de cheque no válido');
}

if (empty($estado)) {
    jsonResponse(false, 'El estado es requerido');
}

// Validar que el estado sea válido
$estados_validos = ['INGRESADO', 'CRUZADO', 'DEPOSITADO', 'ANULADO'];
if (!in_array($estado, $estados_validos)) {
    jsonResponse(false, 'Estado no válido');
}

// Validar fecha de depósito si el estado es DEPOSITADO
if ($estado === 'DEPOSITADO') {
    if (empty($fecha_deposito)) {
        jsonResponse(false, 'La fecha de depósito es requerida para estado Depositado');
    }
    if (!validateDate($fecha_deposito)) {
        jsonResponse(false, 'Formato de fecha de depósito inválido');
    }
    
    // Validar que la fecha de depósito no sea futura
    $fecha_dep = new DateTime($fecha_deposito);
    $fecha_actual = new DateTime();
    if ($fecha_dep > $fecha_actual) {
        jsonResponse(false, 'La fecha de depósito no puede ser futura');
    }
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Verificar el cheque y su estado actual
    $sql_check = "SELECT 
                    ch.estado,
                    ch.fecha_emision,
                    f.id as factura_id
                  FROM cheques ch
                  INNER JOIN facturas f ON ch.factura_id = f.id
                  WHERE ch.id = :id";
    
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ":id", $id);
    
    if (!oci_execute($stmt_check)) {
        throw new Exception("Error al verificar el cheque");
    }
    
    $cheque = oci_fetch_assoc($stmt_check);
    if (!$cheque) {
        throw new Exception("Cheque no encontrado");
    }

    // Validar transición de estado
    if ($cheque['ESTADO'] === 'ANULADO') {
        throw new Exception("No se puede cambiar el estado de un cheque anulado");
    }
    
    if ($cheque['ESTADO'] === 'DEPOSITADO' && $estado !== 'ANULADO') {
        throw new Exception("Un cheque depositado solo puede ser anulado");
    }

    // Si es estado DEPOSITADO, validar que la fecha de depósito sea posterior a la emisión
    if ($estado === 'DEPOSITADO') {
        $fecha_emision = new DateTime($cheque['FECHA_EMISION']);
        $fecha_dep = new DateTime($fecha_deposito);
        if ($fecha_dep < $fecha_emision) {
            throw new Exception("La fecha de depósito debe ser posterior a la fecha de emisión");
        }
    }

    // Iniciar transacción
    oci_execute(oci_parse($conn, "BEGIN TRANSACTION"));

    // Actualizar estado del cheque
    $sql = "UPDATE cheques SET 
                estado = :estado,
                " . ($estado === 'DEPOSITADO' ? "fecha_deposito = TO_DATE(:fecha_deposito, 'YYYY-MM-DD')," : "") . "
                fecha_modificacion = SYSDATE,
                usuario_modificacion = :usuario_id
            WHERE id = :id";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":estado", $estado);
    if ($estado === 'DEPOSITADO') {
        oci_bind_by_name($stmt, ":fecha_deposito", $fecha_deposito);
    }
    oci_bind_by_name($stmt, ":usuario_id", $_SESSION['user_id']);
    oci_bind_by_name($stmt, ":id", $id);
    
    if (!oci_execute($stmt)) {
        throw new Exception("Error al actualizar el estado del cheque");
    }

    // Registrar en el historial
    $sql_historial = "INSERT INTO historial_cheques (
                        cheque_id,
                        estado_anterior,
                        estado_nuevo,
                        usuario_id,
                        fecha_cambio,
                        observacion
                    ) VALUES (
                        :cheque_id,
                        :estado_anterior,
                        :estado_nuevo,
                        :usuario_id,
                        SYSDATE,
                        :observacion
                    )";

    $stmt_historial = oci_parse($conn, $sql_historial);
    oci_bind_by_name($stmt_historial, ":cheque_id", $id);
    oci_bind_by_name($stmt_historial, ":estado_anterior", $cheque['ESTADO']);
    oci_bind_by_name($stmt_historial, ":estado_nuevo", $estado);
    oci_bind_by_name($stmt_historial, ":usuario_id", $_SESSION['user_id']);
    oci_bind_by_name($stmt_historial, ":observacion", $observacion);
    
    if (!oci_execute($stmt_historial)) {
        throw new Exception("Error al registrar en el historial");
    }

    // Si el estado es CRUZADO o DEPOSITADO, actualizar el estado de la factura
    if (in_array($estado, ['CRUZADO', 'DEPOSITADO'])) {
        $sql_factura = "UPDATE facturas SET 
                            estado = 'C',
                            fecha_modificacion = SYSDATE,
                            usuario_modificacion = :usuario_id
                        WHERE id = :factura_id";

        $stmt_factura = oci_parse($conn, $sql_factura);
        oci_bind_by_name($stmt_factura, ":usuario_id", $_SESSION['user_id']);
        oci_bind_by_name($stmt_factura, ":factura_id", $cheque['FACTURA_ID']);
        
        if (!oci_execute($stmt_factura)) {
            throw new Exception("Error al actualizar el estado de la factura");
        }
    }

    // Si el estado es ANULADO, actualizar el estado de la factura a activa
    if ($estado === 'ANULADO') {
        $sql_factura = "UPDATE facturas SET 
                            estado = 'A',
                            fecha_modificacion = SYSDATE,
                            usuario_modificacion = :usuario_id
                        WHERE id = :factura_id";

        $stmt_factura = oci_parse($conn, $sql_factura);
        oci_bind_by_name($stmt_factura, ":usuario_id", $_SESSION['user_id']);
        oci_bind_by_name($stmt_factura, ":factura_id", $cheque['FACTURA_ID']);
        
        if (!oci_execute($stmt_factura)) {
            throw new Exception("Error al actualizar el estado de la factura");
        }
    }

    // Confirmar transacción
    oci_execute(oci_parse($conn, "COMMIT"));

    // Liberar recursos
    oci_free_statement($stmt_check);
    oci_free_statement($stmt);
    oci_free_statement($stmt_historial);
    if (isset($stmt_factura)) {
        oci_free_statement($stmt_factura);
    }
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Estado del cheque actualizado correctamente');

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn)) {
        oci_execute(oci_parse($conn, "ROLLBACK"));
    }
    
    error_log("Error en cambiar_estado_cheque.php: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}