<?php

namespace App\Livewire\Organization;

use App\Models\Organization;
use App\Services\OrganizationService;
use Livewire\Component;

class OrganizationSwitcher extends Component
{
    public ?Organization $currentOrganization = null;
    public bool $showDropdown = false;

    protected $listeners = ['organizationSwitched' => '$refresh'];

    public function mount(): void
    {
        $this->currentOrganization = app()->bound('current_organization') ? app('current_organization') : null;
    }

    public function toggleDropdown(): void
    {
        $this->showDropdown = !$this->showDropdown;
    }

    public function closeDropdown(): void
    {
        $this->showDropdown = false;
    }

    public function switchOrganization(int $organizationId, OrganizationService $service): void
    {
        $user = auth()->user();

        if (!$user->belongsToOrganization($organizationId)) {
            session()->flash('error', 'Accès non autorisé à cette organisation.');
            return;
        }

        try {
            $organization = Organization::findOrFail($organizationId);
            $service->switchOrganization($user, $organization);

            $this->currentOrganization = $organization;
            $this->showDropdown = false;

            // Emit event for other components
            $this->dispatch('organizationSwitched', $organizationId);

            // Redirect to refresh the page with new context
            return $this->redirect(request()->header('Referer', route('dashboard')), navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();

        $organizations = $user->organizations()
            ->with('stores')
            ->orderBy('name')
            ->get();

        return view('livewire.organization.organization-switcher', [
            'organizations' => $organizations,
        ]);
    }
}
