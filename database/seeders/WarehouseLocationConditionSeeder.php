<?php

namespace Database\Seeders;

use App\Models\Warehouse\WarehouseLocationCondition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WarehouseLocationConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pendiente
        WarehouseLocationCondition::create([
            'uid' => Str::uuid(),
            'title' => 'Pendiente',
            'slug' => 'pending',
            'description' => 'Inventario no validado aún',
            'color' => '#FFC107',
            'badge_class' => 'badge-warning',
            'available' => true,
        ]);

        // En Progreso
        WarehouseLocationCondition::create([
            'uid' => Str::uuid(),
            'title' => 'En Progreso',
            'slug' => 'in-progress',
            'description' => 'Validación de inventario en proceso',
            'color' => '#17A2B8',
            'badge_class' => 'badge-info',
            'available' => true,
        ]);

        // Completado
        WarehouseLocationCondition::create([
            'uid' => Str::uuid(),
            'title' => 'Completado',
            'slug' => 'completed',
            'description' => 'Inventario completamente validado',
            'color' => '#28A745',
            'badge_class' => 'badge-success',
            'available' => true,
        ]);

        // Discrepancia
        WarehouseLocationCondition::create([
            'uid' => Str::uuid(),
            'title' => 'Discrepancia',
            'slug' => 'discrepancy',
            'description' => 'Diferencias encontradas en el inventario',
            'color' => '#DC3545',
            'badge_class' => 'badge-danger',
            'available' => true,
        ]);

        // Dañado
        WarehouseLocationCondition::create([
            'uid' => Str::uuid(),
            'title' => 'Dañado',
            'slug' => 'damaged',
            'description' => 'Ubicación con productos dañados',
            'color' => '#6F42C1',
            'badge_class' => 'badge-danger',
            'available' => true,
        ]);

        // En Mantenimiento
        WarehouseLocationCondition::create([
            'uid' => Str::uuid(),
            'title' => 'En Mantenimiento',
            'slug' => 'maintenance',
            'description' => 'Ubicación bajo mantenimiento',
            'color' => '#FF6B6B',
            'badge_class' => 'badge-secondary',
            'available' => true,
        ]);
    }
}
