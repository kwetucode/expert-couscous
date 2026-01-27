/**
 * Store Alpine.js pour la gestion des notifications toast
 */

export default {
    messages: [],

    /**
     * Affiche un message toast
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de message (success, error, info, warning)
     * @param {number} duration - Durée d'affichage en ms (0 = pas de fermeture auto)
     */
    show(message, type = 'info', duration = 3000) {
        const id = Date.now() + Math.random();

        this.messages.push({
            id,
            message,
            type
        });

        // Fermeture automatique si duration > 0
        if (duration > 0) {
            setTimeout(() => {
                this.remove(id);
            }, duration);
        }
    },

    /**
     * Retire un message
     */
    remove(id) {
        const index = this.messages.findIndex(m => m.id === id);
        if (index !== -1) {
            this.messages.splice(index, 1);
        }
    },

    /**
     * Vide tous les messages
     */
    clear() {
        this.messages = [];
    },

    /**
     * Raccourcis pour les types communs
     */
    success(message, duration = 3000) {
        this.show(message, 'success', duration);
    },

    error(message, duration = 5000) {
        this.show(message, 'error', duration);
    },

    info(message, duration = 3000) {
        this.show(message, 'info', duration);
    },

    warning(message, duration = 4000) {
        this.show(message, 'warning', duration);
    }
};
