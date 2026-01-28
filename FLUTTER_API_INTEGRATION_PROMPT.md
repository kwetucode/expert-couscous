# 🚀 Prompt Flutter - Intégration des nouvelles API Mobile

Ce document contient les instructions pour implémenter les nouvelles fonctionnalités API côté Flutter.

---

## CONTEXTE API

- **Base URL:** `/api/mobile/`
- **Authentification:** Bearer Token (Sanctum)

---

## NOUVEAUX ENDPOINTS À INTÉGRER

### 1. Statistiques des Ventes

**Endpoint:** `GET /api/mobile/sales/statistics`

**Paramètres optionnels:**
| Paramètre | Type | Valeurs |
|-----------|------|---------|
| `period` | string | `today`, `yesterday`, `this_week`, `last_week`, `this_month`, `last_month`, `last_3_months`, `this_year`, `all` |
| `date_from` | string | Format: `YYYY-MM-DD` |
| `date_to` | string | Format: `YYYY-MM-DD` |

**Réponse:**
```json
{
  "success": true,
  "data": {
    "completed": {
      "count": 45,
      "amount": 125000,
      "amount_formatted": "125 000,00"
    },
    "pending": {
      "count": 3,
      "amount": 15000,
      "amount_formatted": "15 000,00"
    },
    "cancelled": {
      "count": 2,
      "amount": 5000
    },
    "totals": {
      "total_sales": 45,
      "total_amount": 125000,
      "pending_sales": 3,
      "pending_amount": 15000,
      "average_ticket": 2777.78
    },
    "payment_methods": [
      {
        "method": "cash",
        "label": "Espèces",
        "count": 30,
        "amount": 80000
      },
      {
        "method": "mobile_money",
        "label": "Mobile Money",
        "count": 15,
        "amount": 45000
      }
    ]
  }
}
```

---

### 2. Historique des Ventes (mis à jour)

**Endpoint:** `GET /api/mobile/sales`

**Paramètres:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `per_page` | int | Nombre d'éléments par page (10-100) |
| `period` | string | Période prédéfinie (voir ci-dessus) |
| `date_from` | string | Date de début |
| `date_to` | string | Date de fin |
| `client_id` | int | **NOUVEAU** - Filtrer par client |
| `status` | string | `completed`, `pending`, `cancelled` |
| `payment_status` | string | **NOUVEAU** - `paid`, `partial`, `unpaid` |
| `payment_method` | string | `cash`, `mobile_money`, `card`, `bank_transfer` |

---

### 3. Mouvements de Stock Groupés

**Endpoint:** `GET /api/mobile/stock/movements/grouped`

**Paramètres:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `per_page` | int | Nombre d'éléments par page (10-100) |
| `type` | string | `in` ou `out` |
| `movement_type` | string | `purchase`, `sale`, `adjustment`, `transfer`, `return` |
| `date_from` | string | Date de début |
| `date_to` | string | Date de fin |

**Réponse:**
```json
{
  "success": true,
  "data": {
    "grouped_movements": [
      {
        "product_variant_id": 1,
        "product_variant": {
          "id": 1,
          "sku": "PROD-001",
          "name": "Produit A - Taille M",
          "product_name": "Produit A",
          "current_stock": 50
        },
        "total_in": 100,
        "total_out": 50,
        "net_change": 50,
        "movement_count": 15,
        "last_date": "2026-01-28"
      }
    ],
    "summary": {
      "total_products": 25,
      "total_movements": 150,
      "total_in": 500,
      "total_out": 350
    },
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 20,
      "total": 25
    }
  }
}
```

---

### 4. Produits avec filtre de stock

**Endpoint:** `GET /api/mobile/products`

**Nouveau paramètre:**
| Paramètre | Type | Valeurs |
|-----------|------|---------|
| `stock_level` | string | `in_stock`, `low_stock`, `out_of_stock` |

---

### 5. Vue d'ensemble du Stock (Stock Overview)

**Endpoint:** `GET /api/mobile/stock/overview`

**Paramètres:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `per_page` | int | Nombre d'éléments par page (10-100) |
| `page` | int | Numéro de page |
| `search` | string | Recherche par nom ou SKU |
| `category_id` | int | Filtrer par catégorie |
| `stock_level` | string | `in_stock`, `low_stock`, `out_of_stock` |
| `sort_by` | string | `stock_quantity`, `name`, `value` |
| `sort_dir` | string | `asc` ou `desc` |

