# Prompt pour Implémentation des Transferts Inter-Magasins dans Flutter

## Contexte

Je souhaite implémenter un module complet de gestion des transferts de produits entre magasins dans mon application Flutter. Mon backend Laravel expose une API REST complète pour gérer ces transferts (voir API_TRANSFERTS.md).

## Objectif

Créer une interface Flutter moderne et intuitive permettant de :
- Visualiser la liste des transferts avec filtres et recherche
- Créer de nouveaux transferts de produits
- Approuver les transferts en attente
- Réceptionner les transferts en transit
- Annuler les transferts
- Afficher les détails complets d'un transfert

## Architecture Requise

### 1. Structure des Dossiers

```
lib/
├── models/
│   ├── transfer.dart
│   ├── transfer_item.dart
│   └── store.dart
├── services/
│   └── transfer_service.dart
├── providers/
│   └── transfer_provider.dart
├── screens/
│   ├── transfers/
│   │   ├── transfers_list_screen.dart
│   │   ├── transfer_detail_screen.dart
│   │   ├── transfer_create_screen.dart
│   │   └── transfer_receive_screen.dart
└── widgets/
    └── transfers/
        ├── transfer_card.dart
        ├── transfer_status_badge.dart
        ├── transfer_filter_bottom_sheet.dart
        └── transfer_item_card.dart
```

## Spécifications Détaillées

### 1. Modèles de Données

#### Transfer Model
Créer un modèle `Transfer` avec les propriétés suivantes :
- `id` : int
- `transferNumber` : String (référence unique)
- `status` : enum TransferStatus (pending, inTransit, completed, cancelled)
- `statusLabel` : String
- `fromStore` : Store
- `toStore` : Store
- `items` : List<TransferItem>
- `itemsCount` : int
- `requester` : User (nom, email)
- `approver` : User? (optionnel)
- `receiver` : User? (optionnel)
- `canceller` : User? (optionnel)
- `transferDate` : DateTime
- `createdAt` : DateTime
- `approvedAt` : DateTime?
- `receivedAt` : DateTime?
- `cancelledAt` : DateTime?
- `notes` : String?
- `cancellationReason` : String?
- `canApprove` : bool
- `canReceive` : bool
- `canCancel` : bool

Méthodes requises :
- `fromJson(Map<String, dynamic> json)` : factory constructor
- `toJson()` : Map<String, dynamic>

#### TransferItem Model
- `id` : int
- `product` : ProductInfo (id, name, reference)
- `variant` : VariantInfo (id, name, sku)
- `quantityRequested` : int
- `quantitySent` : int?
- `quantityReceived` : int?
- `quantityDifference` : int?
- `isComplete` : bool
- `hasShortage` : bool
- `notes` : String?

### 2. Service API (transfer_service.dart)

Créer un service avec les méthodes suivantes :

```dart
class TransferService {
  final String baseUrl;
  final String token;

  // GET /api/transfers
  Future<PaginatedResponse<Transfer>> getTransfers({
    String? search,
    String? status,
    int? storeId,
    int? fromStoreId,
    int? toStoreId,
    String? direction, // 'outgoing', 'incoming', 'all'
    String sortBy = 'created_at',
    String sortDirection = 'desc',
    int page = 1,
    int perPage = 15,
  });

  // GET /api/transfers/{id}
  Future<Transfer> getTransfer(int id);

  // POST /api/transfers
  Future<Transfer> createTransfer({
    required int fromStoreId,
    required int toStoreId,
    String? notes,
    required List<TransferItemInput> items,
  });

  // POST /api/transfers/{id}/approve
  Future<Transfer> approveTransfer(int id);

  // POST /api/transfers/{id}/receive
  Future<Transfer> receiveTransfer(int id, Map<int, int> quantities, {String? notes});

  // POST /api/transfers/{id}/cancel
  Future<Transfer> cancelTransfer(int id, String reason);
}
```

### 3. Provider State Management (Riverpod ou Provider)

Créer un `TransferProvider` qui :
- Gère l'état de la liste des transferts
- Gère les filtres et la pagination
- Expose les méthodes pour les actions CRUD
- Gère le loading et les erreurs
- Cache les données pour optimiser les performances

### 4. Écrans UI

#### A. TransfersListScreen

**Fonctionnalités :**
- AppBar avec titre "Transferts" et bouton "+" pour créer
- Onglets ou chips pour filtrer par direction :
  * Tous
  * Sortants (depuis mon magasin)
  * Entrants (vers mon magasin)
- Barre de recherche pour filtrer par référence
- Filtres avancés (bouton avec bottom sheet) :
  * Statut (pending, in_transit, completed, cancelled)
  * Magasin source
  * Magasin destination
  * Date de création
