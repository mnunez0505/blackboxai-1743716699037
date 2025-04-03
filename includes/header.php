<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/session.php';

// Verificar sesión en todas las páginas excepto login
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php') {
    checkSession();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Cheques</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/custom.css">
</head>
<body>
    <?php if ($current_page !== 'login.php'): ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/index.php">
                <i class="fas fa-money-check-alt me-2"></i>
                Sistema de Cheques
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/public/index.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/registro_cheque.php">
                            <i class="fas fa-plus-circle"></i> Registrar Cheque
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/historial_cheques.php">
                            <i class="fas fa-history"></i> Historial
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/clientes.php">
                            <i class="fas fa-users"></i> Clientes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/facturas.php">
                            <i class="fas fa-file-invoice"></i> Facturas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/reporte_estado_cuenta.php">
                            <i class="fas fa-chart-bar"></i> Reportes
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/public/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Contenedor principal -->
    <div class="container-fluid py-4">
        <!-- Los mensajes de alerta se mostrarán aquí -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>