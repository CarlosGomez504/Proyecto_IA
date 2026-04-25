# Sistema de Control de Horarios

Aplicación web para el control de horarios, fichaje de empleados y gestión de proyectos.

## 🚀 ¡Funciona Sin Configuración!

Esta versión usa **SQLite** como base de datos, por lo que **no necesitas instalar MySQL** ni configurar servidores. Solo necesitas PHP.

## Ejecución Rápida

```bash
cd public
php -S localhost:8000
```

Luego abre tu navegador en: **http://localhost:8000**

¡Eso es todo! La base de datos SQLite se crea automáticamente la primera vez.

## Usuarios de Prueba

| Email | Contraseña | Rol |
|-------|------------|-----|
| admin@empresa.com | admin123 | Admin (acceso total) |
| maria.garcia@empresa.com | empleado123 | Empleado |
| juan.perez@empresa.com | empleado123 | Jefe de Sección |
| ana.rodriguez@empresa.com | empleado123 | Jefe de Sección |
| luis.fernandez@empresa.com | empleado123 | Empleado |

## Características

- ✅ **Control de Fichaje**: Entrada, salida y descansos
- ✅ **Temporizador de Proyectos**: Registra tiempo por proyecto
- ✅ **3 Roles**: Admin, Jefe de Sección, Empleado
- ✅ **Reportes con Gráficos**: Chart.js integrado
- ✅ **Exportación CSV**: Descarga de datos
- ✅ **Alertas Automáticas**: Retrasos y salidas anticipadas
- ✅ **Lista Roja**: Empleados con retraso (Admin)
- ✅ **Seguridad**: PDO, password_hash, consultas preparadas

## Requisitos

- PHP 7.4 o superior
- Extensión PDO SQLite habilitada (viene por defecto en PHP 8+)

## Estructura del Proyecto

```
Proyecto_IA/
├── api/                    # Endpoints AJAX
│   ├── fichar.php         # Procesamiento de fichajes
│   └── timer.php          # Gestión de temporizadores
├── assets/
│   ├── css/style.css      # Estilos
│   └── js/timer.js        # Temporizador
├── config/
│   └── config.php         # Configuración
├── database/
│   ├── schema.sql         # Schema MySQL (referencia)
│   ├── schema_sqlite.sql  # Schema SQLite (automático)
│   └── control_horarios.db # Base de datos (auto-creada)
├── includes/
│   ├── auth.php           # Autenticación
│   ├── db.php             # Conexión SQLite
│   └── funciones.php      # Funciones auxiliares
├── public/
│   ├── index.php          # Login
│   ├── dashboard.php      # Panel principal
│   ├── fichaje.php        # Fichaje
│   ├── proyectos.php      # Proyectos
│   ├── empleados.php      # Gestión (Admin)
│   ├── reportes.php       # Reportes
│   └── logout.php         # Cerrar sesión
└── README.md
```

## Seguridad Implementada

- **Contraseñas**: Hasheadas con `password_hash()`
- **SQL**: Consultas preparadas con PDO (anti-inyección)
- **XSS**: `htmlspecialchars()` en todas las salidas
- **Sesiones**: Seguras con regeneración de ID
- **Cookies**: Solo email para "Recordarme" (nunca contraseña)

## Tecnologías

- **Backend**: PHP puro (sin frameworks)
- **Base de Datos**: SQLite con PDO
- **Frontend**: HTML5, CSS3, JavaScript vanilla
- **Gráficos**: Chart.js (CDN)
- **Iconos**: Font Awesome (CDN)

## Departamentos

- Recursos Humanos
- Contabilidad
- Desarrollo
- Diseño
- Dirección

## Notas

- La base de datos SQLite se crea automáticamente en `database/control_horarios.db`
- No es necesario ejecutar scripts SQL manualmente
- Todos los datos de prueba se insertan automáticamente
- El proyecto está listo para producción en entornos pequeños/medianos

## Autor

Carlos Gomez - [CarlosGomez504](https://github.com/CarlosGomez504)

## Licencia

Proyecto académico - Control Horarios S.L.