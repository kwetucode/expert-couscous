<div x-data="{ showDeleteModal: false, showActionModal: false, actionType: '', showModal: false, showSendModal: false }"
     @open-email-modal.window="showModal = true"
     @close-email-modal.window="showModal = false"
     @open-send-modal.window="showSendModal = true"
     @close-send-modal.window="showSendModal = false"
     @open-whatsapp.window="window.open($event.detail.url, '_blank')">
<x-slot name="header">
    <x-breadcrumb :items="[
        ['label' => 'Accueil', 'url' => route('dashboard')],
        ['label' => 'Proformas', 'url' => route('proformas.index')],
        ['label' => $proforma->proforma_number]
    ]" />


</x-slot>

<div class="max-w-7xl mx-auto space-y-6">
    <!-- Toast -->
    <x-toast />

     <div class="flex items-center justify-between mt-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $proforma->proforma_number }}</h1>
            <div class="flex items-center space-x-3 mt-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    @switch($proforma->status)
                        @case('draft') bg-gray-100 text-gray-800 @break
                        @case('sent') bg-blue-100 text-blue-800 @break
                        @case('accepted') bg-green-100 text-green-800 @break
                        @case('rejected') bg-red-100 text-red-800 @break
                        @case('converted') bg-indigo-100 text-indigo-800 @break
                        @case('expired') bg-yellow-100 text-yellow-800 @break
                    @endswitch">
                    {{ $proforma->status_label }}
                </span>
                @if($proforma->isExpired())
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Expirée
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <!-- Bouton principal d'action selon le statut -->
            @if($proforma->status === 'draft' || $proforma->status === 'sent')
                <x-form.button wire:click="prepareEmailSend" icon="mail">
                    Email
                </x-form.button>

                @if($proforma->client_phone)
                    @php
                        $whatsappNumber = preg_replace('/[^0-9]/', '', $proforma->client_phone);
                        $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('public.proformas.pdf', now()->addDays(7), ['proforma' => $proforma->id]);
                        $whatsappMessage = urlencode("Bonjour,\n\nVeuillez trouver ci-joint votre facture proforma {$proforma->proforma_number} d'un montant de " . format_currency($proforma->total) . ".\n\nVous pouvez la t\xe9l\xe9charger ici : " . $pdfUrl . "\n\nCordialement,\n" . ($proforma->store->name ?? config('app.name')));
                    @endphp
                    <a href="https://wa.me/{{ $whatsappNumber }}?text={{ $whatsappMessage }}"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </a>
                @endif
            @endif

            @if($proforma->status === 'accepted')
                <x-form.button wire:click="convert" variant="success" icon="switch-horizontal">
                    Convertir en facture
                </x-form.button>
            @endif

            <!-- Menu dropdown pour les autres actions -->
            <div x-data="{ open: false }" class="relative">
                <x-form.button @click="open = !open" variant="secondary">
                    Actions
                    <svg class="w-4 h-4 ml-2 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </x-form.button>

                <div
                    x-show="open"
                    x-cloak
                    @click.outside="open = false"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 z-50 mt-2 w-56 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                >
                    <div class="py-1">
                        <!-- Modifier -->
                        @if($proforma->canBeEdited())
                            <a href="{{ route('proformas.edit', $proforma) }}" wire:navigate
                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" @click="open = false">
                                <x-icons.edit class="w-4 h-4 mr-3" />
                                Modifier
                            </a>
                        @endif

                        <div class="border-t border-gray-100 my-1"></div>

                        <!-- PDF -->
                        <a href="{{ route('proformas.pdf.view', $proforma) }}" target="_blank"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" @click="open = false">
                            <x-icons.eye class="w-4 h-4 mr-3" />
                            Aperçu PDF
                        </a>
                        <a href="{{ route('proformas.pdf', $proforma) }}"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" @click="open = false">
                            <x-icons.download class="w-4 h-4 mr-3" />
                            Télécharger PDF
                        </a>

                        <!-- WhatsApp -->
                        @if($proforma->client_phone)
                            @php
                                $whatsappNumber = preg_replace('/[^0-9]/', '', $proforma->client_phone);
                                $pdfUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('public.proformas.pdf', now()->addDays(7), ['proforma' => $proforma->id]);
                                $whatsappMessage = urlencode("Bonjour,\n\nVeuillez trouver ci-joint votre facture proforma {$proforma->proforma_number} d'un montant de " . format_currency($proforma->total) . ".\n\nVous pouvez la t\xe9l\xe9charger ici : " . $pdfUrl . "\n\nCordialement,\n" . ($proforma->store->name ?? config('app.name')));
                            @endphp
                            <a href="https://wa.me/{{ $whatsappNumber }}?text={{ $whatsappMessage }}"
                               target="_blank"
                               class="flex items-center px-4 py-2 text-sm text-green-600 hover:bg-green-50" @click="open = false">
                                <svg class="w-4 h-4 mr-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                Envoyer par WhatsApp
                            </a>
                        @endif

                        @if($proforma->status === 'sent')
                            <div class="border-t border-gray-100 my-1"></div>
                            <button wire:click="accept" @click="open = false"
                                    class="flex items-center w-full px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                <x-icons.check-circle class="w-4 h-4 mr-3" />
                                Accepter
                            </button>
                            <button wire:click="reject" @click="open = false"
                                    class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <x-icons.x-circle class="w-4 h-4 mr-3" />
                                Refuser
                            </button>
                        @endif

                        <div class="border-t border-gray-100 my-1"></div>

                        <!-- Dupliquer -->
                        <button wire:click="duplicate" @click="open = false"
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <x-icons.duplicate class="w-4 h-4 mr-3" />
                            Dupliquer
                        </button>

                        <!-- Supprimer -->
                        @if($proforma->status === 'draft')
                            <div class="border-t border-gray-100 my-1"></div>
                            <button @click="showDeleteModal = true; open = false"
                                    class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <x-icons.trash class="w-4 h-4 mr-3" />
                                Supprimer
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <x-form.button href="{{ route('proformas.index') }}" wire:navigate variant="ghost" icon="arrow-left">
                Retour
            </x-form.button>
        </div>
    </div>

    @if (session()->has('success'))
        <x-form.alert type="success" :message="session('success')" />
    @endif

    @if (session()->has('error'))
        <x-form.alert type="error" :message="session('error')" />
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Client Info Card -->
            <x-card>
                <x-slot:header>
                    <x-card-title title="Informations client" />
                </x-slot:header>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Nom</h4>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $proforma->client_name }}</p>
                    </div>
                    @if($proforma->client_phone)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Téléphone</h4>
                            <p class="mt-1 text-gray-900">{{ $proforma->client_phone }}</p>
                        </div>
                    @endif
                    @if($proforma->client_email)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500">Email</h4>
                            <p class="mt-1 text-gray-900">{{ $proforma->client_email }}</p>
                        </div>
                    @endif
                    @if($proforma->client_address)
                        <div class="md:col-span-2">
                            <h4 class="text-sm font-medium text-gray-500">Adresse</h4>
                            <p class="mt-1 text-gray-900">{{ $proforma->client_address }}</p>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Items Card -->
            <x-card>
                <x-slot:header>
                    <x-card-title title="Articles ({{ $proforma->items->count() }})" />
                </x-slot:header>

                <x-table.table>
                    <x-table.head>
                        <tr>
                            <x-table.header>Article</x-table.header>
                            <x-table.header align="right">Qté</x-table.header>
                            <x-table.header align="right">Prix unit.</x-table.header>
                            <x-table.header align="right">Remise</x-table.header>
                            <x-table.header align="right">Total</x-table.header>
                        </tr>
                    </x-table.head>

                    <x-table.body>
                        @foreach($proforma->items as $item)
                            <x-table.row wire:key="item-{{ $item->id }}">
                                <x-table.cell>
                                    <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                    @if($item->description && $item->description !== $item->name)
                                        <div class="text-xs text-gray-500">{{ $item->description }}</div>
                                    @endif
                                </x-table.cell>
                                <x-table.cell align="right">{{ $item->quantity }}</x-table.cell>
                                <x-table.cell align="right">{{ format_currency($item->unit_price) }}</x-table.cell>
                                <x-table.cell align="right" class="text-red-600">
                                    @if($item->discount > 0)
                                        -{{ format_currency($item->discount) }}
                                    @else
                                        -
                                    @endif
                                </x-table.cell>
                                <x-table.cell align="right" class="font-semibold">{{ format_currency($item->total) }}</x-table.cell>
                            </x-table.row>
                        @endforeach
                    </x-table.body>
                </x-table.table>

                <!-- Totals -->
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Sous-total</span>
                                <span class="font-medium">{{ format_currency($proforma->subtotal) }}</span>
                            </div>
                            @if($proforma->discount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Remises</span>
                                    <span class="font-medium text-red-600">-{{ format_currency($proforma->discount) }}</span>
                                </div>
                            @endif
                            @if($proforma->tax_amount > 0)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Taxes</span>
                                    <span class="font-medium">{{ format_currency($proforma->tax_amount) }}</span>
                                </div>
                            @endif
                            <hr>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="text-indigo-600">{{ format_currency($proforma->total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            <!-- Notes Card -->
            @if($proforma->notes || $proforma->terms_conditions)
                <x-card>
                    <x-slot:header>
                        <x-card-title title="Notes et conditions" />
                    </x-slot:header>

                    @if($proforma->notes)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Notes</h4>
                            <p class="text-gray-700 whitespace-pre-line">{{ $proforma->notes }}</p>
                        </div>
                    @endif

                    @if($proforma->terms_conditions)
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Conditions générales</h4>
                            <p class="text-gray-700 whitespace-pre-line">{{ $proforma->terms_conditions }}</p>
                        </div>
                    @endif
                </x-card>
            @endif
        </div>

        <!-- Right Column - Actions & Info -->
        <div class="space-y-6">
            <!-- Actions Card -->
            <x-card>
                <x-slot:header>
                    <x-card-title title="Actions" />
                </x-slot:header>

                <div class="space-y-3">
                    @if($proforma->status === 'draft')
                        <x-form.button wire:click="markAsSent" :fullWidth="true" icon="arrow-right">
                            Marquer comme envoyée
                        </x-form.button>
                    @endif

                    @if($proforma->status === 'sent')
                        <x-form.button wire:click="accept" variant="success" :fullWidth="true" icon="check">
                            Accepter
                        </x-form.button>
                        <x-form.button wire:click="reject" variant="danger" :fullWidth="true" icon="x">
                            Refuser
                        </x-form.button>
                    @endif

                    @if($proforma->status === 'accepted')
                        <x-form.button wire:click="convert" variant="success" :fullWidth="true" icon="switch-horizontal">
                            Convertir en facture
                        </x-form.button>
                    @endif

                    <x-form.button wire:click="duplicate" variant="secondary" :fullWidth="true" icon="clipboard-check">
                        Dupliquer
                    </x-form.button>

                    @if($proforma->status === 'draft')
                        <x-form.button @click="showDeleteModal = true" variant="danger" :fullWidth="true" icon="trash">
                            Supprimer
                        </x-form.button>
                    @endif
                </div>
            </x-card>

            <!-- Info Card -->
            <x-card>
                <x-slot:header>
                    <x-card-title title="Informations" />
                </x-slot:header>

                <div class="space-y-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Date de création</span>
                        <span class="font-medium">{{ $proforma->proforma_date->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Valide jusqu'au</span>
                        <span class="font-medium {{ $proforma->isExpired() ? 'text-red-600' : '' }}">
                            {{ $proforma->valid_until?->format('d/m/Y') ?? '-' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Magasin</span>
                        <span class="font-medium">{{ $proforma->store->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Créé par</span>
                        <span class="font-medium">{{ $proforma->user->name ?? '-' }}</span>
                    </div>
                    @if($proforma->convertedInvoice)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Facture</span>
                            <a href="{{ route('invoices.show', $proforma->convertedInvoice) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800 font-medium">
                                {{ $proforma->convertedInvoice->invoice_number }}
                            </a>
                        </div>
                    @endif
                </div>
            </x-card>

            <!-- Timeline Card -->
            <x-card>
                <x-slot:header>
                    <x-card-title title="Historique" />
                </x-slot:header>

                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-gray-400"></div>
                        <div>
                            <p class="text-sm text-gray-900">Proforma créée</p>
                            <p class="text-xs text-gray-500">{{ $proforma->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    @if($proforma->status !== 'draft')
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-blue-400"></div>
                            <div>
                                <p class="text-sm text-gray-900">Statut: {{ $proforma->status_label }}</p>
                                <p class="text-xs text-gray-500">{{ $proforma->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif
                    @if($proforma->converted_at)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-green-400"></div>
                            <div>
                                <p class="text-sm text-gray-900">Convertie en facture</p>
                                <p class="text-xs text-gray-500">{{ $proforma->converted_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>

    <!-- Delete Modal -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showDeleteModal = false"></div>

            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                        <x-icons.trash class="h-6 w-6 text-red-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Supprimer la proforma</h3>
                    <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer cette proforma ? Cette action est irréversible.</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <x-form.button variant="secondary" @click="showDeleteModal = false">
                        Annuler
                    </x-form.button>
                    <x-form.button variant="danger" wire:click="delete">
                        Supprimer
                    </x-form.button>
                </div>
            </div>
        </div>
    </div>

    <!-- Email Send Modal -->
    <x-ui.alpine-modal name="email" max-width="md" title="Envoyer par email" icon-bg="from-indigo-500 to-indigo-600">
        <x-slot name="icon">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </x-slot>

        <form wire:submit.prevent="sendByEmail">
            <x-ui.alpine-modal-body>
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">
                        La facture proforma <strong>{{ $proforma->proforma_number }}</strong> sera envoyée avec le fichier PDF en pièce jointe.
                    </p>

                    <div>
                        <label for="emailTo" class="block text-sm font-medium text-gray-700 mb-1">
                            Adresse email du destinataire
                        </label>
                        <input
                            type="email"
                            id="emailTo"
                            wire:model.live="emailTo"
                            value="{{ $emailTo }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('emailTo') border-red-500 @enderror"
                            placeholder="client@exemple.com"
                        >
                        @error('emailTo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="p-3 bg-blue-50 rounded-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-blue-700">
                                @if($proforma->status === 'draft')
                                    Le statut de la proforma sera automatiquement mis à jour en "Envoyée".
                                @else
                                    Un rappel sera envoyé au client.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </x-ui.alpine-modal-body>

            <div class="flex-shrink-0 flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button type="button" @click="showModal = false"
                    class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                    Annuler
                </button>
                <button type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition">
                    <svg wire:loading.remove wire:target="sendByEmail" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <svg wire:loading wire:target="sendByEmail" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="sendByEmail">Envoyer</span>
                    <span wire:loading wire:target="sendByEmail">Envoi en cours...</span>
                </button>
            </div>
        </form>
    </x-ui.alpine-modal>
</div>
</div>
