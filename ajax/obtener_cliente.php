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
    jsonResponse(false, 'ID de cliente no válido');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consultar cliente
    $sql = "SELECT 
                id,
                nombre,
                identificacion,
                direccion,
                telefono,
                email,
                estado,
                observaciones
            FROM 
                clientes 
            WHERE 
                id = :id";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":id", $id);
    
    if (!oci_execute($stmt)) {
        throw new Exception("Error al consultar el cliente");
    }

    $cliente = oci_fetch_assoc($stmt);
    
    if (!$cliente) {
        jsonResponse(false, 'Cliente no encontrado');
    }

    // Sanitizar datos para la respuesta
    $cliente_data = [
        'id' => $cliente['ID'],
        'nombre' => htmlspecialchars($cliente['NOMBRE']),
        'identificacion' => htmlspecialchars($cliente['IDENTIFICACION']),
        'direccion' => htmlspecialchars($cliente['DIRECCION']),
        'telefono' => htmlspecialchars($cliente['TELEFONO']),
        'email' => htmlspecialchars($cliente['EMAIL']),
        'estado' => $cliente['ESTADO'],
        'observaciones' => htmlspecialchars($cliente['OBSERVACIONES'])
    ];

    // Liberar recursos
    oci_free_statement($stmt);
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Cliente encontrado', $cliente_data);

} catch (Exception $e) {
    error_log("Error en obtener_cliente.php: " . $e->getMessage());
    jsonResponse(false, 'Error al obtener el cliente');
}