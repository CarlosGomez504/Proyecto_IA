<?php
/**
 * API de Temporizador - Gestiona el tiempo en proyectos
 * 
 * Permite iniciar, parar temporizadores y asignarse a proyectos.
 */

// Iniciar sesión
session_start();

// Incluir archivos necesarios
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/funciones.php';

// Verificar que sea una petición AJAX y que haya sesión
if (!esAjax() || !haySesionActiva()) {
    jsonResponse(['success' => false, 'message' => 'Acceso no autorizado'], 401);
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
}

// Obtener acción
$accion = $_POST['accion'] ?? '';
$accion = htmlspecialchars(trim($accion));

// Obtener usuario actual
$usuario = obtenerUsuarioActual();
$usuario_id = $usuario['id'];
$rol = $usuario['rol'];

try {
    $conexion = obtenerConexion();
    
    switch ($accion) {
        case 'iniciar':
            $proyecto_id = intval($_POST['proyecto_id'] ?? 0);
            
            if (!$proyecto_id) {
                jsonResponse(['success' => false, 'message' => 'ID de proyecto no válido']);
            }
            
            // Verificar que el usuario está asignado al proyecto
            $sql_verificar = "
                SELECT COUNT(*) as total FROM proyecto_empleado 
                WHERE usuario_id = :usuario_id AND proyecto_id = :proyecto_id";
            $stmt = $conexion->prepare($sql_verificar);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':proyecto_id', $proyecto_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetch()['total'] == 0) {
                jsonResponse(['success' => false, 'message' => 'No estás asignado a este proyecto']);
            }
            
            // Verificar que no haya otro timer activo
            $sql_timer_activo = "
                SELECT COUNT(*) as total FROM tiempo_proyectos 
                WHERE usuario_id = :usuario_id AND fin IS NULL";
            $stmt = $conexion->prepare($sql_timer_activo);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetch()['total'] > 0) {
                jsonResponse(['success' => false, 'message' => 'Ya tienes un temporizador activo. Deténlo primero.']);
            }
            
            // Crear nuevo registro de tiempo
            $sql_insert = "
                INSERT INTO tiempo_proyectos (usuario_id, proyecto_id, inicio) 
                VALUES (:usuario_id, :proyecto_id, :inicio)";
            $stmt = $conexion->prepare($sql_insert);
            $ahora = date('Y-m-d H:i:s');
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':proyecto_id', $proyecto_id, PDO::PARAM_INT);
            $stmt->bindParam(':inicio', $ahora, PDO::PARAM_STR);
            $stmt->execute();
            
            $timer_id = $conexion->lastInsertId();
            
            jsonResponse(['success' => true, 'message' => 'Temporizador iniciado', 'timer_id' => $timer_id]);
            break;
            
        case 'parar':
            $timer_id = intval($_POST['timer_id'] ?? 0);
            
            if (!$timer_id) {
                jsonResponse(['success' => false, 'message' => 'ID de temporizador no válido']);
            }
            
            // Verificar que el timer existe y pertenece al usuario
            $sql_timer = "
                SELECT * FROM tiempo_proyectos 
                WHERE id = :id AND usuario_id = :usuario_id AND fin IS NULL
                LIMIT 1";
            $stmt = $conexion->prepare($sql_timer);
            $stmt->bindParam(':id', $timer_id, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $timer = $stmt->fetch();
            
            if (!$timer) {
                jsonResponse(['success' => false, 'message' => 'Temporizador no encontrado o ya fue detenido']);
            }
            
            // Calcular minutos totales
            $inicio = new DateTime($timer['inicio']);
            $fin = new DateTime();
            $diferencia = $inicio->diff($fin);
            $minutos_totales = ($diferencia->days * 24 * 60) + ($diferencia->h * 60) + $diferencia->i;
            
            // Actualizar el registro
            $sql_update = "
                UPDATE tiempo_proyectos 
                SET fin = :fin, minutos_totales = :minutos 
                WHERE id = :id";
            $stmt = $conexion->prepare($sql_update);
            $fin_str = $fin->format('Y-m-d H:i:s');
            $stmt->bindParam(':fin', $fin_str, PDO::PARAM_STR);
            $stmt->bindParam(':minutos', $minutos_totales, PDO::PARAM_INT);
            $stmt->bindParam(':id', $timer_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $horas = floor($minutos_totales / 60);
            $mins = $minutos_totales % 60;
            
            jsonResponse(['success' => true, 'message' => "Temporizador detenido. Tiempo registrado: {$horas}h {$mins}m"]);
            break;
            
        case 'asignar':
            // Solo admin y jefes pueden auto-asignarse
            if ($rol !== 'admin' && $rol !== 'jefe_seccion') {
                jsonResponse(['success' => false, 'message' => 'No tienes permiso para esta acción'], 403);
            }
            
            $proyecto_id = intval($_POST['proyecto_id'] ?? 0);
            
            if (!$proyecto_id) {
                jsonResponse(['success' => false, 'message' => 'ID de proyecto no válido']);
            }
            
            // Verificar que no esté ya asignado
            $sql_verificar = "
                SELECT COUNT(*) as total FROM proyecto_empleado 
                WHERE usuario_id = :usuario_id AND proyecto_id = :proyecto_id";
            $stmt = $conexion->prepare($sql_verificar);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':proyecto_id', $proyecto_id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($stmt->fetch()['total'] > 0) {
                jsonResponse(['success' => false, 'message' => 'Ya estás asignado a este proyecto']);
            }
            
            // Asignar al proyecto
            $sql_insert = "
                INSERT INTO proyecto_empleado (proyecto_id, usuario_id) 
                VALUES (:proyecto_id, :usuario_id)";
            $stmt = $conexion->prepare($sql_insert);
            $stmt->bindParam(':proyecto_id', $proyecto_id, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            
            jsonResponse(['success' => true, 'message' => 'Te has asignado al proyecto correctamente']);
            break;
            
        default:
            jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
    }
    
} catch (PDOException $e) {
    error_log("Error en timer: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Error del sistema. Intente más tarde.'], 500);
}