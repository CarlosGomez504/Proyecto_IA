# Sistema de Control de Horarios

Aplicación web para el control de horarios, fichaje de empleados y gestión de proyectos.

## Características

- **Control de Fichaje**: Registro de entrada, salida y descansos
- **Gestión de Proyectos**: Temporizador de tiempo por proyecto
- **Roles de Usuario**: Admin, Jefe de Sección y Empleado
- **Reportes**: Estadísticas con gráficos y exportación a CSV
- **Alertas**: Notificaciones de retrasos y salidas anticipadas
- **Lista Roja**: Visualización de empleados con retraso (Admin)

## Requisitos

- PHP 8.0 o superior
- MySQL 8.0 o superior
- Servidor web (Apache/Nginx) o PHP built-in server

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/CarlosGomez504/Proyecto_IA.git
cd Proyecto_IA
```

### 2. Configurar la base de datos

1. Crear una base de datos MySQL llamada `control_horarios`:

```sql
CREATE DATABASE control_horarios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importar el schema:

```bash
mysql -u root -p control_horarios < database/schema.sql
```

### 3. Configurar la aplicación

1. Copiar `config/config.php.example` a `config/config.php`
2. Editar `config/config.php` con las credenciales de tu base de datos:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'control_horarios');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 4. Ejecutar la aplicación

Usando el servidor integrado de PHP:

```bash
cd public
php -S localhost:8000
```

O configurar un servidor web apuntando a la carpeta `public/`.

## Usuarios de Prueba

| Rol | Email | Contraseña |
|-----|-------|------------|
| Admin | admin@empresa.com | admin123 |
| Empleado | maria.garcia@empresa.com | empleado123 |
| Jefe Sección | juan.perez@empresa.com | empleado123 |
| Jefe Sección | ana.rodriguez@empresa.com | empleado123 |
| Empleado | luis.fernandez@empresa.com | empleado123 |

## Estructura del Proyecto

```
Proyecto_IA/
├── api/                    # Endpoints AJAX
│   ├── fichar.php         # Procesamiento de fichajes
│   └── timer.php          # Gestión de temporizadores
├── assets/
│   ├── css/
│   │   └── style.css      # Estilos de la aplicación
│   └── js/
│       └── timer.js       # Script del temporizador
├── config/
│   └── config.php         # Configuración (no subir a Git)
├── database/
│   └── schema.sql         # Estructura de la base de datos
├── includes/
│   ├── auth.php           # Autenticación y sesiones
│   ├── db.php             # Conexión a MySQL con PDO
│   └── funciones.php      # Funciones auxiliares
├── public/
│   ├── index.php          # Login
│   ├── dashboard.php      # Panel principal
│   ├── fichaje.php        # Página de fichaje
│   ├── proyectos.php      # Gestión de proyectos
│   ├── empleados.php      # CRUD de empleados (Admin)
│   ├── reportes.php       # Reportes y estadísticas
│   └── logout.php         # Cerrar sesión
└── .gitignore
```

## Seguridad

- **Contraseñas**: Hasheadas con `password_hash()` de PHP
- **SQL**: Consultas preparadas con PDO (anti-inyección)
- **XSS**: `htmlspecialchars()` en todas las salidas
- **Sesiones**: Regeneración de ID y cookies seguras
- **Cookies**: Solo se guarda el email para "Recordarme" (nunca la contraseña)

## Tecnologías

- **Backend**: PHP puro (sin frameworks)
- **Base de Datos**: MySQL con PDO
- **Frontend**: HTML5, CSS3, JavaScript vanilla
- **Gráficos**: Chart.js
- **Iconos**: Font Awesome

## Departamentos

- Recursos Humanos
- Contabilidad
- Desarrollo
- Diseño
- Dirección

## Licencia

Este proyecto es parte de un trabajo académico.

## Autor

Carlos Gomez - [CarlosGomez504](https://github.com/CarlosGomez504)