<?php
/**
 * Página de Fichaje - Registro de entrada, salida y descansos
 * 
 * Permite a los empleados registrar su jornada laboral
 * mediante botones que funcionan con AJAX (sin recargar).
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
// OBTENER ESTADO ACTUAL DEL FICHAJE
// ============================================

$conexion = obtenerConexion();
$hoy = date('Y-m-d');

// Fichaje de hoy
$sql_fichaje = "SELECT * FROM fichajes WHERE usuario_id = :usuario_id AND fecha = :fecha LIMIT 1";
$stmt = $conexion->prepare($sql_fichaje);
$stmt->bindParam(':usuario_id', $usuario['id'], PDO::PARAM_INT);
$stmt->bindParam(':fecha', $hoy, PDO::PARAM_STR);
$stmt->execute();
$fichaje_hoy = $stmt->fetch();

// Determinar estado y acciones disponibles
$estado = 'no_fichado'; // No ha fichado hoy
$hora_entrada = null;
$hora_salida = null;
$inicio_descanso = null;
$fin_descanso = null;
$minutos_retraso = 0;

if ($fichaje_hoy) {
    $hora_entrada = $fichaje_hoy['hora_entrada'];
    $hora_salida = $fichaje_hoy['hora_salida'];
    $inicio_descanso = $fichaje_hoy['inicio_descanso'];
    $fin_descanso = $fichaje_hoy['fin_descanso'];
    $minutos_retraso = $fichaje_hoy['minutos_retraso'];
    
    if ($hora_entrada && !$hora_salida) {
        if ($inicio_descanso && !$fin_descanso) {
            $estado = 'en_descanso'; // Está en descanso
        } else {
            $estado = 'trabajando'; // Ha entrado, está trabajando
        }
    } elseif ($hora_entrada && $hora_salida) {
        $estado = 'jornada_completa'; // Ya terminó su jornada
    }
}

// Obtener horario teórico del usuario
$sql_horario = "SELECT hora_entrada, hora_salida, minutos_margen FROM usuarios WHERE id = :id";
$stmt = $conexion->prepare($sql_horario);
$stmt->bindParam(':id', $usuario['id'], PDO::PARAM_INT);
$stmt->execute();
$horario = $stmt->fetch();

// Calcular horas trabajadas si hay salida
$horas_trabajadas = 0;
$minutos_descanso = 0;

if ($hora_entrada && $hora_salida) {
    $tiempo_trabajo = calcularDiferenciaTiempo($hora_entrada, $hora_salida);
    $horas_trabajadas = $tiempo_trabajo['total_minutos'];
    
    if ($inicio_descanso && $fin_descanso) {
        $tiempo_descanso = calcularDiferenciaTiempo($inicio_descanso, $fin_descanso);
        $minutos_descanso = $tiempo_descanso['total_minutos'];
        $horas_trabajadas -= $minutos_descanso;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichaje - <?php echo e(NOMBRE_EMPRESA); ?></title>
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
                    <a href="fichaje.php" class="active">
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
                <h1><i class="fas fa-fingerprint"></i> Control de Fichaje</h1>
                
                <div class="user-info">
                    <div class="user-details">
                        <div class="nombre"><?php echo e($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></div>
                        <div class="rol"><?php echo e(obtenerNombreRol($rol)); ?></div>
                    </div>
                </div>
            </div>

            <!-- Mensaje de estado actual -->
            <div class="card mb-20">
                <div class="card-header">
                    <h3>Estado del <?php echo date('d/m/Y'); ?></h3>
                </div>
                <div class="card-body">
                    <div class="resumen-grid">
                        <div class="resumen-item">
                            <div class="valor">
                                <?php echo $hora_entrada ? date('H:i', strtotime($hora_entrada)) : '--:--'; ?>
                            </div>
                            <div class="etiqueta">Entrada</div>
                        </div>
                        <div class="resumen-item">
                            <div class="valor">
                                <?php echo $inicio_descanso ? date('H:i', strtotime($inicio_descanso)) : '--:--'; ?>
                            </div>
                            <div class="etiqueta">Inicio Descanso</div>
                        </div>
                        <div class="resumen-item">
                            <div class="valor">
                                <?php echo $fin_descanso ? date('H:i', strtotime($fin_descanso)) : '--:--'; ?>
                            </div>
                            <div class="etiqueta">Fin Descanso</div>
                        </div>
                        <div class="resumen-item">
                            <div class="valor">
                                <?php echo $hora_salida ? date('H:i', strtotime($hora_salida)) : '--:--'; ?>
                            </div>
                            <div class="etiqueta">Salida</div>
                        </div>
                        <div class="resumen-item">
                            <div class="valor">
                                <?php echo $horas_trabajadas > 0 ? minutosAHoras($horas_trabajadas) : '--:--'; ?>
                            </div>
                            <div class="etiqueta">Horas Trabajadas</div>
                        </div>
                        <div class="resumen-item">
                            <div class="valor" style="color: <?php echo $minutos_retraso > 0 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>">
                                <?php echo $minutos_retraso > 0 ? '+' . $minutos_retraso . ' min' : 'A tiempo'; ?>
                            </div>
                            <div class="etiqueta">Retraso</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción según estado -->
            <div class="card">
                <div class="card-header">
                    <h3>Acciones de Fichaje</h3>
                </div>
                <div class="card-body">
                    <div class="fichaje-buttons">
                        <!-- Estado: No ha fichado -->
                        <?php if ($estado === 'no_fichado'): ?>
                            <div id="mensaje-estado">
                                <p class="text-muted">Aún no has registrado tu entrada hoy.</p>
                                <p class="text-muted">Tu hora de entrada es a las <?php echo date('H:i', strtotime($horario['hora_entrada'])); ?> 
                                    (tienes <?php echo $horario['minutos_margen']; ?> minutos de margen)</p>
                            </div>
                            <button id="btn-entrada" class="btn btn-fichar btn-entrada" onclick="registrarFichaje('entrada')">
                                <i class="fas fa-sign-in-alt"></i>
                                REGISTRAR ENTRADA
                            </button>

                        <!-- Estado: Trabajando -->
                        <?php elseif ($estado === 'trabajando'): ?>
                            <div id="mensaje-estado">
                                <p class="text-success">
                                    <i class="fas fa-check-circle"></i>
                                    Entraste a las <?php echo date('H:i', strtotime($hora_entrada)); ?>
                                    <?php if ($minutos_retraso > 0): ?>
                                        <span style="color: var(--color-peligro);">
                                            (+<?php echo $minutos_retraso; ?> min de retraso)
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--color-exito);">(A tiempo)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center;">
                                <button id="btn-descanso" class="btn btn-fichar btn-descanso" onclick="registrarFichaje('inicio_descanso')">
                                    <i class="fas fa-coffee"></i>
                                    INICIAR DESCANSO
                                </button>
                                <button id="btn-salida" class="btn btn-fichar btn-salida" onclick="registrarFichaje('salida')">
                                    <i class="fas fa-sign-out-alt"></i>
                                    REGISTRAR SALIDA
                                </button>
                            </div>

                        <!-- Estado: En descanso -->
                        <?php elseif ($estado === 'en_descanso'): ?>
                            <div id="mensaje-estado">
                                <p class="text-warning">
                                    <i class="fas fa-coffee"></i>
                                    Estás en descanso desde las <?php echo date('H:i', strtotime($inicio_descanso)); ?>
                                </p>
                            </div>
                            <button id="btn-volver" class="btn btn-fichar btn-volver-descanso" onclick="registrarFichaje('fin_descanso')">
                                <i class="fas fa-undo"></i>
                                VOLVER DEL DESCANSO
                            </button>

                        <!-- Estado: Jornada completa -->
                        <?php elseif ($estado === 'jornada_completa'): ?>
                            <div id="mensaje-estado">
                                <p class="text-success">
                                    <i class="fas fa-check-circle"></i>
                                    Jornada completada. Has trabajado <?php echo minutosAHoras($horas_trabajadas); ?> horas.
                                </p>
                                <?php if ($minutos_retraso > 0): ?>
                                    <p class="text-danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Llegaste <?php echo $minutos_retraso; ?> minutos tarde.
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Ya has completado tu fichaje de hoy. Si necesitas corregir algo, contacta a Recursos Humanos.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Mensaje de resultado (se muestra después de fichar) -->
            <div id="resultado-fichaje" class="alert hidden mt-20"></div>
        </main>
    </div>

    <!-- Script para fichaje AJAX -->
    <script>
    /**
     * Registra un fichaje mediante AJAX
     * @param {string} tipo - 'entrada', 'inicio_descanso', 'fin_descanso', 'salida'
     */
    function registrarFichaje(tipo) {
        // Confirmar acción de salida
        if (tipo === 'salida') {
            if (!confirm('¿Estás seguro de que deseas registrar tu salida?')) {
                return;
            }
        }

        // Deshabilitar botón temporalmente
        const btn = document.getElementById('btn-' + tipo.replace('_', '-'));
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        }

        // Petición AJAX
        fetch('api/fichar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'accion=' + encodeURIComponent(tipo)
        })
        .then(response => response.json())
        .then(data => {
            const resultado = document.getElementById('resultado-fichaje');
            
            if (data.success) {
                resultado.className = 'alert alert-success';
                resultado.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                
                // Recargar página después de 2 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                resultado.className = 'alert alert-error';
                resultado.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                
                // Reactivar botón
                if (btn) {
                    btn.disabled = false;
                    // Restaurar texto original
                    if (tipo === 'entrada') btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> REGISTRAR ENTRADA';
                    else if (tipo === 'inicio_descanso') btn.innerHTML = '<i class="fas fa-coffee"></i> INICIAR DESCANSO';
                    else if (tipo === 'fin_descanso') btn.innerHTML = '<i class="fas fa-undo"></i> VOLVER DEL DESCANSO';
                    else if (tipo === 'salida') btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> REGISTRAR SALIDA';
                }
            }
            
            resultado.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            const resultado = document.getElementById('resultado-fichaje');
            resultado.className = 'alert alert-error';
            resultado.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error de conexión. Intente nuevamente.';
            resultado.classList.remove('hidden');
            
            if (btn) {
                btn.disabled = false;
            }
        });
    }
    </script>
</body>
</html>