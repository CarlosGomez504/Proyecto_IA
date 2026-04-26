# 🕐 Sistema de Control de Horarios - Control Horarios S.L.

**Projecte Web: PHP + MySQL amb Cline**

---

## 📚 RA6 - Resultats d'Aprenentatge

Aquest projecte cobreix els següents criteris d'avaluació:

| Codi | Criteri | Complert |
|------|---------|----------|
| 6.1 | Identifica els sistemes gestors de bases de dades més utilitzats en entorns web | ✅ |
| 6.2 | Verifica la integració dels sistemes gestors de bases de dades amb el llenguatge de guions de servidors | ✅ |
| 6.3 | Configura en el llenguatge de guions la connexió per a l'accés al sistema gestor de bases de dades | ✅ |
| 6.4 | Crea bases de dades i taules en el gestor utilitzant el llenguatge de guions | ✅ |
| 6.5 | Obté i actualitza la informació emmagatzemada a bases de dades | ✅ |
| 6.6 | Aplica criteris de seguretat en l'accés dels usuaris | ✅ |
| 6.7 | Verifica el funcionament i el rendiment del sistema | ✅ |
| 6.8 | Identifica i assegura als usuaris que accedeixen al document web | ✅ |
| 6.9 | Verifica l'aïllament de l'entorn específic de cada usuari | ✅ |

---

## 🎯 Objectiu del Projecte

Dissenyar, desenvolupar i publicar en un servidor propi un projecte web creat amb PHP i accés a BBDD amb MySQL/SQLite. El desenvolupament s'ha realitzat amb eines generatives de codi com Cline.

---

## 📧 Enunciat - Briefing del Client

> *"Hola, què tal? Mira, m'han parlat molt bé de tu i necessito això JA. Tenim un caos a l'empresa que no t'ho pots ni imaginar. Som 400 persones i ara mateix no sé qui collons està fent què, m'explico?*
>
> *Necessito una eina, una web, el que sigui, però que sigui ràpida i que no falli. Aquí la penya em diu que treballa vuit hores però jo veig projectes que no avancen i m'estic posant negre.*
>
> **Control total d'hores:** Vull que cada empleat marqui quan entra i quan surt. Però no només això, vull saber en què es gasten el meu temps.*
>
> **Xivatat de l'incompliment:** Vull una llista vermella, m'entens? Que el sistema em digui automàticament qui no està fent les hores que toca, qui arriba tard o qui plega abans d'hora.*
>
> **Reports de Projectes:** Necessito saber quant m'està costant cada projecte en hores.*
>
> **Fàcil, molt fàcil:** No em vinguis amb manuals de 50 pàgines. Vull que l'empleat entri, cliqui un botó i avall."*

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

---

## 📖 Documentació de Codi - Guia d'Estudi

Aquesta secció mostra la comprensió del projecte i serveix com a guia d'estudi per a l'examen.

### 2 - Gestió de Formularis i Seguretat d'Entrada

**Tasca:** Processament del formulari de login.

**Snippet:**
```php
// Ubicació: public/index.php (línia ~45)
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$email = filter_var($email, FILTER_VALIDATE_EMAIL);

$password = $_POST['password'] ?? '';
```

**Explicació:** 
- S'utilitza `$_POST` perquè les dades sensibles (contrasenya) no han de veure's a la URL.
- `filter_var()` amb `FILTER_SANITIZE_EMAIL` neteja l'email.
- `filter_var()` amb `FILTER_VALIDATE_EMAIL` valida el format.
- `htmlspecialchars()` s'usa en mostrar dades a les vistes per evitar XSS.

**Diferència:**
- `htmlspecialchars()`: Converteix caràcters especials en entitats HTML (`<` → `<`). S'usa en SORTIDA.
- `filter_var()`: Valida o neteja dades d'ENTRADA segons el filtre especificat.

---

### 3 - Persistència de Dades

**Tasca:** Manteniment de l'estat de l'usuari.

**Snippet session_start():**
```php
// Ubicació: includes/auth.php (línia ~8)
session_start();
```

**Per què al principi?** `session_start()` ha d'anar abans de qualsevol sortida HTML per poder enviar les capçaleres de sessió correctament.

**Exemple $_SESSION:**
```php
// Ubicació: public/index.php (línia ~70)
$_SESSION['user_id'] = $usuario['id'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['rol'] = $usuario['rol'];
$_SESSION['departamento_id'] = $usuario['departamento_id'];
```

**Exemple setcookie():**
```php
// Ubicació: public/index.php (línia ~75)
// Guardem només l'email, NO la contrasenya (seguretat!)
if (isset($_POST['recordarme'])) {
    setcookie('recordar_email', $email, time() + (86400 * 30), '/');
}
```

**Per què no guardar la contrasenya a la cookie?**
- Les cookies es guarden al navegador del client i són accessibles per JavaScript.
- Si algú roba la cookie, tindria accés complet al compte.
- Només guardem l'email per comoditat (pre-omplir el formulari), mai la contrasenya.

---

### 4 - Autenticació i Xifrat

**Tasca:** Validació d'usuaris.

**Creació d'usuari amb password_hash():**
```php
// Ubicació: database/poblar_datos.php (línia ~30)
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);
// PASSWORD_DEFAULT utilitza bcrypt (més segur)
```

**Algoritme per defecte:** `PASSWORD_DEFAULT` utilitza `PASSWORD_BCRYPT` (bcrypt) amb cost 10.

