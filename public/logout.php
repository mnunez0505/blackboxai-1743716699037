<?php
require_once '../config/config.php';
session_start();

try {
    // Registrar el cierre de sesión si hay un usuario activo
    if (isset($_SESSION['user_id'])) {
        $conn = getOracleConnection();
        
        if ($conn) {
            $sql = "UPDATE log_accesos 
                    SET fecha_salida = SYSDATE 
                    WHERE usuario_id = :usuario_id 
                    AND fecha_salida IS NULL";
            
            $stmt = oci_parse($conn, $sql);
            
            if ($stmt) {
                $user_id = $_SESSION['user_id'];
                oci_bind_by_name($stmt, ":usuario_id", $user_id);
                oci_execute($stmt);
                oci_free_statement($stmt);
            }
            
            closeOracleConnection($conn);
        }
    }

    // Destruir todas las variables de sesión
    $_SESSION = array();

    // Destruir la cookie de sesión si existe
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }

    // Destruir la sesión
    session_destroy();

} catch (Exception $e) {
    error_log("Error en logout.php: " . $e->getMessage());
} finally {
    // Redirigir al login independientemente de si hubo error
    header("Location: login.php");
    exit;
}