<?php
require_once '../config/config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

// Obtener y sanitizar datos de entrada
$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validar datos requeridos
if (empty($username) || empty($password)) {
    jsonResponse(false, 'Usuario y contraseña son requeridos');
}

try {
    // Obtener conexión a la base de datos
    $conn = getOracleConnection();
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Consulta preparada para buscar el usuario
    $sql = "SELECT id, username, password_hash, nombre_completo, estado 
            FROM usuarios 
            WHERE username = :username";
    
    $stmt = oci_parse($conn, $sql);
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta");
    }

    // Bind del parámetro username
    oci_bind_by_name($stmt, ":username", $username);
    
    // Ejecutar la consulta
    if (!oci_execute($stmt)) {
        throw new Exception("Error al ejecutar la consulta");
    }

    // Obtener resultado
    $row = oci_fetch_assoc($stmt);
    
    if (!$row) {
        jsonResponse(false, 'Usuario o contraseña incorrectos');
    }

    // Verificar si el usuario está activo
    if ($row['ESTADO'] !== 'A') {
        jsonResponse(false, 'Usuario inactivo');
    }

    // Verificar la contraseña
    if (!password_verify($password, $row['PASSWORD_HASH'])) {
        jsonResponse(false, 'Usuario o contraseña incorrectos');
    }

    // Iniciar sesión
    session_start();
    
    // Almacenar datos del usuario en la sesión
    $_SESSION['user_id'] = $row['ID'];
    $_SESSION['username'] = $row['USERNAME'];
    $_SESSION['nombre_completo'] = $row['NOMBRE_COMPLETO'];
    $_SESSION['last_activity'] = time();
    $_SESSION['created'] = time();

    // Regenerar ID de sesión para prevenir session fixation
    session_regenerate_id(true);

    // Registrar el inicio de sesión exitoso
    $log_sql = "INSERT INTO log_accesos (usuario_id, fecha_acceso, ip_address) 
                VALUES (:usuario_id, SYSDATE, :ip_address)";
    
    $log_stmt = oci_parse($conn, $log_sql);
    if ($log_stmt) {
        $user_id = $row['ID'];
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        oci_bind_by_name($log_stmt, ":usuario_id", $user_id);
        oci_bind_by_name($log_stmt, ":ip_address", $ip_address);
        
        oci_execute($log_stmt);
    }

    // Cerrar recursos
    oci_free_statement($stmt);
    if (isset($log_stmt)) {
        oci_free_statement($log_stmt);
    }
    closeOracleConnection($conn);

    // Respuesta exitosa
    jsonResponse(true, 'Login exitoso', [
        'redirect' => 'index.php'
    ]);

} catch (Exception $e) {
    // Registrar el error
    error_log("Error en login.php: " . $e->getMessage());
    
    // Respuesta de error
    jsonResponse(false, 'Error al procesar la solicitud');
}