**Login amb password_verify():**
```php
// Ubicació: public/index.php (línia ~55)
if (password_verify($password, $usuario['password_hash'])) {
    // Contrasenya correcta
    $_SESSION['user_id'] = $usuario['id'];
}
```

**Com funciona?**
- `password_verify()` compara la contrasenya plana amb el hash.
- Utilitza el mateix algoritme i sal emmagatzemats al hash.
- És constant-time per evitar atacs de timing.

**Logout:**
```php
// Ubicació: public/logout.php
session_unset();    // Elimina totes les variables de sessió
session_destroy();  // Elimina la sessió del servidor
```

- `session_unset()`: Neteja les variables `$_SESSION`.
- `session_destroy()`: Elimina el fitxer de sessió del servidor.

---

### 5 - Connexió i Seguretat de Base de Dades

**Tasca:** Capa d'accés a dades.

**Fitxer de configuració:**
```php
// Ubicació: includes/db.php
function obtenerConexion() {
    try {
        $db = new PDO("sqlite:" . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        // NO mostrem l'error real (podria contenir informació sensible)
        error_log("Error de connexió a la base de dades");
        die("Error de connexió a la base de dades. Contacti l'administrador.");
    }
}
```

**DSN (Data Source Name):** `"sqlite:" . DB_PATH` indica el tipus de BD i la ruta del fitxer.

**Try-catch:** Evita que un error de connexió mostri la contrasenya o ruta de la BD.

**Prepared Statements (evita Injecció SQL):**
```php
// Ubicació: public/index.php (línia ~50)
$stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();
```

**Com evita Injecció SQL?**
- El `?` és un placeholder que separa el codi SQL de les dades.
- Les dades s'envien per separat i es tracten com a valors, no com a codi executable.
- Encara que l'usuari introdueixi `' OR 1=1 --`, es tractarà com a text literal.

---

### 6 - Operacions CRUD i Rendiment

**Tasca:** Interacció amb la base de dades.

**DDL (CREATE TABLE):**
```sql
-- Ubicació: database/schema_sqlite.sql
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_empleado TEXT UNIQUE NOT NULL,
    nombre TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    rol TEXT NOT NULL DEFAULT 'empleado',
    departamento_id INTEGER,
    hora_entrada TIME NOT NULL DEFAULT '09:00:00',
    hora_salida TIME NOT NULL DEFAULT '17:00:00',
    minutos_margen INTEGER DEFAULT 8,
    activo INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**DML (UPDATE):**
```php
// Ubicació: public/empleados.php (línia ~80)
$stmt = $db->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id = ?");
$stmt->execute([$nombre, $email, $rol, $id]);
// S'utilitza execute() amb array de paràmetres
```

**SELECT amb fetchAll():**
```php
// Ubicació: public/dashboard.php (línia ~40)
$stmt = $db->prepare("SELECT id, nombre, email, rol FROM usuarios WHERE activo = 1");
$stmt->execute();
$usuarios = $stmt->fetchAll();
```

**Per què columnes específiques en lloc de *?**
- Més eficient: només es transmeten les dades necessàries.
- Menys amplada de banda entre BD i aplicació.
- Si la taula té moltes columnes, seleccionar-les totes és innecessari.
- Millor control sobre quines dades s'exposen.

---

## 📊 Tasca 0 - Disseny i Pla del Projecte

### Funcionalitats de l'Aplicació

| Funcionalitat | Descripció |
|---------------|------------|
| Login/Logout | Autenticació segura amb bloqueig després de 5 intents |
| Fichaje | Registrar entrada, sortida, inici i fi de descans |
| Projects | Cronòmetre per registrar temps en projectes |
| Dashboard | Vista personalitzada segons rol |
| Reportes | Filtres per data, departament, empleat; exportació CSV |
| Empleats (Admin) | CRUD complet d'empleats |
| Alertes | Notificacions automàtiques de retard/oblit |
| Llista Roja | Empleats que arriben tard (només Admin) |

### Dades que Utilitza l'App

| Dada | Origen | Ús |
|------|--------|-----|
| Email/Contrasenya | Formulari login | Autenticació |
| Hora d'entrada/sortida | Botons de fitxatge | Càlcul hores treballades |
| Projecte seleccionat | Selector de projectes | Assignació de temps |
| Filtres de reportes | Formulari de filtres | Consulta personalitzada |

### Rols d'Usuari

| Rol | Permisos |
|-----|----------|
| **admin** | Accés total: veure tot, gestionar empleats, reportes, gràfics |
| **jefe_seccion** | Veure el seu equip + les seves pròpies dades |
| **empleado** | Només veure i gestionar les seves pròpies dades |

### Pantalles de l'Aplicació

```
┌─────────────────┐
│    LOGIN        │ ← index.php
│  email/pass     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌─────────────────┐
│   DASHBOARD     │────▶│    FICHAJE      │
│  (segons rol)   │     │  entrada/sortida│
└────────┬────────┘     └─────────────────┘
         │
         ├──▶ ┌─────────────────┐
         │    │   PROJECTS      │
         │    │  cronòmetre     │
         │    └─────────────────┘
         │
         ├──▶ ┌─────────────────┐
         │    │   REPORTES      │
         │    │  filtres/gràfics│
         │    └─────────────────┘
         │
         └──▶ ┌─────────────────┐
              │   EMPLEATS      │ (només Admin)
              │   CRUD          │
              └─────────────────┘
```
</content>
<task_progress>
- [x] Crear README.md amb informació del projecte
- [x] Actualitzar README.md amb criteris RA6 i documentació tècnica
- [ ] Subir al repositori GitHub
</task_progress>
