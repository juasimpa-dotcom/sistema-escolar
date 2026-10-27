# Documentación del Sistema Escolar

Índice de la documentación del sistema.

| Documento | Descripción |
|---|---|
| [SISTEMA.md](SISTEMA.md) | Arquitectura, módulos, reportes, permisos, modelo de datos, API, autenticación Bearer y acceso LAN. |
| [INSTALACION.md](INSTALACION.md) | Instalación, ejecución en Windows/CMD, acceso desde otros equipos, CORS y solución de problemas. |
| [diagramas/er-identidad.html](diagramas/er-identidad.html) | Diagrama entidad-relación de identidad y estructura académica. |
| [diagramas/er-academico.html](diagramas/er-academico.html) | Diagrama entidad-relación académico y de comunicación. |
| [diagramas/casos-uso.html](diagramas/casos-uso.html) | Casos de uso por rol (6 roles). |
| [diagramas/flujos.html](diagramas/flujos.html) | Flujos de inscripción, notas, asistencia y licencias. |
| [diagramas/secuencia.html](diagramas/secuencia.html) | Diagramas de secuencia de los procesos clave. |
| [diagramas/kanban-scrum.html](diagramas/kanban-scrum.html) | Tableros Kanban por sprint, buenas prácticas y cronograma. |

## Cómo abrir los diagramas

Los diagramas son archivos HTML autocontenidos (SVG embebido). Se abren directamente en el navegador, sin servidor ni dependencias externas.
Los ER muestran subconjuntos del modelo, y Kanban/Scrum conserva una instantánea histórica hasta el 23 de octubre de 2026. Para el estado funcional vigente, incluidos reportes y datos territoriales añadidos después, consulta `SISTEMA.md`.

## Estado del proyecto

- Reportes: catálogos por rol, filtros dependientes, vista previa, gráficos y exportación PDF/Excel; algunos formatos y cálculos aún son parciales (detalle en `SISTEMA.md`).
- Acceso LAN de desarrollo: Vite en `0.0.0.0:5173`, Laravel en `0.0.0.0:8000`, API por hostname del navegador y autenticación Bearer sin cookies.
- Backend: Laravel 12 + PostgreSQL + Sanctum.
- Frontend: React 19 + TypeScript + Vite + Tailwind CSS 4.
- Pruebas: ejecutar `php artisan test` para conocer el resultado vigente.
- Roles: superadmin, director, administrativo, profesor, padre, estudiante.