**Réponse:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "total_stock_value": 500000,
      "total_stock_value_formatted": "500 000,00",
      "total_retail_value": 750000,
      "total_retail_value_formatted": "750 000,00",
      "potential_profit": 250000,
      "potential_profit_formatted": "250 000,00",
      "profit_margin_percentage": 33.33,
      "total_products": 25,
      "in_stock_count": 20,
      "out_of_stock_count": 3,
      "low_stock_count": 2,
      "total_units": 500
    },
    "variants": [
      {
        "id": 1,
        "product_id": 1,
        "sku": "PROD-001",
        "product_name": "Produit A",
        "variant_name": "Produit A - Taille M",
        "category": "Vêtements",
        "stock_quantity": 50,
        "low_stock_threshold": 10,
        "status": "in_stock",
        "status_label": "En stock",
        "cost_price": 1000,
        "price": 1500,
        "stock_value": 50000,
        "retail_value": 75000
      }
    ],
    "categories": [
      {"id": 1, "name": "Vêtements"},
      {"id": 2, "name": "Accessoires"}
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 20,
      "total": 50
    }
  }
}
```

---

### 6. Tableau de bord Stock (Stock Dashboard)

**Endpoint:** `GET /api/mobile/stock/dashboard`

**Paramètres:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `date_from` | string | Date de début (défaut: 1er du mois) |
| `date_to` | string | Date de fin (défaut: aujourd'hui) |

**Réponse:**
```json
{
  "success": true,
  "data": {
    "period": {
      "date_from": "2026-01-01",
      "date_to": "2026-01-28"
    },
    "stats": {
      "total_in": 500,
      "total_out": 350,
      "net_movement": 150,
      "total_value_in": 500000,
      "total_value_in_formatted": "500 000,00",
      "total_value_out": 350000,
      "total_value_out_formatted": "350 000,00",
      "total_movements": 120
    },
    "low_stock_products": [
      {
        "id": 1,
        "product_name": "Produit A",
        "variant_name": "Taille M",
        "sku": "PROD-001",
        "stock_quantity": 5,
        "low_stock_threshold": 10,
        "status": "low_stock"
      }
    ],
    "out_of_stock_products": [
      {
        "id": 2,
        "product_name": "Produit B",
        "variant_name": "Taille L",
        "sku": "PROD-002",
        "stock_quantity": 0,
        "status": "out_of_stock"
      }
    ],
    "recent_movements": [
      {
        "id": 1,
        "type": "in",
        "type_label": "Entrée",
        "movement_type": "purchase",
        "quantity": 50,
        "reference": "ACH-202601-0001",
        "date": "2026-01-28",
        "product_variant": {
          "id": 1,
          "sku": "PROD-001",
          "product_name": "Produit A"
        },
        "user": {
          "id": 1,
          "name": "Admin"
        }
      }
    ],
    "alerts_summary": {
      "low_stock_count": 2,
      "out_of_stock_count": 3,
      "total_alerts": 5
    }
  }
}
```

---

### 7. Liste des Alertes de Stock (paginée)

**Endpoint:** `GET /api/mobile/stock/alerts/list`

**Paramètres:**
| Paramètre | Type | Description |
|-----------|------|-------------|
| `per_page` | int | Nombre d'éléments par page (10-100) |
| `alert_type` | string | `all`, `out_of_stock`, `low_stock` |
| `search` | string | Recherche par nom ou SKU |

**Réponse:**
```json
{
  "success": true,
  "data": {
    "variants": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "Produit A",
        "variant_name": "Taille M",
        "sku": "PROD-001",
        "stock_quantity": 0,
        "low_stock_threshold": 10,
        "status": "out_of_stock",
        "status_label": "Rupture",
        "product": {
          "id": 1,
          "name": "Produit A",
          "reference": "REF-001",
          "category": "Vêtements"
        }
      }
    ],
    "summary": {
      "out_of_stock_count": 3,
      "low_stock_count": 2,
      "total_alerts": 5
    },
    "filters": {
      "alert_type": "all",
      "search": null
    },
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 5
    }
  }
}
```

---

### 8. Résumé du Stock (KPIs uniquement)

**Endpoint:** `GET /api/mobile/stock/summary`

**Réponse:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "total_products": 25,
      "in_stock_count": 20,
      "out_of_stock_count": 3,
      "low_stock_count": 2,
      "total_units": 500
    },
    "value": {
      "total_stock_value": 500000,
      "total_stock_value_formatted": "500 000,00",
      "total_retail_value": 750000,
      "total_retail_value_formatted": "750 000,00",
      "potential_profit": 250000,
      "potential_profit_formatted": "250 000,00",
      "profit_margin_percentage": 33.33
    },
    "alerts": {
      "total": 5,
      "out_of_stock": 3,
      "low_stock": 2
    }
  }
}
```

