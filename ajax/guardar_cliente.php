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
$nombre = sanitizeInput($_POST['nombre'] ?? '');
$identificacion = sanitizeInput($_POST['identificacion'] ?? '');
$direccion = sanitizeInput($_POST['direccion'] ?? '');
$telefono = sanitizeInput($_POST['telefono'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$observaciones = sanitizeInput($_POST['observaciones'] ?? '');

// Validaciones
if (empty($nombre) || empty($identificacion) || empty($direccion) || empty($telefono) || empty($email)) {
    jsonResponse(false, 'Todos los campos son requeridos');
}

// Validar formato de email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'El formato del email no es válido');
}

// Validar formato de identificación (RUC/CI)
if (!preg_match('/^[0-9-]{10,13}$/', $identificacion)) {
    jsonResponse(false, 'El formato de RUC/CI no es válido');
}

try {
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Verificar si la identificación ya existe (excepto para el mismo registro en caso de actualización)
    $sql_check = "SELECT COUNT(*) as total FROM clientes 
                  WHERE identificacion = :identificacion 
                  AND (:id IS NULL OR id != :id)";
    
    $stmt_check = oci_parse($conn, $sql_check);
    oci_bind_by_name($stmt_check, ":identificacion", $identificacion);
    oci_bind_by_name($stmt_check, ":id", $id);
    
    if (!oci_execute($stmt_check)) {
        throw new Exception("Error al verificar duplicidad");
    }
    
    $row = oci_fetch_assoc($stmt_check);
    if ($row['TOTAL'] > 0) {
        throw new Exception("Ya existe un cliente con esta identificación");
    }

    // Iniciar transacción
    oci_execute(oci_parse($conn, "BEGIN TRANSACTION"));

    if ($id) {
        // Actualizar cliente existente
        $sql = "UPDATE clientes SET 
                    nombre = :nombre,
                    identificacion = :identificacion,
                    direccion = :direccion,
                    telefono = :telefono,
                    email = :email,
                    observaciones = :observaciones,
                    fecha_modificacion = SYSDATE,
                    usuario_modificacion = :usuario_id
                WHERE id = :id";
        
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":id", $id);
    } else {
        // Insertar nuevo cliente
        $sql = "INSERT INTO clientes (
                    nombre,
                    identificacion,
                    direccion,
                    telefono,
                    email,
                    observaciones,
                    estado,
                    usuario_registro,
                    fecha_registro
                ) VALUES (
                    :nombre,
                    :identificacion,
                    :direccion,
                    :telefono,
                    :email,
                    :observaciones,
                    'A',
                    :usuario_id,
                    SYSDATE
                )";
        
        $stmt = oci_parse($conn, $sql);
    }

    // Bind de parámetros comunes
    oci_bind_by_name($stmt, ":nombre", $nombre);
    oci_bind_by_name($stmt, ":identificacion", $identificacion);
    oci_bind_by_name($stmt, ":direccion", $direccion);
    oci_bind_by_name($stmt, ":telefono", $telefono);
    oci_bind_by_name($stmt, ":email", $email);
    oci_bind_by_name($stmt, ":observaciones", $observaciones);
    oci_bind_by_name($stmt, ":usuario_id", $_SESSION['user_id']);

    // Ejecutar la consulta
    if (!oci_execute($stmt)) {
        throw new Exception("Error al " . ($id ? "actualizar" : "crear") . " el cliente");
    }

    // Confirmar transacción
    oci_execute(oci_parse($conn, "COMMIT"));

    // Liberar recursos
    oci_free_statement($stmt_check);
    oci_free_statement($stmt);
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Cliente ' . ($id ? 'actualizado' : 'creado') . ' correctamente');

} catch (Exception $e) {
    // Rollback en caso de error
    if (isset($conn)) {
        oci_execute(oci_parse($conn, "ROLLBACK"));
    }
    
    error_log("Error en guardar_cliente.php: " . $e->getMessage());
    jsonResponse(false, $e->getMessage());
}