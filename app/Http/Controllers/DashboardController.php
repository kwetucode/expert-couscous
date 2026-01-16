<?php

namespace App\Http\Controllers;

use App\Livewire\Dashboard;

class DashboardController extends Controller
{
    /**
     * Redirect to the appropriate dashboard based on user role
     */
    public function __invoke()
    {
        $user = auth()->user();

        // Super-admin gets the admin dashboard
        if ($user->hasRole('super-admin')) {
            return redirect()->route('admin.dashboard');
        }

        // Regular users get the normal dashboard component
        return app()->call([app(Dashboard::class), '__invoke']);
    }
}
