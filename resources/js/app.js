import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import Chart from 'chart.js/auto';

// Vue.js imports
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PosCart from './components/PosCart.vue';

// Import Alpine stores
import posCartStore from './alpine/stores/posCart.js';
import toastStore from './alpine/stores/toast.js';

console.log('[App] Stores imported:', { posCartStore, toastStore });

// ============================================
// Alpine.js Configuration
// ============================================

// Register Alpine plugins BEFORE making it available
Alpine.plugin(focus);
Alpine.plugin(collapse);

// Register stores IMMEDIATELY before Livewire starts
Alpine.store('posCart', posCartStore);
Alpine.store('toast', toastStore);

console.log('[App] Stores registered BEFORE window.Alpine');
console.log('[App] posCart available:', !!Alpine.store('posCart'));
console.log('[App] toast available:', !!Alpine.store('toast'));

// Make Alpine available globally for Livewire FIRST
// NOTE: Do NOT call Alpine.start() - Livewire 3 handles this automatically
window.Alpine = Alpine;

// Make Chart.js available globally
window.Chart = Chart;

console.log('[App] Alpine available on window');

// ============================================
// Livewire Navigation Events
// ============================================

// Before navigation - cleanup
document.addEventListener('livewire:navigating', () => {
    // Reset body overflow (in case a modal was open)
    document.body.style.overflow = '';

    // Close any open dropdowns or tooltips
    document.querySelectorAll('[x-data]').forEach(el => {
        if (el.__x && el.__x.$data.open !== undefined) {
            el.__x.$data.open = false;
        }
    });
});

// After navigation - initialization
document.addEventListener('livewire:navigated', () => {
    // Refresh CSRF token to prevent 419 errors
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        // Update all hidden CSRF inputs in forms
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            input.value = token;
        });

        // Update axios headers if available
        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    }

    // Ensure body overflow is reset after navigation
    document.body.style.overflow = '';

    // Scroll to top on navigation (optional - comment out if not desired)
    // window.scrollTo({ top: 0, behavior: 'instant' });
});

// ============================================
// Global Alpine Components & Stores
// ============================================

// BACKUP: Also register in alpine:init for safety
document.addEventListener('alpine:init', () => {
    console.log('[Alpine:Init] Event fired - stores should already be registered');
    console.log('[Alpine:Init] posCart exists:', !!Alpine.store('posCart'));
    console.log('[Alpine:Init] toast exists:', !!Alpine.store('toast'));

    // Re-register if missing (shouldn't happen but safety net)
    if (!Alpine.store('posCart')) {
        console.warn('[Alpine:Init] posCart missing, registering now!');
        Alpine.store('posCart', posCartStore);
    }
    if (!Alpine.store('toast')) {
        console.warn('[Alpine:Init] toast missing, registering now!');
        Alpine.store('toast', toastStore);
    }
});

// ============================================
// Vue.js POS Components
// ============================================

// Function to initialize Vue POS components
async function initVuePosComponents() {
    const posCartElement = document.getElementById('vue-pos-cart');
    const posPaymentElement = document.getElementById('vue-pos-payment');

    if (posCartElement || posPaymentElement) {
        // Check if already mounted to avoid double mounting
        if (posCartElement && posCartElement.__vue_app__) {
            console.log('[Vue POS] Cart already mounted, skipping...');
            return;
        }

        const pinia = createPinia();

        if (posCartElement) {
            const clients = JSON.parse(posCartElement.dataset.clients || '[]');
            const currency = posCartElement.dataset.currency || 'USD';

            const cartApp = createApp(PosCart, { clients, currency });
            cartApp.use(pinia);
            cartApp.mount('#vue-pos-cart');
            
            // Expose store globally for Alpine integration
            const { usePosStore } = await import('./stores/posStore.js');
            window.__VUE_POS_STORE__ = usePosStore(pinia);
            console.log('[Vue POS] Store exposed globally:', window.__VUE_POS_STORE__);
        }

        if (posPaymentElement && !posPaymentElement.__vue_app__) {
            const paymentApp = createApp(PosPayment);
            paymentApp.use(pinia);
            paymentApp.mount('#vue-pos-payment');
        }
    }
}

// Initialize Vue components on DOMContentLoaded
document.addEventListener('DOMContentLoaded', initVuePosComponents);

// Re-initialize Vue components after Livewire SPA navigation
document.addEventListener('livewire:navigated', () => {
    console.log('[Vue POS] Livewire navigated, re-initializing...');
    // Small delay to ensure DOM is ready
    setTimeout(initVuePosComponents, 50);
});
