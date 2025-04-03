<?php
session_start();

// Configuración de seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Tiempo de expiración de sesión (2 horas)
$session_timeout = 7200;

function checkSession() {
    global $session_timeout;
    
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['user_id'])) {
        header("Location: /login.php");
        exit;
    }
    
    // Verificar tiempo de inactividad
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        session_unset();
        session_destroy();
        header("Location: /login.php?msg=timeout");
        exit;
    }
    
    // Regenerar ID de sesión periódicamente para prevenir session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    // Actualizar último tiempo de actividad
    $_SESSION['last_activity'] = time();
}

// Función para verificar si el usuario tiene permisos específicos
function hasPermission($permission) {
    if (!isset($_SESSION['user_permissions'])) {
        return false;
    }
    return in_array($permission, $_SESSION['user_permissions']);
}

// Función para obtener información del usuario actual
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nombre_completo' => $_SESSION['nombre_completo'] ?? ''
    ];
}