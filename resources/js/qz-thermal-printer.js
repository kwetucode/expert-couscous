/**
 * QZ Tray Thermal Printer Integration
 * Gère l'impression sur imprimantes thermiques via QZ Tray
 */

class ThermalPrinter {
    constructor() {
        this.connected = false;
        this.printerName = null;
        this.qz = null;
        // Configuration papier thermique
        // 58mm = 32 caractères, 80mm = 48 caractères
        this.paperWidth = 32; // Par défaut 58mm
        this.detectPaperWidth();
    }

    /**
     * Détecte la largeur du papier selon l'imprimante
     */
    detectPaperWidth() {
        // Détection basée sur le nom de l'imprimante ou configuration
        const savedWidth = localStorage.getItem('thermal_paper_width');
        if (savedWidth) {
            this.paperWidth = parseInt(savedWidth);
        } else {
            // Par défaut 58mm (32 caractères) - le plus courant
            this.paperWidth = 32;
        }
        console.log('📏 Largeur papier:', this.paperWidth, 'caractères');
    }

    /**
     * Configure la largeur du papier
     * @param {number} width - Largeur en caractères (32 pour 58mm, 48 pour 80mm)
     */
    setPaperWidth(width) {
        this.paperWidth = width;
        localStorage.setItem('thermal_paper_width', width);
        console.log('📏 Largeur papier configurée:', width, 'caractères');
    }

    /**
     * Initialise QZ Tray et se connecte
     */
    async initialize() {
        try {
            // Ne PAS définir de certificat ni de signature
            // Cela permettra à QZ Tray d'utiliser son système de mise en liste blanche
            // L'utilisateur devra autoriser une seule fois via l'interface QZ Tray

            // Connexion à QZ Tray
            if (!qz.websocket.isActive()) {
                await qz.websocket.connect();
                this.connected = true;
                console.log('✅ Connecté à QZ Tray');

                // Trouver l'imprimante par défaut
                await this.findDefaultPrinter();
            }
            return true;
        } catch (error) {
            console.error('❌ Erreur connexion QZ Tray:', error);
            this.showError('QZ Tray non disponible. Installez QZ Tray: https://qz.io/download/');
            return false;
        }
    }

    /**
     * Trouve l'imprimante thermique par défaut
     */
    async findDefaultPrinter() {
        try {
            const printers = await qz.printers.find();
            console.log('🖨️ Imprimantes trouvées:', printers);

            // Vérifier d'abord s'il y a une imprimante configurée dans localStorage
            const configuredPrinter = localStorage.getItem('thermal_printer_name');

            if (configuredPrinter && printers.includes(configuredPrinter)) {
                this.printerName = configuredPrinter;
                console.log('🎯 Imprimante configurée utilisée:', this.printerName);
                return;
            }

            // Sinon, chercher une imprimante thermique (noms courants)
            // Inclut USB, Bluetooth, et imprimantes réseau
            const thermalPrinter = printers.find(p => {
                const name = p.toLowerCase();
                return name.includes('thermal') ||
                    name.includes('pos') ||
                    name.includes('receipt') ||
                    name.includes('tm-') || // Epson TM series
                    name.includes('rp-') || // Star RP series
                    name.includes('xprinter') ||
                    name.includes('bluetooth') || // Détection explicite Bluetooth
                    name.includes('bt-') || // Préfixe Bluetooth commun
                    name.includes('80mm') ||
                    name.includes('58mm') ||
                    name.includes('zj-') || // Imprimantes Zjiang
                    name.includes('goojprt'); // GoojPRT Bluetooth printers
            });

            if (thermalPrinter) {
                this.printerName = thermalPrinter;
                const connectionType = this.detectConnectionType(thermalPrinter);
                console.log(`🎯 Imprimante thermique détectée: ${this.printerName} (${connectionType})`);
            } else if (printers.length > 0) {
                // Prendre la première imprimante disponible
                this.printerName = printers[0];
                console.log('📝 Imprimante par défaut:', this.printerName);
            }
        } catch (error) {
            console.error('❌ Erreur recherche imprimante:', error);
        }
    }

