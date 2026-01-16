<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        //dd($user);
        // Si pas d'utilisateur authentifié, continuer
        if (!$user) {
            return $next($request);
        }

        // Super-admin n'a pas besoin d'organisation
        if ($user->hasRole('super-admin')) {
            app()->instance('current_organization', null);
            view()->share('currentOrganization', null);
            return $next($request);
        }

        // Exclure la page de paiement (vérification par URL et par nom de route)
        if (preg_match('#^organization/\d+/payment$#', $request->path()) || $request->routeIs('organization.payment')) {
            return $next($request);
        }

        // Récupérer l'organization_id depuis la route, la session ou la valeur par défaut
        $organizationId = $this->resolveOrganizationId($request, $user);

        // Si pas d'organisation, essayer d'en trouver une
        if (!$organizationId) {
            // Essayer la première organisation de l'utilisateur
            $firstOrg = $user->organizations()->first();
            if ($firstOrg) {
                $organizationId = $firstOrg->id;
                // Définir comme organisation par défaut si l'utilisateur n'en a pas
                if (!$user->default_organization_id) {
                    $user->update(['default_organization_id' => $organizationId]);
                }
            } else {
                // Aucune organisation disponible, définir null dans le conteneur
                app()->instance('current_organization', null);
                return $next($request);
            }
        }

        // Vérifier que l'utilisateur a accès à cette organisation
        if (!$user->belongsToOrganization($organizationId)) {
            // Si l'utilisateur n'a pas accès, essayer son organisation par défaut
            if ($user->default_organization_id && $user->default_organization_id !== $organizationId) {
                $organizationId = $user->default_organization_id;
                session(['current_organization_id' => $organizationId]);
            } else {
                // Essayer la première organisation disponible
                $firstOrg = $user->organizations()->first();
                if ($firstOrg) {
                    $organizationId = $firstOrg->id;
                    session(['current_organization_id' => $organizationId]);
                    $user->update(['default_organization_id' => $organizationId]);
                } else {
                    // Aucune organisation disponible
                    app()->instance('current_organization', null);
                    return $next($request);
                }
            }
        }

        // Charger l'organisation et la mettre dans le contexte de l'application
        $organization = Organization::find($organizationId);

        if ($organization) {
            app()->instance('current_organization', $organization);
            session(['current_organization_id' => $organization->id]);

            // Vérifier que le current_store_id de l'utilisateur appartient à cette organisation
            $this->ensureCurrentStoreMatchesOrganization($user, $organization);

            // Partager avec les vues
            view()->share('currentOrganization', $organization);
        }

        return $next($request);
    }

    /**
     * Ensure the user's current_store_id belongs to the current organization.
     * If not, reset it to a valid store from the organization.
     */
    private function ensureCurrentStoreMatchesOrganization($user, Organization $organization): void
    {
        $currentStoreId = $user->current_store_id;

        // If user has a current store, check if it belongs to the organization
        if ($currentStoreId) {
            $store = \App\Models\Store::find($currentStoreId);
            if ($store && $store->organization_id === $organization->id) {
                return; // Current store is valid
            }
        }

        // Find a valid store from the organization
        $validStore = \App\Models\Store::where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->first();

        // Update user's current_store_id (null if no store available)
        if ($user->current_store_id !== ($validStore?->id)) {
            $user->update(['current_store_id' => $validStore?->id]);
        }
    }

    /**
     * Resolve the organization ID from various sources.
     */
    private function resolveOrganizationId(Request $request, $user): ?int
    {
        // 1. Depuis la route (paramètre explicite)
        if ($request->route('organization')) {
            $org = $request->route('organization');
            return $org instanceof Organization ? $org->id : (int) $org;
        }

        // 2. Depuis le header (API)
        if ($request->hasHeader('X-Organization-Id')) {
            return (int) $request->header('X-Organization-Id');
        }

        // 3. Depuis la query string
        if ($request->has('organization_id')) {
            return (int) $request->get('organization_id');
        }

        // 4. Depuis la session
        if (session()->has('current_organization_id')) {
            return (int) session('current_organization_id');
        }

        // 5. Depuis l'utilisateur (organisation par défaut)
        return $user->default_organization_id;
    }
}
