<?php
/**
 * API de Fichaje - Procesa las acciones de fichaje
 * 
 * Recibe peticiones AJAX para registrar entrada, salida,
 * inicio y fin de descanso. Calcula retrasos y genera alertas.
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

// Validar acción
$acciones_validas = ['entrada', 'inicio_descanso', 'fin_descanso', 'salida'];
if (!in_array($accion, $acciones_validas)) {
    jsonResponse(['success' => false, 'message' => 'Acción no válida'], 400);
}

// Obtener usuario actual
$usuario = obtenerUsuarioActual();
$usuario_id = $usuario['id'];
$hoy = date('Y-m-d');
$ahora = date('Y-m-d H:i:s');

try {
    $conexion = obtenerConexion();
    
    // Obtener horario del usuario
    $sql_horario = "SELECT hora_entrada, minutos_margen FROM usuarios WHERE id = :id";
    $stmt = $conexion->prepare($sql_horario);
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $horario = $stmt->fetch();
    
    if (!$horario) {
        jsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
    }
    
    // Obtener o crear fichaje de hoy
    $sql_fichaje = "SELECT * FROM fichajes WHERE usuario_id = :usuario_id AND fecha = :fecha LIMIT 1";
    $stmt = $conexion->prepare($sql_fichaje);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
    $stmt->execute();
    $fichaje = $stmt->fetch();
    
    // ============================================
    // PROCESAR CADA ACCIÓN
    // ============================================
    
    switch ($accion) {
        case 'entrada':
            // Verificar que no haya fichado ya
            if ($fichaje && $fichaje['hora_entrada']) {
                jsonResponse(['success' => false, 'message' => 'Ya has registrado tu entrada hoy']);
            }
            
            // Calcular retraso
            $hora_entrada_teorica = $horario['hora_entrada'];
            $minutos_margen = $horario['minutos_margen'];
            $hora_actual = date('H:i:s');
            
            // Calcular minutos de retraso
            $minutos_retraso = 0;
            $hora_teorica_con_margen = date('H:i:s', strtotime($hora_entrada_teorica . " + {$minutos_margen} minutes"));
            
            if ($hora_actual > $hora_teorica_con_margen) {
                $minutos_retraso = round((strtotime($hora_actual) - strtotime($hora_entrada_teorica)) / 60);
            }
            
            // Crear o actualizar fichaje
            if (!$fichaje) {
                $sql_insert = "INSERT INTO fichajes (usuario_id, fecha, hora_entrada, minutos_retraso, estado) 
                              VALUES (:usuario_id, :fecha, :hora_entrada, :minutos_retraso, 'pendiente')";
                $stmt = $conexion->prepare($sql_insert);
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
                $stmt->bindParam(':hora_entrada', $ahora, PDO::PARAM_STR);
                $stmt->bindParam(':minutos_retraso', $minutos_retraso, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            // Si hay retraso, crear alerta
            if ($minutos_retraso > 0) {
                $mensaje_alerta = "Llegaste {$minutos_retraso} minutos tarde hoy. Tu hora de entrada es a las " . 
                                 date('H:i', strtotime($hora_entrada_teorica));
                
                $sql_alerta = "INSERT INTO alertas (usuario_id, tipo, mensaje) VALUES (:usuario_id, 'retraso', :mensaje)";
                $stmt = $conexion->prepare($sql_alerta);
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                $stmt->bindParam(':mensaje', $mensaje_alerta, PDO::PARAM_STR);
                $stmt->execute();
                
                $mensaje = "Entrada registrada a las " . date('H:i') . ". Llegaste {$minutos_retraso} minutos tarde.";
            } else {
                $mensaje = "Entrada registrada a las " . date('H:i') . ". ¡A tiempo!";
            }
            
            jsonResponse(['success' => true, 'message' => $mensaje]);
            break;
            
        case 'inicio_descanso':
            // Verificar que haya entrado y no esté ya en descanso
            if (!$fichaje || !$fichaje['hora_entrada']) {
                jsonResponse(['success' => false, 'message' => 'Debes registrar tu entrada primero']);
            }
            
            if ($fichaje['inicio_descanso'] && !$fichaje['fin_descanso']) {
                jsonResponse(['success' => false, 'message' => 'Ya estás en descanso']);
            }
            
            // Actualizar fichaje con inicio de descanso
            $sql_update = "UPDATE fichajes SET inicio_descanso = :inicio WHERE id = :id";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bindParam(':inicio', $ahora, PDO::PARAM_STR);
            $stmt->bindParam(':id', $fichaje['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            jsonResponse(['success' => true, 'message' => 'Descanso iniciado a las ' . date('H:i')]);
            break;
            
        case 'fin_descanso':
            // Verificar que esté en descanso
            if (!$fichaje || !$fichaje['inicio_descanso']) {
                jsonResponse(['success' => false, 'message' => 'No has registrado inicio de descanso']);
            }
            
            if ($fichaje['fin_descanso']) {
                jsonResponse(['success' => false, 'message' => 'Ya has vuelto del descanso']);
            }
            
            // Actualizar fichaje con fin de descanso
            $sql_update = "UPDATE fichajes SET fin_descanso = :fin WHERE id = :id";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bindParam(':fin', $ahora, PDO::PARAM_STR);
            $stmt->bindParam(':id', $fichaje['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            $duracion = round((strtotime($ahora) - strtotime($fichaje['inicio_descanso'])) / 60);
            jsonResponse(['success' => true, 'message' => 'Vuelta del descanso registrada. Descanso de ' . $duracion . ' minutos.']);
            break;
            
        case 'salida':
            // Verificar que haya entrado
            if (!$fichaje || !$fichaje['hora_entrada']) {
                jsonResponse(['success' => false, 'message' => 'Debes registrar tu entrada primero']);
            }
            
            if ($fichaje['hora_salida']) {
                jsonResponse(['success' => false, 'message' => 'Ya has registrado tu salida hoy']);
            }
            
            // Verificar que haya vuelto del descanso si lo inició
            if ($fichaje['inicio_descanso'] && !$fichaje['fin_descanso']) {
                jsonResponse(['success' => false, 'message' => 'Debes registrar el fin de tu descanso antes de salir']);
            }
            
            // Actualizar fichaje con salida
            $sql_update = "UPDATE fichajes 
                          SET hora_salida = :salida, 
                              estado = 'completo' 
                          WHERE id = :id";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bindParam(':salida', $ahora, PDO::PARAM_STR);
            $stmt->bindParam(':id', $fichaje['id'], PDO::PARAM_INT);
            $stmt->execute();
            
            // Calcular horas trabajadas
            $tiempo_trabajo = calcularDiferenciaTiempo($fichaje['hora_entrada'], $ahora);
            $minutos_trabajados = $tiempo_trabajo['total_minutos'];
            
            // Restar tiempo de descanso
            if ($fichaje['inicio_descanso'] && $fichaje['fin_descanso']) {
                $tiempo_descanso = calcularDiferenciaTiempo($fichaje['inicio_descanso'], $fichaje['fin_descanso']);
                $minutos_trabajados -= $tiempo_descanso['total_minutos'];
            }
            
            // Verificar salida anticipada
            $hora_salida_teorica = $horario['hora_salida'] ?? '17:00:00';
            $minutos_faltantes = 0;
            $salida_anticipada = false;
            
            // Obtener minutos_margen del usuario (ya lo tenemos en $horario si existe)
            // Si no tenemos hora_salida en $horario, usar valor por defecto
            
            if (strtotime($ahora) < strtotime($hora_salida_teorica)) {
                $minutos_faltantes = round((strtotime($hora_salida_teorica) - strtotime($ahora)) / 60);
                $salida_anticipada = true;
                
                // Crear alerta de salida anticipada
                $mensaje_alerta = "Saliste {$minutos_faltantes} minutos antes de tu hora de salida ({$hora_salida_teorica})";
                
                $sql_alerta = "INSERT INTO alertas (usuario_id, tipo, mensaje) VALUES (:usuario_id, 'salida_anticipada', :mensaje)";
                $stmt = $conexion->prepare($sql_alerta);
                $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                $stmt->bindParam(':mensaje', $mensaje_alerta, PDO::PARAM_STR);
                $stmt->execute();
            }
            
            $horas = floor($minutos_trabajados / 60);
            $mins = $minutos_trabajados % 60;
            $mensaje = "Salida registrada a las " . date('H:i') . ". Jornada de {$horas}h {$mins}m.";
            
            if ($salida_anticipada) {
                $mensaje .= " Saliste {$minutos_faltantes} minutos antes de lo programado.";
            }
            
            jsonResponse(['success' => true, 'message' => $mensaje]);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error en fichaje: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Error del sistema. Intente más tarde.'], 500);
}