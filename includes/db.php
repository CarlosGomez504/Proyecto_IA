<?php
/**
 * Conexión a la base de datos MySQL usando PDO
 * 
 * Este archivo gestiona la conexión a la base de datos
 * de forma segura utilizando PDO con consultas preparadas.
 * 
 * IMPORTANTE: El bloque try-catch NO imprime el error real
 * del sistema para no filtrar información sensible como
 * la contraseña de la base de datos. Solo muestra un mensaje
 * genérico al usuario.
 */

// Verificar que el archivo de configuración existe
if (!file_exists(__DIR__ . '/../config/config.php')) {
    die('Error: El archivo de configuración no existe. Contacte al administrador.');
}

// Incluir configuración
require_once __DIR__ . '/../config/config.php';

/**
 * Función para obtener la conexión a la base de datos
 * 
 * @return PDO|null Devuelve la conexión PDO o null si falla
 */
function obtenerConexion() {
    static $conexion = null;
    
    // Si ya existe una conexión, la reutilizamos
    if ($conexion !== null) {
        return $conexion;
    }
    
    try {
        // Crear la cadena de conexión DSN
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        // Opciones de PDO para mayor seguridad y rendimiento
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // Lanzar excepciones en caso de error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Devolver arrays asociativos
            PDO::ATTR_EMULATE_PREPARES => false,              // Usar prepared statements nativos
            PDO::ATTR_PERSISTENT => false                     // No usar conexiones persistentes
        ];
        
        // Crear la conexión PDO
        $conexion = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        
        return $conexion;
        
    } catch (PDOException $e) {
        // IMPORTANTE: NO mostrar el error real al usuario
        // El error podría contener información sensible como
        // la contraseña de la base de datos o la estructura del servidor
        
        // Registrar el error real en un log (solo accesible para administradores)
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        
        // Mostrar mensaje genérico al usuario
        die('Error: No se pudo conectar a la base de datos. Por favor, contacte al administrador del sistema.');
    }
}

/**
 * Función para cerrar la conexión a la base de datos
 * Se llama automáticamente al finalizar el script
 */
function cerrarConexion() {
    $conexion = null;
}

// Registrar el shutdown para cerrar la conexión
register_shutdown_function('cerrarConexion');