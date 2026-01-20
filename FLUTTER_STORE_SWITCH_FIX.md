# Guide d'implémentation Flutter - Correction Store Switch

## Problème identifié

L'application Flutter ne synchronise pas le changement de store avec le backend. Les logs montrent que `current_store` reste à `{"id":7}` même après avoir sélectionné "Tous les stores".

## Solution requise

Le Flutter doit appeler l'API `/api/mobile/switch-store/{storeId}` au lieu de seulement sauvegarder localement.

---

## 1. Correction Store Switching (PRIORITAIRE)

### API Endpoint disponible

```
POST /api/mobile/switch-store/{storeId}
```

**Paramètres:**
- `storeId` (optionnel): ID du store à activer, ou ne pas fournir/envoyer "null" pour "Tous les stores"

**Réponse:**
```json
{
  "success": true,
  "message": "Store switched successfully",
  "data": {
    "current_store": {
      "id": 7,
      "name": "Store Name"
    }
  }
}
```

Quand `storeId` est null ou non fourni (tous les stores):
```json
{
  "success": true,
  "message": "Store switched successfully",
  "data": {
    "current_store": null
  }
}
```

### Code Flutter à modifier

**Localiser le fichier:** Probablement `StoreNotifier` ou `StoreProvider`

**Remplacer ceci:**
```dart
Future<void> switchStore(int? storeId) async {
  // Sauvegarde locale uniquement
  await _prefs.setInt('current_store_id', storeId);
  notifyListeners();
}
```

**Par ceci:**
```dart
Future<void> switchStore(int? storeId) async {
  try {
    final token = await _getAuthToken();
    
    // Construire l'URL selon si storeId est null ou non
    final url = storeId != null 
        ? '${ApiConfig.baseUrl}/api/mobile/switch-store/$storeId'
        : '${ApiConfig.baseUrl}/api/mobile/switch-store/null';
    
    final response = await http.post(
      Uri.parse(url),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    );
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      
      // Sauvegarder localement après confirmation du backend
      if (storeId != null) {
        await _prefs.setInt('current_store_id', storeId);
      } else {
        await _prefs.remove('current_store_id');
      }
      
      // Mettre à jour l'état local
      _currentStoreId = storeId;
      notifyListeners();
      
      // Invalider le cache et rafraîchir les données
      await refreshDashboardData();
      
      print('✅ Store switched successfully to: ${data['data']['current_store']}');
    } else {
      throw Exception('Failed to switch store: ${response.body}');
    }
  } catch (e) {
    print('❌ Error switching store: $e');
    rethrow;
  }
}
```

### Points importants:

1. **URL avec "null" string**: Quand l'utilisateur sélectionne "Tous les stores", envoyer `/switch-store/null` (pas juste `/switch-store`)
2. **Headers requis**: Authorization Bearer token + Accept + Content-Type
3. **Ordre des opérations**: Appel API → Attendre réponse → Sauvegarder localement → Notifier listeners
4. **Rafraîchissement**: Appeler `refreshDashboardData()` après le switch pour recharger les données

---

## 2. Correction Transfer Creation

### Problème

Les logs montrent l'erreur:
```
The items.0.product_variant_id field is required.
```

Le Flutter envoie `product_id` mais l'API attend `product_variant_id`.

### API Validation Rules

```php
'items.*.product_variant_id' => 'required|exists:product_variants,id',
'items.*.quantity' => 'required|integer|min:1',
```

### Code Flutter à modifier

**Remplacer:**
```dart
{
  "from_store_id": 2,
  "to_store_id": 7,
  "items": [
    {
      "product_id": 4,  // ❌ INCORRECT
      "quantity": 5
    }
  ]
}
```

**Par:**
```dart
{
  "from_store_id": 2,
  "to_store_id": 7,
  "items": [
    {
      "product_variant_id": variantId,  // ✅ CORRECT
      "quantity": 5
    }
  ]
}
```

### Code de création de transfert

```dart
Future<void> createTransfer({
  required int fromStoreId,
  required int toStoreId,
  required List<TransferItem> items,
}) async {
  try {
    final token = await _getAuthToken();
    
    final response = await http.post(
      Uri.parse('${ApiConfig.baseUrl}/api/mobile/transfers'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: json.encode({
        'from_store_id': fromStoreId,
        'to_store_id': toStoreId,
        'items': items.map((item) => {
          'product_variant_id': item.variantId, // ✅ Utiliser variantId, pas productId
          'quantity': item.quantity,
        }).toList(),
      }),
    );
    
    if (response.statusCode == 201) {
      print('✅ Transfer created successfully');
    } else {
      print('❌ Transfer creation failed: ${response.body}');
      throw Exception('Failed to create transfer');
    }
  } catch (e) {
    print('❌ Error creating transfer: $e');
    rethrow;
  }
}
```

### Note importante sur les variantes

- Un **Product** peut avoir plusieurs **ProductVariants**
- L'inventaire est géré au niveau **ProductVariant** (pas Product)
- Toujours utiliser `product_variant_id` dans les opérations stock/transferts

**Pour récupérer le variant_id depuis un produit:**
```dart
// Si le produit n'a qu'une seule variante
final variantId = product.variants.first.id;

// Ou afficher une liste de sélection si plusieurs variantes
final selectedVariant = await showVariantPicker(product.variants);
final variantId = selectedVariant.id;
```

