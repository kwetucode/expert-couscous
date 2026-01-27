/**
 * Store Alpine.js pour la gestion du panier POS
 * Gère tout l'état et les opérations du panier côté client
 */

export default {
    // État du panier - STRUCTURE IDENTIQUE À PosCart
    // Utilise un objet avec clés 'variant_X' au lieu d'un tableau
    cart: {},
    selectedClientId: null,
    quickSaleMode: true,
    isProcessing: false,

    // Cache des produits
    productsCache: new Map(),

    // Getters calculés - STRUCTURE IDENTIQUE À PosCart
    get subtotal() {
        return Object.values(this.cart).reduce((sum, item) =>
            sum + (item.price * item.quantity), 0
        );
    },

    get total() {
        return this.subtotal - this.discount + this.tax;
    },

    get discount() {
        return Object.values(this.cart).reduce((sum, item) =>
            sum + ((item.original_price - item.price) * item.quantity), 0
        );
    },

    get tax() {
        // Calculer la TVA si applicable
        return 0;
    },

    get itemCount() {
        return Object.values(this.cart).reduce((sum, item) => sum + item.quantity, 0);
    },

    get isEmpty() {
        return Object.keys(this.cart).length === 0;
    },

    // Helper pour obtenir les items sous forme de tableau
    get items() {
        return Object.values(this.cart);
    },

    /**
     * Initialise le store avec des données
     */
    init(data = {}) {
        console.log('[POS Cart Store] init() appelé avec:', data);

        // Charger depuis sessionStorage EN PREMIER
        this.loadFromSession();

        // Puis appliquer les données fournies si elles existent (sans écraser le panier)
        if (data.cart && Object.keys(data.cart).length > 0) {
            this.cart = data.cart;
            console.log('[POS Cart Store] Panier écrasé par data.cart:', this.cart);
        }
        if (data.selectedClientId !== undefined && data.selectedClientId !== null) {
            this.selectedClientId = data.selectedClientId;
            console.log('[POS Cart Store] Client sélectionné:', this.selectedClientId);
        }

        console.log('[POS Cart Store] Panier après init:', Object.keys(this.cart).length, 'items');
    },

    /**
     * Ajoute un produit au panier - STRUCTURE IDENTIQUE À PosCart/CartService
     */
    addItem(variant) {
        console.log('[POS Cart Store] addItem appelé:', variant);

        // Vérifier le stock
        if (variant.stock_quantity <= 0) {
            Alpine.store('toast').show('Produit en rupture de stock', 'error');
            return;
        }

        const key = `variant_${variant.id}`;

        // Créer une copie du panier pour forcer la réactivité Alpine
        const newCart = { ...this.cart };

        if (newCart[key]) {
            // Produit déjà dans le panier
            const item = newCart[key];

            if (item.quantity < variant.stock_quantity) {
                item.quantity++;
                Alpine.store('toast').show('Quantité mise à jour', 'success');
            } else {
                Alpine.store('toast').show('Stock insuffisant', 'error');
                return;
            }
        } else {
            // Nouveau produit - STRUCTURE IDENTIQUE À CartService::addItem()
            newCart[key] = {
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
            };

            Alpine.store('toast').show('Produit ajouté au panier', 'success');
        }

        // Remplacer le panier pour déclencher la réactivité
        this.cart = newCart;
        console.log('[POS Cart Store] Panier après ajout:', this.cart);
        this.saveToSession();
    },

    /**
     * Retire un produit du panier - STRUCTURE IDENTIQUE À PosCart
     */
    removeItem(key) {
        if (this.cart[key]) {
            const item = this.cart[key];
            // Créer une copie pour forcer la réactivité
            const newCart = { ...this.cart };
            delete newCart[key];
            this.cart = newCart;
            Alpine.store('toast').show(`${item.product_name} retiré`, 'info');
            this.saveToSession();
        }
    },

    /**
     * Met à jour la quantité d'un article - STRUCTURE IDENTIQUE À CartService
     */
    updateQuantity(key, quantity) {
        const item = this.cart[key];

        if (!item) return;

        if (quantity <= 0) {
            this.removeItem(key);
            return;
        }

        if (quantity > item.stock) {
            Alpine.store('toast').show(
                `Stock disponible: ${item.stock}`,
                'error'
            );
            return;
        }

        // Créer une copie pour forcer la réactivité
        const newCart = { ...this.cart };
        newCart[key] = { ...item, quantity };
        this.cart = newCart;
        this.saveToSession();
    },

    /**
     * Incrémente la quantité - STRUCTURE IDENTIQUE À PosCart
     */
    incrementQuantity(key) {
        const item = this.cart[key];
        if (!item) return;

        if (item.quantity < item.stock) {
            // Créer une copie pour forcer la réactivité
            const newCart = { ...this.cart };
            newCart[key] = { ...item, quantity: item.quantity + 1 };
            this.cart = newCart;
            this.saveToSession();
        } else {
            Alpine.store('toast').show('Stock insuffisant', 'error');
        }
    },

    /**
     * Décrémente la quantité - STRUCTURE IDENTIQUE À PosCart
     */
    decrementQuantity(key) {
        const item = this.cart[key];
        if (!item) return;

        if (item.quantity > 1) {
            // Créer une copie pour forcer la réactivité
            const newCart = { ...this.cart };
            newCart[key] = { ...item, quantity: item.quantity - 1 };
            this.cart = newCart;
            this.saveToSession();
        } else {
            this.removeItem(key);
        }
    },

    /**
     * Met à jour le prix d'un article - STRUCTURE IDENTIQUE À CartService
     */
    updatePrice(key, newPrice) {
        const item = this.cart[key];
        if (!item) return;

        const price = parseFloat(newPrice);

        if (isNaN(price)) {
            Alpine.store('toast').show('Prix invalide', 'error');
            return;
        }

        const maxDiscount = item.max_discount_amount || 0;
        const originalPrice = item.original_price || 0;
        const minPrice = originalPrice - maxDiscount;

        console.log('[POS Cart] updatePrice validation:', { price, minPrice, maxDiscount, originalPrice });

        if (price < minPrice) {
            Alpine.store('toast').show(
                `Remise max autorisée : ${maxDiscount.toFixed(2)}`,
                'error'
            );
            return;
        }

        if (price > originalPrice) {
            Alpine.store('toast').show(
                `Prix maximum : ${originalPrice.toFixed(2)}`,
                'error'
            );
            return;
        }

        // Créer une copie pour forcer la réactivité
        const newCart = { ...this.cart };
        newCart[key] = { ...item, price };
        this.cart = newCart;
        this.saveToSession();
    },

    /**
     * Vide le panier - STRUCTURE IDENTIQUE À PosCart
     */
    clear() {
        if (this.isEmpty) {
            return;
        }

        if (confirm('Vider le panier ?')) {
            this.cart = {};
            this.selectedClientId = null;
            this.saveToSession();
            window.Alpine.store('toast').show('Panier vidé', 'info');
        }
    },

    /**
     * Sélectionne un client
     */
    selectClient(clientId) {
        this.selectedClientId = clientId;
        this.saveToSession();
    },

    /**
     * Valide et soumet la vente
     */
    async validateAndSubmit(paymentData, componentId) {
        if (this.cart.length === 0) {
            window.Alpine.store('toast').show('Le panier est vide', 'error');
            return false;
        }

        if (!this.selectedClientId) {
            window.Alpine.store('toast').show('Veuillez sélectionner un client', 'error');
            return false;
        }

        this.isProcessing = true;

        try {
            // Appel Livewire pour sauvegarder la vente
            const component = window.Livewire.find(componentId);

            if (!component) {
                throw new Error('Composant Livewire non trouvé');
            }

            const result = await component.call('processSale', {
                cart: this.cart,
                client_id: this.selectedClientId,
                payment: paymentData
            });

            if (result.success) {
                this.cart = [];
                this.selectedClientId = null;
                this.saveToSession();
                window.Alpine.store('toast').show('Vente enregistrée avec succès', 'success');

                // Retourner les infos de la vente pour affichage du reçu
                return {
                    success: true,
                    saleId: result.sale_id,
                    receiptUrl: result.receipt_url
                };
            } else {
                window.Alpine.store('toast').show(result.message || 'Erreur', 'error');
                return { success: false };
            }
        } catch (error) {
            console.error('Erreur lors de la validation:', error);
            window.Alpine.store('toast').show('Erreur serveur', 'error');
            return { success: false };
        } finally {
            this.isProcessing = false;
        }
    },

    /**
     * Sauvegarde dans sessionStorage
     */
    saveToSession() {
        try {
            sessionStorage.setItem('pos_cart', JSON.stringify(this.cart));
            sessionStorage.setItem('pos_client', String(this.selectedClientId || ''));
        } catch (e) {
            console.warn('Session storage non disponible:', e);
        }
    },

    /**
     * Charge depuis sessionStorage
     */
    loadFromSession() {
        try {
            const savedCart = sessionStorage.getItem('pos_cart');
            const savedClient = sessionStorage.getItem('pos_client');

            console.log('[POS Cart Store] loadFromSession - savedCart:', savedCart);

            if (savedCart) {
                const parsed = JSON.parse(savedCart);
                // Migration automatique: si c'est un tableau (ancienne structure), le convertir en objet
                if (Array.isArray(parsed)) {
                    this.cart = {};
                    console.warn('[POS Alpine] Migration du panier tableau → objet');
                } else if (typeof parsed === 'object' && parsed !== null) {
                    // IMPORTANT: Créer un nouveau objet pour forcer la réactivité Alpine
                    this.cart = { ...parsed };
                    console.log('[POS Cart Store] Panier chargé depuis session:', this.cart, 'Items:', Object.keys(this.cart).length);
                }
            }

            if (savedClient && savedClient !== '') {
                this.selectedClientId = parseInt(savedClient);
            }
        } catch (e) {
            console.warn('Erreur lors du chargement depuis la session:', e);
        }
    }
};
