<?php
// Configuración de zona horaria
date_default_timezone_set('America/Guayaquil');

// Configuración de la base de datos Oracle
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_CONNECTION_STRING', 'localhost/XE'); // Ajustar según la configuración de Oracle

// Función para establecer la conexión a Oracle
function getOracleConnection() {
    try {
        // Establecer la conexión usando OCI8
        $conn = oci_connect(DB_USERNAME, DB_PASSWORD, DB_CONNECTION_STRING, 'AL32UTF8');
        
        if (!$conn) {
            $e = oci_error();
            throw new Exception("Error de conexión: " . $e['message']);
        }
        
        return $conn;
    } catch (Exception $e) {
        // Registrar el error y mostrar mensaje amigable
        error_log("Error de conexión a Oracle: " . $e->getMessage());
        return false;
    }
}

// Función para ejecutar consultas preparadas
function executeQuery($conn, $sql, $params = []) {
    try {
        $stmt = oci_parse($conn, $sql);
        
        if (!$stmt) {
            $e = oci_error($conn);
            throw new Exception("Error al preparar la consulta: " . $e['message']);
        }
        
        // Bind de parámetros
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, $key, $value);
        }
        
        // Ejecutar la consulta
        $result = oci_execute($stmt);
        
        if (!$result) {
            $e = oci_error($stmt);
            throw new Exception("Error al ejecutar la consulta: " . $e['message']);
        }
        
        return $stmt;
    } catch (Exception $e) {
        error_log("Error en la consulta: " . $e->getMessage());
        return false;
    }
}

// Función para cerrar la conexión
function closeOracleConnection($conn) {
    if ($conn) {
        oci_close($conn);
    }
}

// Función para sanitizar entradas
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para validar fecha
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Función para generar respuesta JSON
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}