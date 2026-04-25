-- ============================================
-- SISTEMA DE CONTROL DE HORARIOS - BASE DE DATOS
-- ============================================
-- Este archivo crea todas las tablas necesarias
-- para el funcionamiento del sistema de control
-- de horarios empresarial.
-- ============================================

-- Desactivar verificación de claves foráneas para evitar errores
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLA 1: DEPARTAMENTOS
-- ============================================
-- Almacena los diferentes departamentos de la empresa
-- Recursos Humanos, Contabilidad, Desarrollo, Diseño, Dirección
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA 2: USUARIOS
-- ============================================
-- Almacena la información de todos los empleados
-- Incluye admin, jefes de sección y empleados
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_empleado VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('empleado', 'jefe_seccion', 'admin') NOT NULL DEFAULT 'empleado',
    departamento_id INT NOT NULL,
    hora_entrada TIME NOT NULL DEFAULT '09:00:00',
    hora_salida TIME NOT NULL DEFAULT '17:00:00',
    minutos_margen INT NOT NULL DEFAULT 8,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA 3: FICHAJES
-- ============================================
-- Registro diario de entradas y salidas de los empleados
CREATE TABLE IF NOT EXISTS fichajes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada DATETIME DEFAULT NULL,
    hora_salida DATETIME DEFAULT NULL,
    inicio_descanso DATETIME DEFAULT NULL,
    fin_descanso DATETIME DEFAULT NULL,
    estado ENUM('pendiente', 'completo', 'olvido_salida') NOT NULL DEFAULT 'pendiente',
    minutos_retraso INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_fichaje_diario (usuario_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA 4: PROYECTOS
-- ============================================
-- Almacena los proyectos de la empresa
CREATE TABLE IF NOT EXISTS proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    horas_estimadas DECIMAL(5,2) DEFAULT NULL,
    estado ENUM('activo', 'pausado', 'finalizado') NOT NULL DEFAULT 'activo',
    departamento_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA 5: PROYECTO_EMPLEADO
-- ============================================
-- Relación muchos a muchos entre empleados y proyectos
CREATE TABLE IF NOT EXISTS proyecto_empleado (
    proyecto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    PRIMARY KEY (proyecto_id, usuario_id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA 6: TIEMPO_PROYECTOS
-- ============================================
-- Registro del tiempo trabajado en cada proyecto
CREATE TABLE IF NOT EXISTS tiempo_proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    proyecto_id INT NOT NULL,
    inicio DATETIME NOT NULL,
    fin DATETIME DEFAULT NULL,
    minutos_totales INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA 7: ALERTAS
-- ============================================
-- Notificaciones para los empleados (retrasos, olvidos, etc.)
CREATE TABLE IF NOT EXISTS alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('retraso', 'olvido_salida', 'salida_anticipada') NOT NULL,
    mensaje TEXT NOT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Insertar departamentos
INSERT INTO departamentos (nombre) VALUES 
('Recursos Humanos'),
('Contabilidad'),
('Desarrollo'),
('Diseño'),
('Dirección');

-- Insertar usuario ADMIN
-- Email: admin@empresa.com, Contraseña: admin123
INSERT INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
VALUES (
    'ADM-0001',
    'Carlos',
    'Administrador',
    'admin@empresa.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    5,
    '09:00:00',
    '18:00:00',
    5
);

-- Insertar empleados (uno por departamento)
-- Contraseña para todos: empleado123
-- El hash es el mismo para todos en este ejemplo

-- Empleado Recursos Humanos
INSERT INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
VALUES (
    'RH-0001',
    'María',
    'García López',
    'maria.garcia@empresa.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'empleado',
    1,
    '09:00:00',
    '17:00:00',
    8
);

-- Empleado Contabilidad
INSERT INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
VALUES (
    'CON-0001',
    'Juan',
    'Pérez Martín',
    'juan.perez@empresa.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'jefe_seccion',
    2,
    '08:30:00',
    '16:30:00',
    5
);

-- Empleado Desarrollo
INSERT INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
VALUES (
    'DEV-0001',
    'Ana',
    'Rodríguez Sánchez',
    'ana.rodriguez@empresa.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'jefe_seccion',
    3,
    '09:00:00',
    '18:00:00',
    10
);

-- Empleado Diseño
INSERT INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
VALUES (
    'DIS-0001',
    'Luis',
    'Fernández Torres',
    'luis.fernandez@empresa.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'empleado',
    4,
    '10:00:00',
    '19:00:00',
    8
);

-- Insertar proyectos de ejemplo
INSERT INTO proyectos (nombre, descripcion, horas_estimadas, estado, departamento_id) 
VALUES 
(
    'Desarrollo Página Web Corporativa',
    'Creación de la nueva página web institucional de la empresa con diseño responsive y panel de administración.',
    120.00,
    'activo',
    3
),
(
    'Campaña Marketing Digital Q1',
    'Estrategia de marketing digital para el primer trimestre incluyendo redes sociales y email marketing.',
    80.00,
    'activo',
    4
);

-- Asignar empleados a proyectos
-- Ana Rodríguez (DEV-0001) y Luis Fernández (DIS-0001) al proyecto 1
INSERT INTO proyecto_empleado (proyecto_id, usuario_id) VALUES (1, 3), (1, 4);
-- Luis Fernández (DIS-0001) al proyecto 2
INSERT INTO proyecto_empleado (proyecto_id, usuario_id) VALUES (2, 4);

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;