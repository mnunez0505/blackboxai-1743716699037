<?php
require_once '../includes/header.php';

// Obtener estados disponibles para el filtro
$estados = [
    'INGRESADO' => 'Ingresado',
    'CRUZADO' => 'Cruzado',
    'DEPOSITADO' => 'Depositado',
    'ANULADO' => 'Anulado'
];
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="fas fa-history text-primary me-2"></i>
                            Historial de Cheques
                        </h4>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary" id="btnExportar">
                                <i class="fas fa-file-excel me-2"></i>
                                Exportar
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="btnImprimir">
                                <i class="fas fa-print me-2"></i>
                                Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="formFiltros" class="row g-3">
                        <div class="col-md-2">
                            <label for="filtroEstado" class="form-label">Estado</label>
                            <select class="form-select" id="filtroEstado" name="estado">
                                <option value="">Todos</option>
                                <?php foreach ($estados as $valor => $texto): ?>
                                    <option value="<?php echo htmlspecialchars($valor); ?>">
                                        <?php echo htmlspecialchars($texto); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="filtroFechaDesde" class="form-label">Fecha Desde</label>
                            <input type="date" class="form-control" id="filtroFechaDesde" name="fecha_desde">
                        </div>

                        <div class="col-md-2">
                            <label for="filtroFechaHasta" class="form-label">Fecha Hasta</label>
                            <input type="date" class="form-control" id="filtroFechaHasta" name="fecha_hasta">
                        </div>

                        <div class="col-md-3">
                            <label for="filtroBeneficiario" class="form-label">Beneficiario</label>
                            <input type="text" class="form-control" id="filtroBeneficiario" name="beneficiario">
                        </div>

                        <div class="col-md-3">
                            <label for="filtroNumero" class="form-label">Número de Cheque</label>
                            <input type="text" class="form-control" id="filtroNumero" name="numero">
                        </div>

                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-light" id="btnLimpiarFiltros">
                                <i class="fas fa-eraser me-2"></i>
                                Limpiar
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>
                                Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Historial -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaHistorial" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Beneficiario</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Fecha Emisión</th>
                                    <th>Fecha Depósito</th>
                                    <th>Factura</th>
                                    <th>Cliente</th>
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

<!-- Modal para Cambiar Estado -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>
                    Cambiar Estado del Cheque
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCambiarEstado">
                <div class="modal-body">
                    <input type="hidden" id="chequeId" name="id">
                    
                    <div class="mb-3">
                        <label for="nuevoEstado" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="nuevoEstado" name="estado" required>
                            <option value="">Seleccione un estado</option>
                            <?php foreach ($estados as $valor => $texto): ?>
                                <option value="<?php echo htmlspecialchars($valor); ?>">
                                    <?php echo htmlspecialchars($texto); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3" id="divFechaDeposito" style="display: none;">
                        <label for="fechaDeposito" class="form-label">Fecha de Depósito</label>
                        <input type="date" class="form-control" id="fechaDeposito" name="fecha_deposito">
                    </div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observación</label>
                        <textarea class="form-control" id="observacion" name="observacion" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Ver Historial -->
