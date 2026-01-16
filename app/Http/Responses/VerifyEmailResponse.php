<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmailResponse implements VerifyEmailResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * Cette réponse est appelée APRÈS que l'utilisateur a cliqué sur le lien de vérification
     */
    public function toResponse($request): Response
    {
        $user = Auth::user();

        // Super-admin : rediriger vers admin.dashboard
        if ($user->hasRole('super-admin')) {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Email vérifié avec succès ! Bienvenue sur ' . config('app.name') . ' 🎉');
        }

        // Vérifier si l'utilisateur a une organisation
        $organization = $user->defaultOrganization;

        if (!$organization) {
            // Normalement ne devrait pas arriver - rediriger vers dashboard quand même
            return redirect()->route('dashboard')
                ->with('error', 'Aucune organisation trouvée.');
        }

        // Vérifier si l'organisation nécessite un paiement
        if (!$organization->isAccessible()) {
            // Rediriger vers la page de paiement
            return redirect()->route('organization.payment', ['organization' => $organization->id])
                ->with('success', 'Email vérifié avec succès ! Veuillez maintenant compléter votre paiement pour accéder à votre organisation.');
        }

        // Plan gratuit ou paiement déjà effectué - rediriger vers le dashboard
        session([
            'current_organization_id' => $organization->id,
            'current_store_id' => $user->current_store_id,
        ]);

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->route('dashboard')
                ->with('success', 'Email vérifié avec succès ! Bienvenue sur ' . config('app.name') . ' 🎉');
    }
}
