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
            'groups.*',
            'groups.show',
            'groups.list',
            'groups.update',
            'groups.create',
            'groups.delete',

            'users.*',
            'users.show',
            'users.list',
            'users.update',
            'users.create',
            'users.delete',

            'roles.*',
            'roles.show',
            'roles.list',
            'roles.update',
            'roles.create',
            'roles.delete',

            'quiz.*',
            'quiz.show',
            'quiz.list',
            'quiz.update',
            'quiz.create',
            'quiz.delete',

            'regulations.*',
            'regulations.show',
            'regulations.list',
            'regulations.update',
            'regulations.create',
            'regulations.delete',

            'periods.*',
            'periods.show',
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
                'groups.*',
                'users.*',
                'quiz.*',
                'regulations.*',
            ]);
        Role::create(['name' => 'Denetmen'])
            ->givePermissionTo([
                'groups.show',
                'groups.list',
                'users.show',
                'users.update',
            ]);
        Role::create(['name' => 'Grup Başkanı'])
            ->givePermissionTo([
                'groups.update',
                'users.show',
                'users.update',
            ]);
        Role::create(['name' => 'HafızOl']);
        Role::create(['name' => 'HafızKal']);
    }
}
