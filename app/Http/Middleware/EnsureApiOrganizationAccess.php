<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de résolution d'organisation pour l'API.
 *
 * Résout l'organisation courante depuis :
 * 1. Header X-Organization-Id
 * 2. Query param organization_id
 * 3. Organisation par défaut de l'utilisateur
 *
 * Vérifie que l'utilisateur appartient à l'organisation et lie l'instance
 * au conteneur d'application via app('current_organization').
 *
 * Usage : Ajouté au groupe de routes API protégées.
 */
class EnsureApiOrganizationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si pas d'utilisateur authentifié, continuer (sera bloqué par auth middleware)
        if (!$user) {
            return $next($request);
        }

        // Super-admin n'a pas besoin d'organisation
        if ($user->hasRole('super-admin')) {
            app()->instance('current_organization', null);
            return $next($request);
        }

        // Résoudre l'ID de l'organisation
        $organizationId = $this->resolveOrganizationId($request, $user);

        if (!$organizationId) {
            // Essayer la première organisation de l'utilisateur
            $firstOrg = $user->organizations()->first();

            if ($firstOrg) {
                $organizationId = $firstOrg->id;

                if (!$user->default_organization_id) {
                    $user->update(['default_organization_id' => $organizationId]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune organisation trouvée pour cet utilisateur.',
                    'error' => 'no_organization',
                ], 403);
            }
        }

        // Vérifier que l'utilisateur appartient à cette organisation
        if (!$user->belongsToOrganization($organizationId)) {
            // Fallback sur l'organisation par défaut
            if ($user->default_organization_id && $user->default_organization_id !== $organizationId) {
                $organizationId = $user->default_organization_id;
            } else {
                $firstOrg = $user->organizations()->first();

                if ($firstOrg) {
                    $organizationId = $firstOrg->id;
                    $user->update(['default_organization_id' => $organizationId]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Vous n\'avez accès à aucune organisation.',
                        'error' => 'no_organization_access',
                    ], 403);
                }
            }
        }

        // Charger l'organisation et la binder au conteneur
        $organization = Organization::find($organizationId);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organisation introuvable.',
                'error' => 'organization_not_found',
            ], 404);
        }

        app()->instance('current_organization', $organization);

        // S'assurer que le current_store_id est cohérent avec l'organisation
        $this->ensureCurrentStoreMatchesOrganization($user, $organization);

        return $next($request);
    }

    /**
     * Résoudre l'ID de l'organisation depuis les sources API.
     */
    private function resolveOrganizationId(Request $request, $user): ?int
    {
        // 1. Header X-Organization-Id
        if ($request->hasHeader('X-Organization-Id')) {
            return (int) $request->header('X-Organization-Id');
        }

        // 2. Query param
        if ($request->has('organization_id')) {
            return (int) $request->get('organization_id');
        }

        // 3. Organisation par défaut de l'utilisateur
        return $user->default_organization_id;
    }

    /**
     * S'assurer que le current_store_id appartient à l'organisation courante.
     */
    private function ensureCurrentStoreMatchesOrganization($user, Organization $organization): void
    {
        $currentStoreId = $user->current_store_id;

        // Admin sans store = vue multi-magasins, OK
        if ($currentStoreId === null && $user->isAdmin()) {
            return;
        }

        // Vérifier que le store actuel appartient à l'organisation
        if ($currentStoreId) {
            $store = \App\Models\Store::find($currentStoreId);
            if ($store && $store->organization_id === $organization->id) {
                return;
            }
        }

        // Réassigner à un store valide de l'organisation
        $validStore = \App\Models\Store::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->first();

        if ($user->current_store_id !== ($validStore?->id)) {
            $user->update(['current_store_id' => $validStore?->id]);
        }
    }
}
