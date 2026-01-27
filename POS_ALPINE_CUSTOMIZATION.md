# 🔧 POS Alpine - Guide de Personnalisation

## Ajouter une nouvelle fonctionnalité au panier

### Exemple : Ajouter une note à un article

#### 1. Modifier le store Alpine (`posCart.js`)

```javascript
// Dans resources/js/alpine/stores/posCart.js

// Ajouter une méthode
addNoteToItem(index, note) {
    if (this.cart[index]) {
        this.cart[index].note = note;
        this.saveToSession();
    }
},

// Modifier addItem pour inclure la note
addItem(variant) {
    // ... code existant ...
    this.cart.push({
        variant_id: variant.id,
        product_id: variant.product.id,
        product_name: variant.product.name,
        variant_size: variant.size || null,
        variant_color: variant.color || null,
        price: parseFloat(variant.product.price),
        original_price: parseFloat(variant.product.price),
        max_discount_amount: parseFloat(variant.product.max_discount_amount || 0),
        quantity: 1,
        stock: variant.stock_quantity,
        note: '' // ← Nouvelle propriété
    });
    // ... code existant ...
}
```

#### 2. Modifier la vue du panier

```html
<!-- Dans pos-cart-alpine.blade.php -->

<!-- Ajouter après le champ de prix unitaire -->
<div class="mt-2" x-data="{ editingNote: false }">
    <div class="flex items-center justify-between">
        <label class="text-xs text-gray-600">Note :</label>
        <button @click="editingNote = !editingNote" 
            class="text-xs text-blue-600 hover:text-blue-700">
            <span x-show="!editingNote">✏️ Ajouter</span>
            <span x-show="editingNote">✓ OK</span>
        </button>
    </div>
    <div x-show="editingNote" x-collapse>
        <textarea
            :value="item.note || ''"
            @change="$store.posCart.addNoteToItem(index, $event.target.value)"
            rows="2"
            placeholder="Ex: Sans oignon, bien cuit..."
            class="w-full text-sm border border-gray-300 rounded px-2 py-1 mt-1">
        </textarea>
    </div>
    <p x-show="item.note && !editingNote" 
        class="text-xs text-gray-600 italic mt-1"
        x-text="item.note">
    </p>
</div>
```

#### 3. Modifier le backend pour sauvegarder la note

```php
// Dans CashRegisterAlpine.php, méthode processSale()

// Préparer les données
$cartItems = collect($saleData['cart'])->map(function($item) {
    return [
        'product_variant_id' => $item['variant_id'],
        'quantity' => $item['quantity'],
        'unit_price' => $item['price'],
        'note' => $item['note'] ?? null, // ← Inclure la note
    ];
})->toArray();
```

## Ajouter un nouveau mode de paiement

### Exemple : Paiement par chèque

#### 1. Modifier la vue de paiement

```html
<!-- Dans pos-payment-alpine.blade.php -->

<!-- Ajouter un bouton -->
<button @click="paymentMethod = 'check'"
    :class="paymentMethod === 'check' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-700 border border-gray-300'"
    class="px-3 py-2 rounded-lg font-medium text-sm hover:shadow transition">
    📝 Chèque
</button>

<!-- Ajouter les champs pour le chèque -->
<div x-show="paymentMethod === 'check'" class="mb-4">
    <label class="block text-sm font-bold text-gray-700 mb-2">Numéro du chèque</label>
    <input type="text"
        x-model="checkNumber"
        placeholder="Ex: 1234567890"
        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
    
    <label class="block text-sm font-bold text-gray-700 mb-2 mt-2">Banque</label>
    <input type="text"
        x-model="checkBank"
        placeholder="Ex: Banque Centrale"
        class="w-full border-2 border-gray-300 rounded-lg px-4 py-2">
</div>
```

#### 2. Modifier le script posPayment

