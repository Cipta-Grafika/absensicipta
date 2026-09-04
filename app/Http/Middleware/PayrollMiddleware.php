<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayrollMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        // Superadmin is strictly forbidden from accessing Syirkah menu / CRUD / management
        if ($user->isSuperadmin) {
            if ($request->routeIs([
                'payroll.saving-transactions',
                'payroll.import-export.saving-transactions',
                'payroll.savings',
                'payroll.import-export.savings',
            ])) {
                abort(403, 'Akses Ditolak: Role Superadmin tidak memiliki akses ke fitur Syirkah.');
            }
            return $next($request);
        }

        if ($user->isPayroll || $user->isOwner) {
            return $next($request);
        }

        if ($user->isSyirkah) {
            $allowedSyirkahRoutes = [
                'payroll.saving-transactions',
                'payroll.loans',
                'payroll.savings',
                'payroll.flexible-deductions',
                'payroll.import-export.savings',
                'payroll.import-export.saving-transactions',
            ];

            if ($request->routeIs($allowedSyirkahRoutes)) {
                return $next($request);
            }

            abort(403, 'Akses Ditolak: Group syirkah tidak memiliki wewenang untuk mengakses halaman ini.');
        }

        if ($user->isAdmin) {
            $allowedAdminRoutes = [
                'payroll.saving-transactions',
            ];

            if ($request->routeIs($allowedAdminRoutes)) {
                return $next($request);
            }
        }

        abort(403, 'Forbidden. Only payroll, admin, syirkah, or owner role can access this.');
    }
}