---

## TÂCHES À RÉALISER

### 1. Services/Repositories

Mettre à jour les services API pour supporter les nouveaux endpoints et paramètres:

```dart
// SalesService
Future<SalesStatistics> getStatistics({String? period, DateTime? dateFrom, DateTime? dateTo});
Future<PaginatedResponse<Sale>> getSales({
  int page = 1,
  String? period,
  int? clientId,        // NOUVEAU
  String? paymentStatus, // NOUVEAU
  // ... autres paramètres existants
});

// StockService
Future<GroupedMovementsResponse> getGroupedMovements({
  int page = 1,
  String? type,
  String? movementType,
  DateTime? dateFrom,
  DateTime? dateTo,
});

// NOUVEAU: Stock Overview, Dashboard, Alerts
Future<StockOverviewResponse> getStockOverview({
  int page = 1,
  String? search,
  int? categoryId,
  String? stockLevel,
  String? sortBy,
  String? sortDir,
});

Future<StockDashboardResponse> getStockDashboard({
  DateTime? dateFrom,
  DateTime? dateTo,
});

Future<StockAlertsListResponse> getStockAlertsList({
  int page = 1,
  String? alertType, // all, out_of_stock, low_stock
  String? search,
});

Future<StockSummaryResponse> getStockSummary();

// ProductService
Future<PaginatedResponse<Product>> getProducts({
  // ... paramètres existants
  String? stockLevel, // NOUVEAU: in_stock, low_stock, out_of_stock
});
```

---

### 2. Models/DTOs

Créer ou mettre à jour les modèles:

```dart
// sales_statistics.dart
class SalesStatistics {
  final SalesCount completed;
  final SalesCount pending;
  final SalesCount cancelled;
  final SalesTotals totals;
  final List<PaymentMethodStats> paymentMethods;
}

class SalesCount {
  final int count;
  final double amount;
  final String amountFormatted;
}

class SalesTotals {
  final int totalSales;
  final double totalAmount;
  final int pendingSales;
  final double pendingAmount;
  final double averageTicket;
}

class PaymentMethodStats {
  final String method;
  final String label;
  final int count;
  final double amount;
}

// grouped_movement.dart
class GroupedMovement {
  final int productVariantId;
  final ProductVariantInfo productVariant;
  final int totalIn;
  final int totalOut;
  final int netChange;
  final int movementCount;
  final DateTime lastDate;
}

class MovementSummary {
  final int totalProducts;
  final int totalMovements;
  final int totalIn;
  final int totalOut;
}

// NOUVEAU: stock_overview.dart
class StockOverviewResponse {
  final StockKpis kpis;
  final List<StockVariant> variants;
  final List<CategoryInfo> categories;
  final PaginationInfo pagination;
}

class StockKpis {
  final double totalStockValue;
  final String totalStockValueFormatted;
  final double totalRetailValue;
  final String totalRetailValueFormatted;
  final double potentialProfit;
  final String potentialProfitFormatted;
  final double profitMarginPercentage;
  final int totalProducts;
  final int inStockCount;
  final int outOfStockCount;
  final int lowStockCount;
  final int totalUnits;
}

class StockVariant {
  final int id;
  final int productId;
  final String sku;
  final String productName;
  final String variantName;
  final String? category;
  final int stockQuantity;
  final int lowStockThreshold;
  final String status; // in_stock, low_stock, out_of_stock
  final String statusLabel;
  final double costPrice;
  final double price;
  final double stockValue;
  final double retailValue;
}

// NOUVEAU: stock_dashboard.dart
class StockDashboardResponse {
  final DatePeriod period;
  final MovementStats stats;
  final List<AlertProduct> lowStockProducts;
  final List<AlertProduct> outOfStockProducts;
  final List<RecentMovement> recentMovements;
  final AlertsSummary alertsSummary;
}

class MovementStats {
  final int totalIn;
  final int totalOut;
  final int netMovement;
  final double totalValueIn;
  final String totalValueInFormatted;
  final double totalValueOut;
  final String totalValueOutFormatted;
  final int totalMovements;
}

class AlertProduct {
  final int id;
  final String productName;
  final String variantName;
  final String sku;
  final int stockQuantity;
  final int? lowStockThreshold;
  final String status;
}

class RecentMovement {
  final int id;
  final String type;
  final String typeLabel;
  final String movementType;
  final int quantity;
  final String? reference;
  final DateTime date;
  final ProductVariantInfo? productVariant;
  final UserInfo? user;
}

// NOUVEAU: stock_alerts_list.dart
class StockAlertsListResponse {
  final List<AlertVariant> variants;
  final AlertsSummary summary;
  final AlertFilters filters;
  final PaginationInfo pagination;
}

class AlertVariant {
  final int id;
  final int productId;
  final String productName;
  final String variantName;
  final String sku;
  final int stockQuantity;
  final int lowStockThreshold;
  final String status;
  final String statusLabel;
  final ProductInfo product;
}

class AlertsSummary {
  final int outOfStockCount;
  final int lowStockCount;
  final int totalAlerts;
}
```

