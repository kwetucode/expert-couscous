<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 mb-2">⚙️ Configuration Imprimante</h1>
                    <p class="text-gray-600">Configurez votre imprimante thermique et les informations d'entreprise</p>
                </div>
                <a href="{{ route('pos.cash-register') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg font-semibold transition">
                    ← Retour au POS
                </a>
            </div>
        </div>

        <!-- Status QZ Tray -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🔌 État de la connexion</h2>
            <div class="flex items-center gap-4">
                <div id="qz-status" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-100">
                    <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                    <span class="font-semibold text-gray-600">Vérification...</span>
                </div>
                <button type="button" onclick="detectAndShowPrinters()"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition">
                    🔍 Détecter les imprimantes
                </button>
                <button wire:click="testPrint"
                        onclick="console.log('🔵 Button TEST PRINT clicked')"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition">
                    <span wire:loading.remove wire:target="testPrint">🖨️ Imprimer un test</span>
                    <span wire:loading wire:target="testPrint">⏳ Envoi en cours...</span>
                </button>
            </div>
            
            <!-- Liste des imprimantes -->
            <div id="printer-list" class="mt-4 hidden">
                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm font-semibold text-blue-800 mb-1">📌 Cliquez sur une imprimante pour la sélectionner</p>
                    <p class="text-xs text-blue-600">L'imprimante sélectionnée sera sauvegardée automatiquement</p>
                </div>
                <div id="printers" class="space-y-2"></div>
            </div>
            
            <!-- Imprimante sélectionnée -->
            <div id="selected-printer" class="mt-4 p-4 bg-green-50 border-2 border-green-400 rounded-lg hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-green-700">✅ Imprimante active :</p>
                        <p id="selected-printer-name" class="text-lg font-bold text-green-900 mt-1"></p>
                    </div>
                    <button type="button" onclick="clearSelectedPrinter()" 
                            class="px-3 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm font-semibold transition">
                        ✕ Effacer
                    </button>
                </div>
            </div>
        </div>

        <!-- Configuration Form -->
        <form id="printer-config-form" class="space-y-6">
              <!-- Configuration Imprimante -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">🖨️ Configuration Imprimante</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Type de connexion</label>
                        <select id="printerType" wire:model="printerType"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="usb">USB (Câble)</option>
                            <option value="bluetooth">Bluetooth</option>
                            <option value="network">Réseau (IP)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Largeur du papier</label>
                        <select id="paperWidth" wire:model="paperWidth"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="32">58mm (32 caractères)</option>
                            <option value="48">80mm (48 caractères)</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nom de l'imprimante</label>
                        <input type="text" id="printerName" wire:model="printerName"
                            placeholder="Ex: Epson TM-T20, XPrinter XP-58..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Laissez vide pour utiliser l'imprimante par défaut</p>
                    </div>
                </div>
            </div>


            <!-- Informations Entreprise -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">🏢 Informations Entreprise</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nom de l'entreprise</label>
                        <input type="text" id="companyName" wire:model="companyName"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse</label>
                        <input type="text" id="companyAddress" wire:model="companyAddress"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                        <input type="text" id="companyPhone" wire:model="companyPhone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" id="companyEmail" wire:model="companyEmail"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Site web</label>
                        <input type="text" id="companyWebsite" wire:model="companyWebsite"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>
            </div>


            <!-- Options d'impression -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Options d'impression</h2>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" id="autoPrint" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="font-semibold text-gray-700">Impression automatique après validation</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" id="printLogo" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="font-semibold text-gray-700">Afficher le logo de l'entreprise</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" id="printBarcode" class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="font-semibold text-gray-700">Imprimer le code-barres de la facture</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <button type="button" onclick="saveConfiguration()"
                    class="flex-1 px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg transition-all transform hover:scale-105">
                    💾 Enregistrer la configuration
                </button>
                <button type="button" onclick="resetConfiguration()"
                    class="px-6 py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg transition-all">
                    🔄 Réinitialiser
                </button>
            </div>
        </form>

        <!-- Guide d'installation QZ Tray -->
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-2xl p-6 mt-6">
            <h3 class="text-lg font-bold text-gray-900 mb-3">💡 Guide d'installation QZ Tray</h3>
            <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                <li>Téléchargez QZ Tray depuis <a href="https://qz.io/download/" target="_blank" class="text-blue-600 font-semibold underline">qz.io/download</a></li>
                <li>Installez et lancez QZ Tray (icône dans la barre des tâches)</li>
                <li>Cliquez sur l'icône QZ Tray → "Site Manager" → "Add Site"</li>
                <li>Ajoutez votre URL (ex: http://localhost) et cochez "Allow" + "Remember"</li>
                <li>Connectez votre imprimante thermique (USB ou Bluetooth)</li>
                <li>Cliquez sur "Tester la connexion" pour vérifier</li>
            </ol>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2/qz-tray.min.js"></script>
    <script src="{{ asset('js/qz-thermal-printer.js') }}"></script>
    <script>
        // Attendre que tout soit chargé
        window.addEventListener('load', () => {
            console.log('🔵 Page chargée, vérification de window.thermalPrinter...');
            console.log('🔵 window.thermalPrinter exists?', !!window.thermalPrinter);
            console.log('🔵 QZ Tray exists?', typeof qz !== 'undefined');

            // Charger la configuration
            loadConfiguration();

            // Vérifier la connexion QZ (timeout pour s'assurer que QZ Tray est prêt)
            setTimeout(() => {
                checkQZConnection();
            }, 500);
        });

        // Charger la configuration depuis localStorage
        function loadConfiguration() {
            const config = {
                companyName: localStorage.getItem('thermal_company_name') || 'VOTRE ENTREPRISE',
                companyAddress: localStorage.getItem('thermal_company_address') || 'Votre Adresse',
                companyPhone: localStorage.getItem('thermal_company_phone') || '+243 XXX XXX XXX',
                companyEmail: localStorage.getItem('thermal_company_email') || 'contact@entreprise.cd',
                companyWebsite: localStorage.getItem('thermal_company_website') || 'www.votre-site.cd',
                paperWidth: localStorage.getItem('thermal_paper_width') || '32',
                printerType: localStorage.getItem('thermal_printer_type') || 'usb',
                printerName: localStorage.getItem('thermal_printer_name') || '',
                autoPrint: localStorage.getItem('thermal_auto_print') === 'true',
                printLogo: localStorage.getItem('thermal_print_logo') === 'true',
                printBarcode: localStorage.getItem('thermal_print_barcode') === 'true',
            };

            // Remplir le formulaire
            document.getElementById('companyName').value = config.companyName;
            document.getElementById('companyAddress').value = config.companyAddress;
            document.getElementById('companyPhone').value = config.companyPhone;
            document.getElementById('companyEmail').value = config.companyEmail;
            document.getElementById('companyWebsite').value = config.companyWebsite;
            document.getElementById('paperWidth').value = config.paperWidth;
            document.getElementById('printerType').value = config.printerType;
            document.getElementById('printerName').value = config.printerName;
            document.getElementById('autoPrint').checked = config.autoPrint;
            document.getElementById('printLogo').checked = config.printLogo;
            document.getElementById('printBarcode').checked = config.printBarcode;
        }

        // Sauvegarder la configuration
        function saveConfiguration() {
            const config = {
                companyName: document.getElementById('companyName').value,
                companyAddress: document.getElementById('companyAddress').value,
                companyPhone: document.getElementById('companyPhone').value,
                companyEmail: document.getElementById('companyEmail').value,
                companyWebsite: document.getElementById('companyWebsite').value,
                paperWidth: document.getElementById('paperWidth').value,
                printerType: document.getElementById('printerType').value,
                printerName: document.getElementById('printerName').value,
                autoPrint: document.getElementById('autoPrint').checked,
                printLogo: document.getElementById('printLogo').checked,
                printBarcode: document.getElementById('printBarcode').checked,
            };

            // Sauvegarder dans localStorage
            localStorage.setItem('thermal_company_name', config.companyName);
            localStorage.setItem('thermal_company_address', config.companyAddress);
            localStorage.setItem('thermal_company_phone', config.companyPhone);
            localStorage.setItem('thermal_company_email', config.companyEmail);
            localStorage.setItem('thermal_company_website', config.companyWebsite);
            localStorage.setItem('thermal_paper_width', config.paperWidth);
            localStorage.setItem('thermal_printer_type', config.printerType);
            localStorage.setItem('thermal_printer_name', config.printerName);
            localStorage.setItem('thermal_auto_print', config.autoPrint);
            localStorage.setItem('thermal_print_logo', config.printLogo);
            localStorage.setItem('thermal_print_barcode', config.printBarcode);

            // Mettre à jour l'imprimante
            if (window.thermalPrinter) {
                window.thermalPrinter.setPaperWidth(parseInt(config.paperWidth));
                if (config.printerName) {
                    window.thermalPrinter.printerName = config.printerName;
                }
            }

            // Afficher un message de succès
            alert('✅ Configuration enregistrée avec succès !');
        }

        // Réinitialiser la configuration
        function resetConfiguration() {
            if (confirm('Voulez-vous vraiment réinitialiser la configuration ?')) {
                localStorage.removeItem('thermal_company_name');
                localStorage.removeItem('thermal_company_address');
                localStorage.removeItem('thermal_company_phone');
                localStorage.removeItem('thermal_company_email');
                localStorage.removeItem('thermal_company_website');
                localStorage.removeItem('thermal_paper_width');
                localStorage.removeItem('thermal_printer_type');
                localStorage.removeItem('thermal_printer_name');
                localStorage.removeItem('thermal_auto_print');
                localStorage.removeItem('thermal_print_logo');
                localStorage.removeItem('thermal_print_barcode');

                loadConfiguration();
                alert('✅ Configuration réinitialisée !');
            }
        }

        // Vérifier la connexion QZ Tray
        async function checkQZConnection() {
            const statusDiv = document.getElementById('qz-status');

            try {
                if (typeof qz === 'undefined') {
                    statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-red-500"></span><span class="font-semibold text-red-600">QZ Tray non chargé</span>';
                    return false;
                }

                if (!qz.websocket.isActive()) {
                    await qz.websocket.connect();
                }

                statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></span><span class="font-semibold text-green-600">Connecté</span>';
                
                // Afficher l'imprimante sauvegardée si elle existe
                const savedPrinter = localStorage.getItem('thermal_printer_name');
                if (savedPrinter) {
                    showSelectedPrinter(savedPrinter);
                }
                
                return true;
            } catch (error) {
                statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-red-500"></span><span class="font-semibold text-red-600">Déconnecté - Lancez QZ Tray</span>';
                console.error('Erreur QZ Tray:', error);
                return false;
            }
        }

        // Détecter et afficher les imprimantes
        async function detectAndShowPrinters() {
            const statusDiv = document.getElementById('qz-status');
            const printerList = document.getElementById('printer-list');
            const printersDiv = document.getElementById('printers');
            
            // Afficher "Recherche en cours..."
            statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-yellow-500 animate-pulse"></span><span class="font-semibold text-yellow-600">Recherche en cours...</span>';
            printersDiv.innerHTML = '<div class="text-center py-4"><span class="text-gray-500">🔄 Connexion à QZ Tray...</span></div>';
            printerList.classList.remove('hidden');

            try {
                // Étape 1: Vérifier si QZ library est chargée
                if (typeof qz === 'undefined') {
                    statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-red-500"></span><span class="font-semibold text-red-600">QZ Tray non chargé</span>';
                    printersDiv.innerHTML = `
                        <div class="text-center py-4 text-red-600">
                            <p class="font-bold">❌ La bibliothèque QZ Tray n'est pas chargée</p>
                            <p class="mt-2">Vérifiez votre connexion internet ou <a href="https://qz.io/download/" target="_blank" class="underline font-bold">téléchargez QZ Tray ici</a></p>
                        </div>`;
                    return;
                }
                
                console.log('✅ QZ library chargée');
                printersDiv.innerHTML = '<div class="text-center py-4"><span class="text-gray-500">🔄 Connexion au service QZ Tray...</span></div>';

                // Étape 2: Se connecter à QZ Tray
                if (!qz.websocket.isActive()) {
                    console.log('🔄 Tentative de connexion WebSocket à QZ Tray...');
                    try {
                        await qz.websocket.connect();
                        console.log('✅ WebSocket connecté');
                    } catch (wsError) {
                        console.error('❌ Erreur WebSocket:', wsError);
                        statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-red-500"></span><span class="font-semibold text-red-600">QZ Tray non démarré</span>';
                        printersDiv.innerHTML = `
                            <div class="bg-red-50 border-2 border-red-300 rounded-lg p-4 text-red-800">
                                <p class="font-bold text-lg">❌ Impossible de se connecter à QZ Tray</p>
                                <p class="mt-2">QZ Tray n'est probablement pas démarré.</p>
                                <div class="mt-4 bg-white rounded p-3 text-sm">
                                    <p class="font-bold">📋 Vérifications à faire :</p>
                                    <ol class="list-decimal list-inside mt-2 space-y-1">
                                        <li>Vérifiez que QZ Tray est installé</li>
                                        <li>Cherchez l'icône <strong>QZ</strong> dans la barre des tâches (près de l'heure)</li>
                                        <li>Si absent, lancez QZ Tray depuis le menu Démarrer</li>
                                        <li>Si non installé: <a href="https://qz.io/download/" target="_blank" class="text-blue-600 underline font-bold">Télécharger QZ Tray</a></li>
                                    </ol>
                                </div>
                            </div>`;
                        return;
                    }
                } else {
                    console.log('✅ WebSocket déjà actif');
                }

                statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></span><span class="font-semibold text-green-600">Connecté à QZ Tray</span>';
                printersDiv.innerHTML = '<div class="text-center py-4"><span class="text-gray-500">🔄 Recherche des imprimantes Windows...</span></div>';

                // Étape 3: Lister les imprimantes
                console.log('🔍 Recherche des imprimantes...');
                const printers = await qz.printers.find();
                console.log('🖨️ Résultat qz.printers.find():', printers);
                console.log('🖨️ Type:', typeof printers);
                console.log('🖨️ Nombre:', printers ? printers.length : 0);

                // Récupérer l'imprimante actuellement sélectionnée
                const selectedPrinter = localStorage.getItem('thermal_printer_name');

                if (!printers || printers.length === 0) {
                    printersDiv.innerHTML = `
                        <div class="bg-orange-50 border-2 border-orange-300 rounded-lg p-4 text-orange-800">
                            <p class="font-bold text-lg">⚠️ Aucune imprimante détectée par QZ Tray</p>
                            <p class="mt-2">QZ Tray est connecté mais ne voit aucune imprimante.</p>
                            <div class="mt-4 bg-white rounded p-3 text-sm">
                                <p class="font-bold">📋 Vérifications à faire :</p>
                                <ol class="list-decimal list-inside mt-2 space-y-1">
                                    <li>Ouvrez <strong>Paramètres Windows > Imprimantes</strong></li>
                                    <li>Vérifiez que votre imprimante <strong>EPSON TM-T20II</strong> apparaît dans la liste</li>
                                    <li>Si elle n'apparaît pas, installez les pilotes EPSON</li>
                                    <li>Redémarrez QZ Tray après avoir ajouté l'imprimante</li>
                                </ol>
                            </div>
                            <button onclick="window.open('ms-settings:printers', '_blank')" class="mt-4 px-4 py-2 bg-orange-600 text-white rounded-lg font-semibold hover:bg-orange-700">
                                📂 Ouvrir Paramètres Imprimantes Windows
                            </button>
                        </div>`;
                    return;
                }

                printersDiv.innerHTML = '';
                printers.forEach(printer => {
                    // Détecter le type de connexion
                    const name = printer.toLowerCase();
                    let connectionIcon = '🔌';
                    let connectionType = 'USB';

                    if (name.includes('bluetooth') || name.includes('bt-') || name.includes('pozer') || name.includes('pp200') || name.includes('peripage') || name.includes('mini printer') || name.includes('portable')) {
                        connectionIcon = '📡';
                        connectionType = 'Bluetooth';
                    } else if (name.includes('network') || name.includes('ip') || name.includes('lan')) {
                        connectionIcon = '🌐';
                        connectionType = 'Réseau';
                    }

                    // Créer un bouton pour chaque imprimante
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full text-left px-4 py-3 rounded-lg border-2 transition-all ' +
                        (printer === selectedPrinter
                            ? 'bg-green-100 border-green-500 text-green-900 font-bold ring-2 ring-green-300'
                            : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-blue-50 hover:border-blue-400');

                    button.innerHTML = `
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">${printer === selectedPrinter ? '✅' : '🖨️'}</span>
                            <div class="flex-1">
                                <p class="font-semibold">${printer}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs px-2 py-1 rounded ${connectionType === 'Bluetooth' ? 'bg-blue-100 text-blue-700' : connectionType === 'Réseau' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'}">
                                        ${connectionIcon} ${connectionType}
                                    </span>
                                    ${printer === selectedPrinter ? '<span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded font-semibold">✓ ACTIVE</span>' : '<span class="text-xs text-blue-600">Cliquez pour sélectionner</span>'}
                                </div>
                            </div>
                        </div>
                    `;

                    button.onclick = () => selectPrinter(printer);
                    printersDiv.appendChild(button);
                });

                // Afficher l'imprimante sélectionnée si elle existe
                if (selectedPrinter) {
                    showSelectedPrinter(selectedPrinter);
                }

            } catch (error) {
                statusDiv.innerHTML = '<span class="w-3 h-3 rounded-full bg-red-500"></span><span class="font-semibold text-red-600">Erreur de connexion</span>';
                printersDiv.innerHTML = `<div class="text-center py-4 text-red-600">❌ Erreur: ${error.message}<br><br>Assurez-vous que QZ Tray est démarré (icône dans la barre des tâches).</div>`;
                console.error('Erreur QZ Tray:', error);
            }
        }

        // Sélectionner une imprimante
        function selectPrinter(printerName) {
            console.log('🎯 Sélection de l\'imprimante:', printerName);
            
            // Sauvegarder dans localStorage
            localStorage.setItem('thermal_printer_name', printerName);

            // Mettre à jour le champ de formulaire
            document.getElementById('printerName').value = printerName;

            // Mettre à jour l'instance thermalPrinter
            if (window.thermalPrinter) {
                window.thermalPrinter.printerName = printerName;
                console.log('✅ thermalPrinter.printerName mis à jour:', window.thermalPrinter.printerName);
            }

            // Afficher la confirmation
            showSelectedPrinter(printerName);

            // Rafraîchir la liste des imprimantes pour montrer la sélection
            detectAndShowPrinters();
        }

        // Effacer l'imprimante sélectionnée
        function clearSelectedPrinter() {
            localStorage.removeItem('thermal_printer_name');
            document.getElementById('printerName').value = '';
            document.getElementById('selected-printer').classList.add('hidden');
            
            if (window.thermalPrinter) {
                window.thermalPrinter.printerName = null;
            }
            
            // Rafraîchir la liste
            detectAndShowPrinters();
        }

        // Afficher l'imprimante sélectionnée
        function showSelectedPrinter(printerName) {
            const selectedDiv = document.getElementById('selected-printer');
            const nameDiv = document.getElementById('selected-printer-name');

            nameDiv.textContent = printerName;
            selectedDiv.classList.remove('hidden');
        }

        // Événements Livewire - écoute globale immédiate
        window.addEventListener('DOMContentLoaded', () => {
            console.log('🔵 DOMContentLoaded - Setup global listeners');
        });

        document.addEventListener('livewire:init', () => {
            console.log('🔵 livewire:init event fired');

            Livewire.on('test-thermal-print', (data) => {
                console.log('🔵 test-thermal-print event received via Livewire.on', data);
                console.log('🔵 window.thermalPrinter exists?', !!window.thermalPrinter);
                console.log('🔵 localStorage thermal_printer_name:', localStorage.getItem('thermal_printer_name'));

                if (window.thermalPrinter) {
                    // S'assurer que l'imprimante est configurée depuis localStorage
                    const savedPrinter = localStorage.getItem('thermal_printer_name');
                    if (savedPrinter && !window.thermalPrinter.printerName) {
                        window.thermalPrinter.printerName = savedPrinter;
                        console.log('🔵 Imprimante chargée depuis localStorage:', savedPrinter);
                    }
                    
                    // Recharger la configuration de largeur papier avant l'impression
                    window.thermalPrinter.detectPaperWidth();
                    console.log('🔵 Paper width:', window.thermalPrinter.paperWidth);
                    console.log('🔵 Printer name:', window.thermalPrinter.printerName);

                    console.log('🔵 Calling printReceipt...');
                    window.thermalPrinter.printReceipt(data[0])
                        .then(() => {
                            console.log('✅ Impression lancée avec succès');
                            alert('✅ Impression de test envoyée!');
                        })
                        .catch(error => {
                            console.error('❌ Erreur d\'impression:', error);
                            alert('❌ Erreur d\'impression: ' + error.message);
                        });
                } else {
                    console.error('❌ ThermalPrinter non disponible');
                    alert('❌ ThermalPrinter non disponible. Vérifiez que qz-thermal-printer.js est bien chargé.');
                }
            });
        });
    </script>
    @endpush
</div>
