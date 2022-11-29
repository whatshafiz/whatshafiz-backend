<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            'whatsappGroups.*',
            'whatsappGroups.view',
            'whatsappGroups.list',
            'whatsappGroups.update',
            'whatsappGroups.create',
            'whatsappGroups.delete',

            'users.*',
            'users.view',
            'users.list',
            'users.update',
            'users.create',
            'users.delete',

            'roles.*',
            'roles.view',
            'roles.list',
            'roles.update',
            'roles.create',
            'roles.delete',

            'quiz.*',
            'quiz.view',
            'quiz.list',
            'quiz.update',
            'quiz.create',
            'quiz.delete',

            'regulations.*',
            'regulations.view',
            'regulations.list',
            'regulations.update',
            'regulations.create',
            'regulations.delete',

            'periods.*',
            'periods.view',
            'periods.list',
            'periods.update',
            'periods.create',
            'periods.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $roles = [
            'Admin',
            'Moderator',
            'Denetmen',
            'Grup Başkanı',
            'HafızOl',
            'HafızKal',
        ];

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Moderator'])
            ->givePermissionTo([
                'whatsappGroups.*',
                'users.*',
                'quiz.*',
                'regulations.*',
            ]);
        Role::create(['name' => 'Denetmen'])
            ->givePermissionTo([
                'whatsappGroups.view',
                'whatsappGroups.list',
                'users.view',
                'users.update',
            ]);
        Role::create(['name' => 'Grup Başkanı'])
            ->givePermissionTo([
                'whatsappGroups.update',
                'users.view',
                'users.update',
            ]);
        Role::create(['name' => 'HafızOl']);
        Role::create(['name' => 'HafızKal']);
    }
}
