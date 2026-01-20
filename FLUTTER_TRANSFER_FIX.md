# Guide de correction - Création de transferts Flutter

## Problème identifié

L'API Laravel retourne une erreur de validation lors de la création de transferts depuis Flutter:

```
The items.0.product_variant_id field is required.
```

**Cause:** Le Flutter envoie `product_id` mais l'API attend `product_variant_id`.

---

## Comprendre l'architecture des produits

### Structure de la base de données

```
products (table)
  └── product_variants (table)
        └── store_stocks (pivot table)
              ├── product_variant_id
              ├── store_id
              └── quantity
```

### Relations importantes

1. **Product** → **ProductVariant** (un produit peut avoir plusieurs variantes)
   - Exemple: T-shirt → Variante S, Variante M, Variante L
   - Chaque variante a son propre ID

2. **ProductVariant** → **StoreStock** (une variante peut être dans plusieurs stores)
   - Exemple: Variante T-shirt-M → 10 unités Store A, 5 unités Store B

### Pourquoi utiliser `product_variant_id` ?

- ✅ L'inventaire est géré au niveau **variante**, pas produit
- ✅ Les quantités en stock sont liées aux variantes
- ✅ Les transferts déplacent des variantes spécifiques entre stores
- ❌ Un produit seul n'a pas de quantité (seules les variantes en ont)

---

## Solution: Utiliser product_variant_id

### 1. Règles de validation API

**Endpoint:** `POST /api/mobile/transfers`

**Validation Laravel:**
```php
[
    'from_store_id' => 'required|exists:stores,id',
    'to_store_id' => 'required|exists:stores,id|different:from_store_id',
    'items' => 'required|array|min:1',
    'items.*.product_variant_id' => 'required|exists:product_variants,id',  // ← VARIANT ID requis
    'items.*.quantity' => 'required|integer|min:1',
]
```

### 2. Format de requête INCORRECT (actuel)

```dart
// ❌ NE FONCTIONNE PAS
final body = {
  "from_store_id": 2,
  "to_store_id": 7,
  "items": [
    {
      "product_id": 4,        // ❌ Champ non reconnu par l'API
      "quantity": 5
    }
  ]
};
```

**Erreur retournée:**
```json
{
  "message": "The items.0.product_variant_id field is required.",
  "errors": {
    "items.0.product_variant_id": [
      "The items.0.product_variant_id field is required."
    ]
  }
}
```

### 3. Format de requête CORRECT

```dart
// ✅ FONCTIONNE
final body = {
  "from_store_id": 2,
  "to_store_id": 7,
  "items": [
    {
      "product_variant_id": 12,  // ✅ ID de la variante, pas du produit
      "quantity": 5
    }
  ]
};
```

**Réponse succès:**
```json
{
  "success": true,
  "message": "Transfer created successfully",
  "data": {
    "id": 45,
    "from_store": {...},
    "to_store": {...},
    "items": [...],
    "status": "pending"
  }
}
```

---

## Implémentation Flutter

### Option 1: Modèle Product avec variantes

Si vos modèles Flutter incluent déjà les variantes:

```dart
class Product {
  final int id;
  final String name;
  final List<ProductVariant> variants;  // ← Liste des variantes
  
  Product.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        name = json['name'],
        variants = (json['variants'] as List)
            .map((v) => ProductVariant.fromJson(v))
            .toList();
}

class ProductVariant {
  final int id;           // ← C'est cet ID qu'on doit envoyer
  final String? size;
  final String? color;
  final int stockQuantity;
  
  ProductVariant.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        size = json['size'],
        color = json['color'],
        stockQuantity = json['stock_quantity'] ?? 0;
}
```

**Utilisation:**
```dart
Future<void> createTransfer({
  required int fromStoreId,
  required int toStoreId,
  required Product product,  // Le produit sélectionné
  required int quantity,
}) async {
  // Récupérer le variant ID du produit
  final variantId = product.variants.first.id;  // ← Premier variant si un seul
  
  final response = await http.post(
    Uri.parse('${ApiConfig.baseUrl}/api/mobile/transfers'),
    headers: {
      'Authorization': 'Bearer ${await _getToken()}',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: json.encode({
      'from_store_id': fromStoreId,
      'to_store_id': toStoreId,
      'items': [
        {
          'product_variant_id': variantId,  // ✅ Utiliser variant.id
          'quantity': quantity,
        }
      ],
    }),
  );
  
  if (response.statusCode == 201) {
    print('✅ Transfer created successfully');
    return json.decode(response.body)['data'];
  } else {
    throw Exception('Failed to create transfer: ${response.body}');
  }
}
```

