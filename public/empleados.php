<?php
/**
 * Gestión de Empleados - CRUD para administradores
 * 
 * Permite crear, editar y dar de baja empleados.
 * Solo accesible para usuarios con rol de admin.
 */

// Incluir archivos necesarios
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

// Requerir login y rol de admin
requerirRol('admin');

// Obtener datos del usuario actual
$usuario = obtenerUsuarioActual();

// ============================================
// PROCESAR ACCIONES
// ============================================

$conexion = obtenerConexion();
$mensaje = '';
$tipo_mensaje = '';
$error = '';

// Procesar formulario de crear/editar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    
    if ($accion === 'crear') {
        // Validar datos
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $departamento_id = intval($_POST['departamento_id'] ?? 0);
        $rol_usuario = $_POST['rol'] ?? 'empleado';
        $hora_entrada = $_POST['hora_entrada'] ?? '09:00:00';
        $hora_salida = $_POST['hora_salida'] ?? '17:00:00';
        $minutos_margen = intval($_POST['minutos_margen'] ?? 8);
        $password = $_POST['password'] ?? '';
        
        // Validaciones
        if (empty($nombre) || empty($apellidos) || empty($email)) {
            $error = 'Todos los campos obligatorios son requeridos';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email no válido';
        } elseif (empty($password)) {
            $error = 'La contraseña is required';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres';
        } elseif ($departamento_id <= 0) {
            $error = 'Debe seleccionar un departamento';
        } else {
            // Generar código de empleado
            $prefijos = ['RH' => 'RH', 'CON' => 'CON', 'DEV' => 'DEV', 'DIS' => 'DIS', 'DIR' => 'DIR'];
            $sql_dept = "SELECT nombre FROM departamentos WHERE id = :id";
            $stmt = $conexion->prepare($sql_dept);
            $stmt->bindParam(':id', $departamento_id, PDO::PARAM_INT);
            $stmt->execute();
            $dept_nombre = $stmt->fetch()['nombre'];
            
            // Obtener prefijo
            $prefijo = 'EMP';
            foreach ($prefijos as $clave => $pref) {
                if (stripos($dept_nombre, $clave) !== false) {
                    $prefijo = $pref;
                    break;
                }
            }
            
            $codigo_empleado = generarCodigoEmpleado($prefijo);
            
            // Hashear contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $sql_insert = "
                INSERT INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, 
                                     departamento_id, hora_entrada, hora_salida, minutos_margen)
                VALUES (:codigo, :nombre, :apellidos, :email, :password, :rol, 
                        :dept_id, :hora_entrada, :hora_salida, :minutos_margen)";
            
            $stmt = $conexion->prepare($sql_insert);
            $stmt->bindParam(':codigo', $codigo_empleado, PDO::PARAM_STR);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password_hash, PDO::PARAM_STR);
            $stmt->bindParam(':rol', $rol_usuario, PDO::PARAM_STR);
            $stmt->bindParam(':dept_id', $departamento_id, PDO::PARAM_INT);
            $stmt->bindParam(':hora_entrada', $hora_entrada, PDO::PARAM_STR);
            $stmt->bindParam(':hora_salida', $hora_salida, PDO::PARAM_STR);
            $stmt->bindParam(':minutos_margen', $minutos_margen, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje = "Empleado {$nombre} {$apellidos} creado correctamente. Código: {$codigo_empleado}";
                $tipo_mensaje = 'success';
            } else {
                $error = 'Error al crear el empleado. Posiblemente el email ya existe.';
            }
        }
    }
    
    elseif ($accion === 'editar') {
        $id = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $departamento_id = intval($_POST['departamento_id'] ?? 0);
        $rol_usuario = $_POST['rol'] ?? 'empleado';
        $hora_entrada = $_POST['hora_entrada'] ?? '09:00:00';
        $hora_salida = $_POST['hora_salida'] ?? '17:00:00';
        $minutos_margen = intval($_POST['minutos_margen'] ?? 8);
        
        if ($id <= 0 || empty($nombre) || empty($apellidos)) {
            $error = 'Datos no válidos';
        } else {
            $sql_update = "
                UPDATE usuarios 
                SET nombre = :nombre, apellidos = :apellidos, departamento_id = :dept_id,
                    rol = :rol, hora_entrada = :hora_entrada, hora_salida = :hora_salida,
                    minutos_margen = :minutos_margen
                WHERE id = :id";
            
            $stmt = $conexion->prepare($sql_update);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
            $stmt->bindParam(':dept_id', $departamento_id, PDO::PARAM_INT);
            $stmt->bindParam(':rol', $rol_usuario, PDO::PARAM_STR);
            $stmt->bindParam(':hora_entrada', $hora_entrada, PDO::PARAM_STR);
            $stmt->bindParam(':hora_salida', $hora_salida, PDO::PARAM_STR);
            $stmt->bindParam(':minutos_margen', $minutos_margen, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje = 'Empleado actualizado correctamente';
                $tipo_mensaje = 'success';
            } else {
                $error = 'Error al actualizar';
            }
        }
    }
    
    elseif ($accion === 'baja') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id > 0) {
            // Dar de baja lógica (no borrar)
            $sql_update = "UPDATE usuarios SET activo = 0 WHERE id = :id";
            $stmt = $conexion->prepare($sql_update);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje = 'Empleado dado de baja correctamente';
                $tipo_mensaje = 'success';
            } else {
                $error = 'Error al dar de baja';
            }
        }
    }
}

// ============================================
// OBTENER DATOS
// ============================================

