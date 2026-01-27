<!-- Composant Toast Alpine.js - Centré en haut -->
<div class="fixed top-20 left-1/2 transform -translate-x-1/2 z-[9999] space-y-2 pointer-events-none"
    x-data="{
        messages: [],
        init() {
            // Polling toutes les 100ms pour synchroniser avec le store
            setInterval(() => {
                const store = window.Alpine?.store('toast');
                if (store && store.messages) {
                    this.messages = [...store.messages];
                }
            }, 100);
        }
    }"
    x-cloak
    style="max-width: 420px; min-width: 320px;">
    <template x-for="toastItem in messages" :key="toastItem.id">
        <div x-show="true"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="-translate-y-4 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            :class="{
                'bg-green-600 border-green-400': toastItem.type === 'success',
                'bg-red-600 border-red-400': toastItem.type === 'error',
                'bg-blue-600 border-blue-400': toastItem.type === 'info',
                'bg-amber-500 border-amber-400': toastItem.type === 'warning'
            }"
            class="px-5 py-3 rounded-lg shadow-xl text-white flex items-center gap-3 pointer-events-auto border-l-4">

            <!-- Icône -->
            <div class="flex-shrink-0">
                <svg x-show="toastItem.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <svg x-show="toastItem.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <svg x-show="toastItem.type === 'info'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="toastItem.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <!-- Message -->
            <span class="flex-1 font-medium text-sm" x-text="toastItem.message"></span>

            <!-- Bouton fermer -->
            <button @click="$store.toast?.remove(toastItem.id)"
                class="flex-shrink-0 hover:bg-white/20 rounded p-1 transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>
    </template>
</div>