    /**
     * Détecte le type de connexion d'une imprimante
     * @param {string} printerName - Nom de l'imprimante
     * @returns {string} Type de connexion (USB, Bluetooth, Réseau)
     */
    detectConnectionType(printerName) {
        const name = printerName.toLowerCase();
        if (name.includes('bluetooth') || name.includes('bt-')) {
            return '📡 Bluetooth';
        } else if (name.includes('network') || name.includes('ip') || name.includes('lan')) {
            return '🌐 Réseau';
        } else {
            return '🔌 USB';
        }
    }

    /**
     * Imprime un reçu de caisse
     * @param {Object} data - Données du reçu
     */
    async printReceipt(data) {
        try {
            console.log('🔵 printReceipt() appelé avec data:', data);
            console.log('🔵 this.connected:', this.connected);
            console.log('🔵 this.printerName:', this.printerName);

            // Initialiser si pas encore fait
            if (!this.connected) {
                console.log('🔵 Connexion à QZ Tray...');
                const initialized = await this.initialize();
                console.log('🔵 Initialisation:', initialized);
                if (!initialized) {
                    console.error('❌ Échec de l\'initialisation');
                    return;
                }
            }

            if (!this.printerName) {
                console.error('❌ Aucune imprimante configurée');
                this.showError('Aucune imprimante configurée');
                return;
            }

            console.log('🔵 Création de la configuration pour:', this.printerName);
            // Créer la configuration d'impression
            const config = qz.configs.create(this.printerName);

            console.log('🔵 Génération des commandes ESC/POS...');
            // Générer les commandes ESC/POS
            const commands = this.generateESCPOSCommands(data);
            console.log('🔵 Commandes générées:', commands.slice(0, 3)); // Afficher les premières commandes

            console.log('🔵 Envoi à l\'imprimante...');
            // Envoyer à l'imprimante
            await qz.print(config, commands);

            console.log('✅ Impression réussie');
            this.showSuccess('Impression envoyée à l\'imprimante');

        } catch (error) {
            console.error('❌ Erreur impression:', error);
            this.showError('Erreur lors de l\'impression: ' + error.message);
            throw error; // Re-throw pour que la promesse soit rejetée
        }
    }

