# API de Gestión de Tareas y Equipos - Laravel

Una API REST desarrollada en Laravel para la gestión de workspaces, equipos y tareas, permitiendo la colaboración entre usuarios con diferentes roles para la pagina web KanbanFlow.

---

## Descripción del proyecto

Esta API tiene como objetivo crear un sistema completo de gestión de proyectos colaborativos, donde los usuarios pueden crear espacios de trabajo, formar equipos y asignar tareas con diferentes niveles de permisos.

### Funcionalidades principales

- **Autenticación**  
  Sistema completo de registro, login y gestión de tokens con Laravel Sanctum.

- **Workspaces**  
  Espacios de trabajo que funcionan como contenedores principales para organizar proyectos.

- **Teams**  
  Equipos de trabajo dentro de los workspaces con roles diferenciados (líder/miembro).

- **Tasks**  
  Sistema de tareas asignables con seguimiento de progreso y estados.

- **Permisos**  
  Control granular de permisos basado en roles de usuario.

---

### Tecnologías utilizadas

- **Laravel 12.x**  
- **PHP 8.2+**
- **Laravel Sanctum** para autenticación API
- **MySQL/PostgreSQL** para base de datos
- **Eloquent ORM** para manejo de datos

---

## Guía de Uso Rápido

###  **1. AUTENTICACIÓN (OBLIGATORIO)**

```bash
# Registro de usuario
POST /api/register
{
  "first_name": "Juan",
  "last_name": "Pérez", 
  "email": "juan@email.com",
  "password": "123456",
  "password_confirmation": "123456"
}

# Login para obtener token
POST /api/login
{
  "email": "juan@email.com",
  "password": "123456"
}
# Respuesta: { "user": {...}, "token": "TOKEN_AQUÍ" }

# Usar token en TODAS las peticiones siguientes:
Headers: {
  "Authorization": "Bearer TOKEN_AQUÍ",
  "Content-Type": "application/json"
}
```

###  **2. FLUJO DE TRABAJO (ORDEN OBLIGATORIO)**

```bash
# PASO 1: Crear workspace (PRIMERO)
POST /api/workspaces
{ 
  "name": "Mi Proyecto", 
  "description": "Descripción del proyecto" 
}
# Respuesta: { "success": true }

# PASO 2: Crear equipo (SEGUNDO)  
POST /api/teams
{ 
  "name": "Frontend Team", 
  "workspace_id": 1 
}
# Respuesta: { "success": true }

# PASO 3: Agregar miembros (TERCERO)
POST /api/teams/1/add-member
{ 
  "user_id": 2, 
  "role": "member" 
}
# Respuesta: { "success": true }

# PASO 4: Crear tareas (CUARTO)
POST /api/tasks
{ 
  "title": "Implementar login",
  "description": "Crear pantalla de autenticación",
  "workspace_id": 1,
  "team_id": 1, 
  "assigned_to": 2
}
# Respuesta: { "success": true }

# PASO 5: Actualizar progreso (QUINTO)
PUT /api/tasks/1
{ 
  "progress": 50, 
  "is_done": false 
}
# Respuesta: { "success": true }
```

###  **3. ROLES Y PERMISOS**

**CREADOR DE WORKSPACE:**
- ✅ Puede crear/editar/eliminar workspace
- ✅ Puede crear equipos dentro del workspace
- ✅ Ve todas las tareas del workspace
- ❌ No puede gestionar miembros de equipos

**LÍDER DE EQUIPO:**
- ✅ Puede agregar/quitar miembros del equipo
- ✅ Puede crear/editar/eliminar tareas
- ✅ Puede cambiar TODO en las tareas (título, descripción, asignación)
- ✅ Ve todas las tareas del equipo

**MIEMBRO DE EQUIPO:**
- ✅ Ve solo sus tareas asignadas
- ✅ Puede actualizar progress e is_done de sus tareas
- ❌ NO puede crear/eliminar tareas
- ❌ NO puede gestionar miembros

### 📊 **4. CONSULTAS IMPORTANTES**