---

## 3. Structure de réponse API complète

### Dashboard avec store context

```
GET /api/mobile/dashboard?store_id=7
```

**Réponse:**
```json
{
  "data": {
    "kpis": {
      "today_sales": 1250.50,
      "total_transactions": 42,
      "avg_transaction_value": 29.77,
      "stock_value": 85000.00
    },
    "low_stock_products": [...],
    "out_of_stock_products": [...],
    "recent_transactions": [...],
    "user_context": {
      "current_store": {
        "id": 7,
        "name": "Store Name"
      },
      "can_access_all_stores": false,
      "organization": {...}
    }
  }
}
```

### Quand "Tous les stores" (admin uniquement)

```
GET /api/mobile/dashboard
// ou
GET /api/mobile/dashboard?store_id=null
```

**Réponse:**
```json
{
  "data": {
    "kpis": { ... },
    "user_context": {
      "current_store": null,  // ← Tous les stores
      "can_access_all_stores": true,
      "organization": {...}
    }
  }
}
```

---

## 4. Checklist d'implémentation

### Phase 1: Store Switching
- [ ] Localiser `StoreNotifier` ou équivalent dans le code Flutter
- [ ] Modifier `switchStore()` pour appeler l'API
- [ ] Ajouter gestion d'erreur avec try-catch
- [ ] Tester switch vers un store spécifique
- [ ] Tester switch vers "Tous les stores" (null)
- [ ] Vérifier que les logs montrent le bon `current_store` dans les réponses API

### Phase 2: Transfer Fix
- [ ] Localiser le code de création de transfert
- [ ] Remplacer `product_id` par `product_variant_id`
- [ ] S'assurer que les objets Product ont accès aux variants
- [ ] Tester création de transfert
- [ ] Vérifier dans les logs que le champ est accepté

### Phase 3: Testing complet
- [ ] Login avec utilisateur assigné à un store
- [ ] Vérifier que le dashboard affiche les données du bon store
- [ ] Changer de store via le StoreSwitcher
- [ ] Vérifier que les données se rafraîchissent
- [ ] Login avec admin
- [ ] Tester "Tous les stores"
- [ ] Créer un transfert entre deux stores
- [ ] Vérifier que le transfert est validé

---

## 5. Debug & Validation

### Logs à vérifier

**Avant la correction (INCORRECT):**
```
POST /api/mobile/dashboard
{
  "user_context": {
    "current_store": {"id": 7}  // ← Toujours le même, jamais null
  }
}
```

**Après la correction (CORRECT):**
```
POST /api/mobile/switch-store/null
Response: {
  "data": {
    "current_store": null  // ← Correctement null pour "tous les stores"
  }
}

GET /api/mobile/dashboard
Response: {
  "user_context": {
    "current_store": null  // ← Correspond au backend
  }
}
```

### Commandes de vérification côté Laravel

```bash
# Vérifier les routes API
php artisan route:list --path=mobile

# Tester l'endpoint manuellement
curl -X POST http://localhost:8000/api/mobile/switch-store/7 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Vérifier le current_store_id dans la DB
php artisan tinker
>>> $user = User::find(YOUR_USER_ID);
>>> $user->current_store_id;  // Devrait être null après switch to "all"
```

---

## 6. Endpoints API disponibles

### Authentication
- `POST /api/mobile/login` - Login avec email/password
- `POST /api/mobile/logout` - Logout

### Dashboard
- `GET /api/mobile/dashboard?store_id={id}` - Dashboard avec filtre store
- `POST /api/mobile/switch-store/{storeId}` - Changer de store

### Products
- `GET /api/mobile/products?store_id={id}` - Liste des produits
- `GET /api/mobile/products/{id}?store_id={id}` - Détail produit

### Transfers
- `GET /api/mobile/transfers?store_id={id}` - Liste des transferts
- `POST /api/mobile/transfers` - Créer un transfert
- `GET /api/mobile/transfers/{id}` - Détail transfert

### Sales
- `POST /api/mobile/sales` - Créer une vente
- `GET /api/mobile/sales?store_id={id}` - Liste des ventes

### Stores
- `GET /api/mobile/stores` - Liste des stores accessibles

---

## 7. Notes importantes

### Gestion du cache

Le backend cache les données dashboard pendant 5 minutes (300 secondes) avec des clés incluant le store_id:
```
mobile_report_{organization_id}_{store_id}
```

Quand vous changez de store via l'API, le cache est automatiquement invalidé.

### Permissions

- **Utilisateurs normaux**: Assignés à un store spécifique, ne peuvent pas voir "Tous les stores"
- **Admins**: Peuvent switch vers null pour voir tous les stores
- L'API vérifie automatiquement les permissions

### Architecture multi-store

Le système utilise une relation `storeStocks` (many-to-many) entre ProductVariant et Store:
- Un variant peut avoir des quantités différentes dans différents stores
- Les quantités sont récupérées via `getStoreStock($storeId)`
- Ne jamais utiliser `product.store_id` (obsolète)

---

## Contact & Support

Si vous rencontrez des problèmes après implémentation:
1. Vérifier les logs Flutter pour les erreurs de réponse API
2. Vérifier les logs Laravel (`storage/logs/laravel.log`)
3. Utiliser `php artisan tinker` pour vérifier le `current_store_id` en DB
4. Tester les endpoints avec curl/Postman pour isoler le problème