```javascript
// Dans le script de pos-payment-alpine.blade.php

function posPayment() {
    return {
        paymentMethod: 'cash',
        amountReceived: 0,
        change: 0,
        checkNumber: '',  // ← Nouveau
        checkBank: '',    // ← Nouveau

        // ... autres méthodes ...

        async validateSale() {
            if (!this.canValidate()) return;

            const cart = window.Alpine.store('posCart');
            
            // Préparer les données de paiement
            const paymentData = {
                method: this.paymentMethod,
                amount_received: this.paymentMethod === 'cash' ? this.amountReceived : cart.total,
                change: this.paymentMethod === 'cash' ? this.change : 0,
                // ← Ajouter les données du chèque
                check_number: this.paymentMethod === 'check' ? this.checkNumber : null,
                check_bank: this.paymentMethod === 'check' ? this.checkBank : null,
            };

            // ... reste du code ...
        }
    }
}
```

## Ajouter des statistiques temps réel

### Exemple : Afficher le total vendu aujourd'hui dans le header

#### 1. Créer un nouveau store Alpine

```javascript
// resources/js/alpine/stores/posStats.js

export default {
    todaySales: 0,
    todayRevenue: 0,
    todayTransactions: 0,
    
    async refresh() {
        try {
            // Appel API pour récupérer les stats
            const response = await fetch('/api/pos/today-stats');
            const data = await response.json();
            
            this.todaySales = data.sales;
            this.todayRevenue = data.revenue;
            this.todayTransactions = data.transactions;
        } catch (error) {
            console.error('Erreur refresh stats:', error);
        }
    },
    
    incrementSale(amount) {
        this.todaySales++;
        this.todayRevenue += amount;
        this.todayTransactions++;
    }
};
```

#### 2. Enregistrer le store

```javascript
// Dans resources/js/app.js

import posStatsStore from './alpine/stores/posStats.js';

document.addEventListener('alpine:init', () => {
    Alpine.store('posCart', posCartStore);
    Alpine.store('toast', toastStore);
    Alpine.store('posStats', posStatsStore); // ← Ajouter
});
```

#### 3. Utiliser dans la vue

```html
<!-- Dans cash-register-alpine.blade.php -->

<div x-data x-init="$store.posStats.refresh()" class="...">
    <span x-text="$store.posStats.todayRevenue.toFixed(2) + ' {{ current_currency() }}'"></span>
</div>
```

#### 4. Mettre à jour après une vente

```javascript
// Dans le store posCart, méthode validateAndSubmit()

if (result.success) {
    this.cart = [];
    this.selectedClientId = null;
    this.saveToSession();
    
    // ← Mettre à jour les stats
    window.Alpine.store('posStats').incrementSale(this.total);
    
    window.Alpine.store('toast').success('Vente enregistrée avec succès');
    
    return { success: true, ... };
}
```

## Ajouter la persistance locale avec IndexedDB

### Pour mode hors-ligne avancé

```javascript
// resources/js/alpine/stores/posCart.js

// Ajouter IndexedDB
async saveToIndexedDB() {
    try {
        const db = await openDB('pos-cache', 1, {
            upgrade(db) {
                db.createObjectStore('cart');
            }
        });
        
        await db.put('cart', this.cart, 'current');
    } catch (e) {
        console.warn('IndexedDB non disponible:', e);
    }
},

async loadFromIndexedDB() {
    try {
        const db = await openDB('pos-cache', 1);
        const cart = await db.get('cart', 'current');
        if (cart) {
            this.cart = cart;
        }
    } catch (e) {
        console.warn('Erreur IndexedDB:', e);
    }
}
```

## Ajouter des animations personnalisées

### Exemple : Animation lors de l'ajout au panier

```html
<!-- Dans la vue principale -->

<div x-data="{ pulse: false }">
    <button @click="addProduct(product); pulse = true; setTimeout(() => pulse = false, 500)"
        :class="{ 'animate-pulse': pulse }"
        class="...">
        Ajouter
    </button>
</div>
```