```bash
# Ver mis tareas asignadas
GET /api/tasks

# Ver mis equipos donde participo
GET /api/teams

# Ver miembros de un equipo
GET /api/teams/1/members

# Ver tareas de un equipo
GET /api/teams/1/tasks

# Ver todos los usuarios (para asignar tareas)
GET /api/users

# Ver tareas de un workspace
GET /api/workspaces/1/tasks
```

###  **5. RESPUESTAS DE LA API**

```json
// ÉXITO en operaciones (crear, editar, eliminar)
{ "success": true }

// ERROR en cualquier operación
{ 
  "success": false, 
  "error": "Mensaje descriptivo del error" 
}

// DATOS en consultas GET
[
  { 
    "id": 1, 
    "name": "Nombre del recurso",
    "..." 
  }
]
```

---

## Instalación y Configuración

### Prerrequisitos
- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Git

### Pasos de instalación

```bash
# 1. Clonar repositorio
git clone [URL_REPOSITORIO]
cd laraApp

# 2. Instalar dependencias
composer install

# 3. Configurar ambiente
cp .env.example .env
# Editar .env con datos de tu base de datos

# 4. Generar clave y migrar
php artisan key:generate
php artisan migrate

# 5. Iniciar servidor
php artisan serve
# API disponible en: http://localhost:8000/api
```

---

## Endpoints Principales

### Autenticación
- `POST /api/register` - Registro de usuarios
- `POST /api/login` - Inicio de sesión  
- `POST /api/logout` - Cierre de sesión
- `GET /api/users` - Lista todos los usuarios

### Workspaces
- `GET /api/workspaces` - Lista workspaces del usuario
- `POST /api/workspaces` - Crear workspace
- `GET /api/workspaces/{id}` - Ver workspace específico
- `PUT /api/workspaces/{id}` - Actualizar workspace
- `DELETE /api/workspaces/{id}` - Eliminar workspace

### Teams
- `GET /api/teams` - Lista equipos del usuario
- `POST /api/teams` - Crear equipo
- `GET /api/teams/{id}/members` - Ver miembros
- `POST /api/teams/{id}/add-member` - Agregar miembro
- `DELETE /api/teams/{id}/remove-member/{userId}` - Quitar miembro

### Tasks
- `GET /api/tasks` - Tareas asignadas al usuario
- `POST /api/tasks` - Crear tarea (solo líderes)
- `PUT /api/tasks/{id}` - Actualizar tarea
- `DELETE /api/tasks/{id}` - Eliminar tarea (solo líderes)

---

## Errores Comunes

- **401 Unauthorized:** Token faltante o expirado
- **403 Forbidden:** Sin permisos para la operación (rol incorrecto)  
- **422 Unprocessable Entity:** Datos de entrada inválidos
- **404 Not Found:** Recurso no encontrado

---

## Notas Importantes

```php
// Estructura de base de datos principal
users -> workspaces -> teams -> tasks
  ↓         ↓          ↓       ↓
login   created_by  members  assigned_to

// Flujo obligatorio de creación
1. User hace login
2. User crea Workspace
3. User crea Team en Workspace
4. User agrega Members a Team
5. Leader crea Tasks y asigna a Members
6. Members actualizan progress de sus Tasks
```

### Propiedades clave

- **Autenticación:** Todas las rutas requieren token Bearer
- **Permisos:** Validación estricta basada en roles
- **Relaciones:** Estructura jerárquica workspace → team → task
- **Respuestas:** Formato consistente con success/error

---

## Características Principales

- **Autenticación y autorización** con Laravel Sanctum
- **Gestión de workspaces** (espacios de trabajo)
- **Administración de equipos** con roles (líder/miembro)
- **Sistema de tareas** asignables a usuarios
- **Control de permisos** basado en roles
- **API RESTful** con respuestas consistentes

##  Arquitectura del Sistema

### Modelos Principales

#### 1. **User (Usuario)**
- Gestiona la información de usuarios registrados
- Campos: `first_name`, `last_name`, `email`, `phone`, `password`
- Relaciones: Pertenece a múltiples equipos, puede tener tareas asignadas

#### 2. **Workspace (Espacio de Trabajo)**
- Contenedor principal para organizar proyectos
- Campos: `name`, `description`, `created_by`
- Relaciones: Tiene múltiples equipos y tareas
- Permisos: Solo el creador puede gestionar el workspace

