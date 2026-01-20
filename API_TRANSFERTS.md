# API de Gestion des Transferts Inter-Magasins

## Authentification

Toutes les routes API nécessitent une authentification via Laravel Sanctum. Incluez le token dans le header :

```
Authorization: Bearer {votre_token}
```

## Base URL

```
/api/transfers
```

---

## Endpoints

### 1. Liste des transferts

**GET** `/api/transfers`

Récupère la liste des transferts avec pagination et filtres.

#### Paramètres Query (optionnels)

- `search` (string) : Recherche par référence
- `status` (string) : Filtrer par statut (`pending`, `in_transit`, `completed`, `cancelled`)
- `store_id` (int) : Filtrer les transferts liés à un magasin (source ou destination)
- `from_store_id` (int) : Filtrer par magasin source
- `to_store_id` (int) : Filtrer par magasin destination
- `direction` (string) : Direction par rapport au magasin actuel (`outgoing`, `incoming`, `all`)
- `sort_by` (string) : Champ de tri (défaut: `created_at`)
- `sort_direction` (string) : Direction du tri (`asc`, `desc`, défaut: `desc`)
- `per_page` (int) : Nombre d'éléments par page (défaut: 15, max: 100)

#### Exemple de requête

```bash
curl -X GET "https://your-domain.com/api/transfers?status=pending&direction=outgoing&per_page=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Réponse (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "transfer_number": "TRF-2026-0001",
      "reference": "TRF-2026-0001",
      "status": "pending",
      "status_label": "En attente d'approbation",
      "from_store": {
        "id": 1,
        "name": "Magasin Central",
        "code": "MC",
        "address": "123 Rue Principale"
      },
      "to_store": {
        "id": 2,
        "name": "Magasin Secondaire",
        "code": "MS",
        "address": "456 Avenue Commerce"
      },
      "items": [
        {
          "id": 1,
          "product": {
            "id": 10,
            "name": "Produit Test",
            "reference": "PROD-001"
          },
          "variant": {
            "id": 15,
            "name": "Variante Standard",
            "sku": "PROD-001-STD"
          },
          "quantity_requested": 50,
          "quantity_sent": null,
          "quantity_received": null,
          "quantity_difference": null,
          "is_complete": false,
          "has_shortage": false,
          "notes": null
        }
      ],
      "items_count": 1,
      "requester": {
        "id": 5,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "approver": null,
      "receiver": null,
      "canceller": null,
      "transfer_date": "2026-01-20 10:30:00",
      "created_at": "2026-01-20 10:30:00",
      "approved_at": null,
      "received_at": null,
      "cancelled_at": null,
      "notes": "Transfert urgent",
      "cancellation_reason": null,
      "can_approve": true,
      "can_receive": false,
      "can_cancel": true
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 45,
    "from": 1,
    "to": 15
  }
}
```

---

### 2. Détails d'un transfert

**GET** `/api/transfers/{id}`

Récupère les détails complets d'un transfert spécifique.

#### Exemple de requête

```bash
curl -X GET "https://your-domain.com/api/transfers/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Réponse (200 OK)

Structure identique à un élément de la liste des transferts.

#### Réponse d'erreur (404 Not Found)

```json
{
  "success": false,
  "message": "Transfer not found"
}
```

---

### 3. Créer un transfert

**POST** `/api/transfers`

Crée un nouveau transfert entre deux magasins.

#### Body (JSON)

```json
{
  "from_store_id": 1,
  "to_store_id": 2,
  "notes": "Transfert mensuel de stock",
  "items": [
    {
      "product_variant_id": 15,
      "quantity": 50,
      "notes": "Vérifier la qualité"
    },
    {
      "product_variant_id": 20,
      "quantity": 100
    }
  ]
}
```

#### Validation

- `from_store_id` : Requis, doit exister dans la table `stores`
- `to_store_id` : Requis, doit exister, doit être différent de `from_store_id`
- `notes` : Optionnel, string
- `items` : Requis, array, minimum 1 élément
  - `product_variant_id` : Requis, doit exister dans la table `product_variants`
  - `quantity` : Requis, entier, minimum 1
  - `notes` : Optionnel, string

#### Exemple de requête

```bash
curl -X POST "https://your-domain.com/api/transfers" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "from_store_id": 1,
    "to_store_id": 2,
    "notes": "Transfert urgent",
    "items": [
      {
        "product_variant_id": 15,
        "quantity": 50
      }
    ]
  }'
