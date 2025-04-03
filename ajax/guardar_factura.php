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
$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$numero = sanitizeInput($_POST['numero'] ?? '');
$clienteId = intval($_POST['cliente_id'] ?? 0);
$fecha = sanitizeInput($_POST['fecha'] ?? '');
$monto = floatval($_POST['monto'] ?? 0);
$concepto = sanitizeInput($_POST['concepto'] ?? '');

// Validaciones
if (empty($numero) || empty($clienteId) || empty($fecha)) {
    jsonResponse(false, 'Todos los campos son requeridos');
}

if ($monto <= 0) {
    jsonResponse(false, 'El monto debe ser mayor a cero');
}

if (!validateDate($fecha)) {
    jsonResponse(false, 'Formato de fecha inválido');
}

// Validar que la fecha no sea futura
$fechaFactura = new DateTime($fecha);
$fechaHoy = new DateTime();
if ($fechaFactura > $fechaHoy) {
    jsonResponse(false, 'La fecha no puede ser futura');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Verificar si el número de factura ya existe
    $sql_check = "SELECT COUNT(*) as total FROM facturas 
                  WHERE numero = :numero 
                  AND (:id IS NULL OR id != :id)";
    
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ":numero", $numero);
    oci_bind_by_name($stmt_check, ":id", $id);
    
    if (!oci_execute($stmt_check)) {
        throw new Exception("Error al verificar duplicidad");
    }
    
    $row = oci_fetch_assoc($stmt_check);
    if ($row['TOTAL'] > 0) {
        throw new Exception("Ya existe una factura con este número");
    }

    // Verificar que el cliente exista y esté activo
    $sql_cliente = "SELECT estado FROM clientes WHERE id = :cliente_id";
    $stmt_cliente = oci_parse($conn, $sql_cliente);
    oci_bind_by_name($stmt_cliente, ":cliente_id", $clienteId);
    
    if (!oci_execute($stmt_cliente)) {
        throw new Exception("Error al verificar el cliente");
    }
    
    $cliente = oci_fetch_assoc($stmt_cliente);
    if (!$cliente) {
        throw new Exception("El cliente seleccionado no existe");
    }
    
    if ($cliente['ESTADO'] !== 'A') {
        throw new Exception("El cliente seleccionado está inactivo");
    }

    // Iniciar transacción
    oci_execute(oci_parse($conn, "BEGIN TRANSACTION"));

    if ($id) {
        // Verificar si la factura puede ser editada (no debe tener cheque asociado)
        $sql_check_cheque = "SELECT COUNT(*) as total FROM cheques WHERE factura_id = :id";
        $stmt_check_cheque = oci_parse($conn, $sql_check_cheque);
        oci_bind_by_name($stmt_check_cheque, ":id", $id);
        oci_execute($stmt_check_cheque);
        
        $row = oci_fetch_assoc($stmt_check_cheque);
        if ($row['TOTAL'] > 0) {
            throw new Exception("No se puede editar la factura porque tiene un cheque asociado");
        }

        // Actualizar factura existente
        $sql = "UPDATE facturas SET 
                    numero = :numero,
                    cliente_id = :cliente_id,
                    fecha = TO_DATE(:fecha, 'YYYY-MM-DD'),
                    monto = :monto,
                    concepto = :concepto,
                    fecha_modificacion = SYSDATE,
                    usuario_modificacion = :usuario_id
                WHERE id = :id";
        
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":id", $id);
    } else {
        // Insertar nueva factura
        $sql = "INSERT INTO facturas (
                    numero,
                    cliente_id,
                    fecha,
                    monto,
                    concepto,
                    estado,
                    usuario_registro,
                    fecha_registro
                ) VALUES (
                    :numero,
                    :cliente_id,
                    TO_DATE(:fecha, 'YYYY-MM-DD'),
                    :monto,
                    :concepto,
                    'A',
                    :usuario_id,
                    SYSDATE
                )";
        
        $stmt = oci_parse($conn, $sql);
    }

    // Bind de parámetros comunes
    oci_bind_by_name($stmt, ":numero", $numero);
    oci_bind_by_name($stmt, ":cliente_id", $clienteId);
    oci_bind_by_name($stmt, ":fecha", $fecha);
    oci_bind_by_name($stmt, ":monto", $monto);
    oci_bind_by_name($stmt, ":concepto", $concepto);
    oci_bind_by_name($stmt, ":usuario_id", $_SESSION['user_id']);

    // Ejecutar la consulta
    if (!oci_execute($stmt)) {
        throw new Exception("Error al " . ($id ? "actualizar" : "crear") . " la factura");
    }

    // Registrar en el historial
    $sql_historial = "INSERT INTO historial_facturas (
                        factura_id,
                        accion,
                        usuario_id,
                        fecha_accion,
                        detalles
                    ) VALUES (
                        " . ($id ? ":factura_id" : "factura_seq.currval") . ",
                        :accion,
                        :usuario_id,
                        SYSDATE,
                        :detalles
                    )";

    $stmt_historial = oci_parse($conn, $sql_historial);
    
    if ($id) {
        oci_bind_by_name($stmt_historial, ":factura_id", $id);
    }
    
    $accion = $id ? 'ACTUALIZACION' : 'CREACION';
    $detalles = $id ? 'Actualización de factura' : 'Registro inicial de factura';
    
    oci_bind_by_name($stmt_historial, ":accion", $accion);
    oci_bind_by_name($stmt_historial, ":usuario_id", $_SESSION['user_id']);
    oci_bind_by_name($stmt_historial, ":detalles", $detalles);
    
    if (!oci_execute($stmt_historial)) {
        throw new Exception("Error al registrar en el historial");
    }

    // Confirmar transacción
    oci_execute(oci_parse($conn, "COMMIT"));

    // Liberar recursos
    oci_free_statement($stmt_check);
    oci_free_statement($stmt_cliente);
    if (isset($stmt_check_cheque)) {
        oci_free_statement($stmt_check_cheque);
    }
    oci_free_statement($stmt);
    oci_free_statement($stmt_historial);
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Factura ' . ($id ? 'actualizada' : 'creada') . ' correctamente');

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn)) {
        oci_execute(oci_parse($conn, "ROLLBACK"));
    }
    
    error_log("Error en guardar_factura.php: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}