import { defineStore } from 'pinia'
import axios from 'axios'

export const usePosStore = defineStore('pos', {
    state: () => ({
        cart: {},
        selectedClientId: null,
        isProcessing: false,
        globalDiscount: 0,
        globalTax: 0,
    }),

    getters: {
        cartItems: (state) => {
            return Object.entries(state.cart).map(([key, item]) => ({
                key,
                ...item
            }))
        },

        itemCount: (state) => {
            return Object.keys(state.cart).length
        },

        totalQuantity: (state) => {
            return Object.values(state.cart).reduce((sum, item) => sum + item.quantity, 0)
        },

        subtotal: (state) => {
            return Object.values(state.cart).reduce((sum, item) => {
                return sum + (item.price * item.quantity)
            }, 0)
        },

        // La remise est uniquement la remise globale
        // La négociation de prix est déjà reflétée dans le subtotal
        discount: (state) => {
            return state.globalDiscount
        },

        tax: (state) => {
            return state.globalTax
        },

        total() {
            return this.subtotal - this.discount + this.tax
        },

        isEmpty: (state) => {
            return Object.keys(state.cart).length === 0
        }
    },

    actions: {
        addItem(variant) {
            console.log('[Pinia Store] addItem called with variant:', variant)
            console.log('[Pinia Store] variant.product:', variant.product)
            console.log('[Pinia Store] variant.product.max_discount_amount:', variant.product?.max_discount_amount)

            const key = `variant_${variant.id}`

            if (this.cart[key]) {
                // Forcer la réactivité en créant un nouveau panier
                const newCart = { ...this.cart }
                newCart[key] = {
                    ...this.cart[key],
                    quantity: this.cart[key].quantity + 1
                }
                this.cart = newCart
                console.log('[Pinia Store] Item quantity incremented:', this.cart[key])
            } else {
                // Support multiple data structures (direct properties or nested in product)
                const productData = variant.product || {}
                const productName = variant.product_name || productData.name || variant.name || 'Produit sans nom'
                const price = parseFloat(variant.price || productData.price || variant.selling_price || 0)
                const productId = variant.product_id || productData.id || null
                const maxDiscount = parseFloat(productData.max_discount_amount || variant.max_discount_amount || 0)

                console.log('[Pinia Store] maxDiscount extracted:', maxDiscount, 'from productData:', productData)

                this.cart[key] = {
                    variant_id: variant.id,
                    product_id: productId,
                    product_name: productName,
                    variant_size: variant.size || variant.variant_size || null,
                    variant_color: variant.color || variant.variant_color || null,
                    price: price,
                    original_price: parseFloat(variant.original_price || price),
                    max_discount_amount: maxDiscount,
                    quantity: 1,
                    stock: variant.stock_quantity || variant.stock || 999
                }
                console.log('[Pinia Store] New item added to cart:', this.cart[key])
            }

            this.saveToSession()
        },

        removeItem(key) {
            delete this.cart[key]
            this.saveToSession()
        },

        updateQuantity(key, quantity) {
            if (this.cart[key]) {
                const newQty = Math.max(1, Math.min(quantity, this.cart[key].stock))
                const newCart = { ...this.cart }
                newCart[key] = {
                    ...this.cart[key],
                    quantity: newQty
                }
                this.cart = newCart
                this.saveToSession()
            }
        },

        incrementQuantity(key) {
            if (this.cart[key] && this.cart[key].quantity < this.cart[key].stock) {
                const newCart = { ...this.cart }
                newCart[key] = {
                    ...this.cart[key],
                    quantity: this.cart[key].quantity + 1
                }
                this.cart = newCart
                this.saveToSession()
            }
        },

        decrementQuantity(key) {
            if (this.cart[key]) {
                if (this.cart[key].quantity > 1) {
                    const newCart = { ...this.cart }
                    newCart[key] = {
                        ...this.cart[key],
                        quantity: this.cart[key].quantity - 1
                    }
                    this.cart = newCart
                    this.saveToSession()
                } else {
                    this.removeItem(key)
                }
            }
        },

        updatePrice(key, newPrice) {
            console.log('[Pinia Store] updatePrice called with:', { key, newPrice, cartExists: !!this.cart[key] })

            if (this.cart[key]) {
                const parsedPrice = parseFloat(newPrice)
                const maxDiscountAmount = this.cart[key].max_discount_amount || 0
                const originalPrice = this.cart[key].original_price

                // Le prix minimum = prix original - remise max autorisée
                const minPrice = originalPrice - maxDiscountAmount
                const maxPrice = originalPrice

                // Validation du prix - afficher toast si rejeté
                if (parsedPrice < minPrice) {
                    console.warn('[Pinia Store] Prix trop bas:', parsedPrice, 'min:', minPrice)
                    // Afficher un toast d'erreur
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').show(`Remise max autorisée : ${maxDiscountAmount.toFixed(2)}`, 'error')
                    }
                    return false
                }

                if (parsedPrice > maxPrice) {
                    console.warn('[Pinia Store] Prix trop haut:', parsedPrice, 'max:', maxPrice)
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').show(`Prix maximum : ${maxPrice.toFixed(2)}`, 'error')
                    }
                    return false
                }

                const finalPrice = parsedPrice

                console.log('[Pinia Store] updatePrice calculation:', {
                    key,
                    oldPrice: this.cart[key].price,
                    requestedPrice: newPrice,
                    finalPrice: finalPrice,
                    minPrice,
                    maxPrice,
                    max_discount_amount: maxDiscountAmount
                })

                // Créer un nouveau panier pour forcer la réactivité
                const newCart = { ...this.cart }
                newCart[key] = {
                    ...this.cart[key],
                    price: finalPrice
                }
                this.cart = newCart

                console.log('[Pinia Store] After update, cart[key].price:', this.cart[key].price)

                this.saveToSession()
                return true
            } else {
                console.error('[Pinia Store] updatePrice failed: key not found:', key)
                return false
            }
        },

        resetPrice(key) {
            if (this.cart[key]) {
                console.log('[Pinia Store] resetPrice:', key, 'to', this.cart[key].original_price)

                // Créer un nouveau panier pour forcer la réactivité
                const newCart = { ...this.cart }
                newCart[key] = {
                    ...this.cart[key],
                    price: this.cart[key].original_price
                }
                this.cart = newCart

                this.saveToSession()
            }
        },

        setGlobalDiscount(amount) {
            this.globalDiscount = Math.max(0, parseFloat(amount) || 0)
            this.saveToSession()
        },

        setGlobalTax(amount) {
            this.globalTax = Math.max(0, parseFloat(amount) || 0)
            this.saveToSession()
        },

        clear() {
            this.cart = {}
            this.selectedClientId = null
            this.globalDiscount = 0
            this.globalTax = 0
            this.saveToSession()
        },

        saveToSession() {
            sessionStorage.setItem('pos_cart', JSON.stringify(this.cart))
            sessionStorage.setItem('pos_client', this.selectedClientId || '')
            sessionStorage.setItem('pos_global_discount', this.globalDiscount)
            sessionStorage.setItem('pos_global_tax', this.globalTax)
        },

        loadFromSession() {
            try {
                const savedCart = sessionStorage.getItem('pos_cart')
                const savedClient = sessionStorage.getItem('pos_client')
                const savedDiscount = sessionStorage.getItem('pos_global_discount')
                const savedTax = sessionStorage.getItem('pos_global_tax')

                if (savedCart) {
                    const parsedCart = JSON.parse(savedCart)
                    // Migration: s'assurer que max_discount_amount existe pour les anciens articles
                    Object.keys(parsedCart).forEach(key => {
                        if (parsedCart[key] && parsedCart[key].max_discount_amount === undefined) {
                            parsedCart[key].max_discount_amount = 0
                            console.warn('[POS Store] Migration: max_discount_amount ajouté pour', key)
                        }
                    })
                    this.cart = parsedCart
                }

                if (savedClient) {
                    this.selectedClientId = savedClient || null
                }

                if (savedDiscount) {
                    this.globalDiscount = parseFloat(savedDiscount) || 0
                }

                if (savedTax) {
                    this.globalTax = parseFloat(savedTax) || 0
                }
            } catch (error) {
                console.error('[POS Store] Erreur chargement session:', error)
            }
        },

        async processSale(paymentMethod = 'cash') {
            if (this.isEmpty || this.isProcessing) {
                return { success: false, error: 'Panier vide ou traitement en cours' }
            }

            this.isProcessing = true

            try {
                // Formater les items pour l'API
                const formattedItems = this.cartItems.map(item => {
                    console.log('[POS Store] Formatting item for sale:', {
                        product_name: item.product_name,
                        original_price: item.original_price,
                        current_price: item.price,
                        quantity: item.quantity
                    })

                    return {
                        variant_id: item.variant_id,
                        product_id: item.product_id,
                        quantity: item.quantity,
                        price: item.price,
                        unit_price: item.price  // Ajouter unit_price pour le backend
                    }
                })

                const payload = {
                    items: formattedItems,
                    client_id: this.selectedClientId,
                    payment_method: paymentMethod,
                    paid_amount: this.total,
                    discount: this.globalDiscount,
                    tax: this.globalTax,
                }

                console.log('[POS Store] Envoi de la vente:', payload)

                const response = await axios.post('/pos/checkout', payload)

                console.log('[POS Store] Réponse API:', response.data)

                if (response.data.success) {
                    const saleData = response.data.data
                    this.clear()
                    // Émettre un événement pour rafraîchir les stats Livewire
                    window.dispatchEvent(new CustomEvent('sale-completed', {
                        detail: { sale: saleData }
                    }))
                    return { success: true, sale: saleData }
                }

                return { success: false, error: response.data.message }
            } catch (error) {
                console.error('[POS Store] Erreur vente:', error)
                return {
                    success: false,
                    error: error.response?.data?.message || error.message || 'Erreur lors de la vente'
                }
            } finally {
                this.isProcessing = false
            }
        }
    }
})
