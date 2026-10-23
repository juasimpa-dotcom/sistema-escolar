# Sistema Escolar Multi-Unidad

Documentación técnica y funcional del sistema implementado en este repositorio.

> **Estado**: implementación completa y verificada (backend + frontend).
> **Fecha de referencia**: 22 de octubre de 2026.
> **Alcance de esta versión**: 6 roles, 15 módulos funcionales, 20 tablas de dominio, 65 pruebas automatizadas.

---

## 1. Resumen ejecutivo

El **Sistema Escolar Multi-Unidad** es una aplicación web para administrar establecimientos educativos desde una sola instalación. Permite que varias unidades educativas convivan en la misma base de datos con **aislamiento lógico por unidad**, y ofrece módulos para:

- autenticación con cambio obligatorio de contraseña;
- gestión de unidades, personas, estudiantes y tutores;
- estructura académica (gestiones, aulas, materias, profesor-aula-materia);
- inscripciones y retiros;
- horarios de aula;
- actividades y notas con corrección auditada;
- asistencia diaria con integración de licencias;
- licencias solicitadas por padres y aprobadas por dirección;
- citaciones y comunicados internos;
- panel de indicadores con caché;
- portal de familia y estudiante;
- notificaciones internas.

La arquitectura es **API REST + SPA**: Laravel expone `/api/v1`, React consume la API con token Bearer y renderiza vistas por rol.

### Roles

| Rol | Código | Alcance |
|---|---|---|
| Superadministrador | `superadmin` | Todas las unidades; crea y da de baja directores. Contexto global. |
| Director | `director` | Su unidad; máxima autoridad académica y de aprobación. |
| Administrativo | `administrativo` | Su unidad; apoyo en gestión académica y personas. |
| Profesor | `profesor` | Sus materias-aula; notas, asistencia y actividades. |
| Padre/Tutor | `padre` | Sus hijos; licencias, citaciones, portal. |
| Estudiante | `estudiante` | Su propia información y portal. |

---

## 2. Arquitectura general

```
┌──────────────────────────────┐
│  SPA React (Vite)            │
│  Router + Query + Contexts   │
│  Token en localStorage       │
└──────────────┬───────────────┘
               │ HTTP JSON /api/v1 (Bearer)
┌──────────────▼───────────────┐
│  Laravel 12                  │
│  routes/api.php              │
│  auth:sanctum → tenant → role │
│  Controllers → Services      │
│  Policies · Requests         │
│  Models (Eloquent)           │
└──────────────┬───────────────┘
               │ SQL
┌──────────────▼───────────────┐
│  PostgreSQL                  │
│  (SQLite en pruebas)         │
└──────────────────────────────┘
```

### Flujo de una petición

1. El cliente envía `Authorization: Bearer <token>`.
2. `auth:sanctum` resuelve al usuario autenticado.
3. `SetTenant` publica el contexto de unidad en `TenantContext`:
   - usuario normal → su `unidad_educativa_id`;
   - superadmin → contexto `null` (visión global).
4. `EnsureRole` valida el rol cuando la ruta lo exige.
5. El controlador autoriza con la policy correspondiente y delega reglas de negocio a un `Service`.
6. El servicio escribe en base de datos dentro de transacciones cuando hay varias tablas involucradas.
7. Los `Observer` invalidan caché derivada (por ejemplo, el resumen del dashboard).
8. La respuesta JSON se devuelve con Eloquent Resources o arrays tipados.

### Decisiones de diseño

- **Aislamiento por tenant con trait**: los modelos de dominio usan `BelongsToUnidadEducativa`, que agrega un global scope por `unidad_educativa_id` directo o por relación (`whereHas`). El superadmin opera sin filtro.
- **Autorización en policies, no en controladores**: cada recurso tiene su policy (12 en total). Las reglas de “quién puede hacer qué” viven en un solo lugar.
- **Reglas de negocio en servicios**: `LicenciaService`, `NotaService`, `AsistenciaService`, `AcademicoService`, `CitacionService`, `DashboardService`, `PortalService`, entre otros. Los controladores quedan delgados.
- **Auditoría explícita**: las correcciones de notas y asistencias generan filas en `nota_historial` y `asistencia_historial`.
- **Caché con versión**: el dashboard usa una clave versionada; cualquier mutación relevante incrementa la versión global y las claves anteriores expiran por TTL.

---

## 3. Stack tecnológico

### Backend

| Componente | Versión / detalle |
|---|---|
| PHP | `^8.2` |
| Laravel | `^12.0` |
| Autenticación | Laravel Sanctum `^4.0` (tokens personales) |
| Base de datos | PostgreSQL (desarrollo y producción); SQLite `:memory:` en pruebas |
| Pruebas | PHPUnit `^11.5.50` |
| Estilo | Laravel Pint |
| Utilidades | Pail, Sail, Collision, Faker, Mockery, Tinker |

### Frontend