- Liste des transferts avec cards :
  * Référence du transfert
  * Statut avec badge coloré
  * Magasin source → Magasin destination
  * Nombre d'articles
  * Date de création
  * Actions rapides selon le statut
- Pull-to-refresh
- Pagination infinie ou bouton "Charger plus"
- État vide avec illustration si aucun transfert

**Design :**
- Cards avec elevation légère
- Badges de statut colorés :
  * Pending : Orange/Amber
  * In Transit : Blue
  * Completed : Green
  * Cancelled : Red
- Icônes représentant les magasins et la direction
- Animation de transition lors du tap

#### B. TransferDetailScreen

**Sections :**
1. **Header** :
   - Référence du transfert (grand et bold)
   - Badge de statut
   - Date de création

2. **Informations des magasins** :
   - Card avec magasin source (avec icône sortie)
   - Flèche ou icône de transfert au milieu
   - Card avec magasin destination (avec icône entrée)

3. **Timeline/Historique** :
   - Liste verticale avec checkpoints :
     * Créé par [nom] le [date]
     * Approuvé par [nom] le [date] (si approuvé)
     * Reçu par [nom] le [date] (si reçu)
     * Annulé par [nom] le [date] (si annulé)

4. **Liste des articles** :
   - Cards pour chaque article avec :
     * Image du produit (si disponible)
     * Nom du produit + variante
     * SKU
     * Quantités (demandée, envoyée, reçue selon le statut)
     * Badge si quantité différente reçue

5. **Notes** :
   - Si présentes, afficher dans une card dédiée

6. **Boutons d'action** (en bas, sticky) :
   - Selon le statut et les permissions :
     * Approuver (si pending et can_approve)
     * Réceptionner (si in_transit et can_receive)
     * Annuler (si pending/in_transit et can_cancel)

#### C. TransferCreateScreen

**Étapes (Stepper ou PageView) :**

**Étape 1 : Sélection des magasins**
- Dropdown ou liste pour sélectionner le magasin source
- Dropdown ou liste pour sélectionner le magasin destination
- Validation : les deux magasins doivent être différents

**Étape 2 : Ajout des produits**
- Barre de recherche pour trouver des produits
- Liste des produits disponibles avec :
  * Photo
  * Nom + variante
  * SKU
  * Stock disponible dans le magasin source
  * Bouton "+" pour ajouter
- Bottom sheet ou dialog pour saisir la quantité
- Liste des produits ajoutés (modifiable) :
  * Possibilité d'ajuster la quantité
  * Possibilité de supprimer
  * Total des articles

**Étape 3 : Notes et confirmation**
- Champ de texte pour notes optionnelles
- Résumé du transfert :
  * Magasins
  * Nombre d'articles
  * Liste complète
- Bouton "Créer le transfert"

**Validation :**
- Au moins 1 article
- Quantités > 0 et <= stock disponible
- Magasins différents

#### D. TransferReceiveScreen

**Fonctionnalités :**
- Afficher la liste des articles du transfert
- Pour chaque article :
  * Nom + variante
  * SKU
  * Quantité envoyée
  * Champ input pour la quantité reçue (pré-rempli avec quantité envoyée)
  * Boutons +/- pour ajuster facilement
  * Indicateur visuel si quantité différente
