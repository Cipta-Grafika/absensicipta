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

        if ($user->isPayroll) {
            return $next($request);
        }

        if ($user->isSyirkah) {
            $allowedSyirkahRoutes = [
                'payroll.saving-transactions',
                'payroll.loans',
                'payroll.savings',
                'payroll.import-export.savings',
                'payroll.import-export.saving-transactions',
            ];

            if ($request->routeIs($allowedSyirkahRoutes)) {
                return $next($request);
            }

            abort(403, 'Akses Ditolak: Group syirkah tidak memiliki wewenang untuk mengakses halaman ini.');
        }

        abort(403, 'Forbidden. Only payroll or syirkah role can access this.');
    }
}
