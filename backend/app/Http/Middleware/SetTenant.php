<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $esSuperadmin = $user->rol === Rol::SUPERADMIN;

            TenantContext::set(
                $esSuperadmin ? null : ($user->unidad_educativa_id ?? null),
                $esSuperadmin
            );
        } else {
            TenantContext::limpiar();
        }

        return $next($request);
    }
}
