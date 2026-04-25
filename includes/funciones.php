<?php
/**
 * Funciones auxiliares de uso general
 * 
 * Este archivo contiene funciones útiles que se utilizan
 * en múltiples partes de la aplicación.
 */

/**
 * Limpia y sanitiza un string para mostrar en HTML
 * Usa htmlspecialchars para prevenir ataques XSS
 * 
 * @param string $texto Texto a sanitizar
 * @return string Texto sanitizado
 */
function e($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Valida y sanitiza un email
 * 
 * @param string $email Email a validar
 * @return string|false Email sanitizado o false si no es válido
 */
function validarEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    return false;
}

/**
 * Formatea una fecha para mostrar en español
 * 
 * @param string $fecha Fecha en formato MySQL
 * @param string $formato Tipo de formato (fecha, hora, completo)
 * @return string Fecha formateada
 */
function formatearFecha($fecha, $formato = 'completo') {
    $timestamp = strtotime($fecha);
    
    switch ($formato) {
        case 'fecha':
            return date('d/m/Y', $timestamp);
        case 'hora':
            return date('H:i', $timestamp);
        case 'completo':
            return date('d/m/Y H:i', $timestamp);
        default:
            return date('d/m/Y H:i:s', $timestamp);
    }
}

/**
 * Calcula la diferencia entre dos fechas en horas y minutos
 * 
 * @param string $inicio Fecha de inicio
 * @param string $fin Fecha de fin
 * @return array Array con horas, minutos y total_minutos
 */
function calcularDiferenciaTiempo($inicio, $fin) {
    $inicio_dt = new DateTime($inicio);
    $fin_dt = new DateTime($fin);
    $diferencia = $inicio_dt->diff($fin_dt);
    
    $horas = $diferencia->h + ($diferencia->days * 24);
    $minutos = $diferencia->i;
    $total_minutos = ($horas * 60) + $minutos;
    
    return [
        'horas' => $horas,
        'minutos' => $minutos,
        'total_minutos' => $total_minutos,
        'formateado' => sprintf('%dh %dm', $horas, $minutos)
    ];
}

/**
 * Obtiene el nombre del rol del usuario
 * 
 * @param string $rol Código del rol
 * @return string Nombre del rol
 */
function obtenerNombreRol($rol) {
    $roles = [
        'empleado' => 'Empleado',
        'jefe_seccion' => 'Jefe de Sección',
        'admin' => 'Administrador'
    ];
    
    return $roles[$rol] ?? $rol;
}

/**
 * Obtiene el nombre del departamento
 * 
 * @param int $departamento_id ID del departamento
 * @return string Nombre del departamento
 */
function obtenerNombreDepartamento($departamento_id) {
    require_once __DIR__ . '/db.php';
    
    try {
        $conexion = obtenerConexion();
        $sql = "SELECT nombre FROM departamentos WHERE id = :id LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':id', $departamento_id, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetch();
        
        return $resultado ? $resultado['nombre'] : 'Desconocido';
    } catch (PDOException $e) {
        error_log("Error al obtener departamento: " . $e->getMessage());
        return 'Desconocido';
    }
}

/**
 * Genera un código de empleado único
 * 
 * @param string $prefijo Prefijo según departamento (RH, DEV, etc.)
 * @return string Código generado
 */
function generarCodigoEmpleado($prefijo) {
    require_once __DIR__ . '/db.php';
    
    try {
        $conexion = obtenerConexion();
        
        // Obtener el último código con ese prefijo
        $sql = "SELECT codigo_empleado FROM usuarios 
                WHERE codigo_empleado LIKE :prefijo 
                ORDER BY id DESC LIMIT 1";
        $stmt = $conexion->prepare($sql);
        $prefijo_busqueda = $prefijo . '%';
        $stmt->bindParam(':prefijo', $prefijo_busqueda, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch();
        
        if ($resultado) {
            // Extraer el número y sumarle 1
            $numero_actual = intval(substr($resultado['codigo_empleado'], -4));
            $nuevo_numero = $numero_actual + 1;
        } else {
            $nuevo_numero = 1;
        }
        
        return $prefijo . '-' . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        error_log("Error al generar código: " . $e->getMessage());
        return $prefijo . '-0001';
    }
}

/**
 * Muestra un mensaje flash (se usa una vez y se borra)
 * 
 * @param string $tipo Tipo de mensaje (success, error, warning, info)
 * @param string $mensaje Texto del mensaje
 */
function setFlash($tipo, $mensaje) {
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'mensaje' => $mensaje
    ];
}

/**
 * Obtiene y borra el mensaje flash
 * 
 * @return array|null Mensaje flash o null si no hay
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirige a una URL
 * 
 * @param string $url URL de destino
 */
function redirigir($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Devuelve un JSON con los headers correctos
 * 
 * @param mixed $datos Datos a codificar como JSON
 * @param int $codigo Código HTTP de respuesta
 */
function jsonResponse($datos, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Verifica si una petición es AJAX
 * 
 * @return bool True si es AJAX, false en caso contrario
 */
function esAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Obtiene el valor de un parámetro GET o POST, sanitizado
 * 
 * @param string $nombre Nombre del parámetro
 * @param mixed $por_defecto Valor por defecto si no existe
 * @param string $metodo Método (GET, POST, REQUEST)
 * @return mixed Valor sanitizado o valor por defecto
 */
function obtenerParametro($nombre, $por_defecto = null, $metodo = 'REQUEST') {
    $valor = $_REQUEST[$nombre] ?? $por_defecto;
    
    if ($valor !== null && is_string($valor)) {
        $valor = trim($valor);
        $valor = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
    }
    
    return $valor;
}

/**
 * Convierte minutos a formato horas:minutos
 * 
 * @param int $minutos Total de minutos
 * @return string Formato HH:MM
 */
function minutosAHoras($minutos) {
    $horas = floor($minutos / 60);
    $mins = $minutos % 60;
    return sprintf('%02d:%02d', $horas, $mins);
}