| Componente | Versión / detalle |
|---|---|
| React | `^19.3.1` |
| TypeScript | `~7.0.2` |
| Vite | `^8.3.4` |
| Tailwind CSS | `^4.3.3` (plugin de Vite) |
| React Router | `^7.18.4` |
| TanStack Query | `^5.104.5` |
| Axios | `^1.20.0` |
| Recharts | `^3.10.1` |
| Base UI | `^1.9.0` |
| Sonner | `^2.0.8` |
| Lucide React | `^1.59.0` |
| Tipografía | Geist Variable e Inter Variable (`@fontsource-variable`) |
| Lint | Oxlint `^1.88.0` |
| Build | `tsc -b && vite build` |

> No se usan `react-hook-form` ni `zod`: la validación de formularios es propia (`src/lib/validacion.ts` + `useFormValidacion`).

---

## 4. Backend en detalle

### 4.1 Organización

```
backend/
├── app/
│   ├── Enums/                 # 13 enums de dominio
│   ├── Http/
│   │   ├── Controllers/Api/V1/
│   │   │   ├── Administrativo/    # aulas, horarios, inscripciones, materias
│   │   │   ├── Auth/              # login, me, logout, cambio de contraseña
│   │   │   ├── Director/          # dashboard
│   │   │   ├── Familia/           # portal, licencias, citaciones, notificaciones
│   │   │   ├── Profesor/          # notas, asistencias
│   │   │   └── Superadmin/        # unidades educativas
│   │   ├── Middleware/            # SetTenant, EnsureRole
│   │   └── Requests/              # validación de entrada
│   ├── Models/                    # 16 modelos + Concerns
│   ├── Notifications/             # LicenciaResuelta, CitacionRecibida
│   ├── Observers/                 # DashboardCacheObserver
│   ├── Policies/                  # 12 policies
│   ├── Providers/
│   ├── Services/                  # 11 servicios de negocio
│   └── Support/TenantContext.php
├── bootstrap/app.php              # middleware, aliases, rutas
├── database/migrations/           # 21 migraciones
├── database/seeders/              # SuperadminSeeder, DemoSeeder
├── routes/api.php                 # catálogo API v1
└── tests/                         # Feature + Unit
```

### 4.2 Multi-tenant

`TenantContext` es un contenedor estático con la unidad activa y una bandera de superadmin.

- `SetTenant` se ejecuta después de `auth:sanctum`.
- Si el usuario es `superadmin`, el contexto queda en `null` y los global scopes no filtran.
- Si es un usuario normal sin unidad (caso no esperado), se rechaza la petición.
- El trait `BelongsToUnidadEducativa`:
  - filtra por columna directa si la tabla tiene `unidad_educativa_id`;
  - si no la tiene, usa `whereHas` sobre la relación declarada;
  - expone `getUnidadEducativaIdAttribute()` para calcularla desde la relación.

Esto permite que un mismo controlador funcione para superadmin (global) y para usuarios de una unidad (filtrado), sin duplicar consultas.

### 4.3 Autenticación

- `POST /api/v1/auth/login` recibe `ci` y `password`.
- Se valida estado activo y se emite un token Sanctum.
- `debe_cambiar_password` obliga al frontend a redirigir al cambio de contraseña.
- `POST /auth/cambiar-contrasena` actualiza la contraseña y limpia la bandera.
- `POST /auth/logout` revoca el token actual.
- `GET /auth/me` devuelve el perfil y el rol.

### 4.4 Autorización

- 12 policies: `Actividad`, `Asistencia`, `Aula`, `Citacion`, `Estudiante`, `Gestion`, `Inscripcion`, `Licencia`, `Materia`, `Nota`, `UnidadEducativa`, `User`.
- Regla transversal: el superadmin puede todo; los demás roles quedan acotados a su unidad y, cuando aplica, a sus propios recursos (hijos, materias asignadas, citaciones emitidas/recibidas).

### 4.5 Validación y errores

- `FormRequest` para entradas complejas; validadores puntuales en controladores.
- Errores de negocio con `ValidationException` → HTTP 422 con `errors`.
- No autorizado → 403; no autenticado → 401.
- El frontend normaliza mensajes en `src/lib/errors.ts`.

### 4.6 Caché y rendimiento

- `DashboardService` cachea 60 s por unidad + filtros + umbral.
- Clave: `dashboard:resumen:<md5(contexto)>`, con `v` = versión global.
- `DashboardCacheObserver` invalida al crear/actualizar/eliminar asistencias, licencias y notas.
- Índices agregados en la migración `2026_10_21_000001_add_performance_indexes.php` para filtros frecuentes.
- Regla de consulta: todo `select()` debe incluir `id` y las claves foráneas usadas por `with()`/`withCount()`.

### 4.7 Notificaciones

- Notificaciones de base de datos (`notifications`).
- `LicenciaResuelta` al padre cuando dirección aprueba o rechaza.
- `CitacionRecibida` al destinatario al crear una citación.
- Endpoints para listar, marcar una o todas como leídas.

