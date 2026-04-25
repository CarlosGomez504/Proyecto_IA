<?php
/**
 * Reportes - Consultas y exportación de datos
 * 
 * Permite filtrar y visualizar reportes de:
 * - Fichajes por fecha/empleado/departamento
 * - Tiempo en proyectos
 * Incluye gráficos con Chart.js y exportación a CSV
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

// ============================================
// PROCESAR EXPORTACIÓN CSV
// ============================================

if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    // Solo el admin puede exportar todos los datos
    if ($rol !== 'admin') {
        die('No tienes permiso para exportar datos');
    }
    
    $conexion = obtenerConexion();
    
    // Obtener datos según filtros
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('monday this week'));
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $departamento_id = intval($_GET['departamento_id'] ?? 0);
    
    $sql = "
        SELECT u.codigo_empleado, CONCAT(u.nombre, ' ', u.apellidos) as empleado,
               d.nombre as departamento, f.fecha, 
               f.hora_entrada, f.hora_salida, f.minutos_retraso, f.estado
        FROM fichajes f
        JOIN usuarios u ON f.usuario_id = u.id
        JOIN departamentos d ON u.departamento_id = d.id
        WHERE f.fecha BETWEEN :fecha_inicio AND :fecha_fin";
    
    $params = [':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin];
    
    if ($departamento_id > 0) {
        $sql .= " AND u.departamento_id = :dept_id";
        $params[':dept_id'] = $departamento_id;
    }
    
    $sql .= " ORDER BY f.fecha DESC, u.nombre";
    
    $stmt = $conexion->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $datos = $stmt->fetchAll();
    
    // Generar CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_fichajes_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM para Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Encabezados
    fputcsv($output, ['Código', 'Empleado', 'Departamento', 'Fecha', 'Entrada', 'Salida', 'Retraso (min)', 'Estado']);
    
    // Datos
    foreach ($datos as $fila) {
        fputcsv($output, [
            $fila['codigo_empleado'],
            $fila['empleado'],
            $fila['departamento'],
            date('d/m/Y', strtotime($fila['fecha'])),
            $fila['hora_entrada'] ? date('H:i', strtotime($fila['hora_entrada'])) : '',
            $fila['hora_salida'] ? date('H:i', strtotime($fila['hora_salida'])) : '',
            $fila['minutos_retraso'],
            $fila['estado']
        ]);
    }
    
    fclose($output);
    exit;
}

// ============================================
// OBTENER DATOS PARA FILTROS
// ============================================

$conexion = obtenerConexion();

// Departamentos
$sql_dept = "SELECT * FROM departamentos ORDER BY nombre";
$departamentos = $conexion->query($sql_dept)->fetchAll();

// Empleados (para admin)
$empleados = [];
if ($rol === 'admin') {
    $sql_empleados = "
        SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo 
        FROM usuarios 
        WHERE activo = 1 AND rol IN ('empleado', 'jefe_seccion')
        ORDER BY nombre, apellidos";
    $empleados = $conexion->query($sql_empleados)->fetchAll();
}

// ============================================
// PROCESAR FILTROS
// ============================================

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d', strtotime('monday this week'));
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$filtro_departamento = intval($_GET['departamento_id'] ?? 0);
$filtro_empleado = intval($_GET['usuario_id'] ?? 0);

// ============================================
// OBTENER DATOS DEL REPORTE
// ============================================

// Fichajes
$sql_fichajes = "
    SELECT u.codigo_empleado, CONCAT(u.nombre, ' ', u.apellidos) as empleado,
           d.nombre as departamento, f.fecha, 
           f.hora_entrada, f.hora_salida, f.minutos_retraso, f.estado,
           TIME_TO_SEC(TIMEDIFF(f.hora_salida, f.hora_entrada)) / 3600 as horas_trabajadas
    FROM fichajes f
    JOIN usuarios u ON f.usuario_id = u.id
    JOIN departamentos d ON u.departamento_id = d.id
    WHERE f.fecha BETWEEN :fecha_inicio AND :fecha_fin";

$params = [':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin];

// Filtro por rol
if ($rol === 'empleado') {
    $sql_fichajes .= " AND f.usuario_id = :usuario_id";
    $params[':usuario_id'] = $usuario['id'];
} elseif ($rol === 'jefe_seccion') {
    // Ver si hay filtro de empleado o mostrar solo su departamento
    if ($filtro_empleado > 0) {
        $sql_fichajes .= " AND f.usuario_id = :usuario_filtro";
        $params[':usuario_filtro'] = $filtro_empleado;
    } else {
        $sql_fichajes .= " AND u.departamento_id = :dept_id";
        $params[':dept_id'] = $usuario['departamento_id'];
    }
} elseif ($rol === 'admin') {
    if ($filtro_departamento > 0) {
        $sql_fichajes .= " AND u.departamento_id = :dept_id";
        $params[':dept_id'] = $filtro_departamento;
    }
    if ($filtro_empleado > 0) {
        $sql_fichajes .= " AND f.usuario_id = :usuario_filtro";
        $params[':usuario_filtro'] = $filtro_empleado;
    }
}

$sql_fichajes .= " ORDER BY f.fecha DESC, u.nombre";

$stmt = $conexion->prepare($sql_fichajes);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$fichajes = $stmt->fetchAll();

// ============================================
// DATOS PARA GRÁFICOS (Chart.js)
// ============================================

// Horas por proyecto esta semana
$inicio_semana = date('Y-m-d', strtotime('monday this week'));

// Para el gráfico de horas por proyecto
$sql_horas_proyectos = "
    SELECT p.nombre, SUM(tp.minutos_totales) as minutos_totales
    FROM tiempo_proyectos tp
    JOIN proyectos p ON tp.proyecto_id = p.id";

if ($rol === 'empleado') {
    $sql_horas_proyectos .= " WHERE tp.usuario_id = :usuario_id";
    $params_grafico = [':usuario_id' => $usuario['id']];
} else {
    $sql_horas_proyectos .= " WHERE 1=1";
    $params_grafico = [];
}

$sql_horas_proyectos .= " AND tp.created_at >= :inicio_semana AND tp.fin IS NOT NULL
    GROUP BY p.id, p.nombre
    ORDER BY minutos_totales DESC";

$stmt = $conexion->prepare($sql_horas_proyectos);
foreach ($params_grafico as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindParam(':inicio_semana', $inicio_semana, PDO::PARAM_STR);
$stmt->execute();
$horas_proyectos = $stmt->fetchAll();

// Resumen de fichajes por día (para gráfico)
$sql_resumen_diario = "
    SELECT f.fecha,
           COUNT(DISTINCT f.usuario_id) as empleados_ficharon,
           AVG(f.minutos_retraso) as promedio_retraso
    FROM fichajes f
    WHERE f.fecha BETWEEN :fecha_inicio AND :fecha_fin";

if ($rol === 'empleado') {
    $sql_resumen_diario .= " AND f.usuario_id = :usuario_id";
}

$sql_resumen_diario .= " GROUP BY f.fecha ORDER BY f.fecha";

$stmt = $conexion->prepare($sql_resumen_diario);
if ($rol === 'empleado') {
    $stmt->bindParam(':usuario_id', $usuario['id'], PDO::PARAM_INT);
}
$stmt->bindParam(':fecha_inicio', $fecha_inicio, PDO::PARAM_STR);
$stmt->bindParam(':fecha_fin', $fecha_fin, PDO::PARAM_STR);
$stmt->execute();
$resumen_diario = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - <?php echo e(NOMBRE_EMPRESA); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="dashboard.php">
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
                    <a href="reportes.php" class="active">
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
                <h1><i class="fas fa-chart-bar"></i> Reportes y Estadísticas</h1>
                
                <div class="user-info">
                    <div class="user-details">
                        <div class="nombre"><?php echo e($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></div>
                        <div class="rol"><?php echo e(obtenerNombreRol($rol)); ?></div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros">
                <form method="GET" action="" style="display: contents;">
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                    </div>
                    <div class="form-group">
                        <label>Fecha Fin</label>
                        <input type="date" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                    </div>
                    
                    <?php if ($rol === 'admin'): ?>
                        <div class="form-group">
                            <label>Departamento</label>
                            <select name="departamento_id">
                                <option value="0">Todos</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php echo $filtro_departamento == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($dept['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Empleado</label>
                            <select name="usuario_id">
                                <option value="0">Todos</option>
                                <?php foreach ($empleados as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" 
                                            <?php echo $filtro_empleado == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($emp['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php elseif ($rol === 'jefe_seccion'): ?>
                        <div class="form-group">
                            <label>Empleado</label>
                            <select name="usuario_id">
                                <option value="0">Todo el equipo</option>
                                <?php
                                $sql_equipo = "
                                    SELECT id, CONCAT(nombre, ' ', apellidos) as nombre_completo 
                                    FROM usuarios 
                                    WHERE departamento_id = :dept_id AND activo = 1 AND rol = 'empleado'
                                    ORDER BY nombre";
                                $stmt = $conexion->prepare($sql_equipo);
                                $stmt->bindParam(':dept_id', $usuario['departamento_id'], PDO::PARAM_INT);
                                $stmt->execute();
                                $equipo = $stmt->fetchAll();
                                foreach ($equipo as $emp):
                                ?>
                                    <option value="<?php echo $emp['id']; ?>" 
                                            <?php echo $filtro_empleado == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($emp['nombre_completo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <?php if ($rol === 'admin'): ?>
                            <a href="?exportar=csv&fecha_inicio=<?php echo $fecha_inicio; ?>&fecha_fin=<?php echo $fecha_fin; ?>&departamento_id=<?php echo $filtro_departamento; ?>&usuario_id=<?php echo $filtro_empleado; ?>" 
                               class="btn btn-success">
                                <i class="fas fa-file-csv"></i> Exportar CSV
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Gráficos -->
            <div class="cards-grid">
                <!-- Horas por Proyecto -->
                <?php if (!empty($horas_proyectos)): ?>
                    <div class="grafico-container">
                        <h3><i class="fas fa-chart-pie"></i> Horas por Proyecto (Esta Semana)</h3>
                        <canvas id="graficoProyectos"></canvas>
                    </div>
                <?php endif; ?>
                
                <!-- Fichajes por Día -->
                <?php if (!empty($resumen_diario)): ?>
                    <div class="grafico-container">
                        <h3><i class="fas fa-chart-line"></i> Empleados que Ficharon por Día</h3>
                        <canvas id="graficoDiario"></canvas>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tabla de fichajes -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-table"></i> Registro de Fichajes</h3>
                    <span class="text-muted"><?php echo count($fichajes); ?> registros</span>
                </div>
                
                <?php if (empty($fichajes)): ?>
                    <div class="alert alert-info" style="margin: 20px;">
                        <i class="fas fa-info-circle"></i>
                        No hay registros para el período seleccionado.
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <?php if ($rol !== 'empleado'): ?><th>Empleado</th><?php endif; ?>
                                <?php if ($rol === 'admin'): ?><th>Departamento</th><?php endif; ?>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Horas</th>
                                <th>Retraso</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fichajes as $fichaje): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($fichaje['fecha'])); ?></td>
                                    <?php if ($rol !== 'empleado'): ?>
                                        <td><?php echo e($fichaje['empleado']); ?></td>
                                    <?php endif; ?>
                                    <?php if ($rol === 'admin'): ?>
                                        <td><?php echo e($fichaje['departamento']); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <?php echo $fichaje['hora_entrada'] ? date('H:i', strtotime($fichaje['hora_entrada'])) : '--:--'; ?>
                                    </td>
                                    <td>
                                        <?php echo $fichaje['hora_salida'] ? date('H:i', strtotime($fichaje['hora_salida'])) : '--:--'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($fichaje['hora_entrada'] && $fichaje['hora_salida']) {
                                            $horas = number_format($fichaje['horas_trabajadas'], 1);
                                            echo $horas . 'h';
                                        } else {
                                            echo '--';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($fichaje['minutos_retraso'] > 0): ?>
                                            <span style="color: var(--color-peligro);">+<?php echo $fichaje['minutos_retraso']; ?> min</span>
                                        <?php else: ?>
                                            <span class="text-success">A tiempo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="estado <?php echo $fichaje['estado']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $fichaje['estado'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Scripts para gráficos -->
    <script>
    // Configuración común de Chart.js
    Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
    Chart.defaults.color = '#666';

    <?php if (!empty($horas_proyectos)): ?>
        // Gráfico de horas por proyecto
        const ctxProyectos = document.getElementById('graficoProyectos').getContext('2d');
        new Chart(ctxProyectos, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($horas_proyectos as $p) echo "'" . addslashes($p['nombre']) . "',"; ?>],
                datasets: [{
                    data: [<?php foreach ($horas_proyectos as $p) echo number_format($p['minutos_totales'] / 60, 2) . ','; ?>],
                    backgroundColor: [
                        '#1e3a5f', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
                        '#6610f2', '#e83e8c', '#fd7e14', '#20c997', '#6f42c1'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const horas = (context.raw).toFixed(1);
                                return context.label + ': ' + horas + ' horas';
                            }
                        }
                    }
                }
            }
        });
    <?php endif; ?>

    <?php if (!empty($resumen_diario)): ?>
        // Gráfico de fichajes por día
        const ctxDiario = document.getElementById('graficoDiario').getContext('2d');
        new Chart(ctxDiario, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($resumen_diario as $d) echo "'" . date('d/m', strtotime($d['fecha'])) . "',"; ?>],
                datasets: [{
                    label: 'Empleados que ficharon',
                    data: [<?php foreach ($resumen_diario as $d) echo $d['empleados_ficharon'] . ','; ?>],
                    backgroundColor: 'rgba(30, 58, 95, 0.8)',
                    borderColor: 'rgba(30, 58, 95, 1)',
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
                            stepSize: 1
                        }
                    }
                }
            }
        });
    <?php endif; ?>
    </script>
</body>
</html>