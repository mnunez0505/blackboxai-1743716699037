<?php
require_once '../includes/header.php';

// Obtener estadísticas de cheques
try {
    $conn = getOracleConnection();
    
    // Total de cheques por estado
    $sql_estados = "SELECT 
                        estado,
                        COUNT(*) as total,
                        SUM(monto) as monto_total
                    FROM cheques 
                    GROUP BY estado";
    
    $stmt_estados = oci_parse($conn, $sql_estados);
    oci_execute($stmt_estados);
    
    $estados_cheques = [];
    while ($row = oci_fetch_assoc($stmt_estados)) {
        $estados_cheques[$row['ESTADO']] = [
            'total' => $row['TOTAL'],
            'monto' => $row['MONTO_TOTAL']
        ];
    }
    
    // Cheques próximos a vencer (próximos 7 días)
    $sql_proximos = "SELECT COUNT(*) as total 
                     FROM cheques 
                     WHERE fecha_deposito BETWEEN SYSDATE AND SYSDATE + 7
                     AND estado NOT IN ('DEPOSITADO', 'ANULADO')";
    
    $stmt_proximos = oci_parse($conn, $sql_proximos);
    oci_execute($stmt_proximos);
    $proximos_vencer = oci_fetch_assoc($stmt_proximos)['TOTAL'];
    
    closeOracleConnection($conn);
} catch (Exception $e) {
    error_log("Error en dashboard: " . $e->getMessage());
    $estados_cheques = [];
    $proximos_vencer = 0;
}
?>

<!-- Banner principal -->
<div class="container-fluid mb-4">
    <div class="row">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="card-title mb-3">
                                <i class="fas fa-money-check-alt me-2"></i>
                                Bienvenido al Sistema de Gestión de Cheques
                            </h2>
                            <p class="card-text">
                                Sistema integral para el control y seguimiento de cheques, 
                                facturas y estados de cuenta.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <img src="https://source.unsplash.com/random/400x200/?bank,check" 
                                 alt="Banner" 
                                 class="img-fluid rounded shadow"
                                 style="max-height: 150px; object-fit: cover;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="container-fluid mb-4">
    <div class="row">
        <!-- Cheques Ingresados -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <span class="fa-stack fa-2x text-primary">
                                <i class="fas fa-circle fa-stack-2x opacity-25"></i>
                                <i class="fas fa-file-alt fa-stack-1x"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-0">Cheques Ingresados</h6>
                            <small class="text-muted">Total registrado</small>
                        </div>
                    </div>
                    <h3 class="mb-0">
                        <?php echo number_format($estados_cheques['INGRESADO']['total'] ?? 0); ?>
                    </h3>
                    <small class="text-muted">
                        $<?php echo number_format(($estados_cheques['INGRESADO']['monto'] ?? 0), 2); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Cheques Depositados -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <span class="fa-stack fa-2x text-success">
                                <i class="fas fa-circle fa-stack-2x opacity-25"></i>
                                <i class="fas fa-check-circle fa-stack-1x"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-0">Cheques Depositados</h6>
                            <small class="text-muted">Total procesado</small>
                        </div>
                    </div>
                    <h3 class="mb-0">
                        <?php echo number_format($estados_cheques['DEPOSITADO']['total'] ?? 0); ?>
                    </h3>
                    <small class="text-muted">
                        $<?php echo number_format(($estados_cheques['DEPOSITADO']['monto'] ?? 0), 2); ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Cheques Pendientes -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <span class="fa-stack fa-2x text-warning">
                                <i class="fas fa-circle fa-stack-2x opacity-25"></i>
                                <i class="fas fa-clock fa-stack-1x"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-0">Cheques Pendientes</h6>
                            <small class="text-muted">Por depositar</small>
                        </div>
                    </div>
                    <h3 class="mb-0"><?php echo $proximos_vencer; ?></h3>
                    <small class="text-muted">Próximos 7 días</small>
                </div>
            </div>
        </div>

        <!-- Cheques Anulados -->
        <div class="col-md-3 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0">
                            <span class="fa-stack fa-2x text-danger">
                                <i class="fas fa-circle fa-stack-2x opacity-25"></i>
                                <i class="fas fa-times-circle fa-stack-1x"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-title mb-0">Cheques Anulados</h6>
                            <small class="text-muted">Total cancelado</small>
                        </div>
                    </div>
                    <h3 class="mb-0">
                        <?php echo number_format($estados_cheques['ANULADO']['total'] ?? 0); ?>
                    </h3>
                    <small class="text-muted">
                        $<?php echo number_format(($estados_cheques['ANULADO']['monto'] ?? 0), 2); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Accesos Rápidos -->
<div class="container-fluid mb-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Accesos Rápidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Registrar Cheque -->
                        <div class="col-md-3">
                            <a href="registro_cheque.php" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="fas fa-plus-circle fa-2x text-primary"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-0">Registrar Cheque</h6>
                                        <small class="text-muted">Nuevo registro</small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Ver Historial -->
                        <div class="col-md-3">
                            <a href="historial_cheques.php" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="fas fa-history fa-2x text-info"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-0">Historial</h6>
                                        <small class="text-muted">Ver movimientos</small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Gestionar Clientes -->
                        <div class="col-md-3">
                            <a href="clientes.php" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="fas fa-users fa-2x text-success"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-0">Clientes</h6>
                                        <small class="text-muted">Administrar clientes</small>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!-- Reportes -->
                        <div class="col-md-3">
                            <a href="reporte_estado_cuenta.php" class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <i class="fas fa-chart-bar fa-2x text-warning"></i>
                                    <div class="ms-3">
                                        <h6 class="mb-0">Reportes</h6>
                                        <small class="text-muted">Ver estados de cuenta</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>