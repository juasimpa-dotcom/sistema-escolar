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
| [PostgreSQL](https://www.postgresql.org/) | 18.x | Base de datos principal del proyecto (recomendado) |
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
php artisan serve
```

El backend estará disponible en: `http://localhost:8000`

La API base es: `http://localhost:8000/api/v1`

## 4. Configurar el Frontend (React + Vite)

Abre una nueva terminal en la raíz del proyecto (o desde `sistema-escolar/`).

### 4.1 Instalar dependencias

```bash
cd frontend
npm install
```

### 4.2 Levantar el servidor de desarrollo

```bash
npm run dev
```

El frontend estará disponible en: `http://localhost:5173`

## 5. Ejecución Rápida (Opcional)

Si prefieres levantar ambos servidores desde una sola terminal, Laravel cuenta con un script `dev` que utiliza `concurrently`. Desde la carpeta `backend/` puedes ejecutar:

```bash
composer run dev
```

Esto levantará: servidor Laravel, cola, logs y servidor Vite al mismo tiempo.

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
| Errores de CORS | Asegúrate de que `APP_URL` en backend y la URL del frontend coincidan con las configuradas. |
| Puerto ocupado | Cambia el puerto con `php artisan serve --port=8001` o `npm run dev -- --port=5174`. |
| Dependencias faltantes | Vuelve a ejecutar `composer install` o `npm install`. |

## 9. Documentación Adicional

- [Documentación del Sistema](./SISTEMA.md) - Arquitectura, módulos, permisos y reglas de negocio
- [Diagramas](./diagramas/) - Casos de uso, flujos, secuencia, Kanban/Scrum y ER
- [README de Documentación](./README.md) - Índice general de documentación