---

## 5. Frontend en detalle

### 5.1 Organización

```
frontend/src/
├── api/            # axios + módulos por dominio
├── components/
│   ├── layout/     # AppLayout, sidebar, header
│   └── ui/         # sistema de componentes (Base UI + Tailwind)
├── contexts/       # AuthContext, ThemeContext
├── hooks/          # useFormValidacion, use-mobile
├── lib/            # validacion, errors, utils
├── pages/          # por rol (superadmin, director, administrativo, profesor, padre, estudiante)
├── types/          # tipos por dominio
├── App.tsx         # rutas + guards
└── main.tsx        # QueryClientProvider + ThemeProvider
```

### 5.2 Rutas y guards

- `ProtectedRoute` exige sesión.
- `RoleGuard` restringe por rol.
- `App.tsx` declara rutas anidadas por rol; `AppLayout` calcula la navegación según el rol.
- `HomeRedirect` decide el destino inicial:
  - si `debe_cambiar_password` → cambio de contraseña;
  - si no → dashboard o portal correspondiente.

### 5.3 Cliente HTTP

`src/api/axios.ts`:

- `baseURL = VITE_API_URL` (por defecto `http://localhost:8000/api/v1`).
- Adjunta `Authorization: Bearer <token>` desde `localStorage`.
- **Aplana la paginación** de Laravel: de `{ data, links, meta }` a `{ data, total, ... }` para simplificar las tablas.
- Ante 401 limpia el token y redirige al login.
- `withCredentials: true` habilitado.

### 5.4 Estado y datos

- `AuthContext`: usuario, token, login, logout, cambio de contraseña.
- `ThemeContext`: tema claro/oscuro persistido (usa `next-themes`).
- TanStack Query: `staleTime` por consulta; mutaciones invalidan las claves afectadas.
- Formularios: `useFormValidacion` con reglas declarativas y mensajes en español.

### 5.5 Interfaz

- Layout responsive con sidebar colapsable.
- Componentes propios sobre Base UI (diálogos, selects, tabs, tooltips) y Tailwind v4.
- Notificaciones toast con Sonner.
- Gráficos del dashboard con Recharts.
- Modo oscuro completo.

---

## 6. Modelo de datos

20 tablas de dominio (más tablas de infraestructura de Laravel).

### 6.1 Identidad y estructura

| Tabla | Propósito | Campos clave |
|---|---|---|
| `unidades_educativas` | Establecimientos | `nombre`, `codigo` único, `estado`, baja lógica |
| `users` | Todas las personas con acceso | `rol`, `ci` único, `usuario` único, `email`, `password`, `debe_cambiar_password`, `unidad_educativa_id` |
| `estudiantes` | Perfil académico del estudiante | `user_id` único, `unidad_educativa_id`, `fecha_nacimiento`, `estado` |
| `padre_estudiante` | Vínculo tutor–estudiante | `padre_id`, `estudiante_id`, `parentesco`; único por par |
| `gestiones` | Año escolar | `anio`, `es_actual`, `estado`; único por unidad+año |
| `aulas` | Curso/grado/paralelo | `gestion_id`, `grado`, `paralelo`, `turno`, `estado`; único por gestión+grado+paralelo+turno |
| `materias` | Materias de la unidad | `nombre`, `estado`; único por unidad+nombre |
| `aula_materia` | Asignación materia–aula–profesor | `aula_id`, `materia_id`, `profesor_id`; único aula+materia |
| `inscripciones` | Matrícula del estudiante en un aula | `estudiante_id`, `aula_id`, `gestion_id`, `estado`; único estudiante+gestión |
| `horarios` | Bloque semanal de una materia-aula | `aula_materia_id`, `dia`, `hora_inicio`, `hora_fin` |

### 6.2 Evaluación y asistencia

| Tabla | Propósito | Campos clave |
|---|---|---|
| `actividades` | Tarea/evaluación de una materia-aula | `titulo`, `fecha_cumplimiento`, `fecha_limite_notas`, `puntaje_maximo` |
| `notas` | Nota de un estudiante en una actividad | `nota`, `fue_modificada`, `registrado_por`, `modificado_por`; único actividad+estudiante |
| `nota_historial` | Auditoría de correcciones | `nota_anterior`, `nota_nueva`, `observacion`, `usuario_id` |
| `asistencias` | Asistencia diaria por materia-aula | `fecha`, `estado` (`presente`, `falta`, `licencia`, `atraso`), `registrado_por`; único materia-aula+estudiante+fecha |
| `asistencia_historial` | Auditoría de cambios | `estado_anterior`, `estado_nuevo`, `usuario_id` |
| `licencias` | Solicitud de ausencia justificada | `fecha_inicio`, `fecha_fin`, `motivo`, `estado`, `resuelto_por`, `motivo_rechazo` |

### 6.3 Comunicación

| Tabla | Propósito | Campos clave |
|---|---|---|
| `citaciones` | Citación o comunicado interno | `emisor_id`, `destinatario_id`, `estudiante_id`, `tipo`, `asunto`, `fecha_hora`, `estado` (`enviada`, `vista`) |
| `notifications` | Notificaciones internas de Laravel | `notifiable`, `data`, `read_at` |

### 6.4 Diagramas entidad-relación

- [ER de identidad y estructura](diagramas/er-identidad.html) — unidades, usuarios, estudiantes, tutores, gestiones, aulas, materias, inscripciones.
- [ER académico y de comunicación](diagramas/er-academico.html) — actividades, notas, asistencias, licencias, citaciones, historiales.

> Los ER son **parciales por legibilidad**: muestran las relaciones principales y omiten algunas claves foráneas secundarias. El diccionario completo está en las tablas de arriba.

---

## 7. Módulos implementados

### 7.1 Autenticación y sesión

- Login por CI y contraseña.
- Token Sanctum.
- Cambio obligatorio de contraseña en el primer acceso.
- Logout con revocación.
- Perfil (`/auth/me`).

### 7.2 Unidades educativas (superadmin)

- CRUD de unidades.
- Baja lógica con fecha y motivo.
- Alta de directores por unidad.

### 7.3 Personas

- CRUD de profesores, administrativos y padres.
- Baja lógica.
- Validación de CI/usuario/email únicos.
- Contraseña inicial = CI y `debe_cambiar_password = true`.

### 7.4 Estudiantes y tutores

- CRUD de estudiantes (usuario + perfil académico).
- Vínculo padre–estudiante con parentesco (`madre`, `padre`, `tutor`, `otro`).
- Baja lógica solo para director.
- `GET /mis-hijos` para el portal de familia.

### 7.5 Gestión académica

- Gestiones por año, con `es_actual`.
- Aulas por gestión, grado, paralelo y turno.
- Materias por unidad.
- Asignación de materia + profesor a un aula.
- Horario semanal por materia-aula.

### 7.6 Inscripciones

- Inscripción de un estudiante en un aula (la gestión se deriva del aula).
- Validaciones: misma unidad y no duplicado en la gestión.
- Retiro con motivo y fecha.
- La inscripción es el eje del portal y de las planillas.

### 7.7 Actividades y notas

- Actividades por materia-aula, con puntaje máximo y fechas.
- Planilla de notas por actividad (estudiantes inscritos).
- Registro individual o guardado masivo de planilla.
- **Corrección única**: una nota puede corregirse una sola vez, con observación obligatoria.
- Historial de cada corrección.
- Validación de rango `0..puntaje_maximo`.
- Solo el profesor titular registra/corrige; superadmin puede.

### 7.8 Asistencia

- Toma diaria masiva por materia-aula.
- Estados: presente, falta, licencia, atraso.
- No se permite fecha futura.
- Si existe licencia aprobada para la fecha, el estado se fuerza a `licencia`.
- El profesor no puede quitar el estado `licencia`.
- Modificación puntual con auditoría.
- Historial por asistencia.

### 7.9 Licencias

- El padre solicita para sus hijos.
- Validación `fecha_fin >= fecha_inicio`.
- Prohibido solapar con otra licencia `pendiente` del mismo estudiante.
- Director/superadmin aprueba o rechaza (solo si está pendiente, con motivo en el rechazo).
- Notificación al padre al resolverse.
- El padre puede anular su solicitud mientras siga pendiente.

### 7.10 Citaciones y comunicados

- Tipos: citación a padre, comunicado a profesor, citación extraordinaria a profesor.
- Valida que el destinatario corresponda al tipo y a la unidad.
- Para tipo padre, valida que el destinatario sea tutor del estudiante.
- Notificación al destinatario.
- El destinatario marca como vista.
- El emisor puede anular si aún no fue vista.

### 7.11 Dashboard

Indicadores por unidad (o global para superadmin):

- total de estudiantes inscritos;
- porcentaje de presentes (presente + atraso);
- total de registros de asistencia;
- estudiantes con faltas;
- estudiantes con licencia;
- estudiantes con promedio bajo el umbral (por defecto 51);
- distribución de asistencia por estado.

Filtros: `fecha`, `fecha_desde`, `fecha_hasta`, `aula_id`, `umbral`. Caché de 60 s.

### 7.12 Portal de familia y estudiante

`GET /portal/mi-perfil` y `GET /portal/hijo/{estudiante}` devuelven:

- datos del estudiante y su aula actual;
- materias con profesor, actividades, notas y promedio;
- actividades pendientes (sin nota);
- resumen y detalle de asistencia;
- citaciones;
- licencias.

El controlador valida acceso con `EstudiantePolicy`; el servicio solo arma la vista.

### 7.13 Notificaciones

- Listado paginado.
- Marcar una como leída.
- Marcar todas como leídas.
- Campana con contador en el layout.

### 7.14 Usuarios y bajas

- Superadmin: crea/actualiza/da de baja directores.
- Director/administrativo: gestionan profesores, administrativos, padres y estudiantes de su unidad.
- Baja lógica con fecha y motivo; no se borran físicamente usuarios con historial.

### 7.15 Reportes y consultas

- Planillas de notas y asistencias.
- Historiales de corrección.
- Dashboard agregado.
- Portal con promedios y pendientes.

---

## 8. Matriz de permisos

Resumen de las reglas implementadas en policies. `—` = sin acceso; `U` = solo su unidad; `P` = solo sus propios recursos (hijos, materias, citaciones).

| Recurso | Superadmin | Director | Administrativo | Profesor | Padre | Estudiante |
|---|---|---|---|---|---|---|
| Unidades educativas | Total | Ver su unidad | Ver su unidad | — | — | — |
| Personas (usuarios) | Directores | U | U | — | — | — |
| Estudiantes | Total | U (alta/edición/baja) | U (alta/edición) | U (ver) | P (ver) | P (ver) |
| Gestiones | Total | U (CRUD) | U (CRUD) | U (ver) | — | — |
| Aulas | Total | U (CRUD) | U (CRUD) | U (ver) | — | — |
| Materias | Total | U (CRUD) | U (CRUD) | U (ver) | — | — |
| Inscripciones | Total | U (crear/retirar) | U (crear/retirar) | U (ver) | — | — |
| Actividades | Total | U (CRUD) | U (CRUD) | P (ver/crear/editar) | — | — |
| Notas | Total | U (ver) | U (ver) | P (registrar/corregir) | P (ver) | P (ver) |
| Asistencias | Total | U (ver/modificar) | U (ver/modificar) | P (registrar/modificar) | — | — |
| Licencias | Total | U (resolver) | U (ver) | — | P (crear/ver/anular) | — |
| Citaciones | Total | U (crear/ver/anular) | U (crear/ver/anular) | P (ver) | P (ver) | — |
| Dashboard | Global | U | U | — | — | — |
| Portal | — | — | — | — | P | P |

> El detalle exacto por acción está en `backend/app/Policies`.

---

## 9. Reglas de negocio destacadas

1. **Una sola corrección de nota**: `NotaService::modificar` rechaza una segunda corrección; exige observación y guarda historial.
2. **Rango de nota**: `0 ≤ nota ≤ puntaje_maximo` de la actividad.
3. **Estudiante inscrito**: no se registra nota ni asistencia para estudiantes fuera del aula/gestión.
4. **Asistencia futura prohibida**.
5. **Licencia aprobada manda**: en la toma de asistencia, el estado se fuerza a `licencia`; el profesor no puede revertirlo.
6. **Sin solape de licencias pendientes** para el mismo estudiante.
7. **Resolución única de licencia**: solo desde `pendiente`; el rechazo exige motivo.
8. **Inscripción única por gestión**: un estudiante no puede estar inscrito dos veces en la misma gestión.
9. **Profesor y materia de la unidad**: al asignar materia a aula se valida rol de profesor, unidad y pertenencia de la materia.
10. **Citación coherente**: tipo padre → destinatario tutor del estudiante; tipo profesor → destinatario profesor de la misma unidad.
11. **Aislamiento multi-unidad**: ningún usuario normal puede leer o escribir datos de otra unidad.
12. **Baja lógica**: usuarios, unidades, estudiantes, gestiones, aulas, materias, inscripciones y actividades usan `softDeletes`.

---

## 10. Catálogo de la API

Base: `/api/v1`. Todas las rutas salvo `login` requieren `Authorization: Bearer <token>`.

### Autenticación

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/auth/login` | Inicia sesión (`ci`, `password`) |
| GET | `/auth/me` | Perfil autenticado |
| POST | `/auth/logout` | Revoca el token actual |
| POST | `/auth/cambiar-contrasena` | Cambia contraseña y limpia la bandera |

### Unidades educativas

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/unidades` | Lista |
| POST | `/unidades` | Crea |
| GET | `/unidades/{unidad}` | Detalle |
| PUT | `/unidades/{unidad}` | Actualiza |
| POST | `/unidades/{unidad}/baja` | Baja lógica |

### Personas

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/personas` | Lista (filtros por rol/estado) |
| POST | `/personas` | Crea profesor/administrativo/padre |
| GET | `/personas/{persona}` | Detalle |
| PUT | `/personas/{persona}` | Actualiza |
| POST | `/personas/{persona}/baja` | Baja lógica |

### Estudiantes

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/mis-hijos` | Hijos del padre autenticado |
| GET | `/estudiantes` | Lista |
| POST | `/estudiantes` | Crea usuario + perfil |
| GET | `/estudiantes/{estudiante}` | Detalle |
| PUT | `/estudiantes/{estudiante}` | Actualiza |
| POST | `/estudiantes/{estudiante}/baja` | Baja lógica |

### Gestión académica

| Método | Ruta | Descripción |
|---|---|---|
| GET/POST | `/gestiones` | Lista / crea |
| GET/PUT/DELETE | `/gestiones/{gestion}` | Detalle / actualiza / elimina |
| GET/POST | `/materias` | Lista / crea |
| GET/PUT/DELETE | `/materias/{materia}` | Detalle / actualiza / elimina |
| GET/POST | `/aulas` | Lista / crea |
| GET/PUT/DELETE | `/aulas/{aula}` | Detalle / actualiza / elimina |
| GET/POST | `/aulas/{aula}/materias` | Materias asignadas / asigna |
| DELETE | `/aulas/{aula}/materias/{aulaMateria}` | Quita asignación |
| GET | `/aulas/{aula}/horario` | Horario del aula |
| POST | `/aulas/{aula}/horario` | Crea bloque |
| PUT/DELETE | `/horarios/{horario}` | Actualiza / elimina bloque |

### Inscripciones

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/aulas/{aula}/inscripciones` | Lista inscripciones del aula |
| POST | `/aulas/{aula}/inscripciones` | Inscribe estudiante |
| POST | `/inscripciones/{inscripcion}/retirar` | Retira con motivo/fecha |

### Actividades y notas

| Método | Ruta | Descripción |
|---|---|---|
| GET/POST | `/materias-aulas/{aulaMateria}/actividades` | Lista / crea actividad |
| GET/PUT/DELETE | `/actividades/{actividad}` | Detalle / actualiza / elimina |
| GET | `/profesor/actividades` | Actividades del profesor autenticado |
| GET | `/actividades/{actividad}/planilla` | Planilla (inscritos + notas) |
| POST | `/actividades/{actividad}/notas` | Registra una nota |
| POST | `/actividades/{actividad}/planilla` | Guarda planilla completa |
| PUT | `/notas/{nota}` | Corrige (una vez, con observación) |
| GET | `/notas/{nota}/historial` | Historial de correcciones |

### Asistencias

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/profesor/materias-aulas` | Asignaciones del profesor |
| GET | `/materias-aulas/{aulaMateria}/asistencias/planilla` | Planilla por fecha |
| POST | `/materias-aulas/{aulaMateria}/asistencias/toma` | Toma masiva |
| GET | `/materias-aulas/{aulaMateria}/asistencias` | Historial |
| PUT | `/asistencias/{asistencia}` | Modifica con auditoría |
| GET | `/asistencias/{asistencia}/historial` | Historial |

### Licencias

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/licencias` | Lista según rol |
| POST | `/licencias` | Padre solicita |
| POST | `/licencias/{licencia}/aprobar` | Director/superadmin aprueba |
| POST | `/licencias/{licencia}/rechazar` | Director/superadmin rechaza con motivo |
| DELETE | `/licencias/{licencia}` | Padre anula si está pendiente |

### Citaciones y notificaciones

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/citaciones` | Bandeja según rol |
| POST | `/citaciones` | Crea citación/comunicado |
| GET | `/citaciones/destinatarios` | Candidatos válidos |
| GET | `/citaciones/tipos` | Tipos disponibles |
| GET | `/citaciones/{citacion}` | Detalle |
| POST | `/citaciones/{citacion}/marcar-vista` | Destinatario marca vista |
| DELETE | `/citaciones/{citacion}` | Emisor anula si no fue vista |
| GET | `/notificaciones` | Lista |
| POST | `/notificaciones/{notificacion}/marcar-leida` | Marca una |
| POST | `/notificaciones/marcar-todas-leidas` | Marca todas |

### Dashboard y portal

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/dashboard/resumen` | Indicadores con filtros |
| GET | `/portal/mi-perfil` | Portal del estudiante autenticado |
| GET | `/portal/hijo/{estudiante}` | Portal de un hijo (padre) |

---

## 11. Funcionamiento de punta a punta

### 11.1 Inicio de sesión

1. El usuario ingresa CI y contraseña.
2. La API valida credenciales y estado.
3. Devuelve token + perfil + `debe_cambiar_password`.
4. El frontend guarda el token y redirige al cambio de contraseña o al inicio por rol.
5. Cada petición posterior adjunta el Bearer; un 401 limpia la sesión.

### 11.2 Preparación de un año escolar

1. Superadmin crea la unidad y su director.
2. Director/administrativo crea la gestión y la marca como actual.
3. Se crean aulas, materias y asignaciones materia-aula-profesor.
4. Se cargan estudiantes y se vinculan tutores.
5. Se inscriben estudiantes en aulas.

### 11.3 Ciclo de una evaluación

1. Profesor crea actividad en su materia-aula.
2. Abre la planilla: la API devuelve solo inscritos.
3. Registra notas (individual o masiva).
4. Si se equivoca, corrige **una vez** con observación; queda historial.
5. Padre/estudiante ve la nota y el promedio en el portal.

### 11.4 Asistencia diaria

1. Profesor elige materia-aula y fecha (no futura).
2. La planilla muestra inscritos.
3. Marca estados y guarda.
4. El servidor fuerza `licencia` si hay licencia aprobada.
5. Dirección puede modificar con auditoría.

### 11.5 Licencia

1. Padre selecciona hijo, rango de fechas y motivo.
2. El servidor valida orden de fechas y solape con pendientes.
3. Dirección revisa y aprueba o rechaza (con motivo).
4. Se notifica al padre.
5. La asistencia futura respeta la licencia aprobada.

### 11.6 Citación

1. Dirección/administración crea la citación y elige destinatario.
2. El servidor valida tipo, unidad y vínculo tutor–estudiante.
3. Se registra y se notifica.
4. El destinatario la ve y la marca como vista.
5. El emisor puede anularla mientras no haya sido vista.

### 11.7 Dashboard

1. Dirección abre el panel.
2. El frontend envía filtros.
3. La API calcula o recupera de caché (60 s).
4. Se muestran tarjetas y gráficos.
5. Cualquier mutación relevante invalida la versión de caché.

---

## 12. Instalación y configuración

### 12.1 Requisitos

- PHP 8.2+ con extensiones habituales de Laravel.
- Composer.
- Node.js 20+ y npm.
- PostgreSQL 14+ (o SQLite para una demo rápida).

### 12.2 Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# configurar DB_* en .env
php artisan migrate --seed
php artisan serve
```

`php artisan migrate --seed` ejecuta `DatabaseSeeder`, que llama a `SuperadminSeeder` y `DemoSeeder`.

### 12.3 Frontend

```bash
cd frontend
npm install
# crear .env con VITE_API_URL=http://localhost:8000/api/v1
npm run dev
```

### 12.4 Variables relevantes

| Variable | Uso |
|---|---|
| `APP_URL` | URL del backend |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión a base de datos |
| `FRONTEND_URL` | Origen permitido para CORS/stateful |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Infraestructura Laravel |
| `VITE_API_URL` (frontend) | Base de la API |

### 12.5 Credenciales demo

| Rol | CI | Contraseña |
|---|---|---|
| Superadmin | `10000001` | `10000001` |
| Director | `20000001` | `20000001` |
| Administrativo | `20000002` | `20000002` |
| Profesor (Ana) | `20000003` | `20000003` |
| Profesor (Luis) | `20000004` | `20000004` |
| Padre (Jorge) | `20000005` | `20000005` |
| Estudiante (Lucía) | `20000006` | `20000006` |
| Estudiante (Diego) | `20000007` | `20000007` |

> Todos los usuarios demo tienen `debe_cambiar_password = true`.

---

## 13. Pruebas y calidad

- **Backend**: `php artisan test` → **65 pruebas, 191 aserciones**, todas en verde.
- Cobertura funcional por módulo: autenticación, aislamiento multi-tenant, personas/estudiantes, gestión académica, inscripciones, actividades/notas, asistencias, licencias, citaciones, dashboard y portal.
- Base de pruebas: SQLite en memoria; caché en array.
- **Frontend**: `npm run build` (TypeScript + Vite) y `npm run lint` (Oxlint) sin errores.
- No hay pruebas unitarias de componentes frontend en esta versión.

Comandos:

```bash
cd backend && php artisan test
cd backend && ./vendor/bin/pint --test
cd frontend && npm run lint
cd frontend && npm run build
```

---

## 14. Buenas prácticas aplicadas

1. **Separación de responsabilidades**: controlador delgado, servicio con reglas, policy con autorización, request con validación.
2. **Aislamiento multi-tenant explícito** con contexto y trait, no con filtros dispersos.
3. **Autorización centralizada** en policies; el superadmin tiene una salida controlada.
4. **Transacciones** en operaciones que tocan varias tablas (notas + historial, asistencia + historial).
5. **Auditoría** de cambios sensibles (notas y asistencias).
6. **Soft delete** para conservar historial académico.
7. **Validación en servidor y cliente**, con mensajes en español.
8. **Caché con invalidación por versión** para no bloquear mutaciones.
9. **Índices** en columnas de filtro/orden y claves foráneas consultadas.
10. **Tipado** en frontend (TypeScript) y enums en backend para estados.
11. **Paginación consistente** normalizada en un solo interceptor.
12. **Migraciones reproducibles** y seeders demo.
13. **Pruebas de integración** con base efímera.
14. **Accesibilidad**: componentes con etiquetas, foco visible y modo oscuro.
15. **Documentación de límites**: los diagramas declaran qué relaciones omiten.

---

## 15. Estructura del repositorio

```
sistema-escolar/
├── backend/                  # API Laravel 12
│   ├── app/
│   ├── database/
│   ├── routes/api.php
│   └── tests/
├── frontend/                 # SPA React 19 + Vite
│   ├── src/
│   └── package.json
├── docs/
│   ├── SISTEMA.md            # este documento
│   └── diagramas/
│       ├── er-identidad.html
│       ├── er-academico.html
│       ├── casos-uso.html
│       ├── flujos.html
│       ├── secuencia.html
│       └── kanban-scrum.html
└── README.md
```

---

## 16. Diagramas

| Diagrama | Archivo | Contenido |
|---|---|---|
| ER identidad | [er-identidad.html](diagramas/er-identidad.html) | Unidades, usuarios, estudiantes, tutores, gestiones, aulas, materias, inscripciones |
| ER académico | [er-academico.html](diagramas/er-academico.html) | Actividades, notas, asistencias, licencias, citaciones, historiales |
| Casos de uso | [casos-uso.html](diagramas/casos-uso.html) | Un diagrama por rol |
| Flujos | [flujos.html](diagramas/flujos.html) | Inscripción, notas, asistencia, licencia |
| Secuencia | [secuencia.html](diagramas/secuencia.html) | Login, licencia, notas, asistencia, citación |
| Kanban + Scrum | [kanban-scrum.html](diagramas/kanban-scrum.html) | Sprints, buenas prácticas y cronograma |

---

## 17. Limitaciones y trabajo futuro

### Limitaciones actuales

- No hay subida de comprobantes de licencia: `comprobante_path` existe en la tabla, pero no se expone endpoint de archivos.
- No hay recuperación de contraseña por correo; el alta inicial usa CI como contraseña.
- No hay envío de correo/SMS: las notificaciones son internas (base de datos).
- No hay pruebas de componentes frontend ni E2E de navegador.
- No hay CI/CD ni contenedores de despliegue en el repositorio.
- El dashboard agrega en vivo sobre PostgreSQL; para volúmenes muy grandes convendría una tabla de agregados.
- Los reportes son consultas en pantalla; no hay exportación PDF/Excel.
- La asistencia es por materia-aula y fecha, no por bloque horario.

### Siguientes pasos sugeridos

1. Subida y validación de comprobantes (almacenamiento privado + endpoint).
2. Recuperación de contraseña y política de complejidad.
3. Exportación de planillas y boletines.
4. Notificaciones por correo y push.
5. Pruebas E2E (Playwright) y de componentes.
6. CI con lint, tests y build.
7. Observabilidad (logs estructurados, métricas, alertas).
8. Agregados materializados para dashboard a escala.
9. Backups y plan de recuperación documentado.
10. Manual de usuario por rol.

---

## 18. Glosario

| Término | Significado |
|---|---|
| **Unidad educativa** | Establecimiento escolar. Unidad de aislamiento de datos. |
| **Gestión** | Año escolar (por ejemplo, 2026). |
| **Aula** | Curso concreto: grado + paralelo + turno dentro de una gestión. |
| **Materia-aula** | Asignación de una materia a un aula con un profesor titular. |
| **Inscripción** | Vínculo de un estudiante con un aula en una gestión. |
| **Actividad** | Tarea o evaluación con puntaje máximo. |
| **Planilla** | Lista de estudiantes inscritos con sus notas o asistencias. |
| **Licencia** | Ausencia justificada solicitada por el padre/tutor. |
| **Citación** | Comunicado formal con destinatario y fecha/hora. |
| **Tenant** | Unidad educativa activa en el contexto de una petición. |
| **Soft delete** | Baja lógica: el registro se conserva con `deleted_at`. |

---

## 19. Anexos

### 19.1 Enums del dominio

| Enum | Valores |
|---|---|
| `Rol` | `superadmin`, `director`, `administrativo`, `profesor`, `padre`, `estudiante` |
| `EstadoUsuario` | `activo`, `baja` |
| `EstadoUnidad` | `activa`, `inactiva` |
| `EstadoGestion` | `activa`, `cerrada` |
| `EstadoInscripcion` | `inscrito`, `retirado` |
| `EstadoAsistencia` | `presente`, `falta`, `licencia`, `atraso` |
| `EstadoLicencia` | `pendiente`, `aprobada`, `rechazada` |
| `EstadoCitacion` | `enviada`, `vista` |
| `TipoCitacion` | `padre`, `profesor_informativa`, `profesor_extraordinaria` |
| `EstadoGenerico` | `activo`, `inactivo` |
| `DiaSemana` | `lunes` … `sabado` |
| `Turno` | `manana`, `tarde`, `noche` |
| `Parentesco` | `madre`, `padre`, `tutor`, `otro` |

### 19.2 Comandos útiles

```bash
# Backend
php artisan migrate:fresh --seed
php artisan test
php artisan route:list --path=api
php artisan tinker

# Frontend
npm run dev
npm run build
npm run lint
npm run preview
```

### 19.3 Documentos relacionados

- [Índice de documentación](README.md)
- [Diagramas](diagramas/)
