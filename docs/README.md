# Documentación del Sistema Escolar

Índice de la documentación del sistema.

| Documento | Descripción |
|---|---|
| [SISTEMA.md](SISTEMA.md) | Documentación técnica y funcional completa: arquitectura, stack, módulos, permisos, API, instalación y pruebas. |
| [diagramas/er-identidad.html](diagramas/er-identidad.html) | Diagrama entidad-relación de identidad y estructura académica. |
| [diagramas/er-academico.html](diagramas/er-academico.html) | Diagrama entidad-relación académico y de comunicación. |
| [diagramas/casos-uso.html](diagramas/casos-uso.html) | Casos de uso por rol (6 roles). |
| [diagramas/flujos.html](diagramas/flujos.html) | Flujos de inscripción, notas, asistencia y licencias. |
| [diagramas/secuencia.html](diagramas/secuencia.html) | Diagramas de secuencia de los procesos clave. |
| [diagramas/kanban-scrum.html](diagramas/kanban-scrum.html) | Tableros Kanban por sprint, buenas prácticas y cronograma. |

## Cómo abrir los diagramas

Los diagramas son archivos HTML autocontenidos (SVG embebido). Se abren directamente en el navegador, sin servidor ni dependencias externas.

## Estado del proyecto

- Backend: Laravel 12 + PostgreSQL + Sanctum.
- Frontend: React 19 + TypeScript + Vite + Tailwind CSS 4.
- Pruebas: 65 pruebas / 191 aserciones en verde.
- Roles: superadmin, director, administrativo, profesor, padre, estudiante.