### Option 2: Produit avec plusieurs variantes (sélection)

Si un produit a plusieurs variantes, laissez l'utilisateur choisir:

```dart
Future<void> createTransferWithVariantSelection({
  required int fromStoreId,
  required int toStoreId,
  required Product product,
  required int quantity,
  required BuildContext context,
}) async {
  // Si plusieurs variantes, afficher une boîte de dialogue
  ProductVariant? selectedVariant;
  
  if (product.variants.length > 1) {
    selectedVariant = await showDialog<ProductVariant>(
      context: context,
      builder: (context) => VariantPickerDialog(variants: product.variants),
    );
    
    if (selectedVariant == null) return; // Utilisateur a annulé
  } else {
    selectedVariant = product.variants.first;
  }
  
  final response = await http.post(
    Uri.parse('${ApiConfig.baseUrl}/api/mobile/transfers'),
    headers: {
      'Authorization': 'Bearer ${await _getToken()}',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: json.encode({
      'from_store_id': fromStoreId,
      'to_store_id': toStoreId,
      'items': [
        {
          'product_variant_id': selectedVariant.id,  // ✅ ID de la variante choisie
          'quantity': quantity,
        }
      ],
    }),
  );
  
  if (response.statusCode == 201) {
    print('✅ Transfer created successfully');
  } else {
    print('❌ Error: ${response.body}');
    throw Exception('Failed to create transfer');
  }
}
```

**Widget de sélection de variante:**
```dart
class VariantPickerDialog extends StatelessWidget {
  final List<ProductVariant> variants;
  
  const VariantPickerDialog({required this.variants});
  
  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text('Sélectionner une variante'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: variants.map((variant) {
            return ListTile(
              title: Text(_getVariantLabel(variant)),
              subtitle: Text('Stock: ${variant.stockQuantity}'),
              onTap: () => Navigator.pop(context, variant),
            );
          }).toList(),
        ),
      ),
    );
  }
  
  String _getVariantLabel(ProductVariant variant) {
    final parts = <String>[];
    if (variant.size != null) parts.add(variant.size!);
    if (variant.color != null) parts.add(variant.color!);
    return parts.isEmpty ? 'Variante par défaut' : parts.join(' - ');
  }
}
```

### Option 3: Transferts multiples avec plusieurs produits

```dart
class TransferItem {
  final int productVariantId;  // ✅ Utiliser variantId
  final int quantity;
  
  TransferItem({
    required this.productVariantId,
    required this.quantity,
  });
  
  Map<String, dynamic> toJson() => {
    'product_variant_id': productVariantId,
    'quantity': quantity,
  };
}

Future<void> createBulkTransfer({
  required int fromStoreId,
  required int toStoreId,
  required List<TransferItem> items,
}) async {
  final response = await http.post(
    Uri.parse('${ApiConfig.baseUrl}/api/mobile/transfers'),
    headers: {
      'Authorization': 'Bearer ${await _getToken()}',
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    body: json.encode({
      'from_store_id': fromStoreId,
      'to_store_id': toStoreId,
      'items': items.map((item) => item.toJson()).toList(),
    }),
  );
  
  if (response.statusCode == 201) {
    print('✅ Transfer with ${items.length} items created');
  } else {
    throw Exception('Failed to create transfer: ${response.body}');
  }
}

// Utilisation:
await createBulkTransfer(
  fromStoreId: 2,
  toStoreId: 7,
  items: [
    TransferItem(productVariantId: 12, quantity: 5),
    TransferItem(productVariantId: 15, quantity: 3),
    TransferItem(productVariantId: 20, quantity: 10),
  ],
);
```

---

## Récupérer les variant IDs depuis l'API

### Endpoint: GET /api/mobile/products

**Requête:**
```
GET /api/mobile/products?store_id=2
Authorization: Bearer {token}
```

**Réponse:**
```json
{
  "data": [
    {
      "id": 4,
      "name": "T-shirt Basic",
      "variants": [
        {
          "id": 12,              // ← Utiliser cet ID pour les transferts
          "size": "M",
          "color": "Blue",
          "sku": "TS-M-BLU",
          "stock_quantity": 50,   // Quantité dans le store actuel
          "price": 1500
        },
        {
          "id": 13,
          "size": "L",
          "color": "Blue",
          "sku": "TS-L-BLU",
          "stock_quantity": 30,
          "price": 1500
        }
      ]
    }
  ]
}
```

### Endpoint: GET /api/mobile/products/{id}

Pour un produit spécifique avec toutes ses variantes:

**Requête:**
```
GET /api/mobile/products/4?store_id=2
Authorization: Bearer {token}
```

**Réponse:**
```json
{
  "data": {
    "id": 4,
    "name": "T-shirt Basic",
    "description": "...",
    "variants": [
      {
        "id": 12,
        "size": "M",
        "color": "Blue",
        "stock_quantity": 50,
        "store_stocks": [         // ← Quantités par store
          {
            "store_id": 2,
            "store_name": "Store A",
            "quantity": 50
          },
          {
            "store_id": 7,
            "store_name": "Store B",
            "quantity": 20
          }
        ]
      }
    ]
  }
}
```

---

## Checklist de migration

### Étape 1: Vérifier les modèles
- [ ] Vérifier que `Product` a une propriété `variants: List<ProductVariant>`
- [ ] Vérifier que `ProductVariant` a une propriété `id: int`
- [ ] Tester que l'API retourne bien les variantes dans les réponses

### Étape 2: Modifier le code de transfert
- [ ] Localiser la fonction de création de transfert (chercher `POST /api/mobile/transfers`)
- [ ] Remplacer `"product_id"` par `"product_variant_id"` dans le body JSON
- [ ] Utiliser `product.variants[index].id` au lieu de `product.id`

### Étape 3: Gérer les cas particuliers
- [ ] Produit avec une seule variante → Utiliser `variants.first.id`
- [ ] Produit avec plusieurs variantes → Afficher sélection à l'utilisateur
- [ ] Produit sans variantes → Afficher erreur (ne devrait pas arriver)

### Étape 4: Tester
- [ ] Créer un transfert avec un produit à variante unique
- [ ] Créer un transfert avec un produit multi-variantes
- [ ] Vérifier les logs: plus d'erreur "product_variant_id required"
- [ ] Vérifier dans le backend que le transfert est créé
- [ ] Vérifier que les quantités sont bien déduites du store source

---

## Gestion des erreurs

### Erreur: Variante non trouvée

```json
{
  "message": "The items.0.product_variant_id is invalid.",
  "errors": {
    "items.0.product_variant_id": [
      "The selected items.0.product_variant_id is invalid."
    ]
  }
}
```

**Solution:** Vérifier que l'ID de variante existe et appartient à un produit actif.

```dart
// Validation côté Flutter avant envoi
if (product.variants.isEmpty) {
  throw Exception('Product has no variants available');
}

if (!product.variants.any((v) => v.id == selectedVariantId)) {
  throw Exception('Selected variant does not belong to this product');
}
```

### Erreur: Stock insuffisant

```json
{
  "message": "Insufficient stock in source store",
  "errors": {
    "items.0.quantity": [
      "Requested quantity (50) exceeds available stock (20)"
    ]
  }
}
```

**Solution:** Vérifier le stock disponible avant de permettre le transfert.

```dart
Future<int> getAvailableStock(int variantId, int storeId) async {
  final response = await http.get(
    Uri.parse('${ApiConfig.baseUrl}/api/mobile/products/${productId}?store_id=$storeId'),
    headers: {'Authorization': 'Bearer ${await _getToken()}'},
  );
  
  if (response.statusCode == 200) {
    final data = json.decode(response.body)['data'];
    final variant = (data['variants'] as List)
        .firstWhere((v) => v['id'] == variantId);
    return variant['stock_quantity'] ?? 0;
  }
  return 0;
}

// Utilisation avant le transfert
final availableStock = await getAvailableStock(variantId, fromStoreId);
if (quantity > availableStock) {
  throw Exception('Stock insuffisant: $availableStock disponible(s)');
}
```

---

## Exemple complet de service Flutter