- Champ de notes optionnelles pour expliquer les différences
- Récapitulatif en bas :
  * Total articles attendus
  * Total articles reçus
  * Différences (s'il y en a)
- Bouton "Confirmer la réception"
- Dialog de confirmation avant validation

### 5. Widgets Réutilisables

#### TransferStatusBadge
- Badge coloré avec texte du statut
- Couleurs différentes selon le statut
- Icône optionnelle

#### TransferCard (pour la liste)
- Card compact avec toutes les infos essentielles
- Action rapide selon le statut
- Tap pour voir les détails

#### TransferFilterBottomSheet
- Bottom sheet avec tous les filtres disponibles
- Chips pour sélection multiple
- Boutons "Réinitialiser" et "Appliquer"

### 6. Gestion des Erreurs

- Toast ou SnackBar pour les erreurs réseau
- Dialog pour les erreurs critiques
- Retry automatique avec exponential backoff
- Affichage d'un message d'erreur si la liste ne charge pas
- État de chargement avec shimmer effect

### 7. Fonctionnalités Supplémentaires Souhaitées

- **Notifications** : Alertes lors de nouveaux transferts entrants
- **Scan QR/Barcode** : Pour ajouter rapidement des produits lors de la création
- **Export PDF** : Générer un bon de transfert en PDF
- **Historique** : Voir tous les transferts passés
- **Statistiques** : Dashboard avec métriques (transferts par mois, par magasin, etc.)
- **Mode hors-ligne** : Cache local pour consulter les transferts récents
- **Photos** : Possibilité de prendre des photos lors de la réception

### 8. Design System

**Palette de couleurs pour les statuts :**
- Pending : Colors.orange[600]
- In Transit : Colors.blue[600]
- Completed : Colors.green[600]
- Cancelled : Colors.red[600]

**Typographie :**
- Titre : TextStyle(fontSize: 24, fontWeight: FontWeight.bold)
- Sous-titre : TextStyle(fontSize: 16, fontWeight: FontWeight.w600)
- Corps : TextStyle(fontSize: 14)
- Caption : TextStyle(fontSize: 12, color: Colors.grey[600])

**Espacement :**
- Padding général : 16.0
- Spacing entre éléments : 12.0
- Cards elevation : 2.0

### 9. Dépendances Recommandées

```yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0  # ou dio pour les requêtes HTTP
  provider: ^6.1.1  # ou riverpod
  intl: ^0.18.1  # pour les dates
  cached_network_image: ^3.3.0  # pour les images
  shimmer: ^3.0.0  # pour le loading skeleton
  flutter_barcode_scanner: ^2.0.0  # pour le scan
  pull_to_refresh: ^2.0.0  # pour le refresh
  infinite_scroll_pagination: ^4.0.0  # pour la pagination
```

### 10. API Configuration

**Base URL :** `https://your-domain.com/api`

**Headers requis :**
```dart
{
  'Content-Type': 'application/json',
  'Accept': 'application/json',
  'Authorization': 'Bearer $token',
}
```

**Gestion du token :**
- Stocker le token dans secure_storage
- Interceptor pour ajouter automatiquement le token
- Rafraîchir le token si expiré (401)
- Rediriger vers login si non authentifié

### 11. Tests

Prévoir des tests pour :
- Parsing JSON des modèles
- Méthodes du service API
- Logique du provider
- Widgets clés (cards, badges, etc.)

## Critères de Qualité

- ✅ Code propre et bien structuré
- ✅ Gestion des erreurs robuste
- ✅ UI/UX fluide et intuitive
- ✅ Performance optimisée (lazy loading, cache)
- ✅ Responsive (support tablette et mobile)
- ✅ Accessibilité (labels, contraste, taille de texte)
- ✅ Commentaires dans le code pour les parties complexes
- ✅ Animations et transitions douces

## Livrables Attendus

1. **Modèles** : transfer.dart, transfer_item.dart, store.dart
2. **Service** : transfer_service.dart avec toutes les méthodes API
3. **Provider** : transfer_provider.dart pour la gestion d'état
4. **Écrans** :
   - transfers_list_screen.dart
   - transfer_detail_screen.dart
   - transfer_create_screen.dart
   - transfer_receive_screen.dart
5. **Widgets** :
   - transfer_card.dart
   - transfer_status_badge.dart
   - transfer_filter_bottom_sheet.dart
   - transfer_item_card.dart
6. **Utils** (si nécessaire) :
   - api_client.dart
   - constants.dart
   - helpers.dart

## Exemples de Réponses API

Voir le fichier `API_TRANSFERTS.md` pour les structures JSON complètes des réponses API.

## Notes Importantes

1. **Authentification** : Toutes les requêtes nécessitent un token Bearer
2. **Permissions** : Vérifier `can_approve`, `can_receive`, `can_cancel` avant d'afficher les boutons
3. **Validation côté client** : Valider les données avant l'envoi (quantités, magasins différents, etc.)
4. **Feedback utilisateur** : Toujours afficher un indicateur de chargement et des messages de succès/erreur
5. **Optimisation** : Utiliser la pagination pour ne pas charger tous les transferts d'un coup
6. **Cache** : Mettre en cache les transferts récents pour une meilleure UX

## Questions à Clarifier

1. Quelle bibliothèque de state management préférez-vous ? (Provider, Riverpod, BLoC, GetX)
2. Utilisez-vous déjà des packages pour les requêtes HTTP ? (http, dio)
3. Y a-t-il un design system existant à respecter ?
4. Souhaitez-vous un mode sombre ?
5. Quelles plateformes ciblez-vous ? (iOS, Android, Web, Desktop)

---

**Prompt Complet pour IA :**

"Implémente un module complet de gestion des transferts inter-magasins dans mon application Flutter selon les spécifications ci-dessus. Crée tous les fichiers nécessaires (modèles, services, providers, écrans, widgets) avec un code propre, commenté et suivant les meilleures pratiques Flutter. Utilise [Provider/Riverpod/BLoC] pour la gestion d'état et [http/dio] pour les requêtes API. Assure-toi que l'UI est moderne, intuitive et responsive. Inclus la gestion des erreurs, le loading state, et les animations appropriées."
