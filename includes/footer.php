</div> <!-- Cierre del container-fluid del header -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (necesario para algunas funcionalidades AJAX) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert2 para notificaciones más atractivas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JavaScript -->
    <script src="/js/main.js"></script>

    <script>
    // Función para mostrar notificaciones
    function showNotification(title, message, type = 'success') {
        Swal.fire({
            title: title,
            text: message,
            icon: type,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }

    // Inicialización de tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Función para confirmar acciones
    function confirmAction(message, callback) {
        Swal.fire({
            title: '¿Está seguro?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                callback();
            }
        });
    }

    // Manejo global de errores AJAX
    $(document).ajaxError(function(event, jqXHR, settings, error) {
        let message = 'Ha ocurrido un error en la operación';
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
            message = jqXHR.responseJSON.message;
        }
        showNotification('Error', message, 'error');
    });
    </script>
</body>
</html>