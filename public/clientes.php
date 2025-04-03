<?php
require_once '../includes/header.php';

// Verificar permisos si es necesario
// if (!hasPermission('gestionar_clientes')) {
//     header("Location: index.php");
//     exit;
// }
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-users text-primary me-2"></i>
                        Gestión de Clientes
                    </h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCliente">
                        <i class="fas fa-plus me-2"></i>
                        Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Clientes -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaClientes" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>RUC/CI</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Email</th>
                                    <th>Estado</th>
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

<!-- Modal para Crear/Editar Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user text-primary me-2"></i>
                    <span id="modalTitle">Nuevo Cliente</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCliente" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="clienteId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre/Razón Social</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100">
                            <div class="invalid-feedback">
                                Por favor ingrese el nombre del cliente
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="identificacion" class="form-label">RUC/CI</label>
                            <input type="text" class="form-control" id="identificacion" name="identificacion" 
                                   required maxlength="13" pattern="[0-9-]{10,13}">
                            <div class="invalid-feedback">
                                Por favor ingrese un número de RUC/CI válido
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" 
                                   required maxlength="200">
                            <div class="invalid-feedback">
                                Por favor ingrese la dirección
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   required maxlength="15" pattern="[0-9-+]{7,15}">
                            <div class="invalid-feedback">
                                Por favor ingrese un número de teléfono válido
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   required maxlength="100">
                            <div class="invalid-feedback">
                                Por favor ingrese un email válido
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="observaciones" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" 
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
    const table = $('#tablaClientes').DataTable({
        ajax: {
            url: '/ajax/listar_clientes.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id' },
            { data: 'nombre' },
            { data: 'identificacion' },
            { data: 'direccion' },
            { data: 'telefono' },
            { data: 'email' },
            { 
                data: 'estado',
                render: function(data) {
                    return data === 'A' 
                        ? '<span class="badge bg-success">Activo</span>' 
                        : '<span class="badge bg-danger">Inactivo</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-primary btn-editar" 
                                    data-id="${data.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-eliminar" 
                                    data-id="${data.id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
        },
        responsive: true,
        order: [[1, 'asc']]
    });

    // Limpiar modal al cerrar
    $('#modalCliente').on('hidden.bs.modal', function() {
        $('#formCliente')[0].reset();
        $('#clienteId').val('');
        $('#formCliente').removeClass('was-validated');
        $('#modalTitle').text('Nuevo Cliente');
    });

    // Manejar clic en botón editar
    $('#tablaClientes').on('click', '.btn-editar', function() {
        const id = $(this).data('id');
        
        // Obtener datos del cliente
        $.ajax({
            url: '/ajax/obtener_cliente.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    const cliente = response.data;
                    
                    // Llenar formulario
                    $('#clienteId').val(cliente.id);
                    $('#nombre').val(cliente.nombre);
                    $('#identificacion').val(cliente.identificacion);
                    $('#direccion').val(cliente.direccion);
                    $('#telefono').val(cliente.telefono);
                    $('#email').val(cliente.email);
                    $('#observaciones').val(cliente.observaciones);
                    
                    // Actualizar título y mostrar modal
                    $('#modalTitle').text('Editar Cliente');
                    $('#modalCliente').modal('show');
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    });

    // Manejar clic en botón eliminar
    $('#tablaClientes').on('click', '.btn-eliminar', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Está seguro?',
            text: "Esta acción no se puede revertir",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/ajax/eliminar_cliente.php',
                    type: 'POST',
                    data: { id: id },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            showNotification('Éxito', 'Cliente eliminado correctamente', 'success');
                        } else {
                            showNotification('Error', response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Manejar envío del formulario
    $('#formCliente').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const formData = $(this).serialize();
        const isEdit = $('#clienteId').val() !== '';
        
        $.ajax({
            url: '/ajax/guardar_cliente.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#modalCliente').modal('hide');
                    table.ajax.reload();
                    showNotification(
                        'Éxito',
                        `Cliente ${isEdit ? 'actualizado' : 'creado'} correctamente`,
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