    /**
     * Génère les commandes ESC/POS pour l'impression thermique
     * @param {Object} data - Données du reçu
     * @returns {Array} Commandes ESC/POS
     */
    generateESCPOSCommands(data) {
        const ESC = '\x1B';
        const GS = '\x1D';
        const width = this.paperWidth;
        const separator = '-'.repeat(width);
        const doubleSeparator = '='.repeat(width);
        
        // Debug: Log des données reçues
        console.log('[QZ Tray] generateESCPOSCommands data:', JSON.stringify(data, null, 2));
        
        // Utiliser la devise fournie dans les données ou CDF par défaut
        const currency = data.currency || 'CDF';

        const commands = [];

        // Initialiser l'imprimante
        commands.push(ESC + '@');

        // Espace initial
        commands.push('\n');

        // === EN-TETE ENTREPRISE ===
        // Lire les informations depuis localStorage
        const companyName = localStorage.getItem('thermal_company_name') || 'VOTRE ENTREPRISE';
        const companyAddress = localStorage.getItem('thermal_company_address') || 'Votre Adresse';
        const companyPhone = localStorage.getItem('thermal_company_phone') || '+243 XXX XXX XXX';
        const companyEmail = localStorage.getItem('thermal_company_email') || 'contact@entreprise.cd';
        const companyWebsite = localStorage.getItem('thermal_company_website') || 'www.votre-site.cd';

        commands.push(ESC + 'a' + '\x01'); // Centre
        commands.push(ESC + 'E' + '\x01'); // Gras ON
        commands.push(GS + '!' + '\x21');  // Double largeur
        commands.push(companyName + '\n');
        commands.push(GS + '!' + '\x00');  // Taille normale
        commands.push(ESC + 'E' + '\x00'); // Gras OFF

        // Informations entreprise
        commands.push('Adresse: ' + companyAddress + '\n');
        commands.push('Tel: ' + companyPhone + '\n');
        commands.push('Email: ' + companyEmail + '\n');
        commands.push('\n');

        // Titre du reçu
        commands.push(ESC + 'E' + '\x01'); // Gras ON
        commands.push(GS + '!' + '\x11');  // Double hauteur/largeur
        commands.push('RECU DE CAISSE\n');
        commands.push(GS + '!' + '\x00');  // Taille normale
        commands.push(ESC + 'E' + '\x00'); // Gras OFF
        commands.push(doubleSeparator + '\n');

        // Informations facture
        commands.push(ESC + 'a' + '\x00'); // Alignement gauche
        commands.push('Facture N: ' + data.invoice_number + '\n');
        commands.push('Date: ' + data.date + '\n');
        commands.push('Caissier: Admin\n');

        // === SECTION ARTICLES ===
        commands.push(doubleSeparator + '\n');

        // En-tête du tableau en gras
        commands.push(ESC + 'E' + '\x01'); // Gras ON
        const headerLine = this.formatTableRow('ARTICLE', 'QTE', 'P.U', 'TOTAL');
        commands.push(headerLine);
        commands.push(separator + '\n');
        commands.push(ESC + 'E' + '\x00'); // Gras OFF

        // Lignes du tableau
        data.items.forEach((item, index) => {
            console.log(`[QZ Tray] Item ${index}:`, {
                name: item.name,
                quantity: item.quantity,
                unit_price: item.unit_price,
                total: item.total
            });
            
            // Nom du produit
            const maxNameLength = width - 2;
            const name = this.truncateText(item.name, maxNameLength);
            commands.push(name + '\n');

            // Détails en colonnes alignées
            const qty = item.quantity.toString();
            const price = this.formatPrice(item.unit_price);
            const total = this.formatPrice(item.total);
            
            console.log(`[QZ Tray] Formatted - qty: ${qty}, price: ${price}, total: ${total}`);
            
            const detailLine = this.formatTableRow('', qty, price, total);
            commands.push(detailLine);
        });

        // === SECTION TOTAUX ===
        commands.push(doubleSeparator + '\n');

        // Sous-total
        commands.push(this.formatLine('Sous-total:', this.formatPrice(data.subtotal) + ' ' + currency));

        // Remise
        if (data.discount > 0) {
            commands.push(this.formatLine('Remise:', '-' + this.formatPrice(data.discount) + ' ' + currency));
        }

        // Taxe
        if (data.tax > 0) {
            commands.push(this.formatLine('Taxe:', this.formatPrice(data.tax) + ' ' + currency));
        }

        // Ligne de séparation forte
        commands.push(doubleSeparator + '\n');

        // TOTAL en grand et gras
        commands.push(ESC + 'a' + '\x01'); // Centre
        commands.push(ESC + 'E' + '\x01'); // Gras ON
        commands.push(GS + '!' + '\x11');  // Double hauteur/largeur
        commands.push('TOTAL\n');
        commands.push(this.formatPrice(data.total) + ' ' + currency + '\n');
        commands.push(GS + '!' + '\x00');  // Taille normale
        commands.push(ESC + 'E' + '\x00'); // Gras OFF
        commands.push(ESC + 'a' + '\x00'); // Alignement gauche

        // === SECTION PAIEMENT ===
        commands.push(doubleSeparator + '\n');

        // Montant payé
        commands.push(this.formatLine('Montant paye:', this.formatPrice(data.paid) + ' ' + currency));

        // Monnaie rendue
        if (data.change > 0) {
            commands.push(ESC + 'E' + '\x01'); // Gras ON
            commands.push(this.formatLine('Monnaie rendue:', this.formatPrice(data.change) + ' ' + currency));
            commands.push(ESC + 'E' + '\x00'); // Gras OFF
        }

        // === PIED DE PAGE ===
        commands.push('\n');
        commands.push(doubleSeparator + '\n');
        commands.push(ESC + 'a' + '\x01'); // Centre
        commands.push('\n');
        commands.push(ESC + 'E' + '\x01'); // Gras ON
        commands.push('MERCI DE VOTRE VISITE!\n');
        commands.push(ESC + 'E' + '\x00'); // Gras OFF
        commands.push('A bientot!\n');
        commands.push('\n');
        commands.push('Service client: ' + companyPhone + '\n');
        commands.push(companyWebsite + '\n');
        commands.push('\n');
        commands.push(doubleSeparator + '\n');

        // Espace suffisant avant la coupe
        commands.push('\n\n\n');
        commands.push(GS + 'V' + '\x41' + '\x00'); // Coupe partielle

        return commands;
    }

