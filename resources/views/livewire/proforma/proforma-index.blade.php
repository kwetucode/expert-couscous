<div>
<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Proformas']
    ]" />
</x-slot>

<div class="space-y-6">
    <!-- Toast -->
    <x-toast />

     <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Factures Proforma</h1>
            <p class="text-gray-500 mt-1">Gérez vos devis et factures proforma</p>
        </div>
        <div class="flex items-center space-x-3">
            <x-form.button href="{{ route('proformas.create') }}" wire:navigate icon="plus">
                Nouvelle Proforma
            </x-form.button>
        </div>
    </div>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <x-kpi-card
            title="Total Proformas"
            :value="$statistics['total']"
            color="indigo">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </x-kpi-card>

        <x-kpi-card
            title="En attente"
            :value="$statistics['pending']"
            color="orange">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-kpi-card>

        <x-kpi-card
            title="Acceptées"
            :value="$statistics['accepted']"
            color="green">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-kpi-card>

        <x-kpi-card
            title="Converties"
            :value="$statistics['converted']"
            color="blue">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
        </x-kpi-card>

        <x-kpi-card
            title="Montant en cours"
            :value="format_currency($statistics['total_amount'])"
            color="purple">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </x-kpi-card>
    </div>

    <!-- Filters Card -->
    <x-card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Filtres</h2>
            <div class="flex items-center space-x-2">
                <!-- Period Filter Dropdown -->
                <x-form.select wire:model.live="periodFilter" class="w-48">
                    <option value="today">Aujourd'hui</option>
                    <option value="yesterday">Hier</option>
                    <option value="this_week">Cette semaine</option>
                    <option value="last_week">Semaine dernière</option>
                    <option value="this_month">Ce mois</option>
                    <option value="last_month">Mois dernier</option>
                    <option value="last_3_months">3 derniers mois</option>
                    <option value="last_6_months">6 derniers mois</option>
                    <option value="this_year">Cette année</option>
                    <option value="last_year">Année dernière</option>
                    <option value="all">Toutes les dates</option>
                    <option value="custom">Personnalisé</option>
                </x-form.select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div>
                <x-form.search-input
                    wire:model.live.debounce.300ms="search"
                    wireModel="search"
                    placeholder="Rechercher par numéro ou client..."
                />
            </div>

            <!-- Status Filter -->
            <div>
                <x-form.select wire:model.live="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="draft">Brouillon</option>
                    <option value="sent">Envoyée</option>
                    <option value="accepted">Acceptée</option>
                    <option value="rejected">Refusée</option>
                    <option value="converted">Convertie</option>
                    <option value="expired">Expirée</option>
                </x-form.select>
            </div>

            <!-- Date From -->
            <div>
                <x-form.input wire:model.live="dateFrom" type="date" placeholder="Date début" />
            </div>

            <!-- Date To -->
            <div>
                <x-form.input wire:model.live="dateTo" type="date" placeholder="Date fin" />
            </div>
        </div>

        <!-- Period indicator -->
        @if($periodFilter && $periodFilter !== 'custom')
        <div class="mt-4 flex items-center">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ $this->getPeriodLabel() }}
                @if($dateFrom && $dateTo)
                    : {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                @endif
            </span>
        </div>
        @endif
    </x-card>

    <!-- Proformas List -->
    <x-card>
        <x-slot:header>
            <x-card-title title="Liste des Proformas ({{ $proformas->total() }})">
                <x-slot:action>
                    <x-form.select wire:model.live="perPage" class="text-sm">
                        <option value="15">15 par page</option>
                        <option value="25">25 par page</option>
                        <option value="50">50 par page</option>
                    </x-form.select>
                </x-slot:action>
            </x-card-title>
        </x-slot:header>

        <x-table.table>
            <x-table.head>
                <tr>
                    <x-table.header sortable sortKey="proforma_number">Numéro</x-table.header>
                    <x-table.header sortable sortKey="client_name">Client</x-table.header>
                    <x-table.header sortable sortKey="valid_until">Validité</x-table.header>
                    <x-table.header sortable sortKey="total" align="right">Total</x-table.header>
                    <x-table.header sortable sortKey="status" align="center">Statut</x-table.header>
                    <x-table.header align="center">Actions</x-table.header>
                </tr>
            </x-table.head>

            <x-table.body>
                @forelse($proformas as $proforma)
                    <x-table.row wire:key="proforma-{{ $proforma->id }}">
                        <x-table.cell>
                            <div>
                                <a href="{{ route('proformas.show', $proforma) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 font-medium">
                                    {{ $proforma->proforma_number }}
                                </a>
                                <div class="text-xs text-gray-500">{{ $proforma->proforma_date->format('d/m/Y') }}</div>
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <div>
                                <div class="font-medium text-gray-900">{{ $proforma->client_name }}</div>
                                @if($proforma->client_email)
                                    <div class="text-xs text-gray-500">{{ $proforma->client_email }}</div>
                                @endif
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            @if($proforma->valid_until)
                                <span class="{{ $proforma->isExpired() ? 'text-red-600' : '' }}">
                                    {{ $proforma->valid_until->format('d/m/Y') }}
                                </span>
                            @else
                                -
                            @endif
                        </x-table.cell>
                        <x-table.cell align="right" class="font-semibold">{{ format_currency($proforma->total) }}</x-table.cell>
                        <x-table.cell align="center">
                            @php
                                $statusClasses = match($proforma->status) {
                                    'draft' => 'bg-gray-100 text-gray-800',
                                    'sent' => 'bg-blue-100 text-blue-800',
                                    'accepted' => 'bg-green-100 text-green-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    'converted' => 'bg-indigo-100 text-indigo-800',
                                    'expired' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
                                {{ $proforma->status_label }}
                            </span>
                        </x-table.cell>
                        <x-table.cell align="center">
                            <x-actions-dropdown>
                                <!-- Voir -->
                                <x-dropdown-item href="{{ route('proformas.show', $proforma) }}" wireNavigate icon="eye">
                                    Voir les détails
                                </x-dropdown-item>

                                <!-- Modifier -->
                                @if($proforma->canBeEdited())
                                    <x-dropdown-item href="{{ route('proformas.edit', $proforma) }}" wireNavigate icon="edit">
                                        Modifier
                                    </x-dropdown-item>
                                @endif

                                <div class="border-t border-gray-100 my-1"></div>

                                <!-- PDF -->
                                <x-dropdown-item href="{{ route('proformas.pdf.view', $proforma) }}" target="_blank" icon="eye">
                                    Aperçu PDF
                                </x-dropdown-item>
                                <x-dropdown-item href="{{ route('proformas.pdf', $proforma) }}" icon="download">
                                    Télécharger PDF
                                </x-dropdown-item>

                                <!-- Email -->
                                @if($proforma->status === 'draft' || $proforma->status === 'sent')
                                    <x-dropdown-item wireClick="prepareEmailSend({{ $proforma->id }})" icon="mail">
                                        Envoyer par email
                                    </x-dropdown-item>
                                @endif

                                @if($proforma->status === 'draft' || $proforma->status === 'sent' || $proforma->status === 'accepted')
                                    <div class="border-t border-gray-100 my-1"></div>
                                @endif

                                <!-- Actions de statut -->
                                @if($proforma->status === 'sent')
                                    <x-dropdown-item
                                        wireClick="prepareAction({{ $proforma->id }}, 'accept')"
                                        icon="check-circle"
                                        variant="success">
                                        Accepter
                                    </x-dropdown-item>
                                    <x-dropdown-item
                                        wireClick="prepareAction({{ $proforma->id }}, 'reject')"
                                        icon="x-circle"
                                        variant="danger">
                                        Refuser
                                    </x-dropdown-item>
                                @endif

                                @if($proforma->status === 'accepted')
                                    <x-dropdown-item
                                        wireClick="prepareAction({{ $proforma->id }}, 'convert')"
                                        icon="switch-horizontal"
                                        variant="success">
                                        Convertir en facture
                                    </x-dropdown-item>
                                @endif

                                <!-- Dupliquer -->
                                <div class="border-t border-gray-100 my-1"></div>
                                <x-dropdown-item
                                    wireClick="duplicate({{ $proforma->id }})"
                                    icon="duplicate">
                                    Dupliquer
                                </x-dropdown-item>

                                <!-- Supprimer -->
                                @if($proforma->status === 'draft')
                                    <div class="border-t border-gray-100 my-1"></div>
                                    <x-dropdown-item
                                        wireClick="prepareDelete({{ $proforma->id }})"
                                        icon="trash"
                                        variant="danger">
                                        Supprimer
                                    </x-dropdown-item>
                                @endif
                            </x-actions-dropdown>
                        </x-table.cell>
                    </x-table.row>
                @empty
                    <x-table.row>
                        <x-table.cell colspan="7">
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>Aucune proforma trouvée</p>
                                <x-form.button href="{{ route('proformas.create') }}" wire:navigate icon="plus" class="mt-4">
                                    Créer une proforma
                                </x-form.button>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                @endforelse
            </x-table.body>
        </x-table.table>

        <!-- Pagination -->
        @if($proformas->hasPages())
            <div class="mt-4 border-t border-gray-200 pt-4">
                {{ $proformas->links() }}
            </div>
        @endif
    </x-card>

    <!-- Delete Confirmation Modal -->
    <x-modal name="showDeleteModal" maxWidth="md" title="Supprimer la proforma">
        <div class="p-6">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600">Êtes-vous sûr de vouloir supprimer cette proforma ? Cette action est irréversible.</p>
                </div>
            </div>
        </div>

        <x-slot:footer>
            <x-form.button variant="secondary" wire:click="closeDeleteModal">
                Annuler
            </x-form.button>
            <x-form.button variant="danger" wire:click="delete" @click="$wire.closeDeleteModal()">
                Supprimer
            </x-form.button>
        </x-slot:footer>
    </x-modal>

    <!-- Action Modal -->
    <x-modal name="showActionModal" maxWidth="md" :title="'Confirmation'">
        <div class="p-6">
            <div class="flex items-center mb-4">
                @if($actionType === 'accept')
                    <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <x-icons.check-circle class="w-6 h-6 text-green-600" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Accepter la proforma</h3>
                    </div>
                @elseif($actionType === 'reject')
                    <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <x-icons.x-circle class="w-6 h-6 text-red-600" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Refuser la proforma</h3>
                    </div>
                @elseif($actionType === 'convert')
                    <div class="flex-shrink-0 w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                        <x-icons.switch-horizontal class="w-6 h-6 text-indigo-600" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">Convertir en facture</h3>
                    </div>
                @endif
            </div>

            <p class="text-gray-600">
                @if($actionType === 'accept')
                    Voulez-vous accepter cette proforma ? Le client pourra procéder au paiement.
                @elseif($actionType === 'reject')
                    Voulez-vous refuser cette proforma ? Cette action peut être annulée.
                @elseif($actionType === 'convert')
                    Voulez-vous convertir cette proforma en facture définitive ? Cette action est irréversible.
                @endif
            </p>
        </div>

        <x-slot:footer>
            <x-form.button variant="secondary" wire:click="closeActionModal" wire:loading.attr="disabled">
                Annuler
            </x-form.button>
            @if($actionType === 'accept')
                <x-form.button variant="success" wire:click="accept" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="accept">Accepter</span>
                    <span wire:loading wire:target="accept">Traitement...</span>
                </x-form.button>
            @elseif($actionType === 'reject')
                <x-form.button variant="danger" wire:click="reject" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="reject">Refuser</span>
                    <span wire:loading wire:target="reject">Traitement...</span>
                </x-form.button>
            @elseif($actionType === 'convert')
                <x-form.button variant="success" wire:click="convert" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="convert">Convertir</span>
                    <span wire:loading wire:target="convert">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Conversion en cours...
                    </span>
                </x-form.button>
            @endif
        </x-slot:footer>
    </x-modal>

    <!-- Email Send Modal -->
    <x-modal name="showEmailModal" maxWidth="md" title="Envoyer par email" >
        <form wire:submit="sendByEmail">
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">
                    La facture proforma sera envoyée avec le fichier PDF en pièce jointe.
                </p>

                <div>
                    <label for="emailTo" class="block text-sm font-medium text-gray-700 mb-1">
                        Adresse email du destinataire
                    </label>
                    <input
                        type="email"
                        id="emailTo"
                        wire:model="emailTo"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('emailTo') @enderror"
                        placeholder="client@exemple.com"
                        autofocus
                    >
                    @error('emailTo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <div class="flex">
                        <svg class="w-5 h-5 text-blue-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm text-blue-700">
                            Le statut de la proforma sera automatiquement mis à jour en "Envoyée".
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 border-t border-gray-200">
                <x-form.button type="button" variant="secondary" @click="$wire.showEmailModal = false">
                    Annuler
                </x-form.button>
                <x-form.button type="submit" icon="mail" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="sendByEmail">Envoyer</span>
                    <span wire:loading wire:target="sendByEmail">Envoi en cours...</span>
                </x-form.button>
            </div>
        </form>
    </x-modal>
</div>
</div>
