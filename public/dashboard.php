<?php
/**
 * Dashboard - Página principal según rol del usuario
 * 
 * Muestra información diferente según si el usuario es:
 * - EMPLEADO: sus propios datos
 * - JEFE_DE_SECCION: sus datos + equipo
 * - ADMIN: toda la empresa + lista roja
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

// Requerir login
requerirLogin();

// Obtener datos del usuario actual
$usuario = obtenerUsuarioActual();
$rol = $usuario['rol'];

// Inicializar variables
$flash = getFlash();

// ============================================
// OBTENER DATOS COMUNES
// ============================================

// Obtener fichaje de hoy
require_once __DIR__ . '/../includes/db.php';
$conexion = obtenerConexion();

$hoy = date('Y-m-d');

// Fichaje de hoy del usuario
$sql_fichaje = "SELECT * FROM fichajes WHERE usuario_id = :usuario_id AND fecha = :fecha LIMIT 1";
$stmt = $conexion->prepare($sql_fichaje);
$stmt->bindParam(':usuario_id', $usuario['id'], PDO::PARAM_INT);
$stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
$stmt->execute();
$fichaje_hoy = $stmt->fetch();

// Estado actual del usuario
$estado_fichaje = 'fuera'; // No ha fichado
$hora_entrada = null;
$hora_salida = null;
$inicio_descanso = null;
$fin_descanso = null;
$horas_trabajadas = 0;

if ($fichaje_hoy) {
    $hora_entrada = $fichaje_hoy['hora_entrada'];
    $hora_salida = $fichaje_hoy['hora_salida'];
    $inicio_descanso = $fichaje_hoy['inicio_descanso'];
    $fin_descanso = $fichaje_hoy['fin_descanso'];
    
    if ($hora_entrada && !$hora_salida) {
        if ($inicio_descanso && !$fin_descanso) {
            $estado_fichaje = 'descanso'; // Está en descanso
        } else {
            $estado_fichaje = 'trabajando'; // Ha entrado pero no ha salido
        }
    } elseif ($hora_entrada && $hora_salida) {
        $estado_fichaje = 'fuera'; // Ya terminó su jornada
        // Calcular horas trabajadas
        $tiempo_trabajo = calcularDiferenciaTiempo($hora_entrada, $hora_salida);
        $horas_trabajadas = $tiempo_trabajo['total_minutos'];
        
        // Restar tiempo de descanso si lo tomó
        if ($inicio_descanso && $fin_descanso) {
            $tiempo_descanso = calcularDiferenciaTiempo($inicio_descanso, $fin_descanso);
            $horas_trabajadas -= $tiempo_descanso['total_minutos'];
        }
    }
}

// Obtener alertas no leídas
$sql_alertas = "SELECT * FROM alertas WHERE usuario_id = :usuario_id AND leida = 0 ORDER BY created_at DESC LIMIT 10";
$stmt = $conexion->prepare($sql_alertas);
$stmt->bindParam(':usuario_id', $usuario['id'], PDO::PARAM_INT);
$stmt->execute();
$alertas = $stmt->fetchAll();

// ============================================
// DATOS ESPECÍFICOS POR ROL
// ============================================

// Datos para JEFE_DE_SECCION - Equipo a cargo
$equipo = [];
if ($rol === 'jefe_seccion') {
    $sql_equipo = "SELECT u.id, u.codigo_empleado, u.nombre, u.apellidos, 
                   u.hora_entrada, f.hora_entrada as fichaje_entrada, 
                   u.minutos_margen, f.minutos_retraso, f.estado as estado_fichaje
                   FROM usuarios u
                   LEFT JOIN fichajes f ON u.id = f.usuario_id AND f.fecha = :fecha
                   WHERE u.departamento_id = :departamento_id 
                   AND u.activo = 1 AND u.rol = 'empleado'
                   ORDER BY u.nombre";
    $stmt = $conexion->prepare($sql_equipo);
    $stmt->bindParam(':departamento_id', $usuario['departamento_id'], PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
    $stmt->execute();
    $equipo = $stmt->fetchAll();
}

// Datos para ADMIN - Estadísticas generales
$stats_admin = [];
$lista_roja = [];
if ($rol === 'admin') {
    // Total empleados activos
    $sql_total = "SELECT COUNT(*) as total FROM usuarios WHERE activo = 1 AND rol = 'empleado'";
    $stmt = $conexion->query($sql_total);
    $total_empleados = $stmt->fetch()['total'];
    
    // Empleados que han fichado hoy
    $sql_ficharon = "SELECT COUNT(DISTINCT usuario_id) as total FROM fichajes WHERE fecha = :fecha AND hora_entrada IS NOT NULL";
    $stmt = $conexion->prepare($sql_ficharon);
    $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
    $stmt->execute();
    $ficharon_hoy = $stmt->fetch()['total'];
    
    // Empleados con retraso hoy
    $sql_retrasos = "SELECT COUNT(*) as total FROM fichajes WHERE fecha = :fecha AND minutos_retraso > 0";
    $stmt = $conexion->prepare($sql_retrasos);
    $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
    $stmt->execute();
    $retrasos_hoy = $stmt->fetch()['total'];
    
    // LISTA ROJA - Empleados con retraso hoy
    $sql_lista_roja = "
        SELECT u.id, u.codigo_empleado, u.nombre, u.apellidos, d.nombre as departamento,
               u.hora_entrada as hora_teorica, f.hora_entrada as hora_real,
               f.minutos_retraso, TIMESTAMPDIFF(MINUTE, u.hora_entrada, f.hora_entrada) as diferencia_real
        FROM fichajes f
        JOIN usuarios u ON f.usuario_id = u.id
        JOIN departamentos d ON u.departamento_id = d.id
        WHERE f.fecha = :fecha 
        AND f.minutos_retraso > 0
        ORDER BY f.minutos_retraso DESC";
    $stmt = $conexion->prepare($sql_lista_roja);
    $stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
    $stmt->execute();
    $lista_roja = $stmt->fetchAll();
    
    // Horas por departamento esta semana
    $sql_horas_dept = "
        SELECT d.nombre, 
               SUM(TIME_TO_SEC(TIMEDIFF(f.hora_salida, f.hora_entrada)) - 
                   TIME_TO_SEC(TIMEDIFF(f.fin_descanso, f.inicio_descanso))) / 3600 as horas_totales
        FROM fichajes f
        JOIN usuarios u ON f.usuario_id = u.id
        JOIN departamentos d ON u.departamento_id = d.id
        WHERE f.fecha BETWEEN :inicio_semana AND :fin_semana
        AND f.hora_salida IS NOT NULL
        GROUP BY d.id, d.nombre";
    $stmt = $conexion->prepare($sql_horas_dept);
    $inicio_semana = date('Y-m-d', strtotime('monday this week'));
    $fin_semana = date('Y-m-d', strtotime('sunday this week'));
    $stmt->bindParam(':inicio_semana', $inicio_semana, PDO::PARAM_STR);
    $stmt->bindParam(':fin_semana', $fin_semana, PDO::PARAM_STR);
    $stmt->execute();
    $horas_departamentos = $stmt->fetchAll();
    
    $stats_admin = [
        'total_empleados' => $total_empleados,
        'ficharon_hoy' => $ficharon_hoy,
        'retrasos_hoy' => $retrasos_hoy
    ];
}

// Horas en proyectos esta semana (para empleados)
$horas_proyectos = [];
if ($rol !== 'admin') {
    $sql_proyectos = "
        SELECT p.nombre, 
               SUM(tp.minutos_totales) as minutos_totales
        FROM tiempo_proyectos tp
        JOIN proyectos p ON tp.proyecto_id = p.id
        WHERE tp.usuario_id = :usuario_id
        AND tp.created_at >= :inicio_semana
        GROUP BY p.id, p.nombre
        ORDER BY minutos_totales DESC";
    $stmt = $conexion->prepare($sql_proyectos);
    $stmt->bindParam(':usuario_id', $usuario['id'], PDO::PARAM_INT);
    $stmt->bindParam(':inicio_semana', $inicio_semana, PDO::PARAM_STR);
    $stmt->execute();
    $horas_proyectos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo e(NOMBRE_EMPRESA); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-clock"></i> Control Horarios</h2>
                <p><?php echo e(NOMBRE_EMPRESA); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <i class="fas fa-home"></i>
                        <span>Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="fichaje.php">
                        <i class="fas fa-fingerprint"></i>
                        <span>Fichaje</span>
                    </a>
                </li>
                <li>
                    <a href="proyectos.php">
                        <i class="fas fa-project-diagram"></i>
                        <span>Proyectos</span>
                    </a>
                </li>
                <?php if ($rol === 'admin'): ?>
                <li>
                    <a href="empleados.php">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="reportes.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Header -->
            <div class="top-header">
                <h1>
                    <?php if ($rol === 'admin'): ?>
                        <i class="fas fa-tachometer-alt"></i> Panel de Administración
                    <?php elseif ($rol === 'jefe_seccion'): ?>
                        <i class="fas fa-user-tie"></i> Panel de Jefe de Sección
                    <?php else: ?>
                        <i class="fas fa-user"></i> Mi Panel
                    <?php endif; ?>
                </h1>
                
                <div class="user-info">
                    <div class="user-status <?php echo $estado_fichaje; ?>">
                        <span class="status-dot"></span>
                        <?php
                        if ($estado_fichaje === 'trabajando') echo 'Trabajando';
                        elseif ($estado_fichaje === 'descanso') echo 'En Descanso';
                        else echo 'Fuera de línea';
                        ?>
                    </div>
                    
                    <div class="user-details">
                        <div class="nombre"><?php echo e($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></div>
                        <div class="rol"><?php echo e(obtenerNombreRol($rol)); ?></div>
                    </div>
                </div>
            </div>

            <!-- Mensajes flash -->
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['tipo']); ?>">
                    <i class="fas fa-<?php echo $flash['tipo'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo e($flash['mensaje']); ?>
                </div>
            <?php endif; ?>

            <!-- Alertas del usuario -->
            <?php if (!empty($alertas)): ?>
                <div class="alert alert-warning mb-20">
                    <i class="fas fa-bell"></i>
                    <strong><?php echo count($alertas); ?> alerta(s) pendiente(s):</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($alertas as $alerta): ?>
                            <li><?php echo e($alerta['mensaje']); ?> (<?php echo formatearFecha($alerta['created_at'], 'fecha'); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- VISTA ADMIN -->
            <?php if ($rol === 'admin'): ?>
                <!-- Estadísticas -->
                <div class="cards-grid">
                    <div class="card card-stat">
                        <div class="numero"><?php echo $stats_admin['total_empleados']; ?></div>
                        <div class="etiqueta">Total Empleados</div>
                    </div>
                    <div class="card card-stat">
                        <div class="numero"><?php echo $stats_admin['ficharon_hoy']; ?></div>
                        <div class="etiqueta">Ficharon Hoy</div>
                    </div>
                    <div class="card card-stat">
                        <div class="numero" style="color: var(--color-peligro);">
                            <?php echo $stats_admin['retrasos_hoy']; ?>
                        </div>
                        <div class="etiqueta">Llegadas Tarde</div>
                    </div>
                </div>

                <!-- LISTA ROJA -->
                <?php if (!empty($lista_roja)): ?>
                    <div class="lista-roja">
                        <h3><i class="fas fa-exclamation-triangle"></i> LISTA ROJA - Retrasos de Hoy</h3>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Empleado</th>
                                        <th>Departamento</th>
                                        <th>Hora Teórica</th>
                                        <th>Hora Real</th>
                                        <th>Retraso</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lista_roja as $empleado): ?>
                                        <tr class="retraso">
                                            <td><?php echo e($empleado['codigo_empleado']); ?></td>
                                            <td><?php echo e($empleado['nombre'] . ' ' . $empleado['apellidos']); ?></td>
                                            <td><?php echo e($empleado['departamento']); ?></td>
                                            <td><?php echo date('H:i', strtotime($empleado['hora_teorica'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($empleado['hora_real'])); ?></td>
                                            <td><strong style="color: var(--color-peligro);">+<?php echo $empleado['minutos_retraso']; ?> min</strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Gráfico de horas por departamento -->
                <div class="grafico-container">
                    <h3><i class="fas fa-chart-pie"></i> Horas Trabajadas por Departamento (Esta Semana)</h3>
                    <canvas id="graficoHoras"></canvas>
                </div>

            <!-- VISTA JEFE DE SECCIÓN -->
            <?php elseif ($rol === 'jefe_seccion'): ?>
                <!-- Estado propio -->
                <div class="card mb-20">
                    <div class="card-header">
                        <h3>Mi Estado Hoy</h3>
                        <a href="fichaje.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-fingerprint"></i> Ir a Fichar
                        </a>
                    </div>
                    <div class="resumen-grid">
                        <div class="resumen-item">
                            <div class="valor"><?php echo $hora_entrada ? date('H:i', strtotime($hora_entrada)) : '--:--'; ?></div>
                            <div class="etiqueta">Hora de Entrada</div>
                        </div>
                        <div class="resumen-item">
                            <div class="valor"><?php echo $hora_salida ? date('H:i', strtotime($hora_salida)) : '--:--'; ?></div>
                            <div class="etiqueta">Hora de Salida</div>
                        </div>
                        <div class="resumen-item">
                            <div class="valor"><?php echo $horas_trabajadas > 0 ? minutosAHoras($horas_trabajadas) : '--:--'; ?></div>
                            <div class="etiqueta">Horas Trabajadas</div>
                        </div>
                    </div>
                </div>

                <!-- Equipo -->
                <div class="table-container">
                    <div class="table-header">
                        <h3><i class="fas fa-users"></i> Mi Equipo - Estado de Hoy</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Empleado</th>
                                <th>Hora Entrada</th>
                                <th>Estado</th>
                                <th>Retraso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipo as $miembro): ?>
                                <?php
                                $estado_color = 'gris';
                                $estado_texto = 'No fichó';
                                
                                if ($miembro['fichaje_entrada']) {
                                    $hora_teorica = strtotime($miembro['hora_entrada']);
                                    $hora_real = strtotime($miembro['fichaje_entrada']);
                                    $margen = ($miembro['minutos_margen'] ?? 8) * 60;
                                    
                                    if ($hora_real <= $hora_teorica + $margen) {
                                        $estado_color = 'verde';
                                        $estado_texto = 'A tiempo';
                                    } else {
                                        $estado_color = 'rojo';
                                        $estado_texto = 'Tarde';
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo e($miembro['codigo_empleado']); ?></td>
                                    <td><?php echo e($miembro['nombre'] . ' ' . $miembro['apellidos']); ?></td>
                                    <td>
                                        <?php echo $miembro['fichaje_entrada'] ? date('H:i', strtotime($miembro['fichaje_entrada'])) : '--:--'; ?>
                                    </td>
                                    <td>
                                        <span class="estado" style="background-color: <?php echo $estado_color === 'verde' ? '#d4edda' : ($estado_color === 'rojo' ? '#f8d7da' : '#fff3cd'); ?>; color: <?php echo $estado_color === 'verde' ? '#155724' : ($estado_color === 'rojo' ? '#721c24' : '#856404'); ?>;">
                                            <?php echo $estado_texto; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($miembro['minutos_retraso'] > 0): ?>
                                            <span style="color: var(--color-peligro);">+<?php echo $miembro['minutos_retraso']; ?> min</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <!-- VISTA EMPLEADO -->
            <?php else: ?>
                <!-- Estado del día -->
                <div class="cards-grid">
                    <div class="card">
                        <div class="card-header">
                            <h3>Mi Estado Hoy</h3>
                        </div>
                        <div class="resumen-grid">
                            <div class="resumen-item">
                                <div class="valor"><?php echo $hora_entrada ? date('H:i', strtotime($hora_entrada)) : '--:--'; ?></div>
                                <div class="etiqueta">Entrada</div>
                            </div>
                            <div class="resumen-item">
                                <div class="valor"><?php echo $hora_salida ? date('H:i', strtotime($hora_salida)) : '--:--'; ?></div>
                                <div class="etiqueta">Salida</div>
                            </div>
                            <div class="resumen-item">
                                <div class="valor">
                                    <?php echo $fichaje_hoy && $fichaje_hoy['inicio_descanso'] && $fichaje_hoy['fin_descanso'] 
                                        ? minutosAHoras(calcularDiferenciaTiempo($fichaje_hoy['inicio_descanso'], $fichaje_hoy['fin_descanso'])['total_minutos'])
                                        : '--:--'; ?>
                                </div>
                                <div class="etiqueta">Descanso</div>
                            </div>
                            <div class="resumen-item">
                                <div class="valor"><?php echo $horas_trabajadas > 0 ? minutosAHoras($horas_trabajadas) : '--:--'; ?></div>
                                <div class="etiqueta">Total Horas</div>
                            </div>
                        </div>
                        <div class="mt-20 text-center">
                            <a href="fichaje.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-fingerprint"></i>
                                <?php
                                if ($estado_fichaje === 'fuera') echo 'REGISTRAR ENTRADA';
                                elseif ($estado_fichaje === 'trabajando') echo 'GESTIONAR FICHAJE';
                                else echo 'VOLVER DEL DESCANSO';
                                ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Horas en proyectos -->
                <?php if (!empty($horas_proyectos)): ?>
                    <div class="table-container">
                        <div class="table-header">
                            <h3><i class="fas fa-clock"></i> Horas en Proyectos (Esta Semana)</h3>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Proyecto</th>
                                    <th>Horas Trabajadas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horas_proyectos as $proyecto): ?>
                                    <tr>
                                        <td><?php echo e($proyecto['nombre']); ?></td>
                                        <td><strong><?php echo minutosAHoras($proyecto['minutos_totales']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- Chart.js para el gráfico del admin -->
    <?php if ($rol === 'admin' && !empty($horas_departamentos)): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('graficoHoras').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [<?php foreach ($horas_departamentos as $d) echo "'" . addslashes($d['nombre']) . "',"; ?>],
                    datasets: [{
                        label: 'Horas Trabajadas',
                        data: [<?php foreach ($horas_departamentos as $d) echo number_format($d['horas_totales'] ?? 0, 2) . ','; ?>],
                        backgroundColor: [
                            'rgba(30, 58, 95, 0.8)',
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(23, 162, 184, 0.8)'
                        ],
                        borderColor: [
                            'rgba(30, 58, 95, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(23, 162, 184, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + 'h';
                                }
                            }
                        }
                    }
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>