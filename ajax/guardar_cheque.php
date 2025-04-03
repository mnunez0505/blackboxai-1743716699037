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
$numeroCheque = sanitizeInput($_POST['numeroCheque'] ?? '');
$monto = floatval($_POST['monto'] ?? 0);
$fechaEmision = sanitizeInput($_POST['fechaEmision'] ?? '');
$fechaDeposito = sanitizeInput($_POST['fechaDeposito'] ?? '');
$beneficiario = sanitizeInput($_POST['beneficiario'] ?? '');
$clienteId = intval($_POST['cliente_id'] ?? 0);
$numeroFactura = sanitizeInput($_POST['numeroFactura'] ?? '');

// Validaciones
if (empty($numeroCheque) || empty($fechaEmision) || empty($fechaDeposito) || 
    empty($beneficiario) || empty($clienteId) || empty($numeroFactura)) {
    jsonResponse(false, 'Todos los campos son requeridos');
}

if ($monto <= 0) {
    jsonResponse(false, 'El monto debe ser mayor a cero');
}

if (!validateDate($fechaEmision) || !validateDate($fechaDeposito)) {
    jsonResponse(false, 'Formato de fecha inválido');
}

// Validar que la fecha de depósito sea posterior a la de emisión
$dateEmision = new DateTime($fechaEmision);
$dateDeposito = new DateTime($fechaDeposito);
if ($dateDeposito < $dateEmision) {
    jsonResponse(false, 'La fecha de depósito debe ser posterior a la fecha de emisión');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Iniciar transacción
    oci_execute(oci_parse($conn, "BEGIN TRANSACTION"));

    // Verificar si el número de cheque ya existe
    $sql_check = "SELECT COUNT(*) as total FROM cheques WHERE numero_cheque = :numero_cheque";
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ":numero_cheque", $numeroCheque);
    oci_execute($stmt_check);
    $row = oci_fetch_assoc($stmt_check);
    
    if ($row['TOTAL'] > 0) {
        throw new Exception("El número de cheque ya existe en el sistema");
    }

    // Verificar que la factura exista y corresponda al cliente
    $sql_factura = "SELECT id, monto FROM facturas 
                    WHERE numero = :numero_factura 
                    AND cliente_id = :cliente_id 
                    AND estado = 'A'";
    
    $stmt_factura = oci_parse($conn, $sql_factura);
    oci_bind_by_name($stmt_factura, ":numero_factura", $numeroFactura);
    oci_bind_by_name($stmt_factura, ":cliente_id", $clienteId);
    oci_execute($stmt_factura);
    
    $factura = oci_fetch_assoc($stmt_factura);
    if (!$factura) {
        throw new Exception("La factura no existe o no corresponde al cliente seleccionado");
    }

    // Verificar que la factura no esté ya asociada a otro cheque
    $sql_check_factura = "SELECT COUNT(*) as total FROM cheques WHERE factura_id = :factura_id";
    $stmt_check_factura = oci_parse($conn, $sql_check_factura);
    oci_bind_by_name($stmt_check_factura, ":factura_id", $factura['ID']);
    oci_execute($stmt_check_factura);
    $row = oci_fetch_assoc($stmt_check_factura);
    
    if ($row['TOTAL'] > 0) {
        throw new Exception("La factura ya está asociada a otro cheque");
    }

    // Insertar el cheque
    $sql_insert = "INSERT INTO cheques (
                    numero_cheque, 
                    monto, 
                    fecha_emision, 
                    fecha_deposito, 
                    beneficiario, 
                    cliente_id, 
                    factura_id, 
                    estado, 
                    usuario_registro, 
                    fecha_registro
                ) VALUES (
                    :numero_cheque,
                    :monto,
                    TO_DATE(:fecha_emision, 'YYYY-MM-DD'),
                    TO_DATE(:fecha_deposito, 'YYYY-MM-DD'),
                    :beneficiario,
                    :cliente_id,
                    :factura_id,
                    'INGRESADO',
                    :usuario_registro,
                    SYSDATE
                ) RETURNING id INTO :inserted_id";

    $stmt_insert = oci_parse($conn, $sql_insert);
    
    // Bind de parámetros
    oci_bind_by_name($stmt_insert, ":numero_cheque", $numeroCheque);
    oci_bind_by_name($stmt_insert, ":monto", $monto);
    oci_bind_by_name($stmt_insert, ":fecha_emision", $fechaEmision);
    oci_bind_by_name($stmt_insert, ":fecha_deposito", $fechaDeposito);
    oci_bind_by_name($stmt_insert, ":beneficiario", $beneficiario);
    oci_bind_by_name($stmt_insert, ":cliente_id", $clienteId);
    oci_bind_by_name($stmt_insert, ":factura_id", $factura['ID']);
    oci_bind_by_name($stmt_insert, ":usuario_registro", $_SESSION['user_id']);
    oci_bind_by_name($stmt_insert, ":inserted_id", $cheque_id, -1, SQLT_INT);

    // Ejecutar la inserción
    $result = oci_execute($stmt_insert);
    
    if (!$result) {
        throw new Exception("Error al registrar el cheque");
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
                        NULL,
                        'INGRESADO',
                        :usuario_id,
                        SYSDATE,
                        'Registro inicial del cheque'
                    )";

    $stmt_historial = oci_parse($conn, $sql_historial);
    oci_bind_by_name($stmt_historial, ":cheque_id", $cheque_id);
    oci_bind_by_name($stmt_historial, ":usuario_id", $_SESSION['user_id']);
    
    if (!oci_execute($stmt_historial)) {
        throw new Exception("Error al registrar el historial");
    }

    // Confirmar transacción
    oci_execute(oci_parse($conn, "COMMIT"));

    // Liberar recursos
    oci_free_statement($stmt_check);
    oci_free_statement($stmt_factura);
    oci_free_statement($stmt_check_factura);
    oci_free_statement($stmt_insert);
    oci_free_statement($stmt_historial);
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Cheque registrado correctamente', [
        'cheque_id' => $cheque_id
    ]);

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn)) {
        oci_execute(oci_parse($conn, "ROLLBACK"));
    }
    
    error_log("Error en guardar_cheque.php: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}