---

### 3. State Management (Riverpod/Bloc/Provider)

Ajouter les providers/blocs pour:

```dart
// Avec Riverpod
final salesStatisticsProvider = FutureProvider.family<SalesStatistics, String?>((ref, period) async {
  final service = ref.read(salesServiceProvider);
  return service.getStatistics(period: period);
});

final groupedMovementsProvider = StateNotifierProvider<GroupedMovementsNotifier, AsyncValue<GroupedMovementsState>>((ref) {
  return GroupedMovementsNotifier(ref.read(stockServiceProvider));
});

// Mettre à jour salesProvider avec nouveaux filtres
final salesFiltersProvider = StateProvider<SalesFilters>((ref) => SalesFilters());
```

---

### 4. UI/Screens

#### a) Écran Statistiques Ventes (`SalesStatsScreen`)

```
┌─────────────────────────────────────┐
│  📊 Statistiques des Ventes         │
├─────────────────────────────────────┤
│  [Aujourd'hui ▼] <- PeriodSelector  │
├─────────────────────────────────────┤
│  ┌─────────┐  ┌─────────┐           │
│  │   45    │  │ 125 000 │           │
│  │ Ventes  │  │ Montant │           │
│  └─────────┘  └─────────┘           │
│  ┌─────────┐  ┌─────────┐           │
│  │    3    │  │ 2 778   │           │
│  │En attente│ │ Panier  │           │
│  └─────────┘  └─────────┘           │
├─────────────────────────────────────┤
│  Répartition par paiement           │
│  ┌─────────────────────┐            │
│  │     [PieChart]      │            │
│  │  Cash: 60%          │            │
│  │  Mobile: 40%        │            │
│  └─────────────────────┘            │
└─────────────────────────────────────┘
```

#### b) Écran Historique Ventes (`SalesHistoryScreen`)

Ajouter les filtres:
- Dropdown période (today, this_week, this_month, etc.)
- Recherche/sélection client
- Chips statut paiement (Tous, Payé, Partiel, Impayé)

#### c) Écran Mouvements Stock (`StockMovementsScreen`)

```
┌─────────────────────────────────────┐
│  📦 Mouvements de Stock             │
├─────────────────────────────────────┤
│  [Détaillée] [Groupée] <- Toggle    │
├─────────────────────────────────────┤
│  VUE GROUPÉE:                       │
│  ┌─────────────────────────────────┐│
│  │ Produit A           [15 mvts]  ││
│  │ Stock: 50                      ││
│  │ ↑ +100  ↓ -50  = +50          ││
│  │ Dernier: 28/01/2026           ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ Produit B           [8 mvts]   ││
│  │ Stock: 25                      ││
│  │ ↑ +30   ↓ -20  = +10          ││
│  └─────────────────────────────────┘│
└─────────────────────────────────────┘
```

#### d) Écran Produits (`ProductsScreen`)

Ajouter filtre chips de niveau de stock:

```
[Tous] [En stock] [Stock bas] [Rupture]
```

#### e) NOUVEAU: Écran État du Stock (`StockOverviewScreen`)

