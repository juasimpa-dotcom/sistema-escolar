<?php

namespace App\Observers;

use App\Services\DashboardService;

/**
 * Invalida el resumen del dashboard cuando cambian datos que lo alimentan
 * (asistencia, licencias y notas). Se registra desde AppServiceProvider.
 */
class DashboardCacheObserver
{
    public function saved(): void
    {
        DashboardService::invalidar();
    }

    public function deleted(): void
    {
        DashboardService::invalidar();
    }
}
