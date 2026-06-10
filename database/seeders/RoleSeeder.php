<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        $roles = [
            ['name' => 'admin',       'description' => 'Administrator'],
            ['name' => 'superviseur', 'description' => 'Superviseur'],
            ['name' => 'caissier',    'description' => 'Caissier'],
            ['name' => 'agent',       'description' => 'Agent'],
        ];

        $modules = [
            'Apps',
            'Configuration',
            'Utilisateurs',
        ];

        $prefixes = ['lire', 'creer', 'editer', 'supprimer'];

        $buildPermissions = function (array $groups) use ($prefixes): array {
            $permissions = [];
            foreach ($groups as $group) {
                $suffix = trim(str_replace('gerer ', '', $group['name']));
                $permissions[] = ['name' => $group['name'],               'group' => $suffix, 'module_name' => $group['module_name']];
                foreach ($prefixes as $prefix) {
                    $permissions[] = ['name' => $prefix . '_' . $suffix,  'group' => $suffix, 'module_name' => $group['module_name']];
                }
            }
            return $permissions;
        };

        // ── Agent ─────────────────────────────────────────────────────────────
        // Effectuer les transactions, tirer son rapport journalier,
        // voir ses données sur l'accueil, voir les transactions en attente.
        $agentPermissions = array_merge(
            $buildPermissions([
                ['name' => 'gerer transactions', 'module_name' => $modules[0]],
                ['name' => 'gerer clients',      'module_name' => $modules[0]],
            ]),
            [
                ['name' => 'lire_rapports',             'group' => 'rapports',           'module_name' => $modules[0]],
                ['name' => 'lire_tableau_bord',         'group' => 'tableau_bord',       'module_name' => $modules[0]],
                // Opérations sur comptes clients
                ['name' => 'deposer_comptes_clients',   'group' => 'comptes_clients',    'module_name' => $modules[0]],
                ['name' => 'retirer_comptes_clients',   'group' => 'comptes_clients',    'module_name' => $modules[0]],
                // Lecture nécessaire pour remplir le formulaire de transaction
                ['name' => 'lire_types_operations',     'group' => 'types_operations',   'module_name' => $modules[1]],
                ['name' => 'lire_branches',             'group' => 'branches',           'module_name' => $modules[1]],
                ['name' => 'lire_portefeuilles',        'group' => 'portefeuilles',      'module_name' => $modules[1]],
                ['name' => 'lire_devises',              'group' => 'devises',            'module_name' => $modules[1]],
                ['name' => 'lire_regles_frais',         'group' => 'regles_frais',       'module_name' => $modules[1]],
            ]
        );

        // ── Caissier ──────────────────────────────────────────────────────────
        // Charger le ravitaillement interne + compléter des transactions.
        $caissierPermissions = array_merge(
            $buildPermissions([
                ['name' => 'gerer ravitaillements', 'module_name' => $modules[0]],
            ]),
            [
                // Lecture contexte dashboard
                ['name' => 'lire_transactions',         'group' => 'transactions',      'module_name' => $modules[0]],
                ['name' => 'lire_clients',              'group' => 'clients',           'module_name' => $modules[0]],
                ['name' => 'lire_portefeuilles',        'group' => 'portefeuilles',     'module_name' => $modules[1]],
                ['name' => 'lire_branches',             'group' => 'branches',          'module_name' => $modules[1]],
                ['name' => 'lire_types_operations',     'group' => 'types_operations',  'module_name' => $modules[1]],
                ['name' => 'lire_regles_frais',         'group' => 'regles_frais',      'module_name' => $modules[1]],
                // Créer + compléter une transaction de ravitaillement
                ['name' => 'creer_transactions',        'group' => 'transactions',      'module_name' => $modules[0]],
                ['name' => 'editer_transactions',       'group' => 'transactions',      'module_name' => $modules[0]],
                // Opérations sur comptes clients
                ['name' => 'deposer_comptes_clients',   'group' => 'comptes_clients',   'module_name' => $modules[0]],
                ['name' => 'retirer_comptes_clients',   'group' => 'comptes_clients',   'module_name' => $modules[0]],
                // Rapport global comptes clients VIP (dashboard) — pas accessible à l'agent
                ['name' => 'lire_rapport_comptes_clients', 'group' => 'comptes_clients', 'module_name' => $modules[0]],
            ]
        );

        // ── Superviseur ───────────────────────────────────────────────────────
        // Tirer les rapports par Agent et audit, annuler une transaction,
        // gérer les opérations sur les comptes clients.
        $superviseurPermissions = array_merge(
            $buildPermissions([
                ['name' => 'gerer rapports',    'module_name' => $modules[0]],
                ['name' => 'gerer validations', 'module_name' => $modules[0]],
                ['name' => 'gerer demandes',    'module_name' => $modules[0]],
            ]),
            [
                ['name' => 'lire_logs',               'group' => 'logs',              'module_name' => $modules[1]],
                // Annuler une transaction (route dédiée)
                ['name' => 'annuler_transactions',    'group' => 'transactions',      'module_name' => $modules[0]],
                // Lecture nécessaire au fonctionnement du dashboard
                ['name' => 'lire_transactions',       'group' => 'transactions',      'module_name' => $modules[0]],
                ['name' => 'lire_clients',            'group' => 'clients',           'module_name' => $modules[0]],
                ['name' => 'lire_branches',           'group' => 'branches',          'module_name' => $modules[1]],
                ['name' => 'lire_types_operations',   'group' => 'types_operations',  'module_name' => $modules[1]],
                ['name' => 'lire_portefeuilles',      'group' => 'portefeuilles',     'module_name' => $modules[1]],
                // Opérations sur comptes clients VIP/standard
                ['name' => 'deposer_comptes_clients', 'group' => 'comptes_clients',   'module_name' => $modules[0]],
                ['name' => 'retirer_comptes_clients', 'group' => 'comptes_clients',   'module_name' => $modules[0]],
                // Rapport global comptes clients VIP (dashboard) — pas accessible à l'agent
                ['name' => 'lire_rapport_comptes_clients', 'group' => 'comptes_clients', 'module_name' => $modules[0]],
            ]
        );

        // ── Admin ─────────────────────────────────────────────────────────────
        // All of the above + full configuration + user management.
        $adminPermissions = array_merge(
            $agentPermissions,
            $caissierPermissions,
            $superviseurPermissions,
            $buildPermissions([
                ['name' => 'gerer logs',             'module_name' => $modules[1]],
                ['name' => 'gerer devises',          'module_name' => $modules[1]],
                ['name' => 'gerer networks',         'module_name' => $modules[1]],
                ['name' => 'gerer operateurs',       'module_name' => $modules[1]],
                ['name' => 'gerer guichets',         'module_name' => $modules[1]],
                ['name' => 'gerer portefeuilles',    'module_name' => $modules[1]],
                ['name' => 'gerer types_operations', 'module_name' => $modules[1]],
                ['name' => 'gerer frais',            'module_name' => $modules[1]],
                ['name' => 'gerer pays',             'module_name' => $modules[1]],
                ['name' => 'gerer branches',         'module_name' => $modules[1]],
                ['name' => 'gerer impressions',      'module_name' => $modules[1]],
                ['name' => 'gerer regles_frais',     'module_name' => $modules[1]],
                ['name' => 'gerer roles',            'module_name' => $modules[2]],
                ['name' => 'gerer permissions',      'module_name' => $modules[2]],
                ['name' => 'gerer utilisateurs',     'module_name' => $modules[2]],
            ]),
            [
                ['name' => 'read_timelines', 'group' => 'timelines', 'module_name' => $modules[2]],
            ]
        );

        $allPermissions = collect($adminPermissions)
            ->unique('name')
            ->map(fn($perm) => Permission::firstOrCreate(['name' => $perm['name']], $perm));

        $agentPermissionNames       = collect($agentPermissions)->pluck('name')->unique()->toArray();
        $caissierPermissionNames    = collect($caissierPermissions)->pluck('name')->unique()->toArray();
        $superviseurPermissionNames = collect($superviseurPermissions)->pluck('name')->unique()->toArray();

        $adminRole = null;
        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(['name' => $roleData['name']], $roleData);

            match ($role->name) {
                'admin'       => (function () use ($role, $allPermissions, &$adminRole) {
                    $role->syncPermissions($allPermissions);
                    $adminRole = $role;
                })(),
                'superviseur' => $role->syncPermissions($superviseurPermissionNames),
                'caissier'    => $role->syncPermissions($caissierPermissionNames),
                'agent'       => $role->syncPermissions($agentPermissionNames),
                default       => null,
            };
        }

        // $admin = User::updateOrCreate(
        //     ['email' => config('mail.admin')],
        //     [
        //         'uuid'             => (new User)->newUniqueId(),
        //         'name'             => 'TAMS ADMIN',
        //         'username'         => 'admin',
        //         'gender'           => 'M',
        //         'password'         => Hash::make('pct*2MQ$X0pya@zkh4ekb'),
        //         'country_code'     => 243,
        //         'phone_number'     => '979575151',
        //         'timezone'         => 'Africa/Harare',
        //         'locale'           => 'fr',
        //         'email_verified_at'=> now(),
        //         'active'           => true,
        //         'remember_token'   => Str::random(10),
        //     ]
        // );

        // $admin->assignRole($adminRole);
    }
}
