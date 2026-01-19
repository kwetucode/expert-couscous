{{--
    Confirmation Modal for Positive Actions
    Pure Alpine.js version - does NOT use Livewire entangle
    Use this for Alpine-controlled modals within x-data scope

    Usage: <x-confirmation-modal
        show="showConfirmModal"
        title="Confirmer l'action"
        icon-color="green"
        on-confirm="$wire.approve(); showConfirmModal = false"
        on-cancel="showConfirmModal = false"
    />
--}}
@props([
    'show' => 'showConfirmModal',
    'title' => 'Confirmer l\'action',
    'iconColor' => 'green',
    'onConfirm' => '',
    'onCancel' => '',
    'confirmText' => 'Confirmer',
    'cancelText' => 'Annuler',
])

@php
$colorClasses = [
    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'button' => 'bg-green-600 hover:bg-green-700', 'ring' => 'focus:ring-green-500'],
    'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'button' => 'bg-blue-600 hover:bg-blue-700', 'ring' => 'focus:ring-blue-500'],
    'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'button' => 'bg-indigo-600 hover:bg-indigo-700', 'ring' => 'focus:ring-indigo-500'],
];
$colors = $colorClasses[$iconColor] ?? $colorClasses['green'];
@endphp

<div x-show="{{ $show }}" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="{{ $show }}"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @if($onCancel)
            @click="{{ $onCancel }}"
        @else
            @click="{{ $show }} = false"
        @endif
        class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

    <!-- Modal -->
    <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div x-show="{{ $show }}"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            @click.stop
            @keydown.escape.window="{{ $show }} = false"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto p-6">

            <!-- Icon -->
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full {{ $colors['bg'] }} mb-5">
                <svg class="h-7 w-7 {{ $colors['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Content -->
            <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $title }}</h3>
                <div class="text-sm text-gray-600">
                    {{ $slot }}
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 justify-center mt-6">
                <button type="button"
                    @if($onCancel)
                        @click="{{ $onCancel }}"
                    @else
                        @click="{{ $show }} = false"
                    @endif
                    class="px-5 py-2.5 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                    {{ $cancelText }}
                </button>
                <button type="button"
                    @if($onConfirm)
                        @click="{{ $onConfirm }}"
                    @endif
                    class="px-5 py-2.5 text-white font-medium rounded-lg {{ $colors['button'] }} transition-colors focus:outline-none focus:ring-2 {{ $colors['ring'] }} focus:ring-offset-2">
                    {{ $confirmText }}
                </button>
            </div>
        </div>
    </div>
</div>
