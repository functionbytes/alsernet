#!/usr/bin/env php
<?php

/**
 * Script de Verificación de Instalación del Módulo Attention
 *
 * Ejecutar desde la raíz del proyecto Laravel:
 * php modules/Attention/scripts/verify-installation.php
 *
 * O darle permisos de ejecución y ejecutar directamente:
 * chmod +x modules/Attention/scripts/verify-installation.php
 * ./modules/Attention/scripts/verify-installation.php
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Colores para la terminal
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");
define('BOLD', "\033[1m");

// Símbolos
define('CHECK', '✅');
define('CROSS', '❌');
define('WARNING', '⚠️');
define('INFO', 'ℹ️');

echo BOLD.COLOR_BLUE."\n╔════════════════════════════════════════════════════════╗\n".COLOR_RESET;
echo BOLD.COLOR_BLUE."║  VERIFICACIÓN DE INSTALACIÓN - MÓDULO ATTENTION       ║\n".COLOR_RESET;
echo BOLD.COLOR_BLUE."╚════════════════════════════════════════════════════════╝\n".COLOR_RESET;

$errors = 0;
$warnings = 0;

// ============================================================================
// 1. VERIFICAR TABLAS
// ============================================================================
echo "\n".BOLD."1. Verificando Tablas de Base de Datos...\n".COLOR_RESET;

$requiredTables = [
    'attention_types' => 'Tipos de PQRSF',
    'attention_categories' => 'Categorías temáticas',
    'attention_departments' => 'Departamentos',
    'attention_sedes' => 'Sedes físicas',
    'attentions' => 'Tabla principal de PQRSF',
    'attention_notes' => 'Notas internas',
    'attention_actions' => 'Historial de acciones',
    'attention_mails' => 'Log de emails',
    'attention_satisfaction' => 'Encuestas de satisfacción',
    'media' => 'Archivos adjuntos (Spatie)',
];

foreach ($requiredTables as $table => $description) {
    if (Schema::hasTable($table)) {
        echo '   '.CHECK." {$table}: ".COLOR_GREEN.'OK'.COLOR_RESET." ($description)\n";
    } else {
        echo '   '.CROSS." {$table}: ".COLOR_RED.'NO EXISTE'.COLOR_RESET." ($description)\n";
        $errors++;
    }
}

// ============================================================================
// 2. VERIFICAR COLUMNAS DE LA TABLA PRINCIPAL
// ============================================================================
echo "\n".BOLD."2. Verificando Columnas de 'attentions'...\n".COLOR_RESET;

if (Schema::hasTable('attentions')) {
    $requiredColumns = [
        'id', 'uid', 'radicado', 'type_id', 'category_id', 'sede_id',
        'customer_firstname', 'customer_lastname', 'customer_email',
        'customer_cellphone', 'customer_dni', 'customer_address',
        'is_anonymous', 'subject', 'description', 'status',
        'department_id', 'assigned_user_id', 'response_type',
        'resolution', 'resolved_at', 'closed_at', 'satisfaction_rating',
        'created_at', 'updated_at',
    ];

    $missingColumns = [];
    foreach ($requiredColumns as $column) {
        if (! Schema::hasColumn('attentions', $column)) {
            $missingColumns[] = $column;
        }
    }

    if (empty($missingColumns)) {
        echo '   '.CHECK.' Todas las columnas necesarias: '.COLOR_GREEN."OK\n".COLOR_RESET;
    } else {
        echo '   '.CROSS.' Columnas faltantes: '.COLOR_RED.implode(', ', $missingColumns)."\n".COLOR_RESET;
        $errors++;
    }
} else {
    echo '   '.CROSS." No se puede verificar columnas (tabla no existe)\n";
}

// ============================================================================
// 3. VERIFICAR FOREIGN KEYS
// ============================================================================
echo "\n".BOLD."3. Verificando Foreign Keys...\n".COLOR_RESET;

if (Schema::hasTable('attentions')) {
    try {
        $foreignKeys = DB::select("
            SELECT
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'attentions'
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (count($foreignKeys) >= 5) {
            echo '   '.CHECK.' Foreign keys encontradas: '.COLOR_GREEN.count($foreignKeys)."\n".COLOR_RESET;
            foreach ($foreignKeys as $fk) {
                echo "      - {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
            }
        } else {
            echo '   '.WARNING.' Pocas foreign keys: '.COLOR_YELLOW.count($foreignKeys)." (esperadas: 5+)\n".COLOR_RESET;
            $warnings++;
        }
    } catch (\Exception $e) {
        echo '   '.WARNING." No se pudieron verificar foreign keys: {$e->getMessage()}\n";
        $warnings++;
    }
}

// ============================================================================
// 4. VERIFICAR ÍNDICES
// ============================================================================
echo "\n".BOLD."4. Verificando Índices...\n".COLOR_RESET;

if (Schema::hasTable('attentions')) {
    try {
        $indexes = DB::select("
            SELECT
                INDEX_NAME,
                GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columnas
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'attentions'
            GROUP BY INDEX_NAME
        ");

        $indexCount = count($indexes);
        if ($indexCount >= 15) {
            echo '   '.CHECK.' Índices creados: '.COLOR_GREEN."$indexCount (excelente)\n".COLOR_RESET;
        } elseif ($indexCount >= 10) {
            echo '   '.WARNING.' Índices creados: '.COLOR_YELLOW."$indexCount (aceptable)\n".COLOR_RESET;
            $warnings++;
        } else {
            echo '   '.CROSS.' Pocos índices: '.COLOR_RED."$indexCount (crítico)\n".COLOR_RESET;
            $errors++;
        }

        // Verificar índices críticos
        $criticalIndexes = ['radicado', 'uid', 'status'];
        foreach ($indexes as $index) {
            if (in_array($index->INDEX_NAME, $criticalIndexes)) {
                echo '      '.CHECK." Índice crítico '{$index->INDEX_NAME}': OK\n";
            }
        }
    } catch (\Exception $e) {
        echo '   '.WARNING." No se pudieron verificar índices: {$e->getMessage()}\n";
        $warnings++;
    }
}

// ============================================================================
// 5. VERIFICAR DATOS SEEDEADOS
// ============================================================================
echo "\n".BOLD."5. Verificando Datos Iniciales (Seeders)...\n".COLOR_RESET;

try {
    // Verificar Tipos de PQRSF
    $typesCount = DB::table('attention_types')->count();
    if ($typesCount >= 5) {
        $types = DB::table('attention_types')->pluck('code')->toArray();
        echo '   '.CHECK.' Tipos de PQRSF: '.COLOR_GREEN."$typesCount tipos (".implode(', ', $types).")\n".COLOR_RESET;
    } else {
        echo '   '.WARNING.' Tipos de PQRSF: '.COLOR_YELLOW."$typesCount tipos (ejecutar seeders)\n".COLOR_RESET;
        $warnings++;
    }

    // Verificar Categorías
    $categoriesCount = DB::table('attention_categories')->count();
    if ($categoriesCount > 0) {
        echo '   '.CHECK.' Categorías: '.COLOR_GREEN."$categoriesCount registros\n".COLOR_RESET;
    } else {
        echo '   '.WARNING.' Categorías: '.COLOR_YELLOW."0 registros (ejecutar seeders)\n".COLOR_RESET;
        $warnings++;
    }

    // Verificar Departamentos
    $departmentsCount = DB::table('attention_departments')->count();
    if ($departmentsCount > 0) {
        echo '   '.CHECK.' Departamentos: '.COLOR_GREEN."$departmentsCount registros\n".COLOR_RESET;
    } else {
        echo '   '.WARNING.' Departamentos: '.COLOR_YELLOW."0 registros (ejecutar seeders)\n".COLOR_RESET;
        $warnings++;
    }

    // Verificar Sedes
    $sedesCount = DB::table('attention_sedes')->count();
    if ($sedesCount > 0) {
        echo '   '.CHECK.' Sedes: '.COLOR_GREEN."$sedesCount registros\n".COLOR_RESET;
    } else {
        echo '   '.WARNING.' Sedes: '.COLOR_YELLOW."0 registros (ejecutar seeders)\n".COLOR_RESET;
        $warnings++;
    }
} catch (\Exception $e) {
    echo '   '.CROSS.' Error verificando datos: '.COLOR_RED.$e->getMessage()."\n".COLOR_RESET;
    $errors++;
}

// ============================================================================
// 6. VERIFICAR MODELOS
// ============================================================================
echo "\n".BOLD."6. Verificando Modelos Eloquent...\n".COLOR_RESET;

$models = [
    'Modules\Attention\Models\Attention' => 'Attention (principal)',
    'Modules\Attention\Models\AttentionType' => 'AttentionType',
    'Modules\Attention\Models\AttentionCategory' => 'AttentionCategory',
    'Modules\Attention\Models\Department' => 'Department',
    'Modules\Attention\Models\Sede' => 'Sede',
    'Modules\Attention\Models\AttentionNote' => 'AttentionNote',
    'Modules\Attention\Models\AttentionAction' => 'AttentionAction',
    'Modules\Attention\Models\AttentionMail' => 'AttentionMail',
    'Modules\Attention\Models\AttentionSatisfaction' => 'AttentionSatisfaction',
];

foreach ($models as $class => $name) {
    if (class_exists($class)) {
        echo '   '.CHECK." {$name}: ".COLOR_GREEN."OK\n".COLOR_RESET;
    } else {
        echo '   '.CROSS." {$name}: ".COLOR_RED."NO ENCONTRADO\n".COLOR_RESET;
        $errors++;
    }
}

// ============================================================================
// 7. VERIFICAR ENUMS
// ============================================================================
echo "\n".BOLD."7. Verificando Enums...\n".COLOR_RESET;

$enums = [
    'Modules\Attention\Enums\AttentionStatus' => 'AttentionStatus',
    'Modules\Attention\Enums\ResponseType' => 'ResponseType',
];

foreach ($enums as $class => $name) {
    if (enum_exists($class)) {
        $cases = $class::cases();
        echo '   '.CHECK." {$name}: ".COLOR_GREEN.'OK ('.count($cases)." casos)\n".COLOR_RESET;
    } else {
        echo '   '.CROSS." {$name}: ".COLOR_RED."NO ENCONTRADO\n".COLOR_RESET;
        $errors++;
    }
}

// ============================================================================
// 8. PRUEBA DE CREACIÓN DE REGISTRO
// ============================================================================
echo "\n".BOLD."8. Prueba de Creación de PQRSF...\n".COLOR_RESET;

try {
    $testType = DB::table('attention_types')->where('code', 'P')->first();

    if ($testType) {
        // Intentar crear un registro de prueba
        $testId = DB::table('attentions')->insertGetId([
            'uid' => \Illuminate\Support\Str::uuid(),
            'radicado' => 'TEST-'.date('Y').'-999999',
            'type_id' => $testType->id,
            'subject' => 'Prueba de instalación',
            'description' => 'Este es un registro de prueba creado por el script de verificación',
            'status' => 'received',
            'is_anonymous' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Eliminar el registro de prueba
        DB::table('attentions')->where('id', $testId)->delete();

        echo '   '.CHECK.' Creación de PQRSF: '.COLOR_GREEN."OK (test exitoso)\n".COLOR_RESET;
    } else {
        echo '   '.WARNING.' No se puede probar creación: '.COLOR_YELLOW."No hay tipos de PQRSF\n".COLOR_RESET;
        $warnings++;
    }
} catch (\Exception $e) {
    echo '   '.CROSS.' Error en prueba de creación: '.COLOR_RED.$e->getMessage()."\n".COLOR_RESET;
    $errors++;
}

// ============================================================================
// 9. VERIFICAR DEPENDENCIAS
// ============================================================================
echo "\n".BOLD."9. Verificando Dependencias de Composer...\n".COLOR_RESET;

// Verificar Spatie MediaLibrary
if (class_exists('Spatie\MediaLibrary\MediaLibraryServiceProvider')) {
    echo '   '.CHECK.' Spatie MediaLibrary: '.COLOR_GREEN."OK\n".COLOR_RESET;
} else {
    echo '   '.CROSS.' Spatie MediaLibrary: '.COLOR_RED."NO INSTALADO\n".COLOR_RESET;
    echo "      Ejecutar: composer require spatie/laravel-medialibrary\n";
    $errors++;
}

// ============================================================================
// 10. VERIFICAR CONFIGURACIÓN
// ============================================================================
echo "\n".BOLD."10. Verificando Configuración...\n".COLOR_RESET;

// Verificar storage link
$storagePath = public_path('storage');
if (is_link($storagePath) || is_dir($storagePath)) {
    echo '   '.CHECK.' Storage link: '.COLOR_GREEN."OK\n".COLOR_RESET;
} else {
    echo '   '.WARNING.' Storage link: '.COLOR_YELLOW."NO EXISTE\n".COLOR_RESET;
    echo "      Ejecutar: php artisan storage:link\n";
    $warnings++;
}

// Verificar tabla users
if (Schema::hasTable('users')) {
    echo '   '.CHECK.' Tabla users: '.COLOR_GREEN."OK\n".COLOR_RESET;
} else {
    echo '   '.CROSS.' Tabla users: '.COLOR_RED."NO EXISTE (requerida)\n".COLOR_RESET;
    $errors++;
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo "\n".BOLD.COLOR_BLUE."╔════════════════════════════════════════════════════════╗\n".COLOR_RESET;
echo BOLD.COLOR_BLUE."║                   RESUMEN DE VERIFICACIÓN              ║\n".COLOR_RESET;
echo BOLD.COLOR_BLUE."╚════════════════════════════════════════════════════════╝\n".COLOR_RESET;

echo "\n";
echo '   Errores críticos: '.($errors > 0 ? COLOR_RED.$errors.CROSS : COLOR_GREEN.'0 '.CHECK).COLOR_RESET."\n";
echo '   Advertencias: '.($warnings > 0 ? COLOR_YELLOW.$warnings.WARNING : COLOR_GREEN.'0 '.CHECK).COLOR_RESET."\n";
echo "\n";

if ($errors === 0 && $warnings === 0) {
    echo BOLD.COLOR_GREEN."   ✅ INSTALACIÓN COMPLETADA EXITOSAMENTE\n".COLOR_RESET;
    echo "   El módulo Attention está listo para usar.\n\n";
    exit(0);
} elseif ($errors === 0) {
    echo BOLD.COLOR_YELLOW."   ⚠️  INSTALACIÓN CON ADVERTENCIAS\n".COLOR_RESET;
    echo "   El módulo funciona pero hay mejoras recomendadas.\n\n";
    exit(0);
} else {
    echo BOLD.COLOR_RED."   ❌ INSTALACIÓN INCOMPLETA\n".COLOR_RESET;
    echo "   Por favor revisa los errores anteriores.\n\n";
    echo "   Comandos sugeridos:\n";
    echo "   - php artisan migrate --path=/modules/Attention/database/migrations\n";
    echo "   - php artisan db:seed --class=Modules\\Attention\\Database\\Seeders\\AttentionDatabaseSeeder\n";
    echo "   - composer require spatie/laravel-medialibrary\n";
    echo "\n";
    exit(1);
}
