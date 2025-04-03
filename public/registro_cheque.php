<?php
require_once '../includes/header.php';

// Obtener lista de clientes para el select
try {
    $conn = getOracleConnection();
    $sql_clientes = "SELECT id, nombre FROM clientes WHERE estado = 'A' ORDER BY nombre";
    $stmt_clientes = oci_parse($conn, $sql_clientes);
    oci_execute($stmt_clientes);
    
    $clientes = [];
    while ($row = oci_fetch_assoc($stmt_clientes)) {
        $clientes[] = $row;
    }
    
    closeOracleConnection($conn);
} catch (Exception $e) {
    error_log("Error al obtener clientes: " . $e->getMessage());
    $clientes = [];
}
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Registro de Cheques
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario de registro -->
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="formRegistroCheque" class="needs-validation" novalidate>
                        <!-- Información del Cheque -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-money-check text-primary me-2"></i>
                                    Información del Cheque
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="numeroCheque" class="form-label">Número de Cheque</label>
                                <input type="text" class="form-control" id="numeroCheque" name="numeroCheque" 
                                       required pattern="[0-9]+" maxlength="20">
                                <div class="invalid-feedback">
                                    Por favor ingrese un número de cheque válido
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="monto" class="form-label">Monto</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto" name="monto" 
                                           required step="0.01" min="0.01">
                                    <div class="invalid-feedback">
                                        Por favor ingrese un monto válido
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fechaEmision" class="form-label">Fecha de Emisión</label>
                                <input type="date" class="form-control" id="fechaEmision" name="fechaEmision" 
                                       required>
                                <div class="invalid-feedback">
                                    Por favor seleccione la fecha de emisión
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="fechaDeposito" class="form-label">Fecha de Depósito</label>
                                <input type="date" class="form-control" id="fechaDeposito" name="fechaDeposito" 
                                       required>
                                <div class="invalid-feedback">
                                    Por favor seleccione la fecha de depósito
                                </div>
                            </div>
                        </div>

                        <!-- Información del Beneficiario -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-user text-primary me-2"></i>
                                    Información del Beneficiario
                                </h5>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="beneficiario" class="form-label">Nombre del Beneficiario</label>
                                <input type="text" class="form-control" id="beneficiario" name="beneficiario" 
                                       required maxlength="100">
                                <div class="invalid-feedback">
                                    Por favor ingrese el nombre del beneficiario
                                </div>
                            </div>
                        </div>

                        <!-- Información de la Factura -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2">
                                    <i class="fas fa-file-invoice text-primary me-2"></i>
                                    Información de la Factura
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cliente" class="form-label">Cliente</label>
                                <select class="form-select" id="cliente" name="cliente_id" required>
                                    <option value="">Seleccione un cliente</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?php echo htmlspecialchars($cliente['ID']); ?>">
                                            <?php echo htmlspecialchars($cliente['NOMBRE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor seleccione un cliente
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="numeroFactura" class="form-label">Número de Factura</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="numeroFactura" name="numeroFactura" 
                                           required pattern="[0-9-]+" maxlength="20">
                                    <button class="btn btn-outline-secondary" type="button" id="buscarFactura">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor ingrese un número de factura válido
                                </div>
                            </div>

                            <!-- Div para mostrar información de la factura -->
                            <div class="col-12">
                                <div id="infoFactura" class="alert alert-info d-none">
                                    <!-- La información de la factura se mostrará aquí -->
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-light me-2" onclick="window.history.back()">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Guardar Cheque
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel lateral de ayuda -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información Importante
                    </h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Asegúrese de ingresar correctamente el número de cheque
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            La fecha de depósito debe ser posterior a la fecha de emisión
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Verifique que la factura corresponda al cliente seleccionado
                        </li>
                        <li>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            El monto del cheque debe coincidir con el valor de la factura
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Establecer fecha mínima para fecha de depósito
    $('#fechaEmision').on('change', function() {
        $('#fechaDeposito').attr('min', $(this).val());
    });

    // Validación del formulario
    $('#formRegistroCheque').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        // Recoger datos del formulario
        const formData = $(this).serialize();

        // Enviar datos al servidor
        $.ajax({
            url: '/ajax/guardar_cheque.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Cheque registrado correctamente',
                        showCancelButton: true,
                        confirmButtonText: 'Registrar otro',
                        cancelButtonText: 'Ir al listado'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Limpiar formulario
                            $('#formRegistroCheque')[0].reset();
                            $('#infoFactura').addClass('d-none');
                            $(this).removeClass('was-validated');
                        } else {
                            // Ir al listado de cheques
                            window.location.href = 'historial_cheques.php';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Error al registrar el cheque'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión al servidor'
                });
            }
        });
    });

    // Buscar factura
    $('#buscarFactura').on('click', function() {
        const numeroFactura = $('#numeroFactura').val();
        const clienteId = $('#cliente').val();

        if (!numeroFactura || !clienteId) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Por favor seleccione un cliente e ingrese un número de factura'
            });
            return;
        }

        $.ajax({
            url: '/ajax/buscar_factura.php',
            type: 'GET',
            data: { 
                numero: numeroFactura,
                cliente_id: clienteId
            },
            success: function(response) {
                if (response.success) {
                    $('#infoFactura')
                        .removeClass('d-none alert-danger')
                        .addClass('alert-info')
                        .html(`
                            <h6 class="alert-heading">Información de la Factura</h6>
                            <p class="mb-0">
                                <strong>Cliente:</strong> ${response.data.cliente}<br>
                                <strong>Monto:</strong> $${response.data.monto}<br>
                                <strong>Fecha:</strong> ${response.data.fecha}
                            </p>
                        `);
                    
                    // Autocompletar el monto si está vacío
                    if (!$('#monto').val()) {
                        $('#monto').val(response.data.monto);
                    }
                } else {
                    $('#infoFactura')
                        .removeClass('d-none alert-info')
                        .addClass('alert-danger')
                        .html(`
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message || 'Factura no encontrada'}
                        `);
                }
            },
            error: function() {
                $('#infoFactura')
                    .removeClass('d-none alert-info')
                    .addClass('alert-danger')
                    .html(`
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error de conexión al servidor
                    `);
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>