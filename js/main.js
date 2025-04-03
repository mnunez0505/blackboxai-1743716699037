// Configuración global de AJAX
$.ajaxSetup({
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});

// Objeto para manejar los cheques
const chequeManager = {
    // Registrar nuevo cheque
    registrar: function(formData) {
        $.ajax({
            url: '/ajax/guardar_cheque.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Éxito', 'Cheque registrado correctamente', 'success');
                    // Limpiar formulario
                    $('#formRegistroCheque')[0].reset();
                    // Actualizar tabla si existe
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
                    }
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    },

    // Actualizar estado de cheque
    actualizarEstado: function(chequeId, nuevoEstado, observacion = '') {
        $.ajax({
            url: '/ajax/actualizar_estado_cheque.php',
            type: 'POST',
            data: {
                cheque_id: chequeId,
                estado: nuevoEstado,
                observacion: observacion
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Éxito', 'Estado actualizado correctamente', 'success');
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
                    }
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    },

    // Buscar factura
    buscarFactura: function(numeroFactura) {
        return $.ajax({
            url: '/ajax/buscar_factura.php',
            type: 'GET',
            data: { numero: numeroFactura }
        });
    }
};

// Objeto para manejar los clientes
const clienteManager = {
    guardar: function(formData) {
        $.ajax({
            url: '/ajax/guardar_cliente.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#modalCliente').modal('hide');
                    showNotification('Éxito', 'Cliente guardado correctamente', 'success');
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
                    }
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    },

    eliminar: function(clienteId) {
        confirmAction('¿Está seguro de eliminar este cliente?', function() {
            $.ajax({
                url: '/ajax/eliminar_cliente.php',
                type: 'POST',
                data: { id: clienteId },
                success: function(response) {
                    if (response.success) {
                        showNotification('Éxito', 'Cliente eliminado correctamente', 'success');
                        if (typeof dataTable !== 'undefined') {
                            dataTable.ajax.reload();
                        }
                    } else {
                        showNotification('Error', response.message, 'error');
                    }
                }
            });
        });
    }
};

// Objeto para manejar las facturas
const facturaManager = {
    guardar: function(formData) {
        $.ajax({
            url: '/ajax/guardar_factura.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#modalFactura').modal('hide');
                    showNotification('Éxito', 'Factura guardada correctamente', 'success');
                    if (typeof dataTable !== 'undefined') {
                        dataTable.ajax.reload();
                    }
                } else {
                    showNotification('Error', response.message, 'error');
                }
            }
        });
    },

    eliminar: function(facturaId) {
        confirmAction('¿Está seguro de eliminar esta factura?', function() {
            $.ajax({
                url: '/ajax/eliminar_factura.php',
                type: 'POST',
                data: { id: facturaId },
                success: function(response) {
                    if (response.success) {
                        showNotification('Éxito', 'Factura eliminada correctamente', 'success');
                        if (typeof dataTable !== 'undefined') {
                            dataTable.ajax.reload();
                        }
                    } else {
                        showNotification('Error', response.message, 'error');
                    }
                }
            });
        });
    }
};

// Event Listeners
$(document).ready(function() {
    // Formulario de registro de cheque
    $('#formRegistroCheque').on('submit', function(e) {
        e.preventDefault();
        chequeManager.registrar($(this).serialize());
    });

    // Búsqueda de factura en tiempo real
    $('#numeroFactura').on('keyup', function() {
        const numero = $(this).val();
        if (numero.length >= 3) {
            chequeManager.buscarFactura(numero)
                .done(function(response) {
                    if (response.success) {
                        $('#infoFactura').html(`
                            <div class="alert alert-info">
                                Cliente: ${response.data.cliente}<br>
                                Monto: ${response.data.monto}<br>
                                Fecha: ${response.data.fecha}
                            </div>
                        `);
                    } else {
                        $('#infoFactura').html('');
                    }
                });
        }
    });

    // Formulario de cliente
    $('#formCliente').on('submit', function(e) {
        e.preventDefault();
        clienteManager.guardar($(this).serialize());
    });

    // Formulario de factura
    $('#formFactura').on('submit', function(e) {
        e.preventDefault();
        facturaManager.guardar($(this).serialize());
    });

    // Inicialización de DataTables si existe la tabla
    if ($('#tablaCheques').length) {
        window.dataTable = $('#tablaCheques').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            },
            responsive: true,
            processing: true,
            serverSide: true,
            ajax: '/ajax/listar_cheques.php'
        });
    }

    // Cambio de estado de cheque
    $('.btn-cambiar-estado').on('click', function() {
        const chequeId = $(this).data('cheque-id');
        const nuevoEstado = $(this).data('estado');
        
        Swal.fire({
            title: 'Cambiar Estado',
            text: '¿Desea agregar una observación?',
            input: 'text',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                chequeManager.actualizarEstado(chequeId, nuevoEstado, result.value);
            }
        });
    });
});

// Función para formatear moneda
function formatMoney(amount) {
    return new Intl.NumberFormat('es-EC', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Función para formatear fecha
function formatDate(date) {
    return new Date(date).toLocaleDateString('es-EC', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

// Validaciones personalizadas
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Función para exportar a Excel
function exportToExcel(tableId, fileName) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet JS"});
    XLSX.writeFile(wb, `${fileName}.xlsx`);
}

// Función para imprimir
function printTable(tableId) {
    const printContent = document.getElementById(tableId).outerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
}