```
┌─────────────────────────────────────┐
│  📊 État du Stock                   │
├─────────────────────────────────────┤
│  ┌─────────┐  ┌─────────┐           │
│  │ 500 000 │  │   25    │           │
│  │ Valeur  │  │Produits │           │
│  └─────────┘  └─────────┘           │
│  ┌─────────┐  ┌─────────┐           │
│  │   3     │  │   2     │           │
│  │Ruptures │  │Stock bas│           │
│  └─────────┘  └─────────┘           │
├─────────────────────────────────────┤
│  [🔍 Recherche...              ]    │
│  [Catégorie ▼] [Niveau stock ▼]     │
├─────────────────────────────────────┤
│  Liste des variantes paginée        │
│  ┌─────────────────────────────────┐│
│  │ Produit A - Taille M    [50]   ││
│  │ SKU: PROD-001  🟢 En stock     ││
│  │ Valeur: 50 000                 ││
│  └─────────────────────────────────┘│
└─────────────────────────────────────┘
```

#### f) NOUVEAU: Tableau de Bord Stock (`StockDashboardScreen`)

```
┌─────────────────────────────────────┐
│  📈 Tableau de Bord Stock           │
├─────────────────────────────────────┤
│  Période: [01/01 ▼] - [28/01 ▼]     │
├─────────────────────────────────────┤
│  ┌─────────┐  ┌─────────┐           │
│  │ ↑ 500   │  │ ↓ 350   │           │
│  │ Entrées │  │ Sorties │           │
│  └─────────┘  └─────────┘           │
│  ┌─────────┐  ┌─────────┐           │
│  │ = +150  │  │   120   │           │
│  │ Net     │  │ Mvmts   │           │
│  └─────────┘  └─────────┘           │
├─────────────────────────────────────┤
│  ⚠️ Ruptures de stock (3)           │
│  ┌─────────────────────────────────┐│
│  │ Produit B - SKU: PROD-002      ││
│  │ Produit C - SKU: PROD-003      ││
│  └─────────────────────────────────┘│
├─────────────────────────────────────┤
│  ⚠️ Stock bas (2)                   │
│  ┌─────────────────────────────────┐│
│  │ Produit A - Stock: 5/10        ││
│  └─────────────────────────────────┘│
├─────────────────────────────────────┤
│  📋 Mouvements Récents              │
│  ┌─────────────────────────────────┐│
│  │ ↑ Achat - Produit A   +50     ││
│  │ ↓ Vente - Produit B   -10     ││
│  └─────────────────────────────────┘│
└─────────────────────────────────────┘
```

#### g) NOUVEAU: Alertes Stock (`StockAlertsScreen`)

```
┌─────────────────────────────────────┐
│  🚨 Alertes de Stock                │
├─────────────────────────────────────┤
│  [Toutes] [Ruptures] [Stock bas]    │
├─────────────────────────────────────┤
│  [🔍 Recherche...              ]    │
├─────────────────────────────────────┤
│  Résumé: 3 ruptures, 2 stock bas    │
├─────────────────────────────────────┤
│  ┌─────────────────────────────────┐│
│  │ 🔴 Produit B                   ││
│  │ SKU: PROD-002  Stock: 0        ││
│  │ Catégorie: Vêtements           ││
│  └─────────────────────────────────┘│
│  ┌─────────────────────────────────┐│
│  │ 🟠 Produit A                   ││
│  │ SKU: PROD-001  Stock: 5/10     ││
│  │ Catégorie: Accessoires         ││
│  └─────────────────────────────────┘│
└─────────────────────────────────────┘
```

---

### 5. Widgets réutilisables

```dart
// period_selector.dart
class PeriodSelector extends StatelessWidget {
  final String? selectedPeriod;
  final ValueChanged<String?> onChanged;
  
  static const periods = [
    ('today', 'Aujourd\'hui'),
    ('yesterday', 'Hier'),
    ('this_week', 'Cette semaine'),
    ('last_week', 'Semaine dernière'),
    ('this_month', 'Ce mois'),
    ('last_month', 'Mois dernier'),
    ('last_3_months', '3 derniers mois'),
    ('this_year', 'Cette année'),
    ('all', 'Tout'),
  ];
}

// stats_card.dart
class StatsCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData? icon;
  final Color? color;
}

// movement_summary_card.dart
class MovementSummaryCard extends StatelessWidget {
  final GroupedMovement movement;
  final VoidCallback? onTap;
}

// stock_level_badge.dart
class StockLevelBadge extends StatelessWidget {
  final String level; // in_stock, low_stock, out_of_stock
  
  Color get color => switch(level) {
    'in_stock' => Colors.green,
    'low_stock' => Colors.orange,
    'out_of_stock' => Colors.red,
    _ => Colors.grey,
  };
}
```

---

## STRUCTURE SUGGÉRÉE

