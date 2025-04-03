<?php
require_once '../config/config.php';
session_start();

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Incluir solo el header básico (sin menú)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestión de Cheques</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/custom.css">

    <style>
        body {
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            font-size: 1.1rem;
            border-radius: 10px;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-money-check-alt"></i>
            <h2>Sistema de Gestión de Cheques</h2>
            <p class="text-muted">Ingrese sus credenciales para continuar</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="/ajax/login.php">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" class="form-control" id="username" name="username" required 
                           placeholder="Ingrese su usuario">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" required 
                           placeholder="Ingrese su contraseña">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                Iniciar Sesión
            </button>
        </form>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function() {
        // Toggle password visibility
        $('#togglePassword').click(function() {
            const passwordInput = $('#password');
            const icon = $(this).find('i');
            
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Handle form submission
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        window.location.href = 'index.php';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Error al iniciar sesión',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error de conexión',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                }
            });
        });
    });
    </script>
</body>
</html>