#!/usr/bin/env php
<?php

/**
 * Namespace Migration Verification Script
 *
 * Verifies that all target classes from the namespace migration exist and are loadable.
 *
 * Usage:
 *   php scripts/verify-namespace-migration.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$configPath = __DIR__.'/../config/namespace-migration-map.json';

if (! file_exists($configPath)) {
    echo "❌ Config file not found: {$configPath}\n";
    exit(1);
}

$config = json_decode(file_get_contents($configPath), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo '❌ Invalid JSON in config file: '.json_last_error_msg()."\n";
    exit(1);
}

// Merge all namespace mappings
$mappings = array_merge(
    $config['models'] ?? [],
    $config['libraries'] ?? [],
    $config['jobs'] ?? [],
    $config['events'] ?? []
);

$stats = [
    'total' => count($mappings),
    'verified' => 0,
    'missing' => 0,
    'errors' => [],
];

echo '🔍 Verifying '.$stats['total']." namespace mappings...\n\n";

$progressBar = str_repeat('░', 50);
$current = 0;

foreach ($mappings as $oldNamespace => $newNamespace) {
    $current++;
    $percent = round(($current / $stats['total']) * 100);
    $filled = round(($current / $stats['total']) * 50);
    $bar = str_repeat('▓', $filled).str_repeat('░', 50 - $filled);

    echo "\r[{$bar}] {$percent}% ";

    // Try to verify the class exists
    try {
        if (class_exists($newNamespace) || interface_exists($newNamespace) || trait_exists($newNamespace)) {
            $stats['verified']++;
        } else {
            $stats['missing']++;
            $stats['errors'][] = [
                'old' => $oldNamespace,
                'new' => $newNamespace,
                'error' => 'Class/Interface/Trait does not exist',
            ];
        }
    } catch (Throwable $e) {
        $stats['missing']++;
        $stats['errors'][] = [
            'old' => $oldNamespace,
            'new' => $newNamespace,
            'error' => $e->getMessage(),
        ];
    }
}

echo "\n\n";

// Display results
echo "═══════════════════════════════════════════════════════════\n";
echo "                  Verification Results                     \n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✅ Verified:     {$stats['verified']}/{$stats['total']}\n";
echo "❌ Missing:      {$stats['missing']}/{$stats['total']}\n";

if ($stats['missing'] > 0) {
    echo "\n❌ Missing/Error Classes:\n\n";

    $table = [
        ['Old Namespace', 'New Namespace', 'Error'],
        ['-------------', '-------------', '-----'],
    ];

    foreach ($stats['errors'] as $error) {
        $table[] = [
            substr($error['old'], 0, 40),
            substr($error['new'], 0, 40),
            substr($error['error'], 0, 40),
        ];
    }

    foreach ($table as $row) {
        echo sprintf("%-42s %-42s %-42s\n", ...$row);
    }

    echo "\n";
}

// Exit code
if ($stats['missing'] > 0) {
    echo "⚠️  Some classes could not be verified. Review the errors above.\n";
    exit(1);
} else {
    echo "\n✅ All namespace mappings verified successfully!\n";
    exit(0);
}
