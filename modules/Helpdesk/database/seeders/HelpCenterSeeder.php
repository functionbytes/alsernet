<?php

namespace Modules\Helpdesk\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\HelpCenterArticle;
use Modules\Helpdesk\Models\HelpCenterCategory;

class HelpCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías principales
        $category1 = HelpCenterCategory::create([
            'name' => 'Primeros Pasos',
            'description' => 'Todo lo que necesitas saber para comenzar',
            'image' => null,
            'position' => 1,
            'is_section' => false,
        ]);

        $category2 = HelpCenterCategory::create([
            'name' => 'Configuración',
            'description' => 'Guías de configuración del sistema',
            'image' => null,
            'position' => 2,
            'is_section' => false,
        ]);

        $category3 = HelpCenterCategory::create([
            'name' => 'Preguntas Frecuentes',
            'description' => 'Respuestas a las preguntas más comunes',
            'image' => null,
            'position' => 3,
            'is_section' => false,
        ]);

        // Crear secciones para "Primeros Pasos"
        $section1 = HelpCenterCategory::create([
            'name' => 'Instalación',
            'description' => 'Guías de instalación paso a paso',
            'parent_id' => $category1->id,
            'position' => 1,
            'is_section' => true,
        ]);

        $section2 = HelpCenterCategory::create([
            'name' => 'Tutoriales Básicos',
            'description' => 'Aprende lo básico del sistema',
            'parent_id' => $category1->id,
            'position' => 2,
            'is_section' => true,
        ]);

        // Crear secciones para "Configuración"
        $section3 = HelpCenterCategory::create([
            'name' => 'Configuración General',
            'description' => 'Ajustes generales del sistema',
            'parent_id' => $category2->id,
            'position' => 1,
            'is_section' => true,
        ]);

        $section4 = HelpCenterCategory::create([
            'name' => 'Seguridad',
            'description' => 'Configuración de seguridad y permisos',
            'parent_id' => $category2->id,
            'position' => 2,
            'is_section' => true,
        ]);

        // Crear artículos para "Instalación"
        $article1 = HelpCenterArticle::create([
            'title' => 'Cómo instalar el sistema',
            'slug' => 'como-instalar-el-sistema',
            'body' => '<p>Esta es una guía completa sobre cómo instalar el sistema paso a paso.</p><h3>Requisitos previos</h3><ul><li>PHP 8.4+</li><li>PostgreSQL 14+</li><li>Composer</li></ul>',
            'description' => 'Guía completa de instalación',
            'draft' => false,
            'views' => 0,
            'author_id' => 1,
        ]);

        $article2 = HelpCenterArticle::create([
            'title' => 'Configuración del servidor',
            'slug' => 'configuracion-del-servidor',
            'body' => '<p>Aprende a configurar correctamente tu servidor para ejecutar la aplicación.</p>',
            'description' => 'Configuración de servidor web',
            'draft' => false,
            'views' => 0,
            'author_id' => 1,
        ]);

        // Asociar artículos con la sección "Instalación"
        $section1->articles()->attach($article1->id, ['position' => 1]);
        $section1->articles()->attach($article2->id, ['position' => 2]);

        // Crear artículos para "Tutoriales Básicos"
        $article3 = HelpCenterArticle::create([
            'title' => 'Primeros pasos con el sistema',
            'slug' => 'primeros-pasos-con-el-sistema',
            'body' => '<p>Descubre cómo dar tus primeros pasos en el sistema.</p>',
            'description' => 'Introducción al sistema',
            'draft' => false,
            'views' => 0,
            'author_id' => 1,
        ]);

        $article4 = HelpCenterArticle::create([
            'title' => 'Navegación básica',
            'slug' => 'navegacion-basica',
            'body' => '<p>Aprende a navegar por las diferentes secciones del sistema.</p>',
            'description' => 'Guía de navegación',
            'draft' => false,
            'views' => 0,
            'author_id' => 1,
        ]);

        $section2->articles()->attach($article3->id, ['position' => 1]);
        $section2->articles()->attach($article4->id, ['position' => 2]);

        // Crear artículos para "Configuración General"
        $article5 = HelpCenterArticle::create([
            'title' => 'Ajustes básicos del sistema',
            'slug' => 'ajustes-basicos-del-sistema',
            'body' => '<p>Configura los ajustes básicos de tu instalación.</p>',
            'description' => 'Configuración básica',
            'draft' => false,
            'views' => 0,
            'author_id' => 1,
        ]);

        $section3->articles()->attach($article5->id, ['position' => 1]);

        // Crear artículos para "Seguridad"
        $article6 = HelpCenterArticle::create([
            'title' => 'Gestión de permisos',
            'slug' => 'gestion-de-permisos',
            'body' => '<p>Aprende a gestionar roles y permisos de usuario.</p>',
            'description' => 'Roles y permisos',
            'draft' => false,
            'views' => 0,
            'author_id' => 1,
        ]);

        $article7 = HelpCenterArticle::create([
            'title' => 'Configuración de autenticación',
            'slug' => 'configuracion-de-autenticacion',
            'body' => '<p>Configura métodos de autenticación seguros.</p>',
            'description' => 'Autenticación segura',
            'draft' => true, // Este es borrador
            'views' => 0,
            'author_id' => 1,
        ]);

        $section4->articles()->attach($article6->id, ['position' => 1]);
        $section4->articles()->attach($article7->id, ['position' => 2]);

        $this->command->info('✓ Help Center seeded successfully!');
        $this->command->info('  - 3 Categories created');
        $this->command->info('  - 4 Sections created');
        $this->command->info('  - 7 Articles created');
    }
}
