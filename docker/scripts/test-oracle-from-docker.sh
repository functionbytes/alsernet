#!/bin/bash

# Script para ejecutar test de Oracle desde dentro del Docker de integracion
# Esto verifica que la configuración funcione correctamente

echo "🐳 Ejecutando test de Oracle desde contenedor Docker..."

cd /Users/developert/Herd/integracion

# Copiar el archivo de test al contenedor y ejecutarlo
docker-compose exec -T app bash -c "
cd /var/www
cat > /tmp/test-oracle-manager.php << 'EOF'
<?php

require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo \"=== Prueba de Conexión Oracle - Desde Docker ===\n\n\";

// Verificar configuración
echo \"📋 Configuración de Oracle:\n\";
echo \"  Host: 192.168.253.8\n\";
echo \"  Port: 1521\n\";
echo \"  Database: GESTCENT\n\";
echo \"  Username: lectura\n\n\";

try {
    echo \"🔗 Intentando conectar al modelo ClienteCent...\n\";
    \$cliente = App\Models\Oracle\Cliente\ClienteCent::first();

    if (\$cliente) {
        echo \"\n✅ CONEXIÓN EXITOSA!\n\n\";
        echo \"📊 Datos del último cliente:\n\";
        echo \"  ID: \" . \$cliente->idcliente . \"\n\";
        echo \"  Nombre: \" . \$cliente->nombre . \"\n\";
        echo \"  Email: \" . \$cliente->email . \"\n\";
        echo \"  CIF: \" . \$cliente->cif . \"\n\";
    } else {
        echo \"\n⚠️  Conexión exitosa pero no hay clientes en la BD\n\";
    }
} catch (\Exception \$e) {
    echo \"\n❌ ERROR DE CONEXIÓN:\n\";
    echo \"  Mensaje: \" . \$e->getMessage() . \"\n\";
    exit(1);
}

echo \"\n✅ Prueba completada exitosamente\n\";
EOF

php /tmp/test-oracle-manager.php
"

echo ""
echo "✅ Test completado"