#### 3. **Team (Equipo)**
- Grupos de usuarios dentro de un workspace
- Relaciones: Pertenece a un workspace, tiene múltiples usuarios
- Roles: `leader` (líder) y `member` (miembro)
- Permisos: Líderes pueden gestionar el equipo y crear tareas

#### 4. **Task (Tarea)**
- Unidad de trabajo asignable a usuarios
- Campos: `title`, `description`, `progress`, `is_done`, `workspace_id`, `assigned_to`, `created_by`
- Estados: Progreso de 0-100% y estado completado/pendiente
- Permisos: Líderes crean/editan, miembros actualizan progreso

## 🔧 Módulos y Tecnologías Utilizadas

### Framework y Core
- **Laravel 12.x** - Framework PHP principal
- **PHP 8.2+** - Lenguaje de programación
- **MySQL/PostgreSQL** - Base de datos relacional

### Autenticación y Seguridad
- **Laravel Sanctum** - Autenticación API con tokens
- **Hash** - Encriptación de contraseñas
- **Middleware de autenticación** - Protección de rutas

### Validación y Manejo de Errores
- **Form Request Validation** - Validación de datos de entrada
- **Exception Handling** - Manejo centralizado de errores
- **HTTP Status Codes** - Respuestas HTTP estándar

### Base de Datos
- **Eloquent ORM** - Mapeo objeto-relacional
- **Migraciones** - Control de versiones de base de datos
- **Relationships** - Relaciones entre modelos (Many-to-Many, Has-Many)
- **Seeders** - Datos de prueba

##  Estructura Detallada del Proyecto

###  **Carpeta de Migraciones** (`database/migrations/`)

Las migraciones definen la estructura de la base de datos y permiten versionar los cambios del esquema:

#### **Migraciones Principales:**
```
0001_01_01_000000_create_users_table.php
├── Tabla users: Información básica de usuarios
├── Campos: id, first_name, last_name, phone, email, password
├── Índices: email único, timestamps
└── Incluye: password_reset_tokens, sessions

0001_01_01_100000_create_workspaces_table.php
├── Tabla workspaces: Espacios de trabajo principales
├── Campos: id, name, description, created_by
├── Relaciones: created_by → users.id (foreign key)
└── Constraints: NOT NULL en name y created_by

0001_01_01_110000_create_teams_table.php
├── Tabla teams: Equipos dentro de workspaces
├── Campos: id, name, workspace_id
├── Relaciones: workspace_id → workspaces.id (foreign key)
└── Cascada: ON DELETE CASCADE

0001_01_01_120000_create_team_user_table.php
├── Tabla pivot team_user: Relación muchos a muchos
├── Campos: id, team_id, user_id, role
├── Roles: 'leader', 'member'
├── Relaciones: team_id → teams.id, user_id → users.id
└── Índices: Compuesto (team_id, user_id) único

0001_01_01_130000_create_tasks_table.php
├── Tabla tasks: Tareas asignables
├── Campos: id, title, description, progress, is_done, workspace_id, assigned_to, created_by
├── Tipos: progress (INTEGER 0-100), is_done (BOOLEAN)
├── Relaciones: workspace_id → workspaces.id, assigned_to → users.id, created_by → users.id
└── Defaults: progress = 0, is_done = false

2025_07_22_034535_create_personal_access_tokens_table.php
├── Tabla personal_access_tokens: Tokens de autenticación Sanctum
├── Funcionalidad: Gestión de tokens API
└── Campos: id, tokenable_type, tokenable_id, name, token, abilities, expires_at
```

### **Carpeta de Controladores** (`app/Http/Controllers/`)

Los controladores manejan la lógica de negocio y procesan las peticiones HTTP:

#### **AuthController.php**
```php
Funcionalidades principales:
├── register(): Registro de nuevos usuarios
│   ├── Validación: first_name, last_name, email, password
│   ├── Encriptación: Hash::make() para passwords
│   └── Token: Generación automática con Sanctum
├── login(): Autenticación de usuarios
│   ├── Validación: email, password
│   ├── Verificación: Hash::check() para passwords
│   └── Respuesta: user + token de acceso
├── logout(): Cierre de sesión
│   └── Eliminación: Todos los tokens del usuario
└── getAllUsers(): Lista de usuarios para asignaciones
    ├── Select: Solo campos necesarios (sin password)
    └── Orden: Alfabético por first_name
```

