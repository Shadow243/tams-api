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

        $roles = [['name' => 'admin','description' => 'Administrator'], ['name' => 'caissier','description' => 'Cashier'], ['name' => 'superviseur','description' => 'Supervisor']];

        $modules = [
            'Apps',
            'Configuration',
            'Utilisateurs',
        ];

        $prefixes = [
            'lire',
            'creer',
            'editer',
            'supprimer',
        ];

        $caissierGroups = [
            [
                'name' => 'gerer transactions',
                'module_name' => $modules[0]
            ]
        ];

        $caissierPermissions = [];

        foreach ($caissierGroups as $cGroup) {
            $suffix = trim(str_replace('gerer ', '', $cGroup['name']));

            $caissierPermissions[] = [
                'name' => $cGroup['name'],
                'group' => $suffix,
                'module_name' => $cGroup['module_name']
            ];

            $caissierPermissions = array_merge(
                $caissierPermissions,
                array_map(function ($prefix) use ($suffix, $cGroup) {
                    return [
                        'name' => $prefix . '_' . $suffix,
                        'group' => $suffix,
                        'module_name' => $cGroup['module_name']
                    ];
                }, $prefixes)
            );
        }

        $superviseurGroups = [
            [
                'name' => 'gerer validations',
                'module_name' => $modules[0]
            ],
            [
                'name' => 'gerer demandes',
                'module_name' => $modules[0]
            ],
            [
                'name' => 'gerer rapports',
                'module_name' => $modules[0]
            ],
            [
                'name' => 'gerer devises',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer networks',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer operateurs',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer guichets',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer frais',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer pays',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer operateurs',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer impressions',
                'module_name' => $modules[1]
            ],
        ];

        $superviseurPermissions = [];

        foreach ($superviseurGroups as $sGroup) {
            $suffix = trim(str_replace('gerer ', '', $sGroup['name']));

            $superviseurPermissions[] = [
                'name' => $sGroup['name'],
                'group' => $suffix,
                'module_name' => $sGroup['module_name']
            ];

            $superviseurPermissions = array_merge(
                $superviseurPermissions,
                array_map(function ($prefix) use ($suffix, $sGroup) {
                    return [
                        'name' => $prefix . '_' . $suffix,
                        'group' => $suffix,
                        'module_name' => $sGroup['module_name']
                    ];
                }, $prefixes)
            );
        }

        $adminGroups = [ //ADMIN
            [
                'name' => 'gerer logs',
                'module_name' => $modules[1]
            ],
            [
                'name' => 'gerer roles',
                'module_name' => $modules[2]
            ],
            [
                'name' => 'gerer permissions',
                'module_name' => $modules[2]
            ],
            [
                'name' => 'gerer utilisateurs',
                'module_name' => $modules[2]
            ],
            [
                'name' => 'gerer impressions',
                'module_name' => $modules[1]
            ]
        ];

        $adminPermissions = array_merge($caissierPermissions, $superviseurPermissions, [
            [
                'name' => 'read_timelines',
                'group' => 'timelines',
                'module_name' => $modules[2]
            ],
        ]);

        foreach ($adminGroups as $aGroup) {
            $suffix = trim(str_replace('gerer ', '', $aGroup['name']));
            $adminPermissions[] = [
                'name' => $aGroup['name'],
                'group' => $suffix,
                'module_name' => $aGroup['module_name']
            ];
            $adminPermissions = array_merge(
                $adminPermissions,
                array_map(function ($prefix) use ($suffix, $aGroup) {
                    return [
                        'name' => $prefix . '_' . $suffix,
                        'group' => $suffix,
                        'module_name' => $aGroup['module_name']
                    ];
                }, $prefixes)
            );
        }

        // Save permission names for caissier and superviseur before merging
        $caissierPermissionNames = collect($caissierPermissions)->pluck('name')->toArray();
        $superviseurPermissionNames = collect($superviseurPermissions)->pluck('name')->toArray();

        // Create all permissions - deduplicate by name and use proper firstOrCreate
        $allPermissions = collect($adminPermissions)
            ->unique('name')
            ->map(function ($perm) {
                return Permission::firstOrCreate(
                    ['name' => $perm['name']], // search criteria (matches unique constraint)
                    $perm // default attributes if creating new record
                );
            });

        // dd($allPermissions);

        $adminRole = null;
        foreach ($roles as $role) {
            $role = Role::firstOrCreate(['name' => $role['name']], $role);
            if ($role->name === 'admin') {
                $role->givePermissionTo($allPermissions);
                $adminRole = $role;
            } elseif ($role->name === 'caissier') {
                $role->givePermissionTo($caissierPermissionNames);
            } elseif ($role->name === 'superviseur') {
                $role->givePermissionTo($superviseurPermissionNames);
            }
        }

        $admin = User::updateOrCreate(
            ['email' => config('mail.admin')],
            [
                'uuid' => (new User)->newUniqueId(),
                'name' => 'TAMS ADMIN',
                'username' => 'admin',
                'gender' => 'M',
                'password' => Hash::make('pct*2MQ$X0pya@zkh4ekb'),
                'country_code' => 243,
                'phone_number' => '979575151',
                'timezone' => 'Africa/Harare',
                'locale' => 'fr',
                'email_verified_at' => now(),
                'active' => true,
                'remember_token' => Str::random(10)
            ]
        );

        $admin->assignRole($adminRole);
    }
}
