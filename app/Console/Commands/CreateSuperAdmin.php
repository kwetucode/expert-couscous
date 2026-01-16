<?php

namespace App\Console\Commands;

use App\Models\MenuItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-super-admin
                            {--name= : Nom de l\'utilisateur}
                            {--email= : Email de l\'utilisateur}
                            {--password= : Mot de passe de l\'utilisateur}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un utilisateur super-admin avec accès complet aux menus: Dashboard, Gestion des utilisateurs, Rôles, Gestion des menus, Paramètres d\'abonnement';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║           CRÉATION D\'UN SUPER ADMINISTRATEUR               ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Collecter les informations
        $name = $this->option('name') ?: $this->ask('Nom de l\'utilisateur');
        $email = $this->option('email') ?: $this->ask('Email de l\'utilisateur');
        $password = $this->option('password') ?: $this->secret('Mot de passe (min 8 caractères)');

        // Validation
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Le nom est requis.',
            'name.min' => 'Le nom doit contenir au moins 2 caractères.',
            'email.required' => 'L\'email est requis.',
            'email.email' => 'L\'email doit être valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
        ]);

        if ($validator->fails()) {
            $this->error('');
            $this->error('❌ Erreurs de validation:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('   • ' . $error);
            }
            return self::FAILURE;
        }

        // Vérifier que le rôle super-admin existe
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if (!$superAdminRole) {
            $this->error('');
            $this->error('❌ Le rôle "super-admin" n\'existe pas. Exécutez d\'abord: php artisan db:seed --class=RoleSeeder');
            return self::FAILURE;
        }

        // Confirmation
        $this->info('');
        $this->info('📋 Récapitulatif:');
        $this->table(
            ['Paramètre', 'Valeur'],
            [
                ['Nom', $name],
                ['Email', $email],
                ['Rôle', 'Super Admin'],
            ]
        );
        $this->info('');
        $this->info('📌 Accès aux menus:');
        $this->info('   • Tableau de bord (Dashboard)');
        $this->info('   • Gestion des utilisateurs');
        $this->info('   • Gestion des rôles');
        $this->info('   • Gestion des menus');
        $this->info('   • Paramètres d\'abonnement');
        $this->info('');

        if (!$this->confirm('Voulez-vous créer ce super-admin?', true)) {
            $this->warn('Opération annulée.');
            return self::SUCCESS;
        }

        try {
            DB::beginTransaction();

            // Créer l'utilisateur
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
                'role' => 'admin', // Champ legacy
            ]);

            // Assigner le rôle super-admin
            $user->roles()->attach($superAdminRole->id);

            // Configurer les accès aux menus pour le rôle super-admin
            $this->configureMenuAccess($superAdminRole);

            DB::commit();

            $this->info('');
            $this->info('╔════════════════════════════════════════════════════════════╗');
            $this->info('║              ✅ SUPER-ADMIN CRÉÉ AVEC SUCCÈS               ║');
            $this->info('╚════════════════════════════════════════════════════════════╝');
            $this->info('');
            $this->info('🔑 Identifiants de connexion:');
            $this->table(
                ['', ''],
                [
                    ['📧 Email', $email],
                    ['🔒 Mot de passe', str_repeat('*', strlen($password))],
                ]
            );
            $this->info('');
            $this->info('📌 Note: Ce super-admin gère l\'ensemble de l\'application');
            $this->info('   et n\'appartient à aucune organisation spécifique.');
            $this->info('');
            $this->warn('⚠️  Conservez ces informations en lieu sûr!');
            $this->info('');

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('');
            $this->error('❌ Erreur lors de la création: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Configurer les accès aux menus pour le super-admin
     */
    private function configureMenuAccess(Role $superAdminRole): void
    {
        // Les menus auxquels le super-admin doit avoir accès
        // Note: admin-dashboard est le dashboard principal du super-admin (pas 'dashboard')
        $menuCodes = [
            'admin-dashboard',     // Tableau de bord Super Admin (menu principal)
            'users',               // Gestion des utilisateurs
            'users.index',         // Liste des utilisateurs
            'roles',               // Rôles
            'roles.index',         // Liste des rôles
            'menu-permissions',    // Gestion des menus
            'subscriptions',       // Paramètres d'abonnement
            'organizations',       // Organisations (pour gérer les abonnements)
            'organizations.index',
            'organizations.create',
        ];

        // Récupérer les menus par code
        $menuItems = MenuItem::whereIn('code', $menuCodes)->get();

        if ($menuItems->isEmpty()) {
            $this->warn('Aucun menu trouvé. Exécutez: php artisan db:seed --class=MenuItemSeeder');
            return;
        }

        // Assigner les menus au rôle super-admin (sync sans détacher les existants)
        $superAdminRole->menus()->syncWithoutDetaching($menuItems->pluck('id')->toArray());

        $this->info('');
        $this->info('📋 Menus configurés:');
        foreach ($menuItems as $menu) {
            $this->info('   ✓ ' . $menu->name . ' (' . $menu->code . ')');
        }
    }
}