#### **WorkspaceController.php**
```php
Funcionalidades principales:
├── index(): Lista workspaces del usuario creador
│   ├── Filtro: created_by = Auth::id()
│   └── Eager Loading: teams.users, tasks
├── store(): Creación de nuevo workspace
│   ├── Validación: name (required), description (optional)
│   ├── Auto-asignación: created_by = Auth::id()
│   └── Respuesta: success: true
├── show(): Detalles de workspace específico
│   ├── Seguridad: Solo el creador puede ver
│   └── Relaciones: teams, users, tasks completas
├── update(): Actualización de workspace
│   ├── Permisos: Solo el creador
│   └── Campos: name, description
├── destroy(): Eliminación de workspace
│   ├── Permisos: Solo el creador
│   ├── Cascada: Elimina teams y tasks relacionadas
│   └── Respuesta: success: true
└── getTasks(): Tareas del workspace
    ├── Permisos: Solo el creador
    └── Relaciones: assignedUser, creator
```

#### **TeamController.php**
```php
Funcionalidades principales:
├── index(): Equipos donde participa el usuario
│   ├── Query: whereHas users con Auth::id()
│   └── Relaciones: workspace, users completas
├── store(): Creación de equipo
│   ├── Validación: workspace_id, name
│   ├── Permisos: Solo creador del workspace
│   ├── Auto-líder: Creador se agrega como leader
│   └── Manejo errores: try-catch completo
├── show(): Detalles del equipo
│   ├── Permisos: Solo miembros del equipo
│   └── Relaciones: workspace, users
├── update(): Actualización del equipo
│   ├── Permisos: Solo líderes
│   └── Campos: name
├── destroy(): Eliminación del equipo
│   ├── Permisos: Solo líderes
│   └── Cascada: Elimina relaciones team_user
├── addMember(): Agregar miembro
│   ├── Validación: user_id, role (member/leader)
│   ├── Permisos: Solo líderes
│   └── Método: syncWithoutDetaching()
├── removeMember(): Quitar miembro
│   ├── Permisos: Solo líderes
│   └── Método: detach()
├── getMembers(): Lista miembros del equipo
│   ├── Permisos: Solo miembros
│   ├── Select: user info + role from pivot
│   └── Join: Tabla pivot team_user
└── getTasks(): Tareas del equipo
    ├── Permisos: Solo miembros
    ├── Filtro: workspace_id del equipo
    └── Relaciones: assignedUser, creator
```

#### **TaskController.php**
```php
Funcionalidades principales:
├── index(): Tareas asignadas al usuario
│   ├── Filtro: assigned_to = Auth::id()
│   └── Relaciones: workspace, assignedUser, creator
├── store(): Creación de tarea
│   ├── Validación: title, description, workspace_id, team_id, assigned_to
│   ├── Permisos: Solo líderes del equipo
│   ├── Defaults: progress = 0, is_done = false
│   └── Auto-asignación: created_by = Auth::id()
├── show(): Detalles de tarea
│   ├── Permisos: assigned_to OR creator
│   └── Relaciones: workspace, assignedUser, creator
├── update(): Actualización de tarea
│   ├── Líderes: Pueden editar todo (title, description, assigned_to, progress, is_done)
│   ├── Miembros: Solo progress e is_done
│   ├── Validación: Diferente según rol
│   └── Respuesta: success: true
└── destroy(): Eliminación de tarea
    ├── Permisos: Solo líderes del equipo
    ├── Verificación: workspace → teams → users → role = leader
    └── Respuesta: success: true
```

### **Carpeta de Modelos** (`app/Models/`)

Los modelos definen las relaciones y comportamientos de las entidades:

#### **User.php**
```php
Características principales:
├── Autenticación: Implements Authenticatable
├── API Tokens: HasApiTokens (Sanctum)
├── Timestamps: created_at, updated_at automáticos
├── Hidden: password, remember_token (seguridad)
├── Casts: email_verified_at → datetime, password → hashed
├── Fillable: first_name, last_name, phone, email, password
└── Relaciones:
    ├── teams(): belongsToMany(Team) → Pivot con role
    ├── assignedTasks(): hasMany(Task, 'assigned_to')
    └── createdTasks(): hasMany(Task, 'created_by')
```