<div class="modal fade" id="modalVerHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clock text-primary me-2"></i>
                    Historial de Cambios
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tablaCambios" class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estado Anterior</th>
                                <th>Estado Nuevo</th>
                                <th>Usuario</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    const table = $('#tablaHistorial').DataTable({
        ajax: {
            url: '/ajax/listar_historial_cheques.php',
            data: function(d) {
                return $.extend({}, d, {
                    estado: $('#filtroEstado').val(),
                    fecha_desde: $('#filtroFechaDesde').val(),
                    fecha_hasta: $('#filtroFechaHasta').val(),
                    beneficiario: $('#filtroBeneficiario').val(),
                    numero: $('#filtroNumero').val()
                });
            }
        },
        columns: [
            { data: 'numero_cheque' },
            { data: 'beneficiario' },
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
                        'INGRESADO': '<span class="badge bg-primary">Ingresado</span>',
                        'CRUZADO': '<span class="badge bg-info">Cruzado</span>',
                        'DEPOSITADO': '<span class="badge bg-success">Depositado</span>',
                        'ANULADO': '<span class="badge bg-danger">Anulado</span>'
                    };
                    return estados[data] || data;
                }
            },
            { 
                data: 'fecha_emision',
                render: function(data) {
                    return new Date(data).toLocaleDateString('es-EC');
                }
            },
            { 
                data: 'fecha_deposito',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString('es-EC') : '-';
                }
            },
            { data: 'numero_factura' },
            { data: 'cliente' },
            {
                data: null,
                render: function(data) {
                    let buttons = `
                        <button type="button" class="btn btn-sm btn-info btn-ver-historial" 
                                data-id="${data.id}" title="Ver historial">
                            <i class="fas fa-clock"></i>
                        </button>
                    `;
                    
                    if (data.estado !== 'ANULADO' && data.estado !== 'DEPOSITADO') {
                        buttons += `
                            <button type="button" class="btn btn-sm btn-primary btn-cambiar-estado" 
                                    data-id="${data.id}" title="Cambiar estado">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        `;
                    }
                    
                    return `<div class="btn-group btn-group-sm">${buttons}</div>`;
                }
            }
        ],
        order: [[4, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        responsive: true
    });

    // Manejar filtros
    $('#formFiltros').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Limpiar filtros
    $('#btnLimpiarFiltros').on('click', function() {
        $('#formFiltros')[0].reset();
        table.ajax.reload();
    });

    // Mostrar/ocultar campo de fecha de depósito según el estado
    $('#nuevoEstado').on('change', function() {
        $('#divFechaDeposito').toggle($(this).val() === 'DEPOSITADO');
        if ($(this).val() === 'DEPOSITADO') {
            $('#fechaDeposito').prop('required', true);
        } else {
            $('#fechaDeposito').prop('required', false);
        }
    });

    // Manejar cambio de estado
    $('#tablaHistorial').on('click', '.btn-cambiar-estado', function() {
        const id = $(this).data('id');
        $('#chequeId').val(id);
        $('#modalEstado').modal('show');
    });

    // Ver historial de cambios
    $('#tablaHistorial').on('click', '.btn-ver-historial', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: '/ajax/obtener_cambios_cheque.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    const tbody = $('#tablaCambios tbody');
                    tbody.empty();
                    
                    response.data.forEach(function(cambio) {
                        tbody.append(`
                            <tr>
                                <td>${new Date(cambio.fecha).toLocaleString('es-EC')}</td>
                                <td>${cambio.estado_anterior || '-'}</td>
                                <td>${cambio.estado_nuevo}</td>
                                <td>${cambio.usuario}</td>
                                <td>${cambio.observacion}</td>
                            </tr>
                        `);
                    });
                    
                    $('#modalVerHistorial').modal('show');
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    });

    // Procesar cambio de estado
    $('#formCambiarEstado').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        $.ajax({
            url: '/ajax/cambiar_estado_cheque.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    $('#modalEstado').modal('hide');
                    table.ajax.reload();
                    showNotification('Éxito', 'Estado actualizado correctamente', 'success');
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    });

    // Exportar a Excel
    $('#btnExportar').on('click', function() {
        const filtros = {
            estado: $('#filtroEstado').val(),
            fecha_desde: $('#filtroFechaDesde').val(),
            fecha_hasta: $('#filtroFechaHasta').val(),
            beneficiario: $('#filtroBeneficiario').val(),
            numero: $('#filtroNumero').val()
        };

        window.location.href = '/ajax/exportar_historial_cheques.php?' + $.param(filtros);
    });

    // Imprimir
    $('#btnImprimir').on('click', function() {
        window.print();
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>