    /**
     * Formate une ligne avec label à gauche et valeur à droite
     */
    formatLine(label, value) {
        const spacing = ' '.repeat(Math.max(0, this.paperWidth - label.length - value.length));
        return label + spacing + value + '\n';
    }

    /**
     * Tronque un texte à la longueur maximale
     */
    truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength - 3) + '...';
    }

    /**
     * Formate une ligne de tableau avec colonnes alignées
     * @param {string} col1 - Article (nom ou vide)
     * @param {string} col2 - Quantité
     * @param {string} col3 - Prix unitaire
     * @param {string} col4 - Total
     */
    formatTableRow(col1, col2, col3, col4) {
        const width = this.paperWidth;

        // Définir les largeurs de colonnes (optimisé pour tenir sur une ligne)
        // Pour 32 caractères: Article(8), Qte(6), P.U.(8), Total(10)
        // Pour 48 caractères: Article(16), Qte(8), P.U.(11), Total(13)
        let col1Width, col2Width, col3Width, col4Width;

        if (width <= 32) {
            col1Width = 8;   // ARTICLE réduit
            col2Width = 6;   // QTE
            col3Width = 8;   // P.U
            col4Width = 10;  // TOTAL
        } else {
            col1Width = 16;
            col2Width = 8;
            col3Width = 11;
            col4Width = 13;
        }

        // Tronquer et aligner les colonnes
        const c1 = this.padText(col1, col1Width, 'left');
        const c2 = this.padText(col2, col2Width, 'right');
        const c3 = this.padText(col3, col3Width, 'right');
        const c4 = this.padText(col4, col4Width, 'right');

        return c1 + c2 + c3 + c4 + '\n';
    }

    /**
     * Ajoute du padding à un texte
     * @param {string} text - Texte à padder
     * @param {number} width - Largeur totale
     * @param {string} align - 'left' ou 'right'
     */
    padText(text, width, align = 'left') {
        const str = text.toString();
        if (str.length >= width) {
            return str.substring(0, width);
        }

        const padding = ' '.repeat(width - str.length);
        return align === 'left' ? str + padding : padding + str;
    }

    /**
     * Formate un prix
     */
    formatPrice(amount) {
        // Convertir en string sans formatage pour éviter les problèmes d'encodage
        const num = parseFloat(amount) || 0;
        const str = Math.floor(num).toString();
        // Retourner uniquement le nombre, la devise sera ajoutée séparément
        return str;
    }

    /**
     * Déconnexion de QZ Tray
     */
    async disconnect() {
        try {
            if (qz.websocket.isActive()) {
                await qz.websocket.disconnect();
                this.connected = false;
                console.log('✅ Déconnecté de QZ Tray');
            }
        } catch (error) {
            console.error('❌ Erreur déconnexion:', error);
        }
    }

    /**
     * Affiche un message d'erreur
     */
    showError(message) {
        // Utiliser l'événement Livewire si disponible
        if (window.Livewire) {
            window.Livewire.dispatch('show-error', { message });
        } else {
            alert('Erreur: ' + message);
        }
    }

    /**
     * Affiche un message de succès
     */
    showSuccess(message) {
        if (window.Livewire) {
            window.Livewire.dispatch('show-success', { message });
        }
        console.log('✅', message);
    }
}

// Créer une instance globale
window.thermalPrinter = new ThermalPrinter();

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 ThermalPrinter initialisé');
});