#### **Workspace.php**
```php
Características principales:
├── Fillable: name, description, created_by
├── Timestamps: created_at, updated_at automáticos
└── Relaciones:
    ├── creator(): belongsTo(User, 'created_by')
    ├── teams(): hasMany(Team) → Cascada
    └── tasks(): hasMany(Task) → Cascada
```

#### **Team.php**
```php
Características principales:
├── Fillable: name, workspace_id
├── Timestamps: created_at, updated_at automáticos
└── Relaciones:
    ├── workspace(): belongsTo(Workspace)
    └── users(): belongsToMany(User) → Pivot con role
        ├── withPivot(['role'])
        └── withTimestamps()
```

#### **Task.php**
```php
Características principales:
├── Fillable: title, description, progress, is_done, workspace_id, assigned_to, created_by
├── Casts: is_done → boolean, progress → integer
├── Timestamps: created_at, updated_at automáticos
└── Relaciones:
    ├── workspace(): belongsTo(Workspace)
    ├── assignedUser(): belongsTo(User, 'assigned_to')
    └── creator(): belongsTo(User, 'created_by')
```

###  **Flujo de Datos Entre Componentes**

```
1. Migración → Crea tabla en BD
2. Modelo → Define relaciones y reglas
3. Controlador → Procesa petición HTTP
4. Middleware → Valida autenticación
5. Validación → Verifica datos entrada
6. Eloquent → Ejecuta query en BD
7. Respuesta → JSON con success/data
```

##  Rutas Completas de la API

### Autenticación
```
POST /api/register     - Registro de usuarios
POST /api/login        - Inicio de sesión  
POST /api/logout       - Cierre de sesión
GET  /api/user         - Obtiene información del usuario autenticado
GET  /api/users        - Lista todos los usuarios registrados
```

### Workspaces (Espacios de Trabajo)
```
GET    /api/workspaces           - Lista workspaces del usuario
POST   /api/workspaces           - Crear nuevo workspace
GET    /api/workspaces/{id}      - Ver workspace específico
PUT    /api/workspaces/{id}      - Actualizar workspace
DELETE /api/workspaces/{id}      - Eliminar workspace
GET    /api/workspaces/{id}/tasks - Ver tareas del workspace
```

### Teams (Equipos)
```
GET    /api/teams                         - Lista equipos del usuario
POST   /api/teams                         - Crear nuevo equipo
GET    /api/teams/{id}                    - Ver equipo específico
PUT    /api/teams/{id}                    - Actualizar equipo
DELETE /api/teams/{id}                    - Eliminar equipo
GET    /api/teams/{id}/members            - Ver miembros del equipo
GET    /api/teams/{id}/tasks              - Ver tareas del equipo
POST   /api/teams/{id}/add-member         - Agregar miembro
DELETE /api/teams/{id}/remove-member/{userId} - Quitar miembro
```

### Tasks (Tareas)
```
GET    /api/tasks       - Lista tareas asignadas al usuario
POST   /api/tasks       - Crear nueva tarea (solo líderes)
GET    /api/tasks/{id}  - Ver tarea específica
PUT    /api/tasks/{id}  - Actualizar tarea
DELETE /api/tasks/{id}  - Eliminar tarea (solo líderes)
```

##  Estructura de Controladores

### 1. **AuthController**
```php
// Gestión de autenticación
POST /api/register     - Registro de usuarios
POST /api/login        - Inicio de sesión
POST /api/logout       - Cierre de sesión
GET  /api/users        - Lista todos los usuarios
```

### 2. **WorkspaceController**
```php
// Gestión de espacios de trabajo
GET    /api/workspaces           - Lista workspaces del usuario
POST   /api/workspaces           - Crear nuevo workspace
GET    /api/workspaces/{id}      - Ver workspace específico
PUT    /api/workspaces/{id}      - Actualizar workspace
DELETE /api/workspaces/{id}      - Eliminar workspace
GET    /api/workspaces/{id}/tasks - Ver tareas del workspace
```

