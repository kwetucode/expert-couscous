<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Role;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer tous les rôles
        $superAdmin = Role::where('name', 'super-admin')->orWhere('slug', 'super-admin')->first();
        $admin = Role::where('name', 'admin')->orWhere('slug', 'admin')->first();
        $manager = Role::where('name', 'manager')->orWhere('slug', 'manager')->first();
        $seller = Role::where('name', 'vendeur')->orWhere('name', 'seller')->orWhere('slug', 'vendeur')->first();

        // Définition des menus
        $menus = [
            // Dashboard Super Admin - accessible uniquement au super-admin (ordre 0 = premier)
            [
                'name' => 'Administration',
                'code' => 'admin-dashboard',
                'route' => 'admin.dashboard',
                'section' => null,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                'order' => 0,
                'roles' => ['super-admin'],
            ],

            // Dashboard - accessible à tous SAUF super-admin (le super-admin utilise admin-dashboard)
            [
                'name' => 'Tableau de bord',
                'code' => 'dashboard',
                'route' => 'dashboard',
                'section' => null,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
                'order' => 1,
                'roles' => ['admin', 'manager', 'vendeur', 'seller'],
            ],

            // === INVENTAIRE ===
            [
                'name' => 'Produits',
                'code' => 'products',
                'section' => 'Inventaire',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                'order' => 1,
                'badge_type' => 'count',
                'badge_color' => 'green',
                'roles' => ['super-admin', 'admin', 'manager'],
                'children' => [
                    ['name' => 'Liste des produits', 'code' => 'products.index', 'route' => 'products.index', 'order' => 1],
                    ['name' => 'Catégories', 'code' => 'categories.index', 'route' => 'categories.index', 'order' => 2],
                    ['name' => 'Types de produits', 'code' => 'product-types.index', 'route' => 'product-types.index', 'order' => 3],
                ],
            ],
            [
                'name' => 'Stock',
                'code' => 'stock',
                'section' => 'Inventaire',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
                'order' => 2,
                'roles' => ['super-admin', 'admin', 'manager'],
                'children' => [
                    ['name' => 'État du stock', 'code' => 'stock.overview', 'route' => 'stock.overview', 'order' => 1],
                    ['name' => 'Mouvements', 'code' => 'stock.index', 'route' => 'stock.index', 'order' => 2],
                    ['name' => 'Statistiques', 'code' => 'stock.dashboard', 'route' => 'stock.dashboard', 'order' => 3],
                    ['name' => 'Alertes Stock', 'code' => 'stock.alerts', 'route' => 'stock.alerts', 'order' => 4, 'badge_type' => 'count', 'badge_color' => 'red'],
                ],
            ],

            // === TRANSACTIONS ===
            [
                'name' => 'Caisse (POS)',
                'code' => 'pos',
                'route' => 'pos.cash-register',
                'section' => 'Transactions',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                'order' => 1,
                'badge_type' => 'text',
                'badge_color' => 'red',
                'roles' => ['super-admin', 'admin', 'manager', 'vendeur', 'seller'],
            ],
            [
                'name' => 'Ventes',
                'code' => 'sales',
                'section' => 'Transactions',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
                'order' => 2,
                'roles' => ['super-admin', 'admin', 'manager', 'vendeur', 'seller'],
                'children' => [
                    ['name' => 'Liste des ventes', 'code' => 'sales.index', 'route' => 'sales.index', 'order' => 1],
                    ['name' => 'Nouvelle vente', 'code' => 'sales.create', 'route' => 'sales.create', 'order' => 2],
                ],
            ],
            [
                'name' => 'Achats',
                'code' => 'purchases',
                'section' => 'Transactions',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>',
                'order' => 3,
                'roles' => ['super-admin', 'admin', 'manager'],
                'children' => [
                    ['name' => 'Liste des achats', 'code' => 'purchases.index', 'route' => 'purchases.index', 'order' => 1],
                    ['name' => 'Nouvel achat', 'code' => 'purchases.create', 'route' => 'purchases.create', 'order' => 2],
                ],
            ],
            [
                'name' => 'Factures',
                'code' => 'invoices',
                'section' => 'Transactions',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                'order' => 4,
                'roles' => ['super-admin', 'admin', 'manager'],
                'children' => [
                    ['name' => 'Liste des factures', 'code' => 'invoices.index', 'route' => 'invoices.index', 'order' => 1],
                    ['name' => 'Nouvelle facture', 'code' => 'invoices.create', 'route' => 'invoices.create', 'order' => 2],
                ],
            ],
            [
                'name' => 'Proformas',
                'code' => 'proformas',
                'section' => 'Transactions',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
                'order' => 5,
                'roles' => ['super-admin', 'admin', 'manager'],
                'children' => [
                    ['name' => 'Liste des proformas', 'code' => 'proformas.index', 'route' => 'proformas.index', 'order' => 1],
                    ['name' => 'Nouvelle proforma', 'code' => 'proformas.create', 'route' => 'proformas.create', 'order' => 2],
                ],
            ],

            // === CONTACTS ===
            [
                'name' => 'Clients',
                'code' => 'clients',
                'section' => 'Contacts',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
                'order' => 1,
                'badge_type' => 'count',
                'badge_color' => 'green',
                'roles' => ['super-admin', 'admin', 'manager', 'vendeur', 'seller'],
                'children' => [
                    ['name' => 'Liste des clients', 'code' => 'clients.index', 'route' => 'clients.index', 'order' => 1],
                ],
            ],
            [
                'name' => 'Fournisseurs',
                'code' => 'suppliers',
                'section' => 'Contacts',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                'order' => 2,
                'badge_type' => 'count',
                'badge_color' => 'purple',
                'roles' => ['super-admin', 'admin', 'manager'],
                'children' => [
                    ['name' => 'Liste des fournisseurs', 'code' => 'suppliers.index', 'route' => 'suppliers.index', 'order' => 1],
                ],
            ],

            // === MULTI-MAGASINS ===
            [
                'name' => 'Magasins',
                'code' => 'stores',
                'section' => 'Multi-Magasins',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                'order' => 1,
                'roles' => ['super-admin', 'admin'],
                'children' => [
                    ['name' => 'Liste des magasins', 'code' => 'stores.index', 'route' => 'stores.index', 'order' => 1],
                ],
            ],
            [
                'name' => 'Transferts',
                'code' => 'transfers',
                'section' => 'Multi-Magasins',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>',
                'order' => 2,
                'roles' => ['super-admin', 'admin', 'manager'],
                'children' => [
                    ['name' => 'Liste des transferts', 'code' => 'transfers.index', 'route' => 'transfers.index', 'order' => 1],
                ],
            ],
            [
                'name' => 'Organisations',
                'code' => 'organizations',
                'section' => 'Multi-Magasins',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
                'order' => 3,
                'roles' => ['super-admin', 'admin'],
                'children' => [
                    ['name' => 'Mes organisations', 'code' => 'organizations.index', 'route' => 'organizations.index', 'order' => 1],
                    ['name' => 'Créer une organisation', 'code' => 'organizations.create', 'route' => 'organizations.create', 'order' => 2],
                ],
            ],
            [
                'name' => 'Abonnements',
                'code' => 'subscriptions',
                'route' => null,
                'url' => '/organizations',
                'section' => 'Multi-Magasins',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>',
                'order' => 4,
                'roles' => ['super-admin', 'admin'],
            ],

            // === ADMINISTRATION ===
            [
                'name' => 'Utilisateurs',
                'code' => 'users',
                'section' => 'Administration',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                'order' => 1,
                'roles' => ['super-admin', 'admin'],
                'children' => [
                    ['name' => 'Liste des utilisateurs', 'code' => 'users.index', 'route' => 'users.index', 'order' => 1],
                ],
            ],
            [
                'name' => 'Rôles',
                'code' => 'roles',
                'section' => 'Administration',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                'order' => 2,
                'roles' => ['super-admin', 'admin'],
                'children' => [
                    ['name' => 'Liste des rôles', 'code' => 'roles.index', 'route' => 'roles.index', 'order' => 1],
                ],
            ],
            [
                'name' => 'Gestion des menus',
                'code' => 'menu-permissions',
                'route' => 'menu-permissions.index',
                'section' => 'Administration',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>',
                'order' => 3,
                'roles' => ['super-admin'],
            ],
        ];

        // Créer les menus
        foreach ($menus as $menuData) {
            $this->createMenuItem($menuData);
        }

        $this->command->info('✅ Menu items créés avec succès !');
    }

    /**
     * Créer un item de menu avec ses enfants et permissions
     */
    private function createMenuItem(array $data, ?MenuItem $parent = null): MenuItem
    {
        $children = $data['children'] ?? [];
        $roles = $data['roles'] ?? [];
        unset($data['children'], $data['roles']);

        $data['parent_id'] = $parent?->id;

        $menuItem = MenuItem::updateOrCreate(
            ['code' => $data['code']],
            $data
        );

        // Attacher les rôles
        if (!empty($roles)) {
            $roleIds = Role::whereIn('name', $roles)->pluck('id')->toArray();
            $menuItem->roles()->sync($roleIds);
        }

        // Créer les enfants
        foreach ($children as $childData) {
            $childData['section'] = $data['section'];
            $childData['roles'] = $roles; // Hériter des rôles du parent par défaut
            $this->createMenuItem($childData, $menuItem);
        }

        return $menuItem;
    }
}
