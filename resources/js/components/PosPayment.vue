<template>
    <div class="px-4 py-3 bg-white border-t-2 border-gray-300 space-y-3">
        <!-- Payment Method -->
        <div>
            <label class="block text-xs font-bold text-gray-700 mb-2">Mode de paiement</label>
            <div class="grid grid-cols-2 gap-2">
                <button @click="paymentMethod = 'cash'"
                    :class="[
                        'px-3 py-2 rounded-lg font-semibold text-sm transition-all',
                        paymentMethod === 'cash' 
                            ? 'bg-green-500 text-white shadow-lg' 
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]">
                    💵 Espèces
                </button>
                <button @click="paymentMethod = 'card'"
                    :class="[
                        'px-3 py-2 rounded-lg font-semibold text-sm transition-all',
                        paymentMethod === 'card' 
                            ? 'bg-blue-500 text-white shadow-lg' 
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    ]">
                    💳 Carte
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-2">
            <button @click="handleProcessSale"
                :disabled="store.isEmpty || store.isProcessing"
                :class="[
                    'w-full py-3 rounded-xl font-bold text-white transition-all',
                    store.isEmpty || store.isProcessing
                        ? 'bg-gray-300 cursor-not-allowed'
                        : 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 shadow-lg'
                ]">
                <svg v-if="!store.isProcessing" class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="animate-spin inline-block w-5 h-5 border-2 border-white border-t-transparent rounded-full mr-2" v-if="store.isProcessing"></span>
                {{ store.isProcessing ? 'EN COURS...' : 'VALIDER' }}
            </button>

            <button @click="handlePrintReceipt"
                :disabled="store.isEmpty"
                :class="[
                    'w-full py-2 rounded-xl font-semibold transition-all',
                    store.isEmpty
                        ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                        : 'bg-gradient-to-r from-purple-100 to-pink-100 text-purple-700 hover:from-purple-200 hover:to-pink-200'
                ]">
                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                IMPRIMER
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { usePosStore } from '../stores/posStore'

const emit = defineEmits(['sale-completed', 'show-toast'])

const store = usePosStore()
const paymentMethod = ref('cash')

const handleProcessSale = async () => {
    const result = await store.processSale(paymentMethod.value)
    
    if (result.success) {
        emit('show-toast', {
            type: 'success',
            message: 'Vente enregistrée avec succès!'
        })
        emit('sale-completed', result.sale)
    } else {
        emit('show-toast', {
            type: 'error',
            message: result.error || 'Erreur lors de la vente'
        })
    }
}

const handlePrintReceipt = () => {
    emit('show-toast', {
        type: 'info',
        message: 'Impression du reçu...'
    })
    // TODO: Implement receipt printing
}
</script>
