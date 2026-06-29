<?php

namespace Modules\Blog\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BlogPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->createPermissions();

        $this->createRoles($permissions);
    }

    /**
     * Create all blog permissions.
     */
    protected function createPermissions(): array
    {
        $permissions = [
            // Post permissions
            [
                'name' => 'blog.post.view',
                'description' => 'Ver publicaciones propias del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.post.view-all',
                'description' => 'Ver todas las publicaciones del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.post.create',
                'description' => 'Crear nuevas publicaciones en el blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.post.update',
                'description' => 'Editar publicaciones del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.post.delete',
                'description' => 'Eliminar publicaciones del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.post.publish',
                'description' => 'Publicar y despublicar entradas del blog',
                'guard_name' => 'web',
            ],

            // Category permissions
            [
                'name' => 'blog.category.view',
                'description' => 'Ver categorías del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.category.create',
                'description' => 'Crear categorías del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.category.update',
                'description' => 'Editar categorías del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.category.delete',
                'description' => 'Eliminar categorías del blog',
                'guard_name' => 'web',
            ],

            // Tag permissions
            [
                'name' => 'blog.tag.view',
                'description' => 'Ver etiquetas del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.tag.create',
                'description' => 'Crear etiquetas del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.tag.update',
                'description' => 'Editar etiquetas del blog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'blog.tag.delete',
                'description' => 'Eliminar etiquetas del blog',
                'guard_name' => 'web',
            ],

            // Comment permissions
            [
                'name' => 'blog.comment.moderate',
                'description' => 'Moderar comentarios del blog',
                'guard_name' => 'web',
            ],

            // Settings permission
            [
                'name' => 'blog.settings',
                'description' => 'Gestionar configuración del módulo blog',
                'guard_name' => 'web',
            ],
        ];

        $createdPermissions = [];

        foreach ($permissions as $permission) {
            $createdPermissions[] = Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => $permission['guard_name'],
            ]);

            $this->command->info("Permiso creado: {$permission['name']}");
        }

        return $createdPermissions;
    }

    /**
     * Create roles and assign permissions.
     */
    protected function createRoles(array $permissions): void
    {
        // Blog Admin - full module control
        $blogAdmin = Role::firstOrCreate(['name' => 'blog-admin', 'guard_name' => 'web']);
        $blogAdmin->givePermissionTo([
            'blog.post.view',
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
            'blog.post.delete',
            'blog.post.publish',
            'blog.category.view',
            'blog.category.create',
            'blog.category.update',
            'blog.category.delete',
            'blog.tag.view',
            'blog.tag.create',
            'blog.tag.update',
            'blog.tag.delete',
            'blog.comment.moderate',
            'blog.settings',
        ]);
        $this->command->info('Rol creado: blog-admin');

        // Blog Editor - can manage content, no delete and no settings
        $blogEditor = Role::firstOrCreate(['name' => 'blog-editor', 'guard_name' => 'web']);
        $blogEditor->givePermissionTo([
            'blog.post.view-all',
            'blog.post.create',
            'blog.post.update',
            'blog.post.publish',
            'blog.category.view',
            'blog.category.create',
            'blog.category.update',
            'blog.tag.view',
            'blog.tag.create',
            'blog.tag.update',
        ]);
        $this->command->info('Rol creado: blog-editor');

        // Blog Author - can create own posts and view categories/tags
        $blogAuthor = Role::firstOrCreate(['name' => 'blog-author', 'guard_name' => 'web']);
        $blogAuthor->givePermissionTo([
            'blog.post.view',
            'blog.post.create',
            'blog.category.view',
            'blog.tag.view',
        ]);
        $this->command->info('Rol creado: blog-author');

        // Assign all blog permissions to the global admin role if it exists
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo([
                'blog.post.view',
                'blog.post.view-all',
                'blog.post.create',
                'blog.post.update',
                'blog.post.delete',
                'blog.post.publish',
                'blog.category.view',
                'blog.category.create',
                'blog.category.update',
                'blog.category.delete',
                'blog.tag.view',
                'blog.tag.create',
                'blog.tag.update',
                'blog.tag.delete',
                'blog.settings',
            ]);
            $this->command->info('Permisos de blog asignados al rol: admin');
        }

        $this->command->info('');
        $this->command->info('=================================================');
        $this->command->info('Permisos y roles del blog creados exitosamente!');
        $this->command->info('=================================================');
        $this->command->info('');
        $this->command->info('Roles creados:');
        $this->command->info('  - blog-admin: Acceso completo al módulo');
        $this->command->info('  - blog-editor: Crear, editar y publicar contenido');
        $this->command->info('  - blog-author: Crear y ver sus propias publicaciones');
        $this->command->info('');
    }
}
