<?php
require_once '../config/config.php';
require_once '../includes/session.php';

// Verificar sesión
checkSession();

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consulta para obtener todos los clientes
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
            ORDER BY 
                nombre ASC";

    $stmt = oci_parse($conn, $sql);
    if (!oci_execute($stmt)) {
        throw new Exception("Error al ejecutar la consulta");
    }

    $clientes = [];
    while ($row = oci_fetch_assoc($stmt)) {
        // Sanitizar datos para la salida
        $clientes[] = [
            'id' => $row['ID'],
            'nombre' => htmlspecialchars($row['NOMBRE']),
            'identificacion' => htmlspecialchars($row['IDENTIFICACION']),
            'direccion' => htmlspecialchars($row['DIRECCION']),
            'telefono' => htmlspecialchars($row['TELEFONO']),
            'email' => htmlspecialchars($row['EMAIL']),
            'estado' => $row['ESTADO'],
            'observaciones' => htmlspecialchars($row['OBSERVACIONES'])
        ];
    }

    // Respuesta para DataTables
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => count($clientes),
        'recordsFiltered' => count($clientes),
        'data' => $clientes
    ]);

} catch (Exception $e) {
    error_log("Error en listar_clientes.php: " . $e->getMessage());
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error al obtener los clientes'
    ]);

} finally {
    if (isset($stmt)) {
        oci_free_statement($stmt);
    }
    if (isset($conn)) {
        closeOracleConnection($conn);
    }
}