```
lib/
├── models/
│   ├── sales_statistics.dart      # Statistiques de ventes
│   ├── grouped_movement.dart      # Mouvements groupés
│   ├── movement_summary.dart      # Résumé mouvement
│   ├── stock_overview.dart        # NOUVEAU - État du stock
│   ├── stock_dashboard.dart       # NOUVEAU - Dashboard stock
│   ├── stock_alert.dart           # NOUVEAU - Alertes stock
│   └── stock_variant.dart         # NOUVEAU - Variante avec stock
├── services/
│   ├── sales_service.dart         # Service ventes
│   └── stock_service.dart         # Service stock (enrichi)
├── providers/ (ou blocs/)
│   ├── sales_stats_provider.dart
│   ├── grouped_movements_provider.dart
│   ├── stock_overview_provider.dart    # NOUVEAU
│   ├── stock_dashboard_provider.dart   # NOUVEAU
│   └── stock_alerts_provider.dart      # NOUVEAU
├── screens/
│   ├── sales/
│   │   ├── sales_stats_screen.dart
│   │   └── sales_history_screen.dart
│   └── stock/
│       ├── stock_movements_screen.dart
│       ├── stock_overview_screen.dart  # NOUVEAU
│       ├── stock_dashboard_screen.dart # NOUVEAU
│       └── stock_alerts_screen.dart    # NOUVEAU
└── widgets/
    ├── period_selector.dart
    ├── stats_card.dart
    ├── kpi_card.dart              # NOUVEAU
    ├── movement_summary_card.dart
    ├── stock_level_badge.dart
    ├── stock_variant_card.dart    # NOUVEAU
    ├── alert_item.dart            # NOUVEAU
    └── movement_item.dart         # NOUVEAU
```

---

## PRIORITÉS

| Priorité | Tâche | Justification |
|----------|-------|---------------|
| 🔴 1 | Modèles et Services | Foundation technique |
| 🔴 2 | Statistiques des ventes | Haute valeur UX |
| 🔴 3 | État du stock (overview) | Vue principale stock |
| 🟡 4 | Dashboard stock | Analyse des mouvements |
| 🟡 5 | Alertes stock | Gestion proactive |
| 🟢 6 | Vue groupée mouvements | Cohérence avec web |
| 🟢 7 | Filtres additionnels | Amélioration UX |

---

## NOTES TECHNIQUES

- ✅ Utiliser `freezed` pour les modèles si disponible dans le projet
- ✅ Gérer le cache des statistiques (5 minutes)
- ✅ Implémenter pull-to-refresh sur tous les écrans de liste
- ✅ Gérer les états `loading` / `error` / `empty`
- ✅ Supporter le mode hors-ligne si applicable
- ✅ Ajouter des tests unitaires pour les nouveaux services
- ✅ Documenter les nouveaux widgets avec des exemples

---

## EXEMPLE D'UTILISATION

### Appel API avec Dio

```dart
// Statistiques des ventes
final response = await dio.get('/api/mobile/sales/statistics', queryParameters: {
  'period': 'this_month',
});
final stats = SalesStatistics.fromJson(response.data['data']);

// Mouvements groupés
final response = await dio.get('/api/mobile/stock/movements/grouped', queryParameters: {
  'per_page': 20,
  'date_from': '2026-01-01',
  'date_to': '2026-01-28',
});
final grouped = GroupedMovementsResponse.fromJson(response.data['data']);

// NOUVEAU: État du stock avec pagination
final response = await dio.get('/api/mobile/stock/overview', queryParameters: {
  'per_page': 20,
  'category_id': 5,
  'stock_level': 'low_stock',
  'search': 'produit',
});
final overview = StockOverviewResponse.fromJson(response.data['data']);

// NOUVEAU: Dashboard stock
final response = await dio.get('/api/mobile/stock/dashboard', queryParameters: {
  'date_from': '2026-01-01',
  'date_to': '2026-01-28',
});
final dashboard = StockDashboardResponse.fromJson(response.data['data']);

// NOUVEAU: Liste alertes paginée
final response = await dio.get('/api/mobile/stock/alerts/list', queryParameters: {
  'per_page': 15,
  'alert_type': 'out_of_stock',
});
final alerts = StockAlertsListResponse.fromJson(response.data['data']);

// Produits avec filtre stock
final response = await dio.get('/api/mobile/products', queryParameters: {
  'stock_level': 'low_stock',
  'per_page': 20,
});
```

---

*Document généré le 28 janvier 2026*
