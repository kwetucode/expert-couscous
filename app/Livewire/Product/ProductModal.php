<?php

namespace App\Livewire\Product;

use App\Services\ProductService;
use App\Services\ReferenceGeneratorService;
use App\Services\BarcodeGeneratorService;
use App\Livewire\Forms\ProductForm;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductTypeRepository;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductModal extends Component
{
    use WithFileUploads;

    public ProductForm $form;

    public $isOpen = false;
    public $modalTitle = 'Nouveau Produit';
    public $productId = null;
    public $currentImage = null;

    // Variants
    public $variants = [];
    public $showVariants = false;

    // Dynamic attributes
    public $attributeValues = [];

    // Variant preview
    public $variantPreview = [];
    public $totalVariantsCount = 0;

    // Optional fields toggles
    public $showDescription = false;
    public $showImage = false;

    // Filtered categories based on selected product type
    public $filteredCategories = [];

    protected $listeners = [
        'openProductModal' => 'open',
        'editProduct' => 'edit',
        'attributesUpdated' => 'updateAttributeValues',
    ];

    public function open(BarcodeGeneratorService $barcodeGenerator)
    {
        $this->reset(['productId', 'currentImage', 'variants', 'showVariants', 'showDescription', 'showImage', 'attributeValues']);
        $this->form->reset();
        $this->modalTitle = 'Nouveau Produit';

        // Auto-generate barcode for new products
        try {
            $this->form->barcode = $barcodeGenerator->generateEAN13();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération automatique du code-barres: ' . $e->getMessage());
            // Continue without barcode - user can generate manually
        }

        // Charger toutes les catégories au départ
        $this->loadFilteredCategories();

        $this->isOpen = true;
        $this->addVariant();
    }

    public function updateAttributeValues($values)
    {
        $this->attributeValues = $values;
        $this->calculateVariantPreview();
    }

    /**
     * Calculate variant preview based on selected variant attributes
     */
    public function calculateVariantPreview()
    {
        $this->variantPreview = [];
        $this->totalVariantsCount = 0;

        if (empty($this->form->product_type_id) || empty($this->attributeValues)) {
            return;
        }

        $productType = \App\Models\ProductType::with('attributes')->find($this->form->product_type_id);

        if (!$productType || !$productType->has_variants) {
            return;
        }

        // Get variant attributes
        $variantAttributes = $productType->variantAttributes;

        if ($variantAttributes->isEmpty()) {
            return;
        }

        // Build variant options from attribute values
        $variantOptions = [];
        foreach ($variantAttributes as $attribute) {
            $value = $this->attributeValues[$attribute->id] ?? null;

            if (empty($value)) {
                continue;
            }

            // For multi-select attributes, handle arrays
            if (is_array($value)) {
                $variantOptions[$attribute->name] = $value;
            } else {
                $variantOptions[$attribute->name] = [$value];
            }
        }

        if (empty($variantOptions)) {
            return;
        }

        // Generate all combinations
        $combinations = $this->generateCombinations($variantOptions);
        $this->variantPreview = array_slice($combinations, 0, 10); // Show first 10
        $this->totalVariantsCount = count($combinations);
    }

    /**
     * Generate all possible combinations from variant options
     */
    private function generateCombinations(array $options): array
    {
        $combinations = [[]];

        foreach ($options as $attributeName => $values) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($values as $value) {
                    $newCombinations[] = array_merge(
                        $combination,
                        [$attributeName => $value]
                    );
                }
            }
            $combinations = $newCombinations;
        }

        return $combinations;
    }

    public function edit($productId, $product)
    {
        $this->reset(['variants', 'showVariants']);
        $this->productId = $productId;
        $this->modalTitle = 'Modifier le Produit';

        $this->form->name = $product['name'];
        $this->form->description = $product['description'] ?? '';
        $this->form->reference = $product['reference'];
        $this->form->barcode = $product['barcode'] ?? '';
        $this->form->price = $product['price'];
        $this->form->cost_price = $product['cost_price'] ?? '';
        $this->form->category_id = $product['category_id'];
        $this->form->product_type_id = $product['product_type_id'] ?? '';
        $this->form->status = $product['status'];
        $this->form->stock_alert_threshold = $product['stock_alert_threshold'] ?? 10;

        $this->currentImage = $product['image'] ?? null;
        $this->showDescription = !empty($product['description']);
        $this->showImage = !empty($product['image']);

        // Charger les catégories filtrées par type
        $this->loadFilteredCategories();

        // Load existing attribute values if product has a type
        if (!empty($product['product_type_id'])) {
            $this->loadExistingAttributeValues($productId);
        }

        $this->isOpen = true;
    }

    /**
     * Load existing attribute values for editing
     */
    private function loadExistingAttributeValues($productId)
    {
        $product = \App\Models\Product::with(['variants.attributeValues'])->find($productId);

        if ($product && $product->variants->isNotEmpty()) {
            $defaultVariant = $product->variants->first();

            foreach ($defaultVariant->attributeValues as $attrValue) {
                $this->attributeValues[$attrValue->product_attribute_id] = $attrValue->value;
            }
        }
    }

    public function close()
    {
        $this->isOpen = false;
        $this->reset(['productId', 'currentImage', 'variants', 'showVariants', 'showDescription', 'showImage', 'attributeValues']);
        $this->form->reset();
        $this->resetValidation();
    }

    public function updatedFormCategoryId()
    {
        if ($this->form->category_id && !$this->productId) {
            try {
                $referenceGenerator = app(ReferenceGeneratorService::class);



                // Generate unique reference with retry
                $maxRetries = 3;
                $attempt = 0;

                do {
                    $this->form->reference = $referenceGenerator->generateForProduct($this->form->category_id);
                    $attempt++;
                } while ($attempt < $maxRetries && empty($this->form->reference));

                if (empty($this->form->reference)) {
                    throw new \Exception("Impossible de générer une référence unique");
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la génération de la référence: ' . $e->getMessage());
                $this->dispatch('show-toast', message: 'Erreur : Impossible de générer la référence', type: 'error');
            }
        }
    }

    /**
     * Charge les catégories filtrées quand le type de produit change
     */
    public function updatedFormProductTypeId()
    {
        $this->loadFilteredCategories();
        
        // Réinitialiser la catégorie sélectionnée si elle n'appartient plus au type
        if ($this->form->category_id) {
            $categoryBelongsToType = collect($this->filteredCategories)
                ->contains('id', $this->form->category_id);
            
            if (!$categoryBelongsToType) {
                $this->form->category_id = null;
                $this->form->reference = '';
            }
        }
        
        $this->calculateVariantPreview();
    }

    /**
     * Charge les catégories filtrées par type de produit
     */
    private function loadFilteredCategories()
    {
        $categoryRepository = app(CategoryRepository::class);
        
        if (empty($this->form->product_type_id)) {
            // Si aucun type n'est sélectionné, afficher toutes les catégories
            $this->filteredCategories = $categoryRepository->all()->toArray();
        } else {
            // Filtrer les catégories par type de produit
            $this->filteredCategories = \App\Models\Category::where('product_type_id', $this->form->product_type_id)
                ->orderBy('name')
                ->get()
                ->toArray();
        }
    }

    public function addVariant()
    {
        $this->variants[] = [
            'size' => '',
            'color' => '',
            'sku' => '',
            'stock_quantity' => 0,
            'additional_price' => 0,
            'low_stock_threshold' => 10,
            'min_stock_threshold' => 0,
        ];
    }

    public function removeVariant($index)
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function save(ProductService $productService, ReferenceGeneratorService $referenceGenerator)
    {
        try {
            // Validation based on mode (create or update)
            if ($this->productId) {
                $this->form->validate($this->form->getRulesForUpdate($this->productId));
            } else {
                // If creating, check if reference already exists and regenerate if needed
                $maxAttempts = 5;
                $attempt = 0;

                while ($attempt < $maxAttempts) {
                    try {
                        $this->form->validate();
                        break; // Validation successful, exit loop
                    } catch (\Illuminate\Validation\ValidationException $e) {
                        // If reference is duplicate, regenerate it
                        if (isset($e->errors()['form.reference'])) {
                            $attempt++;
                            if ($attempt < $maxAttempts && $this->form->category_id) {
                                // Regenerate reference
                                $this->form->reference = $referenceGenerator->generateForProduct($this->form->category_id);
                                Log::info("Régénération de la référence, tentative {$attempt}");
                            } else {
                                throw $e; // Max attempts reached or no category, throw error
                            }
                        } else {
                            throw $e; // Other validation error, throw it
                        }
                    }
                }
            }

            $data = $this->form->all();

            // En mode édition, on ne doit jamais changer le store_id
            if ($this->productId) {
                unset($data['store_id']);
            }
            // En mode création, ProductService gérera automatiquement le store_id

            // Handle image upload
            if ($this->form->image) {
                $data['image'] = $this->form->image->store('products', 'public');
            } else {
                // Don't include image in update data if no new image is uploaded
                unset($data['image']);
            }

            // Add variants if any
            if ($this->showVariants && !empty($this->variants)) {
                $data['variants'] = array_filter($this->variants, function($variant) {
                    return !empty($variant['size']) || !empty($variant['color']);
                });
            }

            // Add dynamic attributes if any
            if (!empty($this->attributeValues)) {
                $data['attributes'] = $this->attributeValues;
            }

            // Create or update
            if ($this->productId) {
                $productService->updateProduct($this->productId, $data);
                $message = 'Produit modifié avec succès.';
                $type = 'success';
            } else {
                $productService->createProduct($data);
                $message = 'Produit créé avec succès.';
                $type = 'success';
            }

            $this->close();
            $this->dispatch('productSaved');
            $this->dispatch('show-toast', message: $message, type: $type);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde du produit: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('show-toast', message: 'Erreur : ' . $e->getMessage(), type: 'error');
        }
    }

    public function render(CategoryRepository $categoryRepository, ProductTypeRepository $productTypeRepository)
    {
        return view('livewire.product.product-modal', [
            'categories' => $this->filteredCategories,
            'productTypes' => $productTypeRepository->allActive(),
        ]);
    }
}