### 3. **TeamController**
```php
// Gestión de equipos
GET    /api/teams                    - Lista equipos del usuario
POST   /api/teams                    - Crear nuevo equipo
GET    /api/teams/{id}               - Ver equipo específico
PUT    /api/teams/{id}               - Actualizar equipo
DELETE /api/teams/{id}               - Eliminar equipo
GET    /api/teams/{id}/members       - Ver miembros del equipo
GET    /api/teams/{id}/tasks         - Ver tareas del equipo
POST   /api/teams/{id}/add-member    - Agregar miembro
DELETE /api/teams/{id}/remove-member/{userId} - Quitar miembro
```

### 4. **TaskController**
```php
// Gestión de tareas
GET    /api/tasks       - Lista tareas asignadas al usuario
POST   /api/tasks       - Crear nueva tarea (solo líderes)
GET    /api/tasks/{id}  - Ver tarea específica
PUT    /api/tasks/{id}  - Actualizar tarea
DELETE /api/tasks/{id}  - Eliminar tarea (solo líderes)
```

##  Sistema de Permisos

### Roles de Usuario

#### **Creador de Workspace**
- Crear, editar y eliminar el workspace
- Ver todas las tareas del workspace
- Crear equipos dentro del workspace

#### **Líder de Equipo**
- Gestionar miembros del equipo
- Crear, editar y eliminar tareas
- Ver todas las tareas del equipo
- Actualizar información del equipo

#### **Miembro de Equipo**
- Ver tareas asignadas
- Actualizar progreso de sus tareas
- Ver información del equipo
- Ver miembros del equipo

## 📊 Estructura de Base de Datos

### Tablas Principales

#### **users**
```sql
id, first_name, last_name, email, phone, password, created_at, updated_at
```

#### **workspaces**
```sql
id, name, description, created_by, created_at, updated_at
```

#### **teams**
```sql
id, name, workspace_id, created_at, updated_at
```

#### **team_user** (Tabla Pivot)
```sql
id, team_id, user_id, role
```

#### **tasks**
```sql
id, title, description, progress, is_done, workspace_id, assigned_to, created_by, created_at, updated_at
```

##  Flujo de Trabajo Típico

### 1. **Para Administradores/Líderes:**
1. Registrarse/Iniciar sesión
2. Crear workspace
3. Crear equipos en el workspace
4. Agregar miembros a los equipos
5. Crear tareas y asignarlas a miembros
6. Monitorear progreso

### 2. **Para Miembros:**
1. Registrarse/Iniciar sesión
2. Ver equipos donde participa
3. Ver tareas asignadas
4. Actualizar progreso de tareas
5. Marcar tareas como completadas

## 📝 Formato de Respuestas API

### Respuestas Exitosas
```json
// Operaciones de modificación
{
  "success": true
}

// Consultas de datos
{
  "id": 1,
  "name": "Datos solicitados",
  ...
}
```

### Respuestas de Error
```json
{
  "success": false,
  "error": "Mensaje descriptivo del error"
}
```

##  Instalación y Configuración

### Prerrequisitos
- PHP 8.2+
- Composer
- MySQL/PostgreSQL
- Node.js (para assets)

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone [URL_REPOSITORIO]
cd laraApp
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar ambiente**
```bash
cp .env.example .env
# Editar .env con configuraciones de base de datos
```

4. **Generar clave de aplicación**
```bash
php artisan key:generate
```

5. **Ejecutar migraciones**
```bash
php artisan migrate
```

6. **Iniciar servidor**
```bash
php artisan serve
```

##  Testing

### Rutas de Prueba
- **Base URL:** `http://localhost:8000/api`
- **Autenticación:** Bearer Token (obtener via login)
- **Content-Type:** `application/json`

### Ejemplo de Uso
```javascript
// Iniciar sesión
POST /api/login
{
  "email": "usuario@email.com",
  "password": "password123"
}

// Usar token en headers
headers: {
  "Authorization": "Bearer TOKEN_AQUI",
  "Content-Type": "application/json"
}
```

##  Características de Seguridad

- **Validación de entrada** en todos los endpoints
- **Autenticación requerida** para todas las operaciones
- **Control de permisos** basado en roles
- **Sanitización de datos** automática
- **Tokens de acceso** con Laravel Sanctum
- **Protección CSRF** incluida

