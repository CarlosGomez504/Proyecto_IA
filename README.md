# 🕐 Sistema de Control de Horarios - Control Horarios S.L.

¡Bienvenido al sistema de control de horarios de nuestra empresa ficticia! Esta web te permitirá gestionar el fichaje de empleados, el tiempo en proyectos y ver reportes detallados.

---

## 🚀 ¿Cómo Empezar?

### 1. Iniciar el Servidor

Abre una terminal y ejecuta:

```bash
cd /home/isard/Desktop/"Proyecto IA"/Proyecto_IA/public
php -S localhost:8000
```

### 2. Abrir en el Navegador

Ve a: **http://localhost:8000**

### 3. Iniciar Sesión

Usa cualquiera de estas cuentas:

| Usuario | Contraseña | Rol | ¿Qué puede hacer? |
|---------|------------|-----|-------------------|
| admin@empresa.com | admin123 | Administrador | Ver y gestionar TODO |
| maria.garcia@empresa.com | empleado123 | Empleado | Ver solo sus datos |
| juan.perez@empresa.com | empleado123 | Jefe de Sección | Ver su equipo |
| ana.rodriguez@empresa.com | empleado123 | Jefe de Sección | Ver su equipo |
| luis.fernandez@empresa.com | empleado123 | Empleado | Ver solo sus datos |
| carmen.ruiz@empresa.com | empleado123 | Empleado | Ver solo sus datos |
| pedro.sanchez@empresa.com | empleado123 | Técnico Informático | Ver solo sus datos |
| laura.moreno@empresa.com | empleado123 | Empleado | Ver solo sus datos |
| david.gil@empresa.com | empleado123 | Empleado | Ver solo sus datos |
| elena.vazquez@empresa.com | empleado123 | RRHH | Ver solo sus datos |

---

## 📋 ¿Qué Puede Hacer Cada Usuario?

### 👤 Empleado (la mayoría de usuarios)
- **Fichar**: Registrar entrada, salida y descansos
- **Ver su estado**: Saber a qué hora entró, salió y cuántas horas trabajó
- **Proyectos**: Ver en qué proyectos está trabajando y el tiempo dedicado
- **Alertas**: Recibir notificaciones si llegó tarde u olvidó fichar

### 👔 Jefe de Sección (Juan y Ana)
- Todo lo que hace un empleado, MÁS:
- **Ver su equipo**: Lista de empleados a su cargo
- **Estado del equipo**: Quién llegó a tiempo, quién tarde, quién no fichó

### 👑 Administrador (Carlos)
- **Ver toda la empresa**: Todos los empleados, todos los fichajes
- **Lista Roja**: Empleados que llegaron tarde hoy (¡en rojo!)
- **Gráficos**: Horas por departamento, fichajes por día
- **Gestionar empleados**: Crear, editar, dar de baja
- **Reportes**: Filtrar por fechas, departamentos, empleados
- **Exportar a Excel**: Descargar reportes en CSV

---

## 🏢 Proyectos de la Empresa

La empresa tiene 4 proyectos activos:

| Proyecto | Descripción | Horas Estimadas |
|----------|-------------|-----------------|
| Desarrollo Página Web Corporativa | Creación de nueva web corporativa | 200h |
| Campaña Marketing Digital Q1 | Campaña de marketing primer trimestre | 80h |
| Migración de Servidores | Migración de servidores a cloud AWS | 150h |
| App Móvil Clientes | Aplicación móvil iOS/Android | 120h |

Cada empleado está asignado a 1-2 proyectos donde registra su tiempo.

---

## ⏰ Horarios de la Empresa

Los empleados tienen diferentes horarios según su puesto:

| Empleado | Entrada | Salida | Margen |
|----------|---------|--------|--------|
| María García | 09:00 | 17:00 | 8 min |
| Juan Pérez | 08:30 | 16:30 | 5 min |
| Ana Rodríguez | 09:00 | 18:00 | 10 min |
| Luis Fernández | 10:00 | 19:00 | 8 min |
| Pedro Sánchez | 08:00 | 17:00 | 5 min |

**Margen**: Minutos de tolerancia para llegar tarde sin penalización.

---

## 🎯 Funcionalidades Principales

### 1. Fichaje (Fichaje.php)
- **Botón verde "REGISTRAR ENTRADA"**: Al llegar al trabajo
- **Botón azul "INICIAR DESCANSO"**: Para ir a comer/descansar
- **Botón verde "VOLVER DEL DESCANSO"**: Al regresar
- **Botón rojo "REGISTRAR SALIDA"**: Al terminar la jornada

El sistema calcula automáticamente:
- Hora de llegada
- Si llegaste tarde (comparando con tu hora teórica + margen)
- Horas trabajadas del día
- Tiempo de descanso tomado

### 2. Proyectos (Proyectos.php)
- Lista de proyectos asignados al empleado
- **Botón "INICIAR"**: Arranca el cronómetro para registrar tiempo en un proyecto
- **Botón "PARAR"**: Detiene el cronómetro y guarda el tiempo
- Solo puede haber UN cronómetro activo a la vez

### 3. Dashboard (Dashboard.php)
- **Empleado**: Ve su estado hoy, alertas, horas en proyectos
- **Jefe**: Ve lo mismo + lista de su equipo con colores (verde=a tiempo, rojo=tarde)
- **Admin**: Ve estadísticas de toda la empresa + Lista Roja de retrasos

### 4. Reportes (Reportes.php)
- Filtra por fechas (desde/hasta)
- Filtra por departamento (solo admin)
- Filtra por empleado específico
- Muestra tabla con todos los fichajes del período
- **Gráficos**:
  - 🍩 Horas por proyecto (gráfico de dona)
  - 📊 Empleados que ficharon por día (gráfico de barras)
