<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de vérification des permissions pour l'API.
 *
 * Vérifie que l'utilisateur authentifié possède au moins une des permissions requises
 * via ses rôles, sans dépendance au MenuService (contrairement à CheckPermission web).
 *
 * Usage : ->middleware('api.permission:products.view')
 *         ->middleware('api.permission:sales.view,sales.create')
 */
class ApiCheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissions  Permissions requises, séparées par des virgules (OR logic)
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        // Si pas d'utilisateur, continuer (sera bloqué par auth middleware)
        if (!$user) {
            return $next($request);
        }

        // Super-admin a toutes les permissions
        if ($user->hasRole('super-admin')) {
            return $next($request);
        }

        // Admin a toutes les permissions (même logique que le web)
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Vérifier les permissions (OR logic : l'utilisateur doit avoir au moins une)
        $requiredPermissions = array_map('trim', explode(',', $permissions));

        foreach ($requiredPermissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        // Aucune permission trouvée
        Log::warning('API: Unauthorized access attempt', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'required_permissions' => $requiredPermissions,
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Vous n\'avez pas les permissions nécessaires pour effectuer cette action.',
            'required_permissions' => $requiredPermissions,
            'error' => 'insufficient_permissions',
        ], 403);
    }
}
