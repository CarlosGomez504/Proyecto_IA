<?php
/**
 * Página de Proyectos - Temporizador de tiempo por proyecto
 * 
 * Muestra los proyectos asignados al empleado y permite
 * iniciar/parar el temporizador para registrar tiempo.
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
$usuario_id = $usuario['id'];

// ============================================
// OBTENER PROYECTOS ASIGNADOS
// ============================================

$conexion = obtenerConexion();
$hoy = date('Y-m-d');

// Obtener proyectos asignados al usuario
$sql_proyectos = "
    SELECT p.id, p.nombre, p.descripcion, p.horas_estimadas, p.estado,
           d.nombre as departamento
    FROM proyectos p
    JOIN proyecto_empleado pe ON p.id = pe.proyecto_id
    JOIN departamentos d ON p.departamento_id = d.id
    WHERE pe.usuario_id = :usuario_id
    ORDER BY p.estado DESC, p.nombre";

$stmt = $conexion->prepare($sql_proyectos);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$proyectos = $stmt->fetchAll();

// Si es admin o jefe, mostrar todos los proyectos activos
if ($rol === 'admin' || $rol === 'jefe_seccion') {
    $sql_todos = "
        SELECT p.id, p.nombre, p.descripcion, p.horas_estimadas, p.estado,
               d.nombre as departamento
        FROM proyectos p
        JOIN departamentos d ON p.departamento_id = d.id
        WHERE p.estado = 'activo'
        AND p.id NOT IN (SELECT proyecto_id FROM proyecto_empleado WHERE usuario_id = :usuario_id)
        ORDER BY p.nombre";
    
    $stmt = $conexion->prepare($sql_todos);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $proyectos_disponibles = $stmt->fetchAll();
}

// ============================================
// OBTENER TEMPORIZADOR ACTIVO (si existe)
// ============================================

$sql_timer_activo = "
    SELECT tp.*, p.nombre as proyecto_nombre
    FROM tiempo_proyectos tp
    JOIN proyectos p ON tp.proyecto_id = p.id
    WHERE tp.usuario_id = :usuario_id AND tp.fin IS NULL
    LIMIT 1";

$stmt = $conexion->prepare($sql_timer_activo);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$timer_activo = $stmt->fetch();

// ============================================
// OBTENER TIEMPO POR PROYECTO (hoy y semana)
// ============================================

$tiempos_proyectos = [];

foreach ($proyectos as $proyecto) {
    $proyecto_id = $proyecto['id'];
    
    // Tiempo hoy (SQLite compatible)
    $sql_hoy = "
        SELECT COALESCE(SUM(
            CASE 
                WHEN fin IS NOT NULL THEN minutos_totales
                ELSE CAST((julianday('now') - julianday(inicio)) * 24 * 60 AS INTEGER)
            END
        ), 0) as minutos_hoy
        FROM tiempo_proyectos
        WHERE usuario_id = :usuario_id AND proyecto_id = :proyecto_id
        AND DATE(inicio) = :hoy";
    
    $stmt = $conexion->prepare($sql_hoy);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':proyecto_id', $proyecto_id, PDO::PARAM_INT);
    $stmt->bindParam(':hoy', $hoy, PDO::PARAM_STR);
    $stmt->execute();
    $minutos_hoy = $stmt->fetch()['minutos_hoy'];
    
    // Tiempo esta semana
    $inicio_semana = date('Y-m-d', strtotime('monday this week'));
    
    $sql_semana = "
        SELECT COALESCE(SUM(minutos_totales), 0) as minutos_semana
        FROM tiempo_proyectos
        WHERE usuario_id = :usuario_id AND proyecto_id = :proyecto_id
        AND DATE(inicio) >= :inicio_semana
        AND fin IS NOT NULL";
    
    $stmt = $conexion->prepare($sql_semana);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':proyecto_id', $proyecto_id, PDO::PARAM_INT);
    $stmt->bindParam(':inicio_semana', $inicio_semana, PDO::PARAM_STR);
    $stmt->execute();
    $minutos_semana = $stmt->fetch()['minutos_semana'];
    
    $tiempos_proyectos[$proyecto_id] = [
        'minutos_hoy' => $minutos_hoy,
        'minutos_semana' => $minutos_semana
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyectos - <?php echo e(NOMBRE_EMPRESA); ?></title>
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <a href="proyectos.php" class="active">
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
                <h1><i class="fas fa-project-diagram"></i> Mis Proyectos</h1>
                
                <div class="user-info">
                    <?php if ($timer_activo): ?>
                        <div class="user-status descanso">
                            <span class="status-dot"></span>
                            Temporizador activo: <?php echo e($timer_activo['proyecto_nombre']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="user-details">
                        <div class="nombre"><?php echo e($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></div>
                        <div class="rol"><?php echo e(obtenerNombreRol($rol)); ?></div>
                    </div>
                </div>
            </div>

            <!-- Temporizador activo -->
            <?php if ($timer_activo): ?>
                <div class="card mb-20" style="border: 2px solid var(--color-info);">
                    <div class="card-header">
                        <h3><i class="fas fa-stopwatch" style="color: var(--color-info);"></i> Temporizador Activo</h3>
                        <span class="estado activo">En curso</span>
                    </div>
                    <div class="card-body">
                        <div class="timer-display">
                            <div class="tiempo" id="timer-activo" data-inicio="<?php echo $timer_activo['inicio']; ?>">
                                00:00:00
                            </div>
                            <div class="etiqueta">Tiempo transcurrido</div>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-danger btn-lg" onclick="pararTimer(<?php echo $timer_activo['id']; ?>, <?php echo $timer_activo['proyecto_id']; ?>)">
                                <i class="fas fa-stop"></i>
                                DETENER TEMPORIZADOR
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Lista de proyectos -->
            <h2 class="mb-20">Proyectos Asignados</h2>
            
            <?php if (empty($proyectos)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    No tienes proyectos asignados. Contacta a tu supervisor.
                </div>
            <?php else: ?>
                <div class="proyectos-grid">
                    <?php foreach ($proyectos as $proyecto): ?>
                        <?php 
                        $proyecto_id = $proyecto['id'];
                        $minutos_hoy = $tiempos_proyectos[$proyecto_id]['minutos_hoy'];
                        $minutos_semana = $tiempos_proyectos[$proyecto_id]['minutos_semana'];
                        $es_activo = $proyecto['estado'] === 'activo';
                        $tiene_timer_activo = $timer_activo && $timer_activo['proyecto_id'] == $proyecto_id;
                        ?>
                        <div class="proyecto-card">
                            <div class="proyecto-card-header">
                                <div>
                                    <h4><?php echo e($proyecto['nombre']); ?></h4>
                                    <span class="estado <?php echo $proyecto['estado']; ?>">
                                        <?php echo ucfirst($proyecto['estado']); ?>
                                    </span>
                                </div>
                                <small class="text-muted"><?php echo e($proyecto['departamento']); ?></small>
                            </div>
                            
                            <?php if ($proyecto['descripcion']): ?>
                                <p><?php echo e(substr($proyecto['descripcion'], 0, 100)) . '...'; ?></p>
                            <?php endif; ?>
                            
                            <?php if ($proyecto['horas_estimadas']): ?>
                                <div class="proyecto-stats">
                                    <span>Estimado: <strong><?php echo $proyecto['horas_estimadas']; ?>h</strong></span>
                                    <span>Semana: <strong><?php echo minutosAHoras($minutos_semana); ?></strong></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="timer-display">
                                <div class="tiempo" id="tiempo-proyecto-<?php echo $proyecto_id; ?>">
                                    <?php echo minutosAHoras($minutos_hoy); ?>
                                </div>
                                <div class="etiqueta">Hoy</div>
                            </div>
                            
                            <?php if ($es_activo): ?>
                                <?php if ($tiene_timer_activo): ?>
                                    <button class="btn btn-danger btn-block" onclick="pararTimer(<?php echo $timer_activo['id']; ?>, <?php echo $proyecto_id; ?>)">
                                        <i class="fas fa-stop"></i> DETENER
                                    </button>
                                <?php elseif ($timer_activo): ?>
                                    <button class="btn btn-warning btn-block" disabled>
                                        <i class="fas fa-pause-circle"></i> Otro timer activo
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-block" onclick="iniciarTimer(<?php echo $proyecto_id; ?>)">
                                        <i class="fas fa-play"></i> INICIAR
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-block" disabled>
                                    <i class="fas fa-ban"></i> Proyecto no activo
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Proyectos disponibles (para admin/jefe) -->
            <?php if (!empty($proyectos_disponibles)): ?>
                <h2 class="mt-20 mb-20">Otros Proyectos Disponibles</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Proyecto</th>
                                <th>Departamento</th>
                                <th>Horas Estimadas</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proyectos_disponibles as $p): ?>
                                <tr>
                                    <td><?php echo e($p['nombre']); ?></td>
                                    <td><?php echo e($p['departamento']); ?></td>
                                    <td><?php echo $p['horas_estimadas']; ?>h</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="asignarmeProyecto(<?php echo $p['id']; ?>)">
                                            <i class="fas fa-plus"></i> Asignarme
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Mensaje de resultado -->
            <div id="resultado-timer" class="alert hidden mt-20"></div>
        </main>
    </div>

    <!-- Script del temporizador -->
    <script src="assets/js/timer.js"></script>
    <script>
    // Iniciar el contador si hay timer activo
    <?php if ($timer_activo): ?>
        iniciarContador('<?php echo $timer_activo['inicio']; ?>', 'timer-activo');
    <?php endif; ?>

    /**
     * Inicia el temporizador para un proyecto
     */
    function iniciarTimer(proyecto_id) {
        fetch('api/timer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'accion=iniciar&proyecto_id=' + proyecto_id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                mostrarMensaje(data.mensaje, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión', 'error');
        });
    }

    /**
     * Detiene el temporizador
     */
    function pararTimer(timer_id, proyecto_id) {
        if (!confirm('¿Detener el temporizador de este proyecto?')) {
            return;
        }

        fetch('api/timer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'accion=parar&timer_id=' + timer_id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarMensaje(data.mensaje, 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                mostrarMensaje(data.mensaje, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión', 'error');
        });
    }

    /**
     * Asignarse a un proyecto (admin/jefe)
     */
    function asignarmeProyecto(proyecto_id) {
        fetch('api/timer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'accion=asignar&proyecto_id=' + proyecto_id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                mostrarMensaje(data.mensaje, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarMensaje('Error de conexión', 'error');
        });
    }

    /**
     * Muestra un mensaje de resultado
     */
    function mostrarMensaje(mensaje, tipo) {
        const resultado = document.getElementById('resultado-timer');
        resultado.className = 'alert alert-' + (tipo === 'success' ? 'success' : 'error');
        resultado.innerHTML = '<i class="fas fa-' + (tipo === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + mensaje;
        resultado.classList.remove('hidden');
    }
    </script>
</body>
</html>