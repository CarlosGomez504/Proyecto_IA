<?php
/**
 * Conexion a la base de datos SQLite
 * 
 * Este archivo gestiona la conexion a SQLite usando PDO.
 * La base de datos se crea automaticamente si no existe.
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
 * @return PDO Devuelve la conexion PDO
 */
function obtenerConexion() {
    static $conexion = null;
    
    // Si ya existe una conexion, la reutilizamos
    if ($conexion !== null) {
        return $conexion;
    }
    
    try {
        // Ruta absoluta del archivo SQLite
        $db_path = realpath(__DIR__ . '/../database') . '/control_horarios.db';
        
        // Verificar que el directorio existe y es escribible
        $db_dir = dirname($db_path);
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        if (!is_writable($db_dir)) {
            throw new Exception("El directorio database/ no es escribible. Verifica los permisos.");
        }
        
        // DSN para SQLite
        $dsn = "sqlite:" . $db_path;
        
        // Opciones de PDO para seguridad
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
            // Leer y ejecutar el schema SQLite
            $schema_file = realpath(__DIR__ . '/../database/schema_sqlite.sql');
            if ($schema_file && file_exists($schema_file)) {
                $schema_sql = file_get_contents($schema_file);
                if ($schema_sql !== false) {
                    $conexion->exec($schema_sql);
                }
            }
        }
        
        return $conexion;
        
    } catch (PDOException $e) {
        // IMPORTANTE: NO mostrar el error real al usuario para no filtrar informacion sensible
        error_log("Error de conexion a la base de datos: " . $e->getMessage());
        die('Error: No se pudo conectar a la base de datos. Verifica que la carpeta database/ tenga permisos de escritura.');
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        die('Error: ' . $e->getMessage());
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