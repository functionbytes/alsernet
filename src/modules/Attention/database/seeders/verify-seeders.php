#!/usr/bin/env php
<?php

/**
 * Script de verificación de seeders del módulo Attention
 *
 * Ejecutar: php modules/Attention/database/seeders/verify-seeders.php
 */
echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  Verificación de Seeders - Módulo Attention\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

$seedersPath = __DIR__;
$expectedSeeders = [
    'AttentionTypesSeeder.php' => [
        'required' => true,
        'description' => 'Crea tipos de peticiones (P/Q/R/S/F)',
    ],
    'AttentionCategoriesSeeder.php' => [
        'required' => true,
        'description' => 'Crea categorías temáticas',
    ],
    'AttentionDepartmentsSeeder.php' => [
        'required' => true,
        'description' => 'Crea departamentos',
    ],
    'AttentionSedesSeeder.php' => [
        'required' => true,
        'description' => 'Crea sedes de atención',
    ],
    'AttentionSlaPoliciesSeeder.php' => [
        'required' => true,
        'description' => 'Crea políticas SLA',
    ],
    'AttentionPermissionsSeeder.php' => [
        'required' => true,
        'description' => 'Crea permisos y roles',
    ],
    'AttentionStatusSeeder.php' => [
        'required' => false,
        'description' => 'Documenta estados (enum)',
    ],
    'AttentionDemoDataSeeder.php' => [
        'required' => false,
        'description' => 'Crea datos de prueba',
    ],
    'AttentionDatabaseSeeder.php' => [
        'required' => true,
        'description' => 'Seeder principal',
    ],
];

$factoriesPath = dirname($seedersPath).'/factories';
$expectedFactories = [
    'AttentionFactory.php',
    'AttentionTypeFactory.php',
    'AttentionCategoryFactory.php',
    'AttentionDepartmentFactory.php',
    'AttentionSedeFactory.php',
    'AttentionNoteFactory.php',
    'AttentionActionFactory.php',
];

echo "Verificando Seeders...\n";
echo "─────────────────────────────────────────────────────────\n";

$missingRequired = [];
$missingOptional = [];
$found = [];

foreach ($expectedSeeders as $seeder => $config) {
    $path = $seedersPath.'/'.$seeder;
    $exists = file_exists($path);

    if ($exists) {
        $found[] = $seeder;
        $status = '✓ OK';
        $color = "\033[32m"; // Verde
    } else {
        if ($config['required']) {
            $missingRequired[] = $seeder;
            $status = '✗ FALTA (REQUERIDO)';
            $color = "\033[31m"; // Rojo
        } else {
            $missingOptional[] = $seeder;
            $status = '! FALTA (OPCIONAL)';
            $color = "\033[33m"; // Amarillo
        }
    }

    $reset = "\033[0m";
    $required = $config['required'] ? '[REQ]' : '[OPT]';

    printf("  %s%-35s%s %s %s%s\n",
        $color,
        $seeder,
        $reset,
        $required,
        $color,
        $status
    );

    if ($exists) {
        printf("      → %s\n", $config['description']);
    }
}

echo "\n";
echo "Verificando Factories...\n";
echo "─────────────────────────────────────────────────────────\n";

$missingFactories = [];

foreach ($expectedFactories as $factory) {
    $path = $factoriesPath.'/'.$factory;
    $exists = file_exists($path);

    if ($exists) {
        $status = '✓ OK';
        $color = "\033[32m"; // Verde
    } else {
        $missingFactories[] = $factory;
        $status = '✗ FALTA';
        $color = "\033[31m"; // Rojo
    }

    $reset = "\033[0m";
    printf("  %s%-35s%s %s%s\n",
        $color,
        $factory,
        $reset,
        $color,
        $status
    );
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  Resumen\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

printf("Seeders encontrados:        %d/%d\n", count($found), count($expectedSeeders));
printf("Factories encontradas:      %d/%d\n",
    count($expectedFactories) - count($missingFactories),
    count($expectedFactories)
);

echo "\n";

if (empty($missingRequired) && empty($missingFactories)) {
    echo "\033[32m✓ Todos los componentes requeridos están presentes!\033[0m\n";
    $exitCode = 0;
} else {
    if (! empty($missingRequired)) {
        echo "\033[31m✗ Faltan seeders requeridos:\033[0m\n";
        foreach ($missingRequired as $seeder) {
            echo "  - $seeder\n";
        }
        echo "\n";
    }

    if (! empty($missingFactories)) {
        echo "\033[31m✗ Faltan factories:\033[0m\n";
        foreach ($missingFactories as $factory) {
            echo "  - $factory\n";
        }
        echo "\n";
    }

    $exitCode = 1;
}

if (! empty($missingOptional)) {
    echo "\033[33m! Seeders opcionales faltantes:\033[0m\n";
    foreach ($missingOptional as $seeder) {
        echo "  - $seeder\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "\n";

echo "Comandos útiles:\n";
echo "─────────────────────────────────────────────────────────\n";
echo "  Ejecutar todos los seeders:\n";
echo "    php artisan module:seed Attention\n";
echo "\n";
echo "  Ejecutar seeder específico:\n";
echo "    php artisan db:seed --class=\"Modules\\\\Attention\\\\Database\\\\Seeders\\\\AttentionTypesSeeder\"\n";
echo "\n";
echo "  Ver documentación:\n";
echo "    cat modules/Attention/database/seeders/README.md\n";
echo "\n";

exit($exitCode);
