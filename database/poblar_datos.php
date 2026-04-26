<?php
/**
 * Script para poblar la base de datos con datos reales y masivos
 * - 10 empleados con nuevos roles
 * - 4 proyectos
 * - Fichajes de los últimos 7 días
 * - Tiempo en proyectos para gráficos
 */

require_once __DIR__ . '/../includes/db.php';
$db = obtenerConexion();

echo "=== LIMPIANDO BASE DE DATOS ===\n";
$db->exec('DELETE FROM tiempo_proyectos');
$db->exec('DELETE FROM alertas');
$db->exec('DELETE FROM fichajes');
$db->exec('DELETE FROM proyecto_empleado');
$db->exec('DELETE FROM proyectos');
$db->exec('DELETE FROM usuarios WHERE id > 0');
$db->exec('DELETE FROM departamentos WHERE id > 0');
$db->exec('DELETE FROM sqlite_sequence WHERE name="usuarios"');
$db->exec('DELETE FROM sqlite_sequence WHERE name="proyectos"');
$db->exec('DELETE FROM sqlite_sequence WHERE name="departamentos"');
echo "✅ Base de datos limpiada\n\n";

// 1. CREAR DEPARTAMENTOS
echo "=== CREANDO DEPARTAMENTOS ===\n";
$deptos = ['Recursos Humanos', 'Contabilidad', 'Desarrollo', 'Diseño', 'Dirección', 'Técnico Informático'];
foreach ($deptos as $depto) {
    $db->exec("INSERT INTO departamentos (nombre) VALUES ('$depto')");
    echo "  ✅ $depto\n";
}

// 2. CREAR 10 EMPLEADOS
echo "\n=== CREANDO 10 EMPLEADOS ===\n";
$empleados = [
    ['admin@empresa.com', 'Carlos', 'Administrador', 'admin', 5, '09:00:00', '18:00:00', 5],
    ['maria.garcia@empresa.com', 'María', 'García López', 'empleado', 1, '09:00:00', '17:00:00', 8],
    ['juan.perez@empresa.com', 'Juan', 'Pérez Martín', 'jefe_seccion', 2, '08:30:00', '16:30:00', 5],
    ['ana.rodriguez@empresa.com', 'Ana', 'Rodríguez Sánchez', 'jefe_seccion', 3, '09:00:00', '18:00:00', 10],
    ['luis.fernandez@empresa.com', 'Luis', 'Fernández Torres', 'empleado', 4, '10:00:00', '19:00:00', 8],
    ['carmen.ruiz@empresa.com', 'Carmen', 'Ruiz Gómez', 'empleado', 1, '09:00:00', '17:00:00', 8],
    ['pedro.sanchez@empresa.com', 'Pedro', 'Sánchez Díaz', 'tecnico', 6, '08:00:00', '17:00:00', 5],
    ['laura.moreno@empresa.com', 'Laura', 'Moreno Jiménez', 'empleado', 3, '09:00:00', '18:00:00', 8],
    ['david.gil@empresa.com', 'David', 'Gil Romero', 'empleado', 4, '09:00:00', '17:00:00', 8],
    ['elena.vazquez@empresa.com', 'Elena', 'Vázquez Pardo', 'rrhh', 1, '09:00:00', '17:00:00', 8],
];

$hash_empleado = password_hash('empleado123', PASSWORD_BCRYPT);
$hash_admin = password_hash('admin123', PASSWORD_BCRYPT);

foreach ($empleados as $i => $emp) {
    $codigo = strtoupper(substr($emp[3], 0, 3)) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
    $hash = ($emp[0] === 'admin@empresa.com') ? $hash_admin : $hash_empleado;
    $nombre = $emp[1];
    $apellidos = $emp[2];
    $email = $emp[0];
    $rol = $emp[3];
    $depto_id = $emp[4];
    $h_entrada = $emp[5];
    $h_salida = $emp[6];
    $margen = $emp[7];
    
    $db->exec("INSERT INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
                VALUES ('$codigo', '$nombre', '$apellidos', '$email', '$hash', '$rol', $depto_id, '$h_entrada', '$h_salida', $margen)");
    echo "  ✅ $email ($rol)\n";
}

// 3. CREAR 4 PROYECTOS
echo "\n=== CREANDO 4 PROYECTOS ===\n";
$proyectos = [
    ['Desarrollo Página Web Corporativa', 'Creación de nueva web corporativa con diseño moderno', 200, 'Desarrollo'],
    ['Campaña Marketing Digital Q1', 'Campaña de marketing para primer trimestre', 80, 'Diseño'],
    ['Migración de Servidores', 'Migración de servidores físicos a cloud AWS', 150, 'Técnico Informático'],
    ['App Móvil Clientes', 'Desarrollo de aplicación móvil para clientes iOS/Android', 120, 'Desarrollo'],
];

foreach ($proyectos as $p) {
    $nombre = $p[0];
    $desc = $p[1];
    $horas = $p[2];
    $depto = $p[3];
    
    $stmt = $db->prepare("SELECT id FROM departamentos WHERE nombre = ?");
    $stmt->execute([$depto]);
    $depto_id = $stmt->fetchColumn();
    
    $db->exec("INSERT INTO proyectos (nombre, descripcion, horas_estimadas, estado, departamento_id) 
                VALUES ('$nombre', '$desc', $horas, 'activo', $depto_id)");
    echo "  ✅ $nombre\n";
}

// 4. ASIGNAR EMPLEADOS A PROYECTOS
echo "\n=== ASIGNANDO EMPLEADOS A PROYECTOS ===\n";
// Usuario 1 (admin) -> todos los proyectos
// Usuarios 2-10 -> al menos un proyecto cada uno
$asignaciones = [
    [1, 1], [1, 2], [1, 3], [1, 4], // Admin en todos
    [2, 1], [2, 4], // María en Desarrollo Web y App Móvil
    [3, 1], [3, 4], // Juan en Desarrollo Web y App Móvil
    [4, 2], [4, 4], // Ana en Marketing y App Móvil
    [5, 1], [5, 3], // Luis en Desarrollo Web y Migración
    [6, 2], // Carmen en Marketing
    [7, 3], // Pedro en Migración
    [8, 4], // Laura en App Móvil
    [9, 1], [9, 2], // David en Desarrollo Web y Marketing
    [10, 2], // Elena en Marketing
];

foreach ($asignaciones as $a) {
    $db->exec("INSERT INTO proyecto_empleado (proyecto_id, usuario_id) VALUES ({$a[0]}, {$a[1]})");
}
echo "  ✅ " . count($asignaciones) . " asignaciones creadas\n";

// 5. GENERAR FICHAJES DE LOS ÚLTIMOS 7 DÍAS
echo "\n=== GENERANDO FICHAJES (7 DÍAS) ===\n";
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
            $estado = 'olvido_salida'; // Para generar alertas
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

// 6. GENERAR TIEMPO EN PROYECTOS
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

echo "\n=== ¡BASE DE DATOS COMPLETAMENTE POBLADA! ===\n";
echo "Total usuarios: " . $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() . "\n";
echo "Total proyectos: " . $db->query("SELECT COUNT(*) FROM proyectos")->fetchColumn() . "\n";
echo "Total fichajes: " . $db->query("SELECT COUNT(*) FROM fichajes")->fetchColumn() . "\n";
echo "Total tiempo_proyectos: " . $db->query("SELECT COUNT(*) FROM tiempo_proyectos")->fetchColumn() . "\n";
?>