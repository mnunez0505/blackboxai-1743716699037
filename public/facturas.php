<?php
require_once '../includes/header.php';

// Obtener lista de clientes activos para el select
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
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-file-invoice text-primary me-2"></i>
                        Gestión de Facturas
                    </h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalFactura">
                        <i class="fas fa-plus me-2"></i>
                        Nueva Factura
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Facturas -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaFacturas" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Cheque Asociado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Factura -->
<div class="modal fade" id="modalFactura" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-invoice text-primary me-2"></i>
                    <span id="modalTitle">Nueva Factura</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formFactura" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="facturaId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="numeroFactura" class="form-label">Número de Factura</label>
                            <input type="text" class="form-control" id="numeroFactura" name="numero" 
                                   required pattern="[0-9-]{1,20}" maxlength="20">
                            <div class="invalid-feedback">
                                Por favor ingrese un número de factura válido
                            </div>
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
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" required>
                            <div class="invalid-feedback">
                                Por favor seleccione una fecha
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

                        <div class="col-md-12 mb-3">
                            <label for="concepto" class="form-label">Concepto</label>
                            <textarea class="form-control" id="concepto" name="concepto" 
                                      rows="3" maxlength="500"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    const table = $('#tablaFacturas').DataTable({
        ajax: {
            url: '/ajax/listar_facturas.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'numero' },
            { data: 'cliente' },
            { 
                data: 'fecha',
                render: function(data) {
                    return new Date(data).toLocaleDateString('es-EC');
                }
            },
            { 
                data: 'monto',
                render: function(data) {
                    return `$${parseFloat(data).toFixed(2)}`;
                }
            },
            { 
                data: 'estado',
                render: function(data) {
                    const estados = {
                        'A': '<span class="badge bg-success">Activa</span>',
                        'C': '<span class="badge bg-info">Cruzada</span>',
                        'N': '<span class="badge bg-danger">Anulada</span>'
                    };
                    return estados[data] || data;
                }
            },
            { 
                data: 'cheque_numero',
                render: function(data, type, row) {
                    return data ? `<span class="badge bg-primary">${data}</span>` : 
                                '<span class="badge bg-secondary">Sin cheque</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    const btns = [];
                    
                    if (data.estado === 'A') {
                        btns.push(`
                            <button type="button" class="btn btn-sm btn-primary btn-editar" 
                                    data-id="${data.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                        `);
                        
                        if (!data.cheque_numero) {
                            btns.push(`
                                <button type="button" class="btn btn-sm btn-danger btn-anular" 
                                        data-id="${data.id}" title="Anular">
                                    <i class="fas fa-times"></i>
                                </button>
                            `);
                        }
                    }
                    
                    return btns.length ? `<div class="btn-group btn-group-sm">${btns.join('')}</div>` : '';
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        order: [[2, 'desc']],
        responsive: true
    });

    // Limpiar modal al cerrar
    $('#modalFactura').on('hidden.bs.modal', function() {
        $('#formFactura')[0].reset();
        $('#facturaId').val('');
        $('#formFactura').removeClass('was-validated');
        $('#modalTitle').text('Nueva Factura');
    });

    // Manejar clic en botón editar
    $('#tablaFacturas').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: '/ajax/obtener_factura.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    const factura = response.data;
                    
                    $('#facturaId').val(factura.id);
                    $('#numeroFactura').val(factura.numero);
                    $('#cliente').val(factura.cliente_id);
                    $('#fecha').val(factura.fecha);
                    $('#monto').val(factura.monto);
                    $('#concepto').val(factura.concepto);
                    
                    $('#modalTitle').text('Editar Factura');
                    $('#modalFactura').modal('show');
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    });

    // Manejar clic en botón anular
    $('#tablaFacturas').on('click', '.btn-anular', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción no se puede revertir",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/ajax/anular_factura.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            showNotification('Éxito', 'Factura anulada correctamente', 'success');
                        } else {
                            showNotification('Error', response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Manejar envío del formulario
    $('#formFactura').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const formData = $(this).serialize();
        const isEdit = $('#facturaId').val() !== '';
        
        $.ajax({
            url: '/ajax/guardar_factura.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#modalFactura').modal('hide');
                    table.ajax.reload();
                    showNotification(
                        'Éxito',
                        `Factura ${isEdit ? 'actualizada' : 'creada'} correctamente`,
                        'success'
                    );
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>