```

#### Réponse (201 Created)

```json
{
  "success": true,
  "message": "Transfer created successfully",
  "data": {
    // Structure complète du transfert créé
  }
}
```

#### Réponse d'erreur (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "from_store_id": ["The from store id field is required."],
    "items": ["The items field must have at least 1 items."]
  }
}
```

---

### 4. Approuver un transfert

**POST** `/api/transfers/{id}/approve`

Approuve un transfert en attente et le passe en statut "en transit". Le stock est retiré du magasin source.

#### Exemple de requête

```bash
curl -X POST "https://your-domain.com/api/transfers/1/approve" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

#### Réponse (200 OK)

```json
{
  "success": true,
  "message": "Transfer approved successfully",
  "data": {
    // Structure complète du transfert approuvé
  }
}
```

#### Réponse d'erreur (500 Internal Server Error)

```json
{
  "success": false,
  "message": "Transfer cannot be approved in current status"
}
```

---

### 5. Réceptionner un transfert

**POST** `/api/transfers/{id}/receive`

Réceptionne un transfert en transit. Permet d'ajuster les quantités reçues si elles diffèrent des quantités envoyées.

#### Body (JSON)

```json
{
  "quantities": {
    "1": 48,
    "2": 95
  },
  "notes": "2 articles endommagés pendant le transport"
}
```

- Les clés de `quantities` correspondent aux IDs des items du transfert
- Les valeurs sont les quantités réellement reçues

#### Validation

- `quantities` : Requis, objet/array
- `quantities.*` : Requis, entier, minimum 0
- `notes` : Optionnel, string

#### Exemple de requête

```bash
curl -X POST "https://your-domain.com/api/transfers/1/receive" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "quantities": {
      "1": 48,
      "2": 100
    },
    "notes": "Réception partielle"
  }'
```

#### Réponse (200 OK)

```json
{
  "success": true,
  "message": "Transfer received successfully",
  "data": {
    // Structure complète du transfert réceptionné
  }
}
```

---

### 6. Annuler un transfert

**POST** `/api/transfers/{id}/cancel`

Annule un transfert en attente ou en transit. Si le transfert était en transit, le stock est restauré dans le magasin source.

#### Body (JSON)

```json
{
  "reason": "Produits non disponibles"
}
```

#### Validation

- `reason` : Requis, string

#### Exemple de requête

```bash
curl -X POST "https://your-domain.com/api/transfers/1/cancel" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "reason": "Demande annulée par le client"
  }'
```

#### Réponse (200 OK)

```json
{
  "success": true,
  "message": "Transfer cancelled successfully",
  "data": {
    // Structure complète du transfert annulé
  }
}
```

---

## Statuts des transferts

| Statut | Description | Actions disponibles |
|--------|-------------|-------------------|
| `pending` | En attente d'approbation | Approuver, Supprimer |
| `in_transit` | En transit vers le magasin destination | Réceptionner, Annuler |
| `completed` | Transfert complété et réceptionné | Aucune |
| `cancelled` | Transfert annulé | Aucune |

---

## Codes d'erreur

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Créé avec succès |
| 400 | Requête invalide |
| 401 | Non authentifié |
| 403 | Non autorisé |
| 404 | Ressource non trouvée |
| 422 | Erreur de validation |
| 500 | Erreur serveur |

---

## Notes importantes

1. **Authentification** : Toutes les requêtes nécessitent un token Sanctum valide
2. **Permissions** : L'utilisateur doit avoir les permissions appropriées pour effectuer les actions
3. **Stock** : Le système vérifie automatiquement la disponibilité du stock avant l'approbation
4. **Traçabilité** : Tous les mouvements de stock sont enregistrés avec l'utilisateur et l'horodatage
5. **Pagination** : La liste des transferts est paginée par défaut (15 éléments par page)
