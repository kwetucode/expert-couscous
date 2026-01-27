<div>
    @if ($isOpen)
    <div x-data="{ show: true }"
         x-show="show"
         x-init="$watch('show', value => { if(!value) { $wire.close(); } document.body.style.overflow = value ? 'hidden' : '' })"
         x-on:livewire:navigating.window="document.body.style.overflow = ''"
         class="fixed inset-0 z-50 overflow-hidden"
         @keydown.escape.window="show = false"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">

        <!-- Backdrop -->
        <div @click="show = false"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm">
        </div>

        <!-- Modal Container -->
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div x-show="show"
                 @click.stop
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl transform transition-all w-full sm:max-w-4xl flex flex-col pointer-events-auto"
                 style="max-height: 85vh;">

                <!-- Modal Header -->
                <div
                    class="flex-shrink-0 flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white rounded-t-2xl">
                    <div class="flex items-center space-x-3">
                        <div
                            class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ $modalTitle }}
                        </h3>
                    </div>
                    <button @click="$wire.close()" type="button"
                        class="text-gray-400 hover:text-gray-600 transition-all duration-200 hover:scale-110">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form wire:submit="save" class="flex flex-col flex-1 min-h-0" x-data="{
                    showDescription: @entangle('showDescription'),
                    showImage: @entangle('showImage'),
                    showVariants: @entangle('showVariants')
                }">
                    <div class="p-6 space-y-6 overflow-y-auto flex-1">
                        <!-- Basic Information -->
                        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                            <div class="flex items-center space-x-2 mb-4">
                                <div class="h-8 w-1 bg-gradient-to-b from-indigo-500 to-purple-600 rounded-full">
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-800">Informations de base</h3>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <!-- Name -->
                                    <div>
                                        <label for="form.name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Nom du produit <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                            </div>
                                            <input type="text" id="form.name" wire:model="form.name"
                                                placeholder="Ex: T-shirt Coton Premium"
                                                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                        </div>
                                        @error('form.name')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <!-- Product Type -->
                                    <div>
                                        <label for="form.product_type_id"
                                            class="block text-sm font-medium text-gray-700 mb-2">
                                            Type de produit <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model.live="form.product_type_id" id="form.product_type_id"
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                            <option value="">Sélectionnez un type</option>
                                            @foreach ($productTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->icon }}
                                                    {{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('form.product_type_id')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                        <p class="mt-1.5 text-xs text-gray-500">
                                            Sélectionnez d'abord le type pour filtrer les catégories
                                        </p>
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label for="form.category_id"
                                            class="block text-sm font-medium text-gray-700 mb-2">
                                            Catégorie
                                        </label>
                                        <select wire:model.live="form.category_id" id="form.category_id"
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            @if(empty($form->product_type_id)) disabled @endif>
                                            <option value="">
                                                @if(empty($form->product_type_id))
                                                    Sélectionnez d'abord un type
                                                @else
                                                    Sélectionnez une catégorie
                                                @endif
                                            </option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                                            @endforeach
                                        </select>
                                        @error('form.category_id')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                        @if(!empty($form->product_type_id) && empty($categories))
                                            <p class="mt-1.5 text-xs text-amber-600">
                                                Aucune catégorie pour ce type. Créez-en une d'abord.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                    <!-- Cost Price -->
                                    <div>
                                        <label for="form.cost_price"
                                            class="block text-sm font-medium text-gray-700 mb-2">
                                            Prix d'achat ({{ current_currency() }})
                                        </label>
                                        <input type="number" id="form.cost_price" wire:model.live="form.cost_price"
                                            step="0.01" min="0" placeholder="0"
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                        @error('form.cost_price')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <!-- Price -->
                                    <div>
                                        <label for="form.price" class="block text-sm font-medium text-gray-700 mb-2">
                                            Prix de vente ({{ current_currency() }}) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" id="form.price" wire:model.live="form.price"
                                            step="0.01" min="0" placeholder="0"
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                        @error('form.price')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <!-- Status -->
                                    <div>
                                        <label for="form.status" class="block text-sm font-medium text-gray-700 mb-2">
                                            Statut <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model="form.status" id="form.status"
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                                            <option value="active">Actif</option>
                                            <option value="inactive">Inactif</option>
                                        </select>
                                        @error('form.status')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    <!-- Stock Alert Threshold -->
                                    <div>
                                        <label for="form.stock_alert_threshold"
                                            class="block text-sm font-medium text-gray-700 mb-2">
                                            Seuil d'alerte
                                        </label>
                                        <input type="number" id="form.stock_alert_threshold"
                                            wire:model="form.stock_alert_threshold" min="0" placeholder="10"
                                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                        @error('form.stock_alert_threshold')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Remise maximum autorisée -->
                                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="form.max_discount_amount"
                                            class="block text-sm font-medium text-gray-700 mb-2">
                                            Remise max autorisée ({{ current_currency() }})
                                            <span class="text-gray-400 font-normal text-xs ml-1">(optionnel)</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <input type="number" id="form.max_discount_amount"
                                                wire:model="form.max_discount_amount"
                                                step="1" min="0"
                                                placeholder="Aucune limite"
                                                max="{{ $form->price ?: '' }}"
                                                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition" />
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">
                                            Montant maximum qu'on peut réduire sur ce produit au POS. Laissez vide pour aucune limite.
                                        </p>
                                        @error('form.max_discount_amount')
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>

                                    @if($form->max_discount_amount && $form->price)
                                        <div class="flex items-center">
                                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-4 w-full">
                                                <div class="flex items-center space-x-2">
                                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="text-sm text-amber-700">
                                                        Prix minimum de vente:
                                                        <strong>{{ number_format(max(0, $form->price - $form->max_discount_amount), 0, ',', ' ') }} {{ current_currency() }}</strong>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if ($form->price && $form->cost_price && floatval($form->cost_price) > 0)
                                    <div
                                        class="mt-4 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-xl p-4 shadow-sm">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-5 h-5 text-indigo-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                                </svg>
                                                <span class="text-sm font-semibold text-indigo-700">Marge
                                                    bénéficiaire:</span>
                                            </div>
                                            <span class="text-xl font-bold text-indigo-900">
                                                {{ number_format((($form->price - $form->cost_price) / $form->cost_price) * 100, 2) }}%
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Description (Optional) -->
                            <x-optional-section title="Description" show="showDescription" gradient-from="green-500"
                                gradient-to="teal-600">
                                <textarea id="form.description" wire:model="form.description" rows="3"
                                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg bg-white text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition resize-none"
                                    placeholder="Description détaillée du produit..."></textarea>
                                @error('form.description')
                                    <p class="mt-2 text-sm text-red-600 flex items-center">
                                        <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </x-optional-section>

                            <!-- Image Upload (Optional) -->
                            <x-optional-section title="Image" show="showImage" gradient-from="pink-500"
                                gradient-to="rose-600">
                                <div x-data="{
                                    isDragging: false,
                                    handleDragOver(e) {
                                        e.preventDefault();
                                        this.isDragging = true;
                                    },
                                    handleDragLeave(e) {
                                        e.preventDefault();
                                        this.isDragging = false;
                                    },
                                    handleDrop(e) {
                                        e.preventDefault();
                                        this.isDragging = false;
                                        const files = e.dataTransfer.files;
                                        if (files.length > 0) {
                                            $refs.fileInput.files = files;
                                            $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                                        }
                                    }
                                }">

                                    @if ($form->image || $currentImage)
                                        <!-- Preview avec option de changement -->
                                        <div class="relative group">
                                            <div
                                                class="relative overflow-hidden rounded-xl border-2 border-gray-200 bg-gray-50 hover:border-indigo-300 transition-colors duration-300">
                                                <img src="{{ $form->image ? $form->image->temporaryUrl() : asset('storage/' . $currentImage) }}"
                                                    alt="Aperçu" class="w-full h-64 object-cover">

                                                <!-- Overlay on hover -->
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
                                                        <p class="text-sm font-medium">
                                                            {{ $form->image ? 'Nouvelle image' : 'Image actuelle' }}
                                                        </p>
                                                        <p class="text-xs text-gray-200 mt-1">Cliquez pour changer</p>
                                                    </div>
                                                </div>

                                                <!-- Change button overlay -->
                                                <label for="form.image"
                                                    class="absolute inset-0 cursor-pointer flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                    <div
                                                        class="bg-white/90 backdrop-blur-sm rounded-full p-4 shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                                                        <svg class="w-8 h-8 text-indigo-600" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    </div>
                                                </label>
                                            </div>

                                            <!-- Remove button -->
                                            <button type="button" wire:click="$set('form.image', null)"
                                                class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white rounded-full p-2 shadow-lg opacity-0 group-hover:opacity-100 transition-all duration-300 transform hover:scale-110">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>

                                            <input x-ref="fileInput" wire:model="form.image" id="form.image"
                                                type="file" accept="image/*" class="hidden" />
                                        </div>
                                    @else
                                        <!-- Drag & Drop Zone -->
                                        <div @dragover="handleDragOver" @dragleave="handleDragLeave"
                                            @drop="handleDrop"
                                            :class="{ 'border-indigo-500 bg-indigo-50 scale-105': isDragging, 'border-gray-300 bg-gray-50':
                                                    !isDragging }"
                                            class="relative border-2 border-dashed rounded-xl p-8 text-center transition-all duration-300 hover:border-indigo-400 hover:bg-indigo-50/50 cursor-pointer group">

                                            <label for="form.image"
                                                class="cursor-pointer flex flex-col items-center space-y-4">
                                                <!-- Icon avec animation -->
                                                <div class="relative">
                                                    <div
                                                        class="absolute inset-0 bg-indigo-400 rounded-full blur-xl opacity-20 group-hover:opacity-40 transition-opacity duration-300">
                                                    </div>
                                                    <div :class="{ 'scale-110': isDragging }"
                                                        class="relative bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-4 shadow-lg transform transition-all duration-300 group-hover:shadow-xl group-hover:scale-110">
                                                        <svg class="w-12 h-12 text-white" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                        </svg>
                                                    </div>
                                                </div>

                                                <!-- Text -->
                                                <div class="space-y-2">
                                                    <p class="text-base font-semibold text-gray-700">
                                                        <span x-show="!isDragging">Cliquez pour parcourir</span>
                                                        <span x-show="isDragging" class="text-indigo-600">Déposez
                                                            votre image ici</span>
                                                    </p>
                                                    <p class="text-sm text-gray-500">ou glissez-déposez votre fichier
                                                    </p>
                                                </div>

                                                <!-- Formats supportés -->
                                                <div class="flex items-center space-x-4 text-xs text-gray-400">
                                                    <div class="flex items-center space-x-1">
                                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span>PNG, JPG, GIF</span>
                                                    </div>
                                                    <span>•</span>
                                                    <span>Max 2MB</span>
                                                </div>
                                            </label>

                                            <input x-ref="fileInput" wire:model="form.image" id="form.image"
                                                type="file" accept="image/*" class="hidden" />
                                        </div>
                                    @endif

                                    <!-- Error message -->
                                    @error('form.image')
                                        <div class="mt-3 flex items-center p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        </div>
                                    @enderror

                                    <!-- Loading state -->
                                    <div wire:loading wire:target="form.image" class="mt-3">
                                        <div class="flex items-center justify-center space-x-2 text-indigo-600">
                                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                            <span class="text-sm font-medium">Téléchargement en cours...</span>
                                        </div>
                                    </div>
                                </div>
                            </x-optional-section>

                            <!-- Dynamic Attributes (Based on Product Type) -->
                            @if ($form->product_type_id)
                                @livewire('product.dynamic-attributes', ['productTypeId' => $form->product_type_id, 'attributeValues' => $attributeValues], key('dynamic-attrs-' . $form->product_type_id . '-' . ($productId ?? 'new')))
                            @endif

                            <!-- Variant Preview -->
                            @if ($totalVariantsCount > 0)
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-5 shadow-sm animate-fade-in">
                                    <div class="flex items-center space-x-2 mb-4">
                                        <div class="h-8 w-1 bg-gradient-to-b from-green-500 to-emerald-600 rounded-full"></div>
                                        <h3 class="text-lg font-semibold text-gray-800">📦 Aperçu des Variantes</h3>
                                    </div>

                                    <div class="mb-4">
                                        <div class="inline-flex items-center px-4 py-2 bg-green-100 border border-green-300 rounded-lg">
                                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-bold text-green-700">
                                                {{ $totalVariantsCount }} variante(s) seront générées automatiquement
                                            </span>
                                        </div>
                                    </div>

                                    @if (count($variantPreview) > 0)
                                        <div class="space-y-2">
                                            <p class="text-sm font-medium text-gray-700 mb-2">Exemples de variantes :</p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto">
                                                @foreach($variantPreview as $index => $variant)
                                                    <div class="flex items-center p-3 bg-white rounded-lg border border-green-200 shadow-sm hover:shadow-md transition-shadow duration-200">
                                                        <div class="flex-shrink-0 w-6 h-6 bg-green-100 text-green-700 rounded-full flex items-center justify-center text-xs font-bold mr-3">
                                                            {{ $index + 1 }}
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                                {{ implode(' • ', array_map(fn($k, $v) => "$k: $v", array_keys($variant), $variant)) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @if ($totalVariantsCount > count($variantPreview))
                                                <p class="text-xs text-gray-500 mt-2 text-center">
                                                    ... et {{ $totalVariantsCount - count($variantPreview) }} autre(s) variante(s)
                                                </p>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="mt-4 p-3 bg-green-100 border border-green-300 rounded-lg">
                                        <div class="flex items-start">
                                            <svg class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="ml-3">
                                                <p class="text-sm text-green-700">
                                                    <strong>Info:</strong> Chaque variante aura son propre SKU, stock et pourra avoir un prix différent.
                                                    Vous pourrez gérer le stock de chaque variante individuellement.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Variants (Optional) -->
                            <x-optional-section title="Variantes" show="showVariants" gradient-from="blue-500"
                                gradient-to="cyan-600">
                                <div class="space-y-3">
                                    @foreach ($variants as $index => $variant)
                                        <div
                                            class="p-4 border border-gray-200 rounded-lg bg-gradient-to-br from-gray-50 to-white hover:border-indigo-300 transition-colors duration-200">
                                            <div class="flex items-center justify-between mb-2">
                                                <h5 class="font-medium text-sm text-gray-900">Variante
                                                    {{ $index + 1 }}</h5>
                                                @if (count($variants) > 1)
                                                    <button type="button"
                                                        wire:click="removeVariant({{ $index }})"
                                                        class="text-red-600 hover:text-red-800 text-xs">
                                                        Supprimer
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                <input wire:model="variants.{{ $index }}.size" type="text"
                                                    placeholder="Taille"
                                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                                <input wire:model="variants.{{ $index }}.color" type="text"
                                                    placeholder="Couleur"
                                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                                <input wire:model="variants.{{ $index }}.sku" type="text"
                                                    placeholder="SKU"
                                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                            </div>

                                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2">
                                                <input wire:model="variants.{{ $index }}.stock_quantity"
                                                    type="number" min="0" placeholder="Qté"
                                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                                <input wire:model="variants.{{ $index }}.additional_price"
                                                    type="number" step="0.01" placeholder="Prix +"
                                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                                <input wire:model="variants.{{ $index }}.low_stock_threshold"
                                                    type="number" min="0" placeholder="Seuil bas"
                                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                                <input wire:model="variants.{{ $index }}.min_stock_threshold"
                                                    type="number" min="0" placeholder="Seuil min"
                                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" />
                                            </div>
                                        </div>
                                    @endforeach

                                    <button type="button" wire:click="addVariant"
                                        class="w-full px-4 py-3 text-sm font-medium border-2 border-dashed border-indigo-300 rounded-lg bg-indigo-50/50 hover:bg-indigo-100 text-indigo-700 hover:border-indigo-400 transition-all duration-200 flex items-center justify-center group">
                                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                        Ajouter une variante
                                    </button>
                                </div>
                            </x-optional-section>

                            <!-- Info Box -->
                            <div
                                class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-xl p-4 shadow-sm">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-6 w-6 text-indigo-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-indigo-700">
                                            Les produits peuvent avoir des variantes (taille, couleur) pour mieux gérer
                                            votre inventaire et les options de vente.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div
                            class="flex-shrink-0 bg-gradient-to-r from-gray-50 to-white px-6 py-5 flex items-center justify-end space-x-3 border-t border-gray-200 shadow-inner rounded-b-2xl">
                            <button type="button" @click="$wire.close()"
                                class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 shadow-sm hover:shadow">
                                Annuler
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105">
                                <svg wire:loading.remove wire:target="save" class="w-5 h-5 mr-2" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <svg wire:loading wire:target="save" class="animate-spin w-5 h-5 mr-2"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span wire:loading.remove wire:target="save">
                                    {{ $productId ? 'Modifier' : 'Créer' }}
                                </span>
                                <span wire:loading wire:target="save">
                                    Enregistrement...
                                </span>
                            </button>
                        </div>
                    </form>
            </div>
        </div>
    </div>
    @endif
</div>