// Obtener todos los empleados (activos e inactivos)
$sql_empleados = "
    SELECT u.*, d.nombre as departamento_nombre
    FROM usuarios u
    JOIN departamentos d ON u.departamento_id = d.id
    WHERE u.rol IN ('empleado', 'jefe_seccion')
    ORDER BY u.activo DESC, u.nombre, u.apellidos";

$empleados = $conexion->query($sql_empleados)->fetchAll();

// Obtener departamentos para el formulario
$sql_dept = "SELECT * FROM departamentos ORDER BY nombre";
$departamentos = $conexion->query($sql_dept)->fetchAll();

// Empleado en edición (si existe)
$empleado_edicion = null;
if (isset($_GET['editar'])) {
    $id_editar = intval($_GET['editar']);
    $sql_editar = "SELECT * FROM usuarios WHERE id = :id LIMIT 1";
    $stmt = $conexion->prepare($sql_editar);
    $stmt->bindParam(':id', $id_editar, PDO::PARAM_INT);
    $stmt->execute();
    $empleado_edicion = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empleados - <?php echo e(NOMBRE_EMPRESA); ?></title>
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
                <li>
                    <a href="empleados.php" class="active">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>
                </li>
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
                <h1><i class="fas fa-users"></i> Gestión de Empleados</h1>
                
                <div class="user-info">
                    <div class="user-details">
                        <div class="nombre"><?php echo e($usuario['nombre'] . ' ' . $usuario['apellidos']); ?></div>
                        <div class="rol">Administrador</div>
                    </div>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo e($mensaje); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de crear/editar -->
            <div class="formulario-container mb-20">
                <h3>
                    <?php if ($empleado_edicion): ?>
                        <i class="fas fa-edit"></i> Editar Empleado
                    <?php else: ?>
                        <i class="fas fa-user-plus"></i> Nuevo Empleado
                    <?php endif; ?>
                </h3>
                
                <form method="POST" action="">
                    <?php if ($empleado_edicion): ?>
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id" value="<?php echo $empleado_edicion['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="accion" value="crear">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" required 
                                   value="<?php echo $empleado_edicion ? e($empleado_edicion['nombre']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="apellidos">Apellidos *</label>
                            <input type="text" id="apellidos" name="apellidos" required 
                                   value="<?php echo $empleado_edicion ? e($empleado_edicion['apellidos']) : ''; ?>">
                        </div>
                    </div>
                    
                    <?php if (!$empleado_edicion): ?>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="departamento_id">Departamento *</label>
                            <select id="departamento_id" name="departamento_id" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"
                                            <?php echo ($empleado_edicion && $empleado_edicion['departamento_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($dept['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rol">Rol *</label>
                            <select id="rol" name="rol" required>
                                <option value="empleado" <?php echo (!$empleado_edicion || $empleado_edicion['rol'] === 'empleado') ? 'selected' : ''; ?>>Empleado</option>
                                <option value="jefe_seccion" <?php echo ($empleado_edicion && $empleado_edicion['rol'] === 'jefe_seccion') ? 'selected' : ''; ?>>Jefe de Sección</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="hora_entrada">Hora de Entrada</label>
                            <input type="time" id="hora_entrada" name="hora_entrada" 
                                   value="<?php echo $empleado_edicion ? $empleado_edicion['hora_entrada'] : '09:00'; ?>">
                        </div>
                        <div class="form-group">
                            <label for="hora_salida">Hora de Salida</label>
                            <input type="time" id="hora_salida" name="hora_salida" 
                                   value="<?php echo $empleado_edicion ? $empleado_edicion['hora_salida'] : '17:00'; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="minutos_margen">Minutos de Margen (Tolerancia)</label>
                        <input type="number" id="minutos_margen" name="minutos_margen" min="0" max="60"
                               value="<?php echo $empleado_edicion ? $empleado_edicion['minutos_margen'] : '8'; ?>">
                    </div>
                    
                    <div class="form-actions">
                        <?php if ($empleado_edicion): ?>
                            <a href="empleados.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo $empleado_edicion ? 'Actualizar' : 'Crear Empleado'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de empleados -->
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-users"></i> Lista de Empleados</h3>
                    <span class="text-muted"><?php echo count($empleados); ?> empleados</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Empleado</th>
                            <th>Email</th>
                            <th>Departamento</th>
                            <th>Rol</th>
                            <th>Horario</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empleados as $empleado): ?>
                            <tr style="<?php echo !$empleado['activo'] ? 'opacity: 0.6;' : ''; ?>">
                                <td><?php echo e($empleado['codigo_empleado']); ?></td>
                                <td><?php echo e($empleado['nombre'] . ' ' . $empleado['apellidos']); ?></td>
                                <td><?php echo e($empleado['email']); ?></td>
                                <td><?php echo e($empleado['departamento_nombre']); ?></td>
                                <td>
                                    <span class="estado <?php echo $empleado['rol'] === 'jefe_seccion' ? 'activo' : 'pendiente'; ?>">
                                        <?php echo obtenerNombreRol($empleado['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('H:i', strtotime($empleado['hora_entrada'])); ?> - 
                                    <?php echo date('H:i', strtotime($empleado['hora_salida'])); ?>
                                    <br><small class="text-muted">Margen: <?php echo $empleado['minutos_margen']; ?>min</small>
                                </td>
                                <td>
                                    <span class="estado <?php echo $empleado['activo'] ? 'activo' : 'baja'; ?>">
                                        <?php echo $empleado['activo'] ? 'Activo' : 'De Baja'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?editar=<?php echo $empleado['id']; ?>" class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($empleado['activo']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Dar de baja a este empleado?');">
                                            <input type="hidden" name="accion" value="baja">
                                            <input type="hidden" name="id" value="<?php echo $empleado['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Dar de baja">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>