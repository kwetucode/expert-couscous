<?php

namespace App\Http\Responses;

use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        $user = Auth::user();

        // 1. Vérifier d'abord si l'email est vérifié
        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')
                ->with('info', 'Veuillez vérifier votre adresse email pour accéder à votre compte.');
        }

        // 2. Si l'utilisateur est super-admin, rediriger vers le dashboard admin
        if ($user->hasRole('super-admin')) {
            return $request->wantsJson()
                ? new JsonResponse('', 204)
                : redirect()->route('admin.dashboard');
        }

        // 3. Vérifier si l'utilisateur a une organisation par défaut
        $organization = $user->defaultOrganization;

        if (!$organization) {
            // Pas d'organisation, rediriger vers la page d'inscription (normalement ne devrait pas arriver)
            return redirect()->route('register')
                ->with('error', 'Aucune organisation trouvée. Veuillez créer un compte.');
        }

        // 4. Vérifier si l'organisation nécessite un paiement
        if (!$organization->isAccessible()) {
            // L'organisation a un plan payant mais n'a pas complété le paiement
            return redirect()->route('organization.payment', ['organization' => $organization->id])
                ->with('warning', 'Veuillez compléter le paiement pour accéder à votre organisation.');
        }

        // 5. Tout est OK, rediriger vers le dashboard
        // Définir les sessions nécessaires
        session([
            'current_organization_id' => $organization->id,
            'current_store_id' => $user->current_store_id,
        ]);

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended(config('fortify.home'));
    }
}
