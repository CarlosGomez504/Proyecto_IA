-- ============================================
-- SISTEMA DE CONTROL DE HORARIOS - SQLite
-- ============================================
-- Version adaptada para SQLite (sin MySQL)
-- ============================================

-- TABLA 1: DEPARTAMENTOS
CREATE TABLE IF NOT EXISTS departamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- TABLA 2: USUARIOS
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_empleado VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL DEFAULT 'empleado',
    departamento_id INTEGER NOT NULL,
    hora_entrada TIME NOT NULL DEFAULT '09:00:00',
    hora_salida TIME NOT NULL DEFAULT '17:00:00',
    minutos_margen INTEGER NOT NULL DEFAULT 8,
    activo INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

-- TABLA 3: FICHAJES
CREATE TABLE IF NOT EXISTS fichajes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    fecha DATE NOT NULL,
    hora_entrada DATETIME DEFAULT NULL,
    hora_salida DATETIME DEFAULT NULL,
    inicio_descanso DATETIME DEFAULT NULL,
    fin_descanso DATETIME DEFAULT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    minutos_retraso INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    UNIQUE(usuario_id, fecha)
);

-- TABLA 4: PROYECTOS
CREATE TABLE IF NOT EXISTS proyectos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    horas_estimadas DECIMAL(5,2) DEFAULT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'activo',
    departamento_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

-- TABLA 5: PROYECTO_EMPLEADO
CREATE TABLE IF NOT EXISTS proyecto_empleado (
    proyecto_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    PRIMARY KEY (proyecto_id, usuario_id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- TABLA 6: TIEMPO_PROYECTOS
CREATE TABLE IF NOT EXISTS tiempo_proyectos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    proyecto_id INTEGER NOT NULL,
    inicio DATETIME NOT NULL,
    fin DATETIME DEFAULT NULL,
    minutos_totales INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (proyecto_id) REFERENCES proyectos(id)
);

-- TABLA 7: ALERTAS
CREATE TABLE IF NOT EXISTS alertas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id INTEGER NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    mensaje TEXT NOT NULL,
    leida INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Insertar departamentos
INSERT OR IGNORE INTO departamentos (nombre) VALUES 
('Recursos Humanos'),
('Contabilidad'),
('Desarrollo'),
('Diseño'),
('Dirección');

-- Insertar usuario ADMIN
-- Email: admin@empresa.com, Contraseña: admin123
INSERT OR IGNORE INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
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

-- Empleado Recursos Humanos
INSERT OR IGNORE INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
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

-- Empleado Contabilidad (Jefe)
INSERT OR IGNORE INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
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

-- Empleado Desarrollo (Jefe)
INSERT OR IGNORE INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
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
INSERT OR IGNORE INTO usuarios (codigo_empleado, nombre, apellidos, email, password_hash, rol, departamento_id, hora_entrada, hora_salida, minutos_margen) 
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
INSERT OR IGNORE INTO proyectos (nombre, descripcion, horas_estimadas, estado, departamento_id) 
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
INSERT OR IGNORE INTO proyecto_empleado (proyecto_id, usuario_id) VALUES (1, 3), (1, 4);
INSERT OR IGNORE INTO proyecto_empleado (proyecto_id, usuario_id) VALUES (2, 4);