- **Exportar CSV**: Descarga los datos para Excel

### 5. Empleados (Empleados.php) - Solo Admin
- Ver todos los empleados en una tabla
- **Crear nuevo empleado**: Nombre, email, departamento, rol, horario
- **Editar empleado**: Modificar cualquier dato
- **Dar de baja**: Marca como inactivo (no se borra)

---

## 🚨 Sistema de Alertas

El sistema genera alertas automáticamente:

| Tipo | Cuándo se genera |
|------|------------------|
| 🔴 Retraso | Cuando llegas después de tu hora + margen |
| 🟡 Olvido Salida | Cuando sales muy temprano sin registrar salida |
| 🟠 Salida Anticipada | Cuando fichas salida antes de tu hora teórica |

Las alertas aparecen en el dashboard y se marcan como leídas al verlas.

---

## 📊 Lista Roja (Solo Admin)

En el dashboard del admin, hay una sección especial llamada **"LISTA ROJA"** que muestra:

- Empleados que llegaron tarde HOY
- Hora teórica vs hora real de llegada
- Minutos exactos de retraso
- Todo en color rojo para destacar

¡Perfecto para identificar patrones de impuntualidad!

---

## 💾 Base de Datos

La web usa **SQLite** (archivo `database/control_horarios.db`), no necesita MySQL.

### Tablas principales:
- `usuarios`: Empleados con sus datos y contraseñas encriptadas
- `fichajes`: Registro diario de entradas/salidas
- `proyectos`: Proyectos de la empresa
- `proyecto_empleado`: Qué empleado trabaja en qué proyecto
- `tiempo_proyectos`: Registro del cronómetro por proyecto
- `alertas`: Notificaciones del sistema
- `departamentos`: Áreas de la empresa

---

## 🔒 Seguridad

- ✅ Contraseñas encriptadas con `password_hash()` (bcrypt)
- ✅ Consultas preparadas (evita inyección SQL)
- ✅ Validación de emails con `filter_var()`
- ✅ Protección contra XSS con `htmlspecialchars()`
- ✅ Sesiones seguras con regeneración de ID
- ✅ Bloqueo tras 5 intentos fallidos de login (15 min)

---

## 🛠️ Estructura del Proyecto

```
Proyecto_IA/
├── config/
│   └── config.php          ← Configuración general
├── database/
│   ├── control_horarios.db ← Base de datos SQLite
│   ├── schema.sql          ← Estructura MySQL
│   └── schema_sqlite.sql   ← Estructura SQLite
├── includes/
│   ├── db.php              ← Conexión a BD
│   ├── auth.php            ← Login y sesiones
│   └── funciones.php       ← Funciones útiles
├── public/
│   ├── index.php           ← Login
│   ├── dashboard.php       ← Página principal
│   ├── fichaje.php         ← Fichar entrada/salida
│   ├── proyectos.php       ← Cronómetro de proyectos
│   ├── empleados.php       ← Gestión (solo admin)
│   ├── reportes.php        ← Reportes y gráficos
│   ├── logout.php          ← Cerrar sesión
│   ├── api/
│   │   ├── fichar.php      ← AJAX para fichar
│   │   └── timer.php       ← AJAX para cronómetro
│   └── assets/
│       ├── css/style.css   ← Estilos
│       └── js/timer.js     ← JavaScript del cronómetro
├── .gitignore
└── README.md               ← ¡Este archivo!
```

---

## 🎨 Diseño

- **Moderno y limpio**: Interfaz intuitiva que cualquiera puede usar
- **Responsive**: Funciona en computadora, tablet y móvil
- **Colores**:
  - 🔵 Azul (#1e3a5f) - Cabecera y menú
  - ⚪ Blanco - Fondo
  - 🟢 Verde (#28a745) - Botones de acción
  - 🔴 Rojo (#dc3545) - Alertas y salidas
- **Iconos**: Font Awesome para todos los iconos

---

## 📝 Datos de Ejemplo

La base de datos ya viene con:
- ✅ 10 empleados de diferentes departamentos
- ✅ 63 fichajes de los últimos 7 días
- ✅ 95 registros de tiempo en proyectos
- ✅ Algunos empleados llegan tarde (para ver la Lista Roja)
- ✅ Todos los proyectos tienen horas registradas

---

## 🔄 ¿Qué Hacer Si...?

### ...Se reinicia la máquina virtual
El servidor se detiene. Para volver a iniciarlo:
```bash
cd /home/isard/Desktop/"Proyecto IA"/Proyecto_IA/public
php -S localhost:8000
```

### ...Quiero crear un empleado nuevo
1. Inicia sesión como admin
2. Ve a "Empleados"
3. Haz clic en "Nuevo Empleado"
4. Rellena los datos (el código se genera solo)
5. Guarda

### ...Quiero asignar un proyecto a un empleado
1. Inicia sesión como admin
2. Ve a "Empleados"
3. Edita el empleado
4. Marca los proyectos deseados
5. Guarda

### ...Olvidé mi contraseña
Todas las contraseñas están en esta tabla. Si necesitas cambiarla, usa `password_hash()` en PHP.

---

## 👨‍💻 Tecnologías Usadas

- **PHP 8** puro (sin frameworks)
- **SQLite** con PDO
- **JavaScript** vanilla (sin jQuery)
- **Chart.js** para gráficos
- **Font Awesome** para iconos
- **CSS3** moderno con variables

---

## 📞 Contacto

Proyecto desarrollado por **Carlos Gómez** para la asignatura de Proyecto IA.

¡Espero que disfrutes usando el sistema! 😊