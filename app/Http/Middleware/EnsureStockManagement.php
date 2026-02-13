<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware qui bloque l'accès aux fonctionnalités de stock
 * pour les organisations de type "service uniquement".
 *
 * Les organisations dont le business_activity est "services"
 * n'ont pas de gestion de stock (pas de produits physiques).
 *
 * Usage: Route::middleware('stock.required')
 */
class EnsureStockManagement
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!has_stock_management()) {
            return response()->json([
                'success' => false,
                'message' => 'La gestion de stock n\'est pas disponible pour les organisations de type service.',
                'error' => 'stock_not_available_for_services',
            ], 403);
        }

        return $next($request);
    }
}