```dart
class TransferService {
  final String baseUrl;
  final AuthService authService;
  
  TransferService({
    required this.baseUrl,
    required this.authService,
  });
  
  /// Créer un transfert entre deux stores
  Future<Map<String, dynamic>> createTransfer({
    required int fromStoreId,
    required int toStoreId,
    required List<TransferItemInput> items,
  }) async {
    // Validation locale
    if (fromStoreId == toStoreId) {
      throw Exception('Les stores source et destination doivent être différents');
    }
    
    if (items.isEmpty) {
      throw Exception('Au moins un produit doit être sélectionné');
    }
    
    final token = await authService.getToken();
    
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/mobile/transfers'),
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: json.encode({
          'from_store_id': fromStoreId,
          'to_store_id': toStoreId,
          'items': items.map((item) => {
            'product_variant_id': item.variantId,  // ✅ Utiliser variantId
            'quantity': item.quantity,
          }).toList(),
        }),
      );
      
      if (response.statusCode == 201) {
        final data = json.decode(response.body);
        print('✅ Transfer #${data['data']['id']} created successfully');
        return data['data'];
      } else if (response.statusCode == 422) {
        // Erreur de validation
        final errors = json.decode(response.body);
        throw ValidationException(
          message: errors['message'],
          errors: Map<String, List<String>>.from(
            errors['errors'].map((key, value) => MapEntry(key, List<String>.from(value))),
          ),
        );
      } else {
        throw Exception('Failed to create transfer: ${response.body}');
      }
    } catch (e) {
      print('❌ Error creating transfer: $e');
      rethrow;
    }
  }
  
  /// Récupérer les détails d'un transfert
  Future<Map<String, dynamic>> getTransfer(int transferId) async {
    final token = await authService.getToken();
    
    final response = await http.get(
      Uri.parse('$baseUrl/api/mobile/transfers/$transferId'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );
    
    if (response.statusCode == 200) {
      return json.decode(response.body)['data'];
    } else {
      throw Exception('Failed to get transfer: ${response.body}');
    }
  }
  
  /// Lister les transferts du store actuel
  Future<List<Map<String, dynamic>>> listTransfers({
    int? storeId,
    int page = 1,
  }) async {
    final token = await authService.getToken();
    
    final queryParams = {
      if (storeId != null) 'store_id': storeId.toString(),
      'page': page.toString(),
    };
    
    final uri = Uri.parse('$baseUrl/api/mobile/transfers')
        .replace(queryParameters: queryParams);
    
    final response = await http.get(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body)['data'];
      return List<Map<String, dynamic>>.from(data);
    } else {
      throw Exception('Failed to list transfers: ${response.body}');
    }
  }
}

/// Classe pour les données d'entrée
class TransferItemInput {
  final int variantId;    // ✅ ID de la variante
  final int quantity;
  
  TransferItemInput({
    required this.variantId,
    required this.quantity,
  });
}

/// Exception personnalisée pour les erreurs de validation
class ValidationException implements Exception {
  final String message;
  final Map<String, List<String>> errors;
  
  ValidationException({
    required this.message,
    required this.errors,
  });
  
  @override
  String toString() => message;
  
  String getFieldError(String field) {
    return errors[field]?.first ?? '';
  }
}
```

---

## Debug et validation

### Logs à activer

```dart
// Dans votre service de transfert
print('📤 Creating transfer:');
print('  From Store: $fromStoreId');
print('  To Store: $toStoreId');
print('  Items: ${items.length}');
items.forEach((item) {
  print('    - Variant #${item.variantId}: ${item.quantity} units');
});

// Après la réponse
print('📥 Response: ${response.statusCode}');
print('   Body: ${response.body}');
```

### Test avec curl

```bash
curl -X POST http://localhost:8000/api/mobile/transfers \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "from_store_id": 2,
    "to_store_id": 7,
    "items": [
      {
        "product_variant_id": 12,
        "quantity": 5
      }
    ]
  }'
```

### Vérification dans la base de données

```sql
-- Vérifier les variant IDs disponibles
SELECT 
  p.id as product_id,
  p.name as product_name,
  pv.id as variant_id,
  pv.sku,
  ss.store_id,
  ss.quantity
FROM products p
JOIN product_variants pv ON pv.product_id = p.id
JOIN store_stocks ss ON ss.product_variant_id = pv.id
WHERE p.id = 4;

-- Vérifier un transfert créé
SELECT * FROM transfers WHERE id = 45;
SELECT * FROM transfer_items WHERE transfer_id = 45;
```

---

## Points clés à retenir

1. ✅ **Toujours utiliser `product_variant_id`** dans les transferts, jamais `product_id`
2. ✅ **Un produit peut avoir plusieurs variantes**, gérer la sélection si nécessaire
3. ✅ **Les quantités en stock sont liées aux variantes**, pas aux produits
4. ✅ **Vérifier le stock disponible** avant de permettre un transfert
5. ✅ **Valider côté Flutter** pour une meilleure UX avant l'envoi API
6. ✅ **Gérer les erreurs de validation** pour informer l'utilisateur clairement

---

## Support

Si le problème persiste:
1. Vérifier que l'API retourne bien les `variants` dans `GET /api/mobile/products`
2. Inspecter les logs réseau Flutter pour voir exactement ce qui est envoyé
3. Tester l'endpoint avec curl/Postman pour isoler le problème
4. Vérifier dans la DB que les `product_variants` ont bien des IDs valides