### CSS personnalisé

```css
/* Dans votre fichier CSS */

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.cart-item-enter {
    animation: slideInRight 0.3s ease-out;
}
```

## Ajouter la validation en temps réel

### Exemple : Vérifier le stock en direct

```javascript
// Dans le store posCart

async validateStockRealtime(variantId) {
    try {
        const response = await fetch(`/api/products/variants/${variantId}/stock`);
        const data = await response.json();
        
        // Mettre à jour le stock dans le panier
        const item = this.cart.find(i => i.variant_id === variantId);
        if (item) {
            item.stock = data.stock_quantity;
        }
        
        return data.stock_quantity;
    } catch (error) {
        console.error('Erreur validation stock:', error);
        return 0;
    }
},

// Ajouter un polling automatique
initStockPolling() {
    setInterval(() => {
        this.cart.forEach(item => {
            this.validateStockRealtime(item.variant_id);
        });
    }, 30000); // Toutes les 30 secondes
}
```

## Ajouter des raccourcis clavier personnalisés

### Exemple : Scan rapide avec Enter

```javascript
// Dans cash-register-alpine.blade.php

function cashRegisterAlpine() {
    return {
        // ... autres propriétés ...
        
        handleKeyboard(event) {
            // Raccourcis existants (F2, F4, F9)
            // ...
            
            // ← Nouveau : Scan rapide avec Enter
            if (event.key === 'Enter' && this.searchQuery) {
                event.preventDefault();
                const products = this.filteredProducts;
                if (products.length === 1) {
                    this.addProductToCart(products[0]);
                    this.searchQuery = '';
                }
            }
            
            // ← Nouveau : Navigation avec flèches
            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                // Implémenter la navigation dans la liste
            }
        }
    }
}
```

## Débogage et développement

### Activer le mode debug

```javascript
// Dans resources/js/alpine/stores/posCart.js

// Ajouter au début du fichier
const DEBUG = import.meta.env.DEV;

// Utiliser dans les méthodes
addItem(variant) {
    if (DEBUG) {
        console.log('[POS Cart] Adding item:', variant);
    }
    // ... code ...
}
```

### Outils de développement

```javascript
// Console du navigateur

// Activer les logs Alpine
Alpine.debug = true;

// Observer les changements du store
Alpine.effect(() => {
    console.log('Cart changed:', Alpine.store('posCart').cart);
});

// Simuler une vente
Alpine.store('posCart').cart = [
    {
        variant_id: 1,
        product_name: 'Test',
        price: 100,
        quantity: 2,
        stock: 10
    }
];
```

## Tests automatisés

### Exemple avec Jest

```javascript
// tests/js/posCart.test.js

import { beforeEach, describe, expect, test } from '@jest/globals';
import Alpine from 'alpinejs';
import posCartStore from '../../resources/js/alpine/stores/posCart.js';

describe('POS Cart Store', () => {
    beforeEach(() => {
        Alpine.store('posCart', posCartStore);
        Alpine.store('posCart').cart = [];
    });
    
    test('adds item to cart', () => {
        const variant = {
            id: 1,
            product: { id: 1, name: 'Test Product', price: 100 },
            stock_quantity: 10
        };
        
        Alpine.store('posCart').addItem(variant);
        
        expect(Alpine.store('posCart').cart.length).toBe(1);
        expect(Alpine.store('posCart').total).toBe(100);
    });
    
    test('calculates total correctly', () => {
        Alpine.store('posCart').cart = [
            { price: 100, quantity: 2 },
            { price: 50, quantity: 3 }
        ];
        
        expect(Alpine.store('posCart').subtotal).toBe(350);
    });
});
```

---

**Ces exemples montrent comment étendre facilement la solution Alpine.js !**

Pour plus d'informations :
- Documentation Alpine.js : https://alpinejs.dev
- Guide principal : `POS_ALPINE_GUIDE.md`
