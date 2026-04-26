<?php
/**
 * Página de Login - Punto de entrada principal
 * 
 * Formulario de inicio de sesión con validación de credenciales,
 * bloqueo tras intentos fallidos y opción "Recordarme".
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

// Si ya hay sesión activa, redirigir al dashboard
if (haySesionActiva()) {
    redirigir('dashboard.php');
}

// Inicializar variables
$error = '';
$email_recordar = '';

// Verificar si hay cookie de "Recordarme" para prellenar el email
if (isset($_COOKIE['recordar_email'])) {
    $email_recordar = e($_COOKIE['recordar_email']);
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $recordarme = isset($_POST['recordarme']);
    
    // Validar campos
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos';
    } else {
        // Intentar login
        $resultado = intentarLogin($email, $password);
        
        if ($resultado['success']) {
            // Establecer cookie de "Recordarme" si se seleccionó
            // IMPORTANTE: Solo guardamos el email, NUNCA la contraseña
            // Las cookies pueden ser interceptadas, por lo que guardar
            // la contraseña sería un grave error de seguridad
            if ($recordarme) {
                establecerCookieRecordar($email);
            }
            
            // Redirigir al dashboard
            redirigir('dashboard.php');
        } else {
            $error = $resultado['message'];
            
            // Si está bloqueado, mostrar tiempo restante
            if ($resultado['bloqueado'] ?? false) {
                $tiempo_restante = BLOQUEO_INTENTOS - (time() - ($_SESSION['ultimo_intento'] ?? time()));
                $minutos = ceil($tiempo_restante / 60);
                $error = "Demasiados intentos fallidos. Espere {$minutos} minutos antes de intentar nuevamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo e(NOMBRE_EMPRESA); ?></title>
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-clock"></i>
                <h1>Control de Horarios</h1>
                <p><?php echo e(NOMBRE_EMPRESA); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control"
                        value="<?php echo $email_recordar; ?>"
                        placeholder="tu@email.com"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="recordarme" value="1">
                        <span class="checkmark"></span>
                        Recordarme en este equipo
                    </label>
                    <small class="help-text">
                        <i class="fas fa-info-circle"></i>
                        Solo se guarda tu email para facilitar el acceso
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo e(NOMBRE_EMPRESA); ?></p>
            </div>
        </div>
    </div>
</body>
</html>