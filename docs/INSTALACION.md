# Guía de Instalación y Ejecución - Sistema Escolar

## 1. Requisitos Previos

Antes de ejecutar el sistema, asegúrate de tener instalados los siguientes programas:

| Herramienta | Versión Recomendada | Notas |
|---|---|---|
| [Git](https://git-scm.com/) | Última versión | Para clonar el repositorio |
| [PHP](https://www.php.net/) | 8.2 o superior | Requerido por Laravel 12 |
| [Composer](https://getcomposer.org/) | Última versión | Gestor de dependencias de PHP |
| [Node.js](https://nodejs.org/) | 20.x o superior | Requerido por Vite y React 19 |
| [npm](https://www.npmjs.com/) | 10.x o superior | Viene incluido con Node.js |
| [PostgreSQL](https://www.postgresql.org/) | 14 o superior | Base de datos principal del proyecto (recomendado) |
| [SQLite](https://www.sqlite.org/) | Última versión | Opcional para pruebas locales rápidas |

> Nota: El proyecto está configurado para usar **PostgreSQL** en desarrollo, tal como se documenta en el sistema. Las pruebas utilizan `SQLite` en memoria.

## 2. Clonar el Repositorio

Abre una terminal y ejecuta los siguientes comandos:

```bash
git clone https://github.com/juasimpa-dotcom/sistema-escolar.git
cd sistema-escolar
```

## 3. Configurar el Backend (Laravel)

### 3.1 Instalar dependencias

```bash
cd backend
composer install
```

### 3.2 Configurar variables de entorno

Copia el archivo de ejemplo y genera la clave de la aplicación:

```bash
cp .env.example .env
php artisan key:generate
```

### 3.3 Configurar la base de datos

Abre el archivo `.env` que acabas de crear y configura la conexión a PostgreSQL. Modifica las siguientes variables:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sistema_escolar
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

Asegúrate de haber creado la base de datos `sistema_escolar` en PostgreSQL antes de continuar.

### 3.4 Ejecutar migraciones y seeders

Esto creará todas las tablas, roles, usuarios y datos de demostración:

```bash
php artisan migrate
php artisan db:seed --class=SuperadminSeeder
php artisan db:seed --class=DemoSeeder
```

### 3.5 Levantar el servidor backend

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

El backend escucha en todas las interfaces en el puerto `8000`. En el servidor puedes abrirlo como `http://localhost:8000` o con su IP LAN, por ejemplo `http://10.225.150.106:8000`.

La API base se resuelve como `http(s)://<hostname-del-frontend>:8000/api/v1`.

## 4. Configurar el Frontend (React + Vite)

Abre una nueva terminal en la raíz del proyecto (o desde `sistema-escolar/`).

### 4.1 Instalar dependencias

```bash
cd frontend
npm install
```

### 4.2 Levantar el servidor de desarrollo

```bash
npm run dev -- --host 0.0.0.0
```

Vite escucha en todas las interfaces. En el servidor abre `http://localhost:5173`; desde otro equipo usa `http://<IP-LAN-del-servidor>:5173`.

No configures `VITE_API_URL` en `frontend/.env` durante desarrollo: `src/api/axios.ts` usa automáticamente el hostname con que se abrió el frontend. Define esa variable solo si en producción la API tiene otro origen.

### 4.3 Acceso desde otro equipo de la red local (Windows/CMD)

Inicia cada servidor en una ventana CMD diferente. Si se modificó la configuración, limpia la caché de Laravel:

```cmd
cd /d C:\Proyectos\sistema-escolar\backend
php artisan config:clear
php artisan cache:clear
php artisan serve --host=0.0.0.0 --port=8000
```

En otra ventana CMD:

```cmd
cd /d C:\Proyectos\sistema-escolar\frontend
npm run dev -- --host 0.0.0.0
```

Desde el otro equipo, abre primero `http://<IP-LAN-servidor>:8000/api/v1/auth/login`. Login solo admite `POST`, por lo que el navegador normalmente mostrará `405 Method Not Allowed`; eso confirma que alcanzó Laravel. Luego abre `http://<IP-LAN-servidor>:5173` y prueba el inicio de sesión. Si no conecta, permite los puertos TCP `8000` y `5173` en el firewall de Windows del servidor.

La autenticación de API utiliza `Authorization: Bearer` y tokens Sanctum; no requiere cookies de sesión ni credenciales cross-origin.

## 5. Ejecución Rápida (Opcional)

Si prefieres levantar ambos servidores desde una sola terminal, Laravel cuenta con un script `dev` que utiliza `concurrently`. Desde la carpeta `backend/` puedes ejecutar:

```bash
composer run dev
```

Esto levantará: servidor Laravel, cola, logs y servidor Vite al mismo tiempo.
Este atajo es práctico para desarrollo en la misma PC. Para acceder desde otros equipos, usa los comandos explícitos de la sección 4.3 para que ambos servidores escuchen en `0.0.0.0`.

## 6. Credenciales de Prueba

Una vez que los seeders se hayan ejecutado, puedes acceder con las siguientes credenciales:

| Rol | CI/Usuario | Contraseña | Notas |
|---|---|---|---|
| Superadmin | `10000001` | `10000001` | Acceso global a todas las unidades |
| Director | `20000001` | `20000001` | María - Director de unidad |
| Administrativo | `20000002` | `20000002` | Apoyo administrativo |
| Profesor | `20000003` | `20000003` | Ana - Profesora |
| Profesor | `20000004` | `20000004` | Luis - Profesor |
| Padre/Tutor | `20000005` | `20000005` | Jorge - Padre |
| Estudiante | `20000006` | `20000006` | Lucía |
| Estudiante | `20000007` | `20000007` | Diego |

> Al iniciar sesión por primera vez, el sistema te pedirá cambiar la contraseña (`debe_cambiar_password = true`).

## 7. Comandos Útiles

### Backend

```bash
php artisan migrate:fresh --seed  # Reiniciar BD con migraciones y seeders
php artisan test                  # Ejecutar pruebas (SQLite en memoria)
./vendor/bin/pint                 # Formatear código con Laravel Pint
```

### Frontend

```bash
npm run build    # Compilar para producción
npm run lint     # Ejecutar linter (oxlint)
npm run preview  # Previsualizar build de producción
```

## 8. Solución de Problemas Comunes

| Problema | Solución |
|---|---|
| Error de conexión a BD | Verifica que PostgreSQL esté corriendo y las credenciales en `.env` sean correctas. |
| `APP_KEY` vacía | Ejecuta `php artisan key:generate` en la carpeta `backend/`. |
| Error de CORS | En desarrollo verifica `supports_credentials=false`, ausencia de `withCredentials` y reinicia tras `php artisan config:clear`. En producción define `FRONTEND_URL` con el origen exacto de la SPA. |
| API apunta a `localhost` desde otro equipo | Elimina `VITE_API_URL` de `frontend/.env`, reinicia Vite y abre la SPA usando la IP LAN del servidor. |
| Otro equipo no conecta | Confirma que ambos estén en la misma red WiFi, inicia Vite y Laravel con `--host 0.0.0.0` y permite TCP `5173` y `8000` en el firewall del servidor. |
| Puerto ocupado | Cambia el puerto con `php artisan serve --port=8001` o `npm run dev -- --port=5174`. |
| Dependencias faltantes | Vuelve a ejecutar `composer install` o `npm install`. |

En desarrollo CORS permite `allowed_origins=['*']` porque `supports_credentials=false`. Para producción, `backend/config/cors.php` usa el origen configurado en `FRONTEND_URL`; no uses `*` junto con cookies/credenciales.

## 9. Documentación Adicional

- [Documentación del Sistema](./SISTEMA.md) - Arquitectura, módulos, permisos y reglas de negocio
- [Diagramas](./diagramas/) - Casos de uso, flujos, secuencia, Kanban/Scrum y ER
- [README de Documentación](./README.md) - Índice general de documentación
