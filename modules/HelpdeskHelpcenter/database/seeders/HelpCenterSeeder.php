<?php

namespace Modules\HelpdeskHelpcenter\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;

class HelpCenterSeeder extends Seeder
{
    public function run(): void
    {
        $authorId = User::query()->oldest('id')->value('id') ?? 1;
        // Categorías principales
        $gettingStarted = HelpCenterCategory::firstOrCreate(
            ['slug' => 'primeros-pasos'],
            ['name' => 'Primeros pasos', 'description' => 'Todo lo que necesitas saber para comenzar', 'position' => 1, 'is_section' => false]
        );

        $configuration = HelpCenterCategory::firstOrCreate(
            ['slug' => 'configuracion'],
            ['name' => 'Configuración', 'description' => 'Guías de configuración del sistema', 'position' => 2, 'is_section' => false]
        );

        $faq = HelpCenterCategory::firstOrCreate(
            ['slug' => 'preguntas-frecuentes'],
            ['name' => 'Preguntas frecuentes', 'description' => 'Respuestas a las preguntas más comunes', 'position' => 3, 'is_section' => false]
        );

        // Secciones de "Primeros pasos"
        $installation = HelpCenterCategory::firstOrCreate(
            ['slug' => 'instalacion'],
            ['name' => 'Instalación', 'description' => 'Guías de instalación paso a paso', 'parent_id' => $gettingStarted->id, 'position' => 1, 'is_section' => true]
        );

        $basicTutorials = HelpCenterCategory::firstOrCreate(
            ['slug' => 'tutoriales-basicos'],
            ['name' => 'Tutoriales básicos', 'description' => 'Aprende lo básico del sistema', 'parent_id' => $gettingStarted->id, 'position' => 2, 'is_section' => true]
        );

        // Secciones de "Configuración"
        $generalConfig = HelpCenterCategory::firstOrCreate(
            ['slug' => 'configuracion-general'],
            ['name' => 'Configuración general', 'description' => 'Ajustes generales del sistema', 'parent_id' => $configuration->id, 'position' => 1, 'is_section' => true]
        );

        $security = HelpCenterCategory::firstOrCreate(
            ['slug' => 'seguridad'],
            ['name' => 'Seguridad', 'description' => 'Configuración de seguridad y permisos', 'parent_id' => $configuration->id, 'position' => 2, 'is_section' => true]
        );

        // Artículos de "Instalación"
        $article1 = HelpCenterArticle::firstOrCreate(
            ['slug' => 'como-instalar-el-sistema'],
            [
                'title' => 'Cómo instalar el sistema',
                'body' => '<p>Guía completa para instalar el sistema paso a paso.</p><h3>Requisitos</h3><ul><li>PHP 8.4+</li><li>MariaDB 10.6+</li><li>Composer</li><li>Redis</li></ul>',
                'description' => 'Guía completa de instalación',
                'draft' => false,
                'views_count' => 0,
                'author_id' => $authorId,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $article2 = HelpCenterArticle::firstOrCreate(
            ['slug' => 'configuracion-del-servidor'],
            [
                'title' => 'Configuración del servidor',
                'body' => '<p>Aprende a configurar correctamente tu servidor para ejecutar la aplicación.</p>',
                'description' => 'Configuración de servidor web',
                'draft' => false,
                'views_count' => 0,
                'author_id' => $authorId,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $installation->articles()->syncWithoutDetaching([
            $article1->id => ['position' => 1],
            $article2->id => ['position' => 2],
        ]);

        // Artículos de "Tutoriales básicos"
        $article3 = HelpCenterArticle::firstOrCreate(
            ['slug' => 'primeros-pasos-con-el-sistema'],
            [
                'title' => 'Primeros pasos con el sistema',
                'body' => '<p>Descubre cómo dar tus primeros pasos en el sistema.</p>',
                'description' => 'Introducción al sistema',
                'draft' => false,
                'views_count' => 0,
                'author_id' => $authorId,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $article4 = HelpCenterArticle::firstOrCreate(
            ['slug' => 'navegacion-basica'],
            [
                'title' => 'Navegación básica',
                'body' => '<p>Aprende a navegar por las diferentes secciones del sistema.</p>',
                'description' => 'Guía de navegación',
                'draft' => false,
                'views_count' => 0,
                'author_id' => $authorId,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $basicTutorials->articles()->syncWithoutDetaching([
            $article3->id => ['position' => 1],
            $article4->id => ['position' => 2],
        ]);

        // Artículos de "Configuración general"
        $article5 = HelpCenterArticle::firstOrCreate(
            ['slug' => 'ajustes-basicos-del-sistema'],
            [
                'title' => 'Ajustes básicos del sistema',
                'body' => '<p>Configura los ajustes básicos de tu instalación.</p>',
                'description' => 'Configuración básica',
                'draft' => false,
                'views_count' => 0,
                'author_id' => $authorId,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $generalConfig->articles()->syncWithoutDetaching([$article5->id => ['position' => 1]]);

        // Artículos de "Seguridad"
        $article6 = HelpCenterArticle::firstOrCreate(
            ['slug' => 'gestion-de-permisos'],
            [
                'title' => 'Gestión de permisos',
                'body' => '<p>Aprende a gestionar roles y permisos de usuario.</p>',
                'description' => 'Roles y permisos',
                'draft' => false,
                'views_count' => 0,
                'author_id' => $authorId,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $article7 = HelpCenterArticle::firstOrCreate(
            ['slug' => 'configuracion-de-autenticacion'],
            [
                'title' => 'Configuración de autenticación',
                'body' => '<p>Configura métodos de autenticación seguros.</p>',
                'description' => 'Autenticación segura',
                'draft' => true,
                'views_count' => 0,
                'author_id' => $authorId,
                'is_published' => true,
                'published_at' => now(),
            ]
        );

        $security->articles()->syncWithoutDetaching([
            $article6->id => ['position' => 1],
            $article7->id => ['position' => 2],
        ]);

        $this->command->info('✅ Centro de ayuda inicializado (3 categorías, 4 secciones, 7 artículos)');
    }
}
