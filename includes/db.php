<?php
/**
 * Conexion a la base de datos usando SQLite
 * 
 * Esta version usa SQLite para que funcione sin MySQL.
 * El archivo de base de datos se crea automaticamente.
 */

// Verificar que el archivo de configuracion existe
if (!file_exists(__DIR__ . '/../config/config.php')) {
    die('Error: El archivo de configuracion no existe. Contacte al administrador.');
}

// Incluir configuracion
require_once __DIR__ . '/../config/config.php';

/**
 * Funcion para obtener la conexion a la base de datos SQLite
 * 
 * @return PDO|null Devuelve la conexion PDO o null si falla
 */
function obtenerConexion() {
    static $conexion = null;
    
    // Si ya existe una conexion, la reutilizamos
    if ($conexion !== null) {
        return $conexion;
    }
    
    try {
        // Ruta del archivo SQLite
        $db_path = __DIR__ . '/../database/control_horarios.db';
        $dsn = "sqlite:" . $db_path;
        
        // Opciones de PDO
        $opciones = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        
        // Crear la conexion PDO
        $conexion = new PDO($dsn, null, null, $opciones);
        
        // Habilitar foreign keys en SQLite
        $conexion->exec("PRAGMA foreign_keys = ON");
        
        // Verificar si las tablas existen, si no, crearlas
        $tablas = $conexion->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tablas) || !in_array('departamentos', $tablas)) {
            // Leer y ejecutar el schema
            $schema_sql = file_get_contents(__DIR__ . '/../database/schema_sqlite.sql');
            if ($schema_sql !== false) {
                $conexion->exec($schema_sql);
            }
        }
        
        return $conexion;
        
    } catch (PDOException $e) {
        // IMPORTANTE: NO mostrar el error real al usuario
        error_log("Error de conexion a la base de datos: " . $e->getMessage());
        die('Error: No se pudo conectar a la base de datos. Por favor, contacte al administrador del sistema.');
    }
}

/**
 * Funcion para cerrar la conexion a la base de datos
 */
function cerrarConexion() {
    $conexion = null;
}

// Registrar el shutdown para cerrar la conexion
register_shutdown_function('cerrarConexion');