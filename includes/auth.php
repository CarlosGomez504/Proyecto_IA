<?php
/**
 * Sistema de autenticación y control de sesiones
 * 
 * Este archivo gestiona el inicio de sesión, verificación
 * de sesiones y control de accesos según el rol del usuario.
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si un usuario ha iniciado sesión
 * 
 * @return bool True si hay sesión activa, false en caso contrario
 */
function haySesionActiva() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario ha iniciado sesión, redirige al login si no
 */
function requerirLogin() {
    if (!haySesionActiva()) {
        // Verificar si hay cookie de "Recordarme"
        if (isset($_COOKIE['recordar_email'])) {
            // Podríamos intentar auto-login con la cookie
            // Pero por seguridad, mejor redirigir al login
        }
        
        header('Location: ' . URL_BASE . 'index.php');
        exit;
    }
}

/**
 * Verifica si el usuario tiene el rol requerido
 * 
 * @param string|array $roles Rol o array de roles permitidos
 * @return bool True si tiene el rol, false en caso contrario
 */
function tenerRol($roles) {
    if (!haySesionActiva()) {
        return false;
    }
    
    $rol_usuario = $_SESSION['usuario_rol'];
    
    if (is_array($roles)) {
        return in_array($rol_usuario, $roles);
    }
    
    return $rol_usuario === $roles;
}

/**
 * Verifica el rol y redirige si no tiene permiso
 * 
 * @param string|array $roles Rol o array de roles permitidos
 */
function requerirRol($roles) {
    requerirLogin();
    
    if (!tenerRol($roles)) {
        header('Location: ' . URL_BASE . 'dashboard.php?error=permisos');
        exit;
    }
}

/**
 * Intenta iniciar sesión con email y contraseña
 * 
 * @param string $email Email del usuario
 * @param string $password Contraseña en texto plano
 * @return array ['success' => bool, 'message' => string, 'bloqueado' => bool]
 */
function intentarLogin($email, $password) {
    // Limpiar entradas
    $email = trim($email);
    $password = trim($password);
    
    // Validar email con filter_var
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email no válido'];
    }
    
    // Convertir email a minúsculas para comparación insensible a mayúsculas
    $email = strtolower($email);
    
    // Verificar si está bloqueado por muchos intentos
    if (estaBloqueado()) {
        return ['success' => false, 'message' => 'Demasiados intentos fallidos. Intente en 15 minutos.', 'bloqueado' => true];
    }
    
    // Incluir conexión a base de datos
    require_once __DIR__ . '/db.php';
    
    try {
        $conexion = obtenerConexion();
        
        // Consulta preparada para evitar inyección SQL
        $sql = "SELECT id, codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, activo 
                FROM usuarios 
                WHERE email = :email AND activo = 1 
                LIMIT 1";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        $usuario = $stmt->fetch();
        
        if (!$usuario) {
            // Usuario no encontrado, registrar intento fallido
            registrarIntentoFallido();
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
        // Verificar contraseña con password_verify
        if (!password_verify($password, $usuario['password_hash'])) {
            // Contraseña incorrecta, registrar intento fallido
            registrarIntentoFallido();
            return ['success' => false, 'message' => 'Credenciales incorrectas'];
        }
        
        // Login exitoso - limpiar intentos fallidos
        limpiarIntentosFallidos();
        
        // Guardar datos en sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_codigo'] = $usuario['codigo_empleado'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_rol'] = $usuario['rol'];
        $_SESSION['usuario_departamento_id'] = $usuario['departamento_id'];
        $_SESSION['usuario_login_time'] = time();
        
        // Regenerar ID de sesión para prevenir fijación de sesión
        session_regenerate_id(true);
        
        return ['success' => true, 'message' => 'Login exitoso'];
        
    } catch (PDOException $e) {
        // No mostrar error real
        error_log("Error en login: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error del sistema. Intente más tarde.'];
    }
}

/**
 * Cierra la sesión del usuario actual
 */
function cerrarSesion() {
    // Limpiar todas las variables de sesión
    $_SESSION = [];
    
    // Destruir la cookie de sesión
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destruir la sesión
    session_destroy();
    
    // Eliminar cookie de "Recordarme" si existe
    if (isset($_COOKIE['recordar_email'])) {
        setcookie('recordar_email', '', time() - 3600, '/');
    }
}

/**
 * Registra un intento fallido de login
 */
function registrarIntentoFallido() {
    if (!isset($_SESSION['intentos_fallidos'])) {
        $_SESSION['intentos_fallidos'] = 0;
    }
    $_SESSION['intentos_fallidos']++;
    $_SESSION['ultimo_intento'] = time();
}

/**
 * Limpia los intentos fallidos tras un login exitoso
 */
function limpiarIntentosFallidos() {
    unset($_SESSION['intentos_fallidos']);
    unset($_SESSION['ultimo_intento']);
}

/**
 * Verifica si el usuario está bloqueado por muchos intentos fallidos
 * 
 * @return bool True si está bloqueado, false en caso contrario
 */
function estaBloqueado() {
    if (!isset($_SESSION['intentos_fallidos'])) {
        return false;
    }
    
    $intentos = $_SESSION['intentos_fallidos'];
    $ultimo_intento = $_SESSION['ultimo_intento'] ?? 0;
    
    if ($intentos >= MAX_INTENTOS) {
        $tiempo_transcurrido = time() - $ultimo_intento;
        if ($tiempo_transcurrido < BLOQUEO_INTENTOS) {
            return true;
        }
        // Ya pasó el tiempo de bloqueo, limpiar
        limpiarIntentosFallidos();
    }
    
    return false;
}

/**
 * Obtiene los datos del usuario actual
 * 
 * @return array|null Datos del usuario o null si no hay sesión
 */
function obtenerUsuarioActual() {
    if (!haySesionActiva()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['usuario_id'],
        'codigo' => $_SESSION['usuario_codigo'],
        'nombre' => $_SESSION['usuario_nombre'],
        'apellidos' => $_SESSION['usuario_apellidos'],
        'email' => $_SESSION['usuario_email'],
        'rol' => $_SESSION['usuario_rol'],
        'departamento_id' => $_SESSION['usuario_departamento_id']
    ];
}

/**
 * Establece una cookie para "Recordarme"
 * 
 * IMPORTANTE: Solo guardamos el email, NUNCA la contraseña.
 * Guardar la contraseña en una cookie sería un grave error
 * de seguridad, ya que las cookies pueden ser robadas o
 * interceptadas. El email es información pública que no
 * compromete la seguridad de la cuenta.
 * 
 * @param string $email Email del usuario
 */
function establecerCookieRecordar($email) {
    // Solo guardamos el email, nunca la contraseña
    // La contraseña debe permanecer segura en la base de datos
    setcookie('recordar_email', $email, time() + (86400 * 30), '/'); // 30 días
}