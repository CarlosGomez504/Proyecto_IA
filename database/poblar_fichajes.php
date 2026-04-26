<?php
/**
 * Script para generar fichajes y tiempo en proyectos de los últimos 7 días
 */

require_once __DIR__ . '/../includes/db.php';
$db = obtenerConexion();

echo "=== GENERANDO FICHAJES (7 DÍAS) ===\n";
$usuarios_ids = range(2, 10); // IDs 2-10 (empleados, no admin)

for ($dia = 7; $dia >= 1; $dia--) {
    $fecha = date('Y-m-d', strtotime("-$dia days"));
    
    foreach ($usuarios_ids as $uid) {
        // Obtener hora teórica de entrada del usuario
        $stmt = $db->prepare("SELECT hora_entrada, minutos_margen FROM usuarios WHERE id = ?");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        $hora_teorica = $user['hora_entrada'];
        $margen = $user['minutos_margen'];
        
        // Hora de entrada aleatoria entre 08:00 y 10:00
        $entrada_h = rand(8, 10);
        $entrada_m = rand(0, 59);
        $hora_entrada = "$fecha " . sprintf('%02d:%02d:00', $entrada_h, $entrada_m);
        
        // Hora de salida aleatoria entre 17:00 y 19:00
        $salida_h = rand(17, 19);
        $salida_m = rand(0, 59);
        $hora_salida = "$fecha " . sprintf('%02d:%02d:00', $salida_h, $salida_m);
        
        // Calcular retraso
        $entrada_teorica_ts = strtotime("$fecha $hora_teorica") + ($margen * 60);
        $entrada_real_ts = strtotime($hora_entrada);
        $retraso = max(0, ($entrada_real_ts - $entrada_teorica_ts) / 60);
        
        // Determinar estado
        $estado = 'completo';
        if ($retraso > 30) {
            $estado = 'olvido_salida';
        }
        
        // Inicio descanso (aleatorio entre 13:00 y 14:00)
        $descanso_h = 13;
        $descanso_m = rand(0, 59);
        $inicio_descanso = "$fecha " . sprintf('%02d:%02d:00', $descanso_h, $descanso_m);
        
        // Fin descanso (30-45 min después)
        $fin_descanso = date('Y-m-d H:i:s', strtotime($inicio_descanso) + rand(1800, 2700));
        
        $db->exec("INSERT INTO fichajes (usuario_id, fecha, hora_entrada, hora_salida, inicio_descanso, fin_descanso, estado, minutos_retraso) 
                    VALUES ($uid, '$fecha', '$hora_entrada', '$hora_salida', '$inicio_descanso', '$fin_descanso', '$estado', " . round($retraso) . ")");
    }
    echo "  ✅ Día $dia ($fecha) - " . count($usuarios_ids) . " fichajes\n";
}

echo "\n=== GENERANDO TIEMPO EN PROYECTOS ===\n";
$proyectos_ids = [1, 2, 3, 4];

for ($dia = 7; $dia >= 1; $dia--) {
    $fecha = date('Y-m-d', strtotime("-$dia days"));
    
    foreach ($usuarios_ids as $uid) {
        // Cada usuario trabaja en 1-2 proyectos por día
        $proyectos_dia = array_rand(array_flip($proyectos_ids), rand(1, 2));
        if (!is_array($proyectos_dia)) $proyectos_dia = [$proyectos_dia];
        
        foreach ($proyectos_dia as $pid) {
            // Hora inicio aleatoria entre 9:00 y 16:00
            $inicio_h = rand(9, 16);
            $inicio_m = rand(0, 59);
            $inicio = "$fecha " . sprintf('%02d:%02d:00', $inicio_h, $inicio_m);
            
            // Duración entre 30 y 180 minutos
            $duracion = rand(30, 180);
            $fin = date('Y-m-d H:i:s', strtotime($inicio) + ($duracion * 60));
            
            $db->exec("INSERT INTO tiempo_proyectos (usuario_id, proyecto_id, inicio, fin, minutos_totales) 
                        VALUES ($uid, $pid, '$inicio', '$fin', $duracion)");
        }
    }
}
echo "  ✅ Tiempo en proyectos generado\n";

echo "\n=== RESUMEN ===\n";
echo "Total fichajes: " . $db->query("SELECT COUNT(*) FROM fichajes")->fetchColumn() . "\n";
echo "Total tiempo_proyectos: " . $db->query("SELECT COUNT(*) FROM tiempo_proyectos")->fetchColumn() . "\n";
?>