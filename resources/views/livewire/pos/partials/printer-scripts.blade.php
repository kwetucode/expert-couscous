<!-- Thermal Printer Scripts -->
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.min.js"></script>
<script src="{{ asset('js/qz-thermal-printer.js') }}"></script>

<script>
    // Variable pour suivre si le printer est initialisé
    let thermalPrinterReady = false;
    
    // Charger l'imprimante depuis localStorage
    function loadPrinterFromStorage() {
        const savedPrinter = localStorage.getItem('thermal_printer_name');
        if (savedPrinter && window.thermalPrinter) {
            window.thermalPrinter.printerName = savedPrinter;
            console.log('🎯 Imprimante chargée depuis localStorage:', savedPrinter);
        }
    }
    
    // Fonction d'impression avec retry
    async function printWithRetry(data, retries = 3) {
        console.log('🖨️ printWithRetry appelé avec data:', data);
        
        // S'assurer que l'imprimante est configurée depuis localStorage
        loadPrinterFromStorage();
        
        for (let attempt = 1; attempt <= retries; attempt++) {
            console.log(`🔄 Tentative ${attempt}/${retries}...`);
            
            if (typeof window.thermalPrinter === 'undefined') {
                console.error('❌ ThermalPrinter non défini, attente...');
                await new Promise(resolve => setTimeout(resolve, 500));
                continue;
            }
            
            // Vérifier si une imprimante est configurée
            if (!window.thermalPrinter.printerName) {
                console.log('⚠️ Aucune imprimante configurée, tentative de chargement...');
                loadPrinterFromStorage();
            }
            
            try {
                await window.thermalPrinter.printReceipt(data);
                console.log('✅ Impression réussie!');
                return true;
            } catch (error) {
                console.error(`❌ Erreur tentative ${attempt}:`, error);
                if (attempt < retries) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                }
            }
        }
        
        // Afficher message d'erreur à l'utilisateur
        if (window.Livewire) {
            window.Livewire.dispatch('show-toast', { 
                message: 'Erreur d\'impression. Vérifiez que QZ Tray est installé et configurez votre imprimante.', 
                type: 'error' 
            });
        }
        return false;
    }
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', () => {
        console.log('🚀 DOM chargé, initialisation du thermal printer...');
        
        // S'assurer que thermalPrinter est disponible
        if (typeof window.thermalPrinter !== 'undefined') {
            console.log('✅ ThermalPrinter disponible');
            thermalPrinterReady = true;
            
            // Charger l'imprimante depuis localStorage
            loadPrinterFromStorage();
        } else {
            console.warn('⚠️ ThermalPrinter pas encore disponible');
        }
    });

    // Écouter l'événement d'impression thermique
    document.addEventListener('livewire:init', () => {
        console.log('🔌 Livewire initialisé, enregistrement des listeners...');
        
        Livewire.on('print-thermal-receipt', async (data) => {
            console.log('📄 Événement print-thermal-receipt reçu');
            console.log('📄 Données brutes reçues:', data);
            console.log('📄 localStorage thermal_printer_name:', localStorage.getItem('thermal_printer_name'));
            
            // Extraire les données (Livewire 3 peut envoyer dans un tableau ou objet)
            let receiptData = data;
            if (Array.isArray(data) && data.length > 0) {
                receiptData = data[0];
                console.log('📄 Données extraites du tableau:', receiptData);
            }
            
            if (!receiptData || Object.keys(receiptData).length === 0) {
                console.error('❌ Données de reçu vides ou invalides');
                return;
            }
            
            // Appeler l'impression avec retry
            await printWithRetry(receiptData);
        });

        // Réinitialiser automatiquement après impression
        Livewire.on('sale-completed', () => {
            console.log('✅ Vente terminée');
        });
        
        console.log('✅ Listeners Livewire enregistrés